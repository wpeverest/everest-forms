( function( $, wp ) {
	var $document = $( document );

	/**
	 * Sends an Ajax request to the server to install a extension.
	 *
	 * @since 4.6.0
	 *
	 * @param {object}                   args         Arguments.
	 * @param {string}                   args.slug    Plugin identifier in the WordPress.org Plugin repository.
	 * @param {installExtensionSuccess=} args.success Optional. Success callback. Default: wp.updates.installPluginSuccess
	 * @param {installExtensionError=}   args.error   Optional. Error callback. Default: wp.updates.installPluginError
	 * @return {$.promise} A jQuery promise that represents the request,
	 *                     decorated with an abort() method.
	 */
	wp.updates.installExtension = function( args ) {
		var $card    = $( '.plugin-card-' + args.slug ),
			$message = $card.find( '.install-now' );

		args = _.extend( {
			success: wp.updates.installExtensionSuccess,
			error: wp.updates.installExtensionError
		}, args );

		if ( $message.html() !== wp.updates.l10n.installing ) {
			$message.data( 'originaltext', $message.html() );
		}

		$message
			.addClass( 'updating-message' )
			.attr( 'aria-label', wp.updates.l10n.pluginInstallingLabel.replace( '%s', $message.data( 'name' ) ) )
			.text( wp.updates.l10n.installing );

		wp.a11y.speak( wp.updates.l10n.installingMsg, 'polite' );

		// Remove previous error messages, if any.
		$card.removeClass( 'plugin-card-install-failed' ).find( '.notice.notice-error' ).remove();

		$document.trigger( 'wp-extension-installing', args );

		return wp.updates.ajax( 'everest_forms_install_extension', args );
	};

	/**
	 * Updates the UI appropriately after a successful extension install.
	 *
	 * @since 4.6.0
	 *
	 * @typedef {object} installPluginSuccess
	 * @param {object} response             Response from the server.
	 * @param {string} response.slug        Slug of the installed plugin.
	 * @param {string} response.pluginName  Name of the installed plugin.
	 * @param {string} response.activateUrl URL to activate the just installed plugin.
	 */
	wp.updates.installExtensionSuccess = function( response ) {
		var $message = $( '.plugin-card-' + response.slug ).find( '.install-now' ),
			$status = $( '.plugin-card-' + response.slug ).find( '.status-label' );

		$message
			.removeClass( 'updating-message' )
			.addClass( 'updated-message installed button-disabled' )
			.attr( 'aria-label', wp.updates.l10n.pluginInstalledLabel.replace( '%s', response.pluginName ) )
			.text( wp.updates.l10n.pluginInstalled );

		wp.a11y.speak( wp.updates.l10n.installedMsg, 'polite' );

		$document.trigger( 'wp-plugin-install-success', response );

		if ( response.activateUrl ) {
			setTimeout( function() {
				$status.removeClass( 'status-install-now' ).addClass( 'status-active' ).text( wp.updates.l10n.pluginInstalled );

				// Transform the 'Install' button into an 'Activate' button.
				$message.removeClass( 'install-now installed button-disabled updated-message' ).addClass( 'activate-now button-primary' )
					.attr( 'href', response.activateUrl )
					.attr( 'aria-label', wp.updates.l10n.activatePluginLabel.replace( '%s', response.pluginName ) )
					.text( wp.updates.l10n.activatePlugin );
			}, 1000 );
		}
	};

	/**
	 * Updates the UI appropriately after a failed extension install.
	 *
	 * @since 4.6.0
	 *
	 * @typedef {object} installExtensionError
	 * @param {object}  response              Response from the server.
	 * @param {string}  response.slug         Slug of the plugin to be installed.
	 * @param {string=} response.pluginName   Optional. Name of the plugin to be installed.
	 * @param {string}  response.errorCode    Error code for the error that occurred.
	 * @param {string}  response.errorMessage The error that occurred.
	 */
	wp.updates.installExtensionError = function( response ) {
		var $card   = $( '.plugin-card-' + response.slug ),
			$button = $card.find( '.install-now' ),
			errorMessage;

		if ( ! wp.updates.isValidResponse( response, 'install' ) ) {
			return;
		}

		if ( wp.updates.maybeHandleCredentialError( response, 'everest_forms_install_extension' ) ) {
			return;
		}

		errorMessage = wp.updates.l10n.installFailed.replace( '%s', response.errorMessage );

		$card
			.addClass( 'plugin-card-update-failed' )
			.append( '<div class="notice notice-error notice-alt is-dismissible"><p>' + errorMessage + '</p></div>' );

		$card.on( 'click', '.notice.is-dismissible .notice-dismiss', function() {

			// Use same delay as the total duration of the notice fadeTo + slideUp animation.
			setTimeout( function() {
				$card
					.removeClass( 'plugin-card-update-failed' )
					.find( '.column-name a' ).focus();
			}, 200 );
		} );

		$button
			.removeClass( 'updating-message' ).addClass( 'button-disabled' )
			.attr( 'aria-label', wp.updates.l10n.pluginInstallFailedLabel.replace( '%s', $button.data( 'name' ) ) )
			.text( wp.updates.l10n.installFailedShort );

		wp.a11y.speak( errorMessage, 'assertive' );

		$document.trigger( 'wp-plugin-install-error', response );
	};

	/**
	 * Pulls available jobs from the queue and runs them.
	 * @see https://core.trac.wordpress.org/ticket/39364
	 */
	wp.updates.queueChecker = function() {
		var job;

		if ( wp.updates.ajaxLocked || ! wp.updates.queue.length ) {
			return;
		}

		job = wp.updates.queue.shift();

		// Handle a queue job.
		switch ( job.action ) {
			case 'everest_forms_install_extension':
				wp.updates.installExtension( job.data );
				break;

			default:
				break;
		}

		// Handle a queue job.
		$document.trigger( 'wp-updates-queue-job', job );
	};

	$( function() {
		var $pluginFilter = $( '#extension-filter' );

		/**
		 * Click handler for extension installs.
		 *
		 * @param {Event} event Event interface.
		 */
		$pluginFilter.on( 'click', '.extension-install .install-now', function( event ) {
			var $button = $( event.target ),
				pluginName = $( this ).data( 'name' );

			event.preventDefault();

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.ajaxLocked ) {
				wp.updates.requestFilesystemCredentials( event );

				$document.on( 'credential-modal-cancel', function() {
					var $message = $( '.install-now.updating-message' );

					$message
						.removeClass( 'updating-message' )
						.text( wp.updates.l10n.installNow )
						.attr( 'aria-label', wp.updates.l10n.pluginInstallNowLabel.replace( '%s', pluginName ) );

					wp.a11y.speak( wp.updates.l10n.updateCancel, 'polite' );
				} );
			}

			wp.updates.installExtension( {
				name: pluginName,
				slug: $button.data( 'slug' )
			} );
		} );
	} );
})( jQuery, window.wp );
