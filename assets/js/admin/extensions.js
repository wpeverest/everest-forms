( function( $, wp ) {
	var $document = $( document );
	__ = wp.i18n.__,
	_x = wp.i18n._x,
	sprintf = wp.i18n.sprintf;

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
			$message = $card.find( '.install-now, .activate-now' );

		args = _.extend( {
			success: wp.updates.installExtensionSuccess,
			error: wp.updates.installExtensionError
		}, args );

		if ( $message.html() !== __( 'Installing...' ) ) {
			$message.data( 'originaltext', $message.html() );
		}

		$message
			.addClass( 'updating-message' )
			.attr(
				'aria-label',
				sprintf(
					/* translators: %s: Plugin name and version. */
					_x( 'Installing %s...', 'everest-forms' ),
					$message.data( 'name' )
				)
			)
			.text( __( 'Installing...' ) );

		wp.a11y.speak( __( 'Installing... please wait.' ), 'polite' );

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
		if ( 'everest-forms_page_evf-builder' === pagenow ) {
			var $pluginRow = $( 'tr[data-slug="' + response.slug + '"]' ).removeClass( 'install' ).addClass( 'installed' ),
				$updateMessage = $pluginRow.find( '.plugin-status span' );

			$updateMessage
				.removeClass( 'updating-message install-now' )
				.addClass( 'updated-message active' )
				.attr(
					'aria-label',
					sprintf(
						/* translators: %s: Plugin name and version. */
					    _x( '%s installed!', 'everest-forms' ),
				        response.pluginName
					 )
				)
			    .text( _x( 'Installed!', 'plugin' ) );

			 wp.a11y.speak( __( 'Installation completed successfully.' ), 'polite' );


			$document.trigger( 'wp-plugin-bulk-install-success', response );

		} else {
			var $message = $( '.plugin-card-' + response.slug ).find( '.install-now' ),
				$status = $( '.plugin-card-' + response.slug ).find( '.status-label' );

			$message
				.removeClass( 'updating-message' )
				.addClass( 'updated-message installed button-disabled' )
				.attr(
					'aria-label',
					sprintf(
						/* translators: %s: Plugin name and version. */
					    _x( '%s installed!', 'everest-forms' ),
				        response.pluginName
					 )
				)
			    .text( _x( 'Installed!', 'everest-forms' ) );

			 wp.a11y.speak( __( 'Installation completed successfully.' ), 'polite' );

			$document.trigger( 'wp-plugin-install-success', response );

			if ( response.activateUrl ) {
				setTimeout( function() {
					$status.removeClass( 'status-install-now' ).addClass( 'status-active' ).text( wp.updates.l10n.pluginInstalled );

					// Transform the 'Install' button into an 'Activate' button.
					$message.removeClass( 'install-now installed button-disabled updated-message' )
					.addClass( 'activate-now button-primary' )
					.attr( 'href', response.activateUrl );

					if ( 'plugins-network' === pagenow ) {
						$message
							.attr(
								'aria-label',
								 sprintf(
									 /* translators: %s: Plugin name. */
									 _x( 'Network Activate %s', 'everest-forms' ),
									 response.pluginName
								)
							)
							.text( __( 'Network Activate' ) );
					} else {
						$message
							.attr(
								'aria-label',
								sprintf(
									/* translators: %s: Plugin name. */
									_x( 'Activate %s', 'everest-forms' ),
									response.pluginName
								)
							)
							.text( __( 'Activate' ) );
					}
				}, 1000 );
			}
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
		if ( 'everest-forms_page_evf-builder' === pagenow ) {
			var $pluginRow     = $( 'tr[data-slug="' + response.slug + '"]' ),
			$updateMessage = $pluginRow.find( '.plugin-status span' ),
			errorMessage;

			if ( ! wp.updates.isValidResponse( response, 'install' ) ) {
				return;
			}

			if ( wp.updates.maybeHandleCredentialError( response, 'install-plugin' ) ) {
				return;
			}

			errorMessage = sprintf(
				  /* translators: %s: Error string for a failed installation. */
				  __( 'Installation failed: %s' ),
				  response.errorMessage
				);

			$updateMessage
				.removeClass( 'updating-message' )
				.addClass( 'updated-message' )
				.attr(
					'aria-label',
					sprintf(
					   /* translators: %s: Plugin name and version. */
					   _x( '%s installation failed', 'everest-forms' ),
					   $button.data( 'name' )
					)
				)
				.text( __( 'Installation Failed!' ) );

			wp.a11y.speak( errorMessage, 'assertive' );

			$document.trigger( 'wp-plugin-bulk-install-error', response );
		} else {
			var $card   = $( '.plugin-card-' + response.slug ),
				$button = $card.find( '.install-now' ),
				errorMessage;

			if ( ! wp.updates.isValidResponse( response, 'install' ) ) {
				return;
			}

			if ( wp.updates.maybeHandleCredentialError( response, 'everest_forms_install_extension' ) ) {
				return;
			}

			errorMessage = sprintf(
				  /* translators: %s: Error string for a failed installation. */
				  __( 'Installation failed: %s' ),
				  response.errorMessage
				);

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
				.attr(
					'aria-label',
					sprintf(
					   /* translators: %s: Plugin name and version. */
					   _x( '%s installation failed', 'everest-forms' ),
					   $button.data( 'name' )
					)
				)
				.text( __( 'Installation Failed!' ) );

			wp.a11y.speak( errorMessage, 'assertive' );

			$document.trigger( 'wp-plugin-install-error', response );
		}
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
						.attr(
							'aria-label',
							sprintf(
								  /* translators: %s: Plugin name. */
								  _x( 'Install %s now', 'everest-forms' ),
								  pluginName
							)
						)
						.text( __( 'Install Now' ) );

					wp.a11y.speak( __( 'Update canceled.' ), 'polite' );
				} );
			}

			wp.updates.installExtension( {
				name: pluginName,
				slug: $button.data( 'slug' )
			} );
		} );
	} );
})( jQuery, window.wp );
