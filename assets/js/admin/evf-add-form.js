/* global everest_add_form_params */
(function( $ ){
	'use strict';

	$( function() {
		// Close modal
		var evfModalClose = function() {
			if ( $('#evf-modal-select-form').length ) {
				$('#evf-modal-select-form').get(0).selectedIndex = 0;
				$('#evf-modal-checkbox-title, #evf-modal-checkbox-description').prop('checked', false);
			}
			$('#evf-modal-backdrop, #evf-modal-wrap').css('display','none');
			$( document.body ).removeClass( 'modal-open' );
		};
		// Open modal when media button is clicked
		$(document).on('click', '.evf-insert-form-button', function(event) {
			event.preventDefault();
			$('#evf-modal-backdrop, #evf-modal-wrap').css('display','block');
			$( document.body ).addClass( 'modal-open' );
		});
		// Close modal on close or cancel links
		$(document).on('click', '#evf-modal-close, #evf-modal-cancel a', function(event) {
			event.preventDefault();
			evfModalClose();
		});
		// Insert shortcode into TinyMCE
		$(document).on('click', '#evf-modal-submit', function(event) {
			event.preventDefault();
			var shortcode;
			shortcode = '[everest_form id="' + $('#evf-modal-select-form').val() + '"';
			if ( $('#evf-modal-checkbox-title').is(':checked') ) {
				shortcode = shortcode+' title="true"';
			}
			if ( $('#evf-modal-checkbox-description').is(':checked') ) {
				shortcode = shortcode+' description="true"';
			}
			shortcode = shortcode+']';
			wp.media.editor.insert(shortcode);
			evfModalClose();
		});

		/**
		 * Setup actions.
		 */
		var everest_setup_form_actions = {
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
				}, 100);
			},
			template_select: function( e ) {
				e.preventDefault();

				var $this        = $( this ),
					$formName    = $( '#everest-forms-setup-name' ),
					template     = $this.data( 'template' ),
					templateName = $this.data( 'template-name-raw' ),
					formName     = '';

				// Don't do anything for selects that trigger modal
				if ( $this.parent().hasClass( 'loading' ) ) {
					return;
				}

				// Check that form title is provided.
				if ( ! $formName.val() ) {
					formName = templateName;
				} else {
					formName = $formName.val();
				}

				$this.parent().addClass( 'loading' );

				var data = {
					title: formName,
					action: 'everest_forms_create_form',
					template: template,
					security: everest_add_form_params.create_form_nonce
				};

				$.post( everest_add_form_params.ajax_url, data, function( response ) {
					if ( response.success ) {
						$this.parent().removeClass( 'loading' );
						window.location.href = response.data.redirect;
					} else {
						window.console.log( response );
					}
				}).fail( function( xhr ) {
					window.console.log( xhr.responseText );
				});
			},
			input_keypress: function ( e ) {
				var button = e.keyCode || e.which;

				// Enter key.
				if ( 13 === button && e.target.tagName.toLowerCase() === 'input' ) {
					e.preventDefault();
					return false;
				}
			}
		};

		everest_setup_form_actions.init();
	} );
}(jQuery));
