/* global evf_setup_params */
jQuery( function( $ ) {

	/**
	 * Setup actions.
	 */
	var evf_setup_actions = {
		$setup_form: $( 'form.everest-forms-setup' ),
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
				$formName    = $( '#everest-forms-setup-name' ),
				template     = $this.data( 'template' ),
				templateName = $this.data( 'template-name-raw' ),
				formName     = '';

			// Don't do anything for selects that trigger modal.
			if ( $this.parent().hasClass( 'loading' ) ) {
				return;
			}

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
