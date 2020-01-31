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
									let err_field, fid, lbl = true;

									switch ( type ){
										case 'signature':
											fid = 'evf-signature-img-input-' + tuple;
											err_field = $('#' + fid);
											break;

										case 'likert':
											fid = 'everest_forms-' + form_id + '-field_' + tuple + '_';
											err_field = $('[id^="' + fid + '"]');
											lbl = false;

											err_field.each ( function ( index, element ) {
												var tbl_header = $( element ).closest( 'tr.evf-' + form_id +'-field_' + tuple).find('th'),
													id         = 'everest_forms[form_fields][' + tuple + '][' + ( parseInt( tbl_header.closest("tr").index() ) + 1 ) + ']';

												if ( ! tbl_header.children().is('label') ) {
													tbl_header.append('<label id="' + id + '" for="' + id + '" class="evf-error">' + ajax_form_submission_params.required + '</label>');
												}
											});
											break;

										case 'address':
											fid = 'evf-' + form_id + '-field_' + tuple;
											err_field = $('[id^="' + fid + '"]');

											err_field.each ( function ( index, element ) {
												let fieldid   =  String( $( element ).attr('id') );

												if ( fieldid.includes( '-container' ) || fieldid.includes( '-address2' ) ) {
													err_field.splice( index, 1 );
												}
											});
											break;

										default:
											fid = 'evf-' + form_id + '-field_' + tuple;
											err_field = $('#' + fid);
											break;
									}

									err_field.addClass('evf-error');
									err_field.attr('required', true);
									err_field.attr('aria-invalid', true);
									err_field.first().closest('.evf-field').addClass('everest-forms-invalid evf-has-error');

									if (  true === lbl ) {
										err_field.after('<label id="' + err_field.attr('id') + '-error" class="evf-error" for="' + err_field.attr('id') + '">' + ajax_form_submission_params.required + '</label>');
									}
								});

								btn.attr('disabled', false).html( ajax_form_submission_params.submit );
						}
					})
					.fail( function () {
						btn.attr('disabled', false).html( ajax_form_submission_params.submit );
						formTuple.trigger('focusout').trigger('change');
						formTuple.closest('.everest-forms').find('.everest-forms-notice').remove();
						formTuple.closest('.everest-forms').prepend('<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ ajax_form_submission_params.error  +'</div>').focus();
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
