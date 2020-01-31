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
					formTuple.trigger('focusout').trigger('change').trigger('submit');
					var errors = formTuple.find('.evf-error:visible');

					if( errors.length > 0 ) {
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
					.done( function ( xhr, textStatus, errorThrown ) {
						if ( 'success' === xhr.data.response ){
								formTuple.trigger('reset');
								formTuple.closest('.everest-forms').html('<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + xhr.data.message + '</div>').focus();
						} else {
								var error   =  ajax_form_submission_params.error,
									err     =  JSON.parse(errorThrown.responseText),
									fields  = err.data.error;
								var	form_id = formTuple.data('formid');

								if ( 'string' === typeof err.data.message ) {
									error =  err.data.message;
								}

								formTuple.closest('.everest-forms').find('.everest-forms-notice').remove();
								formTuple.closest('.everest-forms').prepend('<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ error  +'</div>').focus();

								// Begin fixing the tamper.
								$( fields ).each( function( index, fieldTuple ) {
									let tuple = Object.values(fieldTuple)[0],
										type  = Object.keys(fieldTuple)[0];
									let err_field, fid;

									switch ( type ){
										case 'signature':
											fid = 'evf-signature-img-input-' + tuple;
											err_field = $('#' + fid);
											break;

										case 'likert':
											fid = 'evf-' + form_id + '-field_' + tuple;
											err_field = $('.' + fid);
											break;

										default:
											fid = 'evf-' + form_id + '-field_' + tuple;
											err_field = $('#' + fid);
											break;
									}

									err_field.addClass('evf-error');
									err_field.after('<label id="' + err_field.attr('id') + '-error" class="evf-error" for="' + err_field.attr('id') + '">' + ajax_form_submission_params.required + '</label>');
									err_field.attr('required', true);
									err_field.attr('aria-invalid', true);
									err_field.closest('.evf-field').addClass('everest-forms-invalid evf-has-error');
								});
						}
					})
					.fail( function () {
						formTuple.trigger('focusout').trigger('change');
						formTuple.closest('.everest-forms').find('.everest-forms-notice').remove();
						formTuple.closest('.everest-forms').prepend('<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ ajax_form_submission_params.error  +'</div>').focus();
					})
					.always(function() {
						$([document.documentElement, document.body]).animate({
							scrollTop: $('.everest-forms-notice').offset().top
						}, 800);
					});

					$( this ).attr('disabled', false).html( ajax_form_submission_params.submit );
				});
			});
		});
	};

	ajax_submission_init();
});
