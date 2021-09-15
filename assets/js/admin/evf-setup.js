/* global evf_setup_params */
jQuery( function( $ ) {

	/**
	 * Setup actions.
	 */
	var evf_setup_actions = {
		$setup_form: $( '.everest-forms-setup--form' ),
		$button_install: evf_data.i18n_activating,
		init: function() {
			this.title_focus();

			// Template actions.
			$( document ).on( 'click', '.everest-forms-template-install-addon', this.install_addon );
			$( document ).on( 'click', '.everest-forms-builder-setup .upgrade-modal', this.message_upgrade );
			$( document ).on( 'click', '.everest-forms-builder-setup .evf-template-preview', this.template_preview );

			// Select and apply a template.
			this.$setup_form.on( 'click', '.evf-template-select', this.template_select );

			// Prevent <ENTER> key for setup actions.
			$( document.body ).on( 'keypress', '.everest-forms-setup-form-name input', this.input_keypress );
		},
		title_focus: function() {
			setTimeout( function (){
				$( '#everest-forms-setup-name' ).focus();
			}, 100 );
		},
		install_addon: function( event ) {
			var pluginsList   = $( '.plugins-list-table' ).find( '#the-list tr' ),
				$target       = $( event.target ),
				success       = 0,
				error         = 0,
				errorMessages = [];

			wp.updates.maybeRequestFilesystemCredentials(event);

			$( '.everest-forms-template-install-addon' ).html( '<div class="evf-loading evf-loading-active"></div>' + evf_setup_actions.$button_install ).prop( 'disabled', true );

			$( document ).trigger( 'wp-plugin-bulk-install', pluginsList );

			// Find all the plugins which are required.
			pluginsList.each( function( index, element ) {
				var $itemRow = $(element);

				// Only add inactive items to the update queue.
				if ( ! $itemRow.hasClass( 'inactive' ) || $itemRow.find( 'notice-error' ).length ) {
					return;
				}

				// Add it to the queue.
				wp.updates.queue.push({
					action: 'everest_forms_install_extension',
					data: {
						page: pagenow,
						name: $itemRow.data( 'name' ),
						slug: $itemRow.data( 'slug' )
					}
				});
			});

			// Display bulk notification for install of plugin.
			$( document ).on( 'wp-plugin-bulk-install-success wp-plugin-bulk-install-error', function( event, response ) {
				var $itemRow = $( '[data-slug="' + response.slug + '"]' ), $bulkActionNotice, itemName;

				if ( 'wp-' + response.install + '-bulk-install-success' === event.type ) {
					success++;
				} else {
					itemName = response.pluginName ? response.pluginName : $itemRow.find( '.plugin-name' ).text();
					error++;
					errorMessages.push( itemName + ': ' + response.errorMessage );
				}

				wp.updates.adminNotice = wp.template( 'wp-bulk-installs-admin-notice' );

				// Remove previous error messages, if any.
				$( '.everest-forms-recommend-addons .bulk-action-notice' ).remove();

				$( '.everest-forms-recommend-addons .plugins-info' ).after( wp.updates.adminNotice({
						id: 'bulk-action-notice',
						className: 'bulk-action-notice notice-alt',
						successes: success,
						errors: error,
						errorMessages: errorMessages,
						type: response.install
					})
				);

				$bulkActionNotice = $( '#bulk-action-notice' ).on( 'click', 'button', function() {
					// $( this ) is the clicked button, no need to get it again.
					$( this )
						.toggleClass( 'bulk-action-errors-collapsed' )
						.attr( 'aria-expanded', ! $( this ).hasClass( 'bulk-action-errors-collapsed' ) );
					// Show the errors list.
					$bulkActionNotice.find( '.bulk-action-errors' ).toggleClass( 'hidden' );
				});

				if ( ! wp.updates.queue.length ) {
					if ( error > 0 ) {
						$target
							.removeClass( 'updating-message' )
							.text( $target.data( 'originaltext' ) );
					}
				}

				if ( 0 === wp.updates.queue.length ) {
					$( '.everest-forms-template-install-addon' ).remove();
					$( '.everest-forms-builder-setup .jconfirm-buttons button' ).show();
				}
			} );

			// Check the queue, now that the event handlers have been added.
			wp.updates.queueChecker();
		},
		message_upgrade: function( e ) {
			var templateName = $( this ).data( 'template-name-raw' );

			e.preventDefault();

			$.alert( {
				title: templateName + ' ' + evf_setup_params.upgrade_title,
				theme: 'jconfirm-modern jconfirm-everest-forms',
				icon: 'dashicons dashicons-lock',
				backgroundDismiss: false,
				scrollToPreviousElement: false,
				content: evf_setup_params.upgrade_message,
				type: 'red',
				boxWidth: '565px',
				buttons: {
					confirm: {
						text: evf_setup_params.upgrade_button,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							window.open( evf_setup_params.upgrade_url, '_blank' );
						}
					},
					cancel: {
						text: evf_data.i18n_ok
					}
				}
			} );
		},
		template_preview: function() {
			var $this       = $(this),
				previewLink = $this.data('preview-link');

			$this.closest( '.everest-forms-setup--form' ).find( '.evf-template-preview-iframe #frame' ).attr( 'src', previewLink );
		},
		template_select: function( event ) {
			var $this        = $( this ),
				template     = $this.data( 'template' ),
				templateName = $this.data( 'template-name-raw' ),
				formName     = '',
				namePrompt   = evf_setup_params.i18n_form_name,
				nameField    = '<input autofocus="" type="text" id="everest-forms-setup-name" class="everest-forms-setup-name" placeholder="'+evf_setup_params.i18n_form_placeholder+'">',
				nameError    = '<p class="error">' + evf_setup_params.i18n_form_error_name + '</p>';

			event.preventDefault();

			$target = $( event.target );

			if ( $target.hasClass( 'disabled' ) || $target.hasClass( 'updating-message' ) ) {
				return;
			}

			$.confirm( {
				title: evf_setup_params.i18n_form_title,
				theme: 'jconfirm-modern jconfirm-everest-forms-left',
				backgroundDismiss: false,
				scrollToPreviousElement: false,
				content: function() {
					// Fire AJAX.
					var self = this,
						button = evf_data.i18n_install_only;

					if ( $target.closest( '.evf-template' ).find( 'span.everest-forms-badge' ).length ) {
						var data =  {
							action: 'everest_forms_template_licence_check',
							plan: $this.attr( 'data-licence-plan' ).replace('-lifetime',''),
							slug: $this.attr( 'data-template' ),
							security: evf_setup_params.template_licence_check_nonce
						};

						return $.ajax( {
							url: evf_email_params.ajax_url,
							data: data,
							type: 'POST',
						} ).done( function( response ) {
							self.setContentAppend( namePrompt+nameField+nameError+response.data.html );

							if ( response.data.activate ) {
								$( '.everest-forms-builder-setup .jconfirm-buttons button' ).show();
							} else {
								if ( response.data.html.includes( 'install-now' ) ) {
									button = evf_data.i18n_install_activate;
									evf_setup_actions.$button_install = evf_data.i18n_installing;
								}
								var installButton = '<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-template-install-addon">' + button + '</a>';
								$( '.everest-forms-builder-setup .jconfirm-buttons' ).append( installButton );
							}
						} );
					} else {
						$( '.everest-forms-builder-setup .jconfirm-buttons button' ).show();
						return namePrompt+nameField+nameError;
					}
				},
				buttons: {
					Continue: {
						isHidden: true, // Hide the button.
						btnClass: 'everest-forms-btn everest-forms-btn-primary',
						action: function () {
							var $formName = $( '#everest-forms-setup-name' ),
								overlay   = $( '.everest-forms-loader-overlay' );

							// Check that form title is provided.
							if ( ! $formName.val() ) {
								formName = templateName;
								var error = this.$content.find( '.error' );
								$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
								error.show();
								return false;
							} else {
								formName = $formName.val();
							}

							overlay.show();

							var data = {
								title: formName,
								action: 'everest_forms_create_form',
								template: template,
								security: evf_setup_params.create_form_nonce
							};

							$.post( evf_setup_params.ajax_url, data, function( response ) {
								if ( response.success ) {
									window.location.href = response.data.redirect;
								} else {
									overlay.hide();
									$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
									window.console.log( response );
								}
							}).fail( function( xhr ) {
								window.console.log( xhr.responseText );
							});
						}
					},
				}
			} );
		},
		input_keypress: function ( e ) {
			var button = e.keyCode || e.which;

			$( this ).removeClass( 'everest-forms-required' );

			// Enter key.
			if ( 13 === button && e.target.tagName.toLowerCase() === 'input' ) {
				e.preventDefault();
				return false;
			}
		}
	};

	evf_setup_actions.init();
});
