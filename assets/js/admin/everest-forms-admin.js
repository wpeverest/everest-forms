/* global pagenow */
( function( $, wp ) {
	var $document = $( document );

	/**
	 * Sends an Ajax request to the server to install a plugin.
	 *
	 * @since 4.6.0
	 *
	 * @param {object}                args         Arguments.
	 * @param {string}                args.slug    Plugin identifier in the WordPress.org Plugin repository.
	 * @param {installPluginSuccess=} args.success Optional. Success callback. Default: wp.updates.installPluginSuccess
	 * @param {installPluginError=}   args.error   Optional. Error callback. Default: wp.updates.installPluginError
	 * @return {$.promise} A jQuery promise that represents the request,
	 *                     decorated with an abort() method.
	 */
	wp.updates.installExtension = function( args ) {
		var $card    = $( '.plugin-card-' + args.slug ),
			$message = $card.find( '.install-now' );

		args = _.extend( {
			success: wp.updates.installExtensionSuccess,
			error: wp.updates.installPluginError
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

		$document.trigger( 'wp-plugin-installing', args );

		return wp.updates.ajax( 'install-extension', args );
	};

	/**
	 * Updates the UI appropriately after a successful plugin install.
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
		var $message = $( '.plugin-card-' + response.slug ).find( '.install-now' );
		var $status  = $( '.plugin-card-' + response.slug ).find( '.status-label' );

		$message
			.removeClass( 'updating-message' )
			.addClass( 'updated-message installed button-disabled' )
			.attr( 'aria-label', wp.updates.l10n.pluginInstalledLabel.replace( '%s', response.pluginName ) )
			.text( wp.updates.l10n.pluginInstalled );

		wp.a11y.speak( wp.updates.l10n.installedMsg, 'polite' );

		$document.trigger( 'wp-extension-install-success', response );

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

	$( function() {

		/**
		 * Click handler for importer plugins installs in the Demo Importer screen.
		 *
		 * @param {Event} event Event interface.
		 */
		$document.on( 'click', '.plugin-install .install-now', function( event ) {
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
						.attr( 'aria-label', wp.updates.l10n.installNowLabel.replace( '%s', pluginName ) );

					wp.a11y.speak( wp.updates.l10n.updateCancel, 'polite' );
				} );
			}

			wp.updates.installExtension( {
				slug: $button.data( 'slug' ),
				name: $button.data( 'name' ),
				pagenow: pagenow,
				success: wp.updates.installExtensionSuccess,
				error:   wp.updates.installPluginError
			} );
		} );
	} );
})( jQuery, window.wp );
