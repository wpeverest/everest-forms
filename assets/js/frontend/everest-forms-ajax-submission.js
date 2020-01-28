/* function ajax_submission_init */
jQuery( function($) {
	'use strict';

	var ajax_submission_init = function(){
		var form = $('form[data-ajax_submission="1"]');

		form.each( function( i, v ) {
			$( document ).ready(function() {
				var formTuple = $(v),
					btn = formTuple.find('.evf-submit');

				btn.on('click', function(e) {
					var data = formTuple.serializeArray();

					e.preventDefault();

					// We let the bubbling events in form play itself out.
					// @todo - bad practice
					formTuple.trigger('focusout').trigger('change');
					var errors = formTuple.find('.evf-error:visible');
					if( errors.length > 0 ){
						$([document.documentElement, document.body]).animate({
							scrollTop: errors.last().offset().top
						}, 800);
						return;
					}

					// Change the text to user defined property.
					$( this ).html( formTuple.data('process-text'));

					// Add action intend for ajax_form_submission endpoint.
					data.push({
						name: 'action',
						value: 'everest_forms_ajax_form_submission'
					});
					data.push({
						name: 'security',
						value: ajax_form_submission_params.evf_ajax_submission
					});


					// Fire the ajax request.
					$.ajax({
						url: ajax_form_submission_params.ajax_url,
						type: 'POST',
						data: data
					})
					.done( function ( res ) {
						if ( 'success' === res.data.response ){
								formTuple.trigger('reset');
								formTuple.closest('.everest-forms').html('<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + res.data.message + '</div>').focus();
						} else {
							formTuple.closest('.everest-forms').find('.everest-forms-notice').remove();
								formTuple.closest('.everest-forms').prepend('<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ res.data.message +'</div>').focus();
						}
					})
					.fail( function () {
						formTuple.closest('.everest-forms').find('.everest-forms-notice').remove();
						formTuple.closest('.everest-forms').prepend('<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ ajax_form_submission_params.error +'</div>').focus();
					})
					.always(function() {
						$([document.documentElement, document.body]).animate({
							scrollTop: $('.everest-forms-notice').offset().top
						}, 800);
					});
				});
			});
		});
	};

	ajax_submission_init();
});
