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
				spinner      = '<i class="evf-loading evf-loading-active" />';
				template     = $this.data( 'template' ),
				templateName = $this.data( 'template-name-raw' ),
				formName     = '',
				namePrompt   = evf_setup_params.i18n_form_name,
				nameField    = '<input autofocus="" type="text" id="everest-forms-setup-name" class="everest-forms-setup-name" placeholder="'+evf_setup_params.i18n_form_placeholder+'">',
				nameError    = '<p class="error">'+evf_setup_params.i18n_form_error_name+'</p>',
				modalContent = namePrompt+nameField+nameError;
				$.confirm({
					title: evf_setup_params.i18n_form_title,
					content: modalContent,
					backgroundDismiss: false,
					closeIcon: true,
					buttons: {
						confirm: {
							text: evf_setup_params.i18n_form_ok,
							btnClass: 'everest-forms-btn everest-forms-btn-primary',
							keys: ['enter'],
							action: function() {
							// Don't do anything for selects that trigger modal.
							if ( $this.closest('.everest-forms').find('.evf-loading').hasClass( 'evf-loading-active' ) ) {
								return;
							}
							var $formName    = $( '#everest-forms-setup-name' );
							// Check that form title is provided.
							if ( ! $formName.val() ) {
								formName = templateName;
								var error = this.$content.find('.error');
								$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
								error.show();
								return false;
							} else {
								formName = $formName.val();
							}

							$this.closest('.everest-forms').find('.evf-loading').addClass( 'evf-loading-active' );

							var data = {
								title: formName,
								action: 'everest_forms_create_form',
								template: template,
								security: evf_setup_params.create_form_nonce
							};

							$.post( evf_setup_params.ajax_url, data, function( response ) {
								if ( response.success ) {
									$this.closest('.everest-forms').find('.evf-loading').removeClass( 'evf-loading-active' );
									window.location.href = response.data.redirect;
								} else {
									$this.closest('.everest-forms').find('.evf-loading').removeClass( 'evf-loading-active' );
									$( '.everest-forms-setup-name' ).addClass( 'everest-forms-required' ).focus();
									window.console.log( response );
								}
							}).fail( function( xhr ) {
								window.console.log( xhr.responseText );
							});
						}
					},
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
