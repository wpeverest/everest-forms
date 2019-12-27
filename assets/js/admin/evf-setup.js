/* global evf_setup_params */
jQuery( function( $ ) {

	/**
	 * Setup actions.
	 */
	var evf_setup_actions = {
		$setup_form: $( '.everest-forms-setup' ),
		init: function() {
			this.title_focus();

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
		template_select: function( e ) {
			e.preventDefault();
			var $this        = $( this ),
				template     = $this.data( 'template' ),
				templateName = $this.data( 'template-name-raw' ),
				formName     = '',
				namePrompt   = evf_setup_params.i18n_form_name,
				nameField    = '<input autofocus="" type="text" id="everest-forms-setup-name" class="everest-forms-setup-name" placeholder="'+evf_setup_params.i18n_form_placeholder+'">',
				nameError    = '<p class="error">'+evf_setup_params.i18n_form_error_name+'</p>',
				modalContent = namePrompt+nameField+nameError;
				$.confirm({
					title: false,
					content: modalContent,
				   icon: 'dashicons dashicons-info',
					type: 'blue',
					backgroundDismiss: false,
					closeIcon: false,
					buttons: {
						confirm: {
							text: evf_setup_params.i18n_form_ok,
							btnClass: 'btn-confirm',
							keys: ['enter'],
							action: function() {
							// Don't do anything for selects that trigger modal.
							if ( $this.parent().hasClass( 'loading' ) ) {
								return;
							}
							var $formName    = $( '#everest-forms-setup-name' );
							// Check that form title is provided.
							if ( ! $formName.val() ) {
								formName = templateName;
								$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
								return false;
							} else {
								formName = $formName.val();
							}

							$this.parent().addClass( 'loading' );

							var data = {
								title: formName,
								action: 'everest_forms_create_form',
								template: template,
								security: evf_setup_params.create_form_nonce
							};

							$.post( evf_setup_params.ajax_url, data, function( response ) {
								if ( response.success ) {
									$this.parent().removeClass( 'loading' );
									window.location.href = response.data.redirect;
								} else {
									$this.parent().removeClass( 'loading' );
									$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
									window.console.log( response );
								}
							}).fail( function( xhr ) {
								window.console.log( xhr.responseText );
							});
						}
					},
					cancel: {
						text: evf_email_params.i18n_email_cancel
					}
				}
			});
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
