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
	} );
}(jQuery));
