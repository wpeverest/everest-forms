/* global everest_forms_ajax_submission_params */
jQuery( function( $ ) {
	'use strict';

	var evf_ajax_submission_init = function(){
		var form = $( 'form[data-ajax_submission="1"]' );

		form.each( function( i, v ) {
			$( document ).ready( function() {
				var formTuple = $( v ),
					btn = formTuple.find( '.evf-submit' ),
					stripeForms = formTuple.find( "[data-gateway*='stripe']:visible" )

				// If it's an ajax form containing a stripe gateway, donot latch into the button.
				if ( stripeForms.length > 0 ) {
					return;
				}

				btn.on( 'click', function( e ) {
					var data = formTuple.serializeArray();

					e.preventDefault();

					// We let the bubbling events in form play itself out.
					formTuple.trigger( 'focusout' ).trigger( 'change' ).trigger( 'submit' );

					var errors = formTuple.find( '.evf-error:visible' );

					if ( errors.length > 0 ) {
						$( [document.documentElement, document.body] ).animate({
							scrollTop: errors.last().offset().top
						}, 800 );
						return;
					}

					// Change the text to user defined property.
					$( this ).html( formTuple.data( 'process-text' ) );

					// Add action intend for ajax_form_submission endpoint.
					data.push({
						name: 'action',
						value: 'everest_forms_ajax_form_submission'
					});
					data.push({
						name: 'security',
						value: everest_forms_ajax_submission_params.evf_ajax_submission
					});

					// Fire the ajax request.
					$.ajax({
						url: everest_forms_ajax_submission_params.ajax_url,
						type: 'POST',
						data: data
					})
					.done( function ( xhr, textStatus, errorThrown ) {
						if ( 'undefined' !== typeof xhr.data.redirect_url ) {
							formTuple.trigger( 'reset' );
							window.location = xhr.data.redirect_url;
							return;
						}
						if ( 'success' === xhr.data.response || true === xhr.success ) {
							formTuple.trigger( 'reset' );
							formTuple.closest( '.everest-forms' ).html( '<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + xhr.data.message + '</div>' ).focus();
						} else {
							var	form_id = formTuple.data( 'formid' ),
								error   =  everest_forms_ajax_submission_params.error,
								err     =  JSON.parse( errorThrown.responseText ),
								fields  = err.data.error;

								if ( 'string' === typeof err.data.message ) {
									error =  err.data.message;
								}

								formTuple.closest( '.everest-forms' ).find( '.everest-forms-notice' ).remove();
								formTuple.closest( '.everest-forms' ).prepend( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ error  +'</div>' ).focus();

								// Begin fixing the tamper.
								$( fields ).each( function( index, fieldTuple ) {
									var tuple = Object.values(fieldTuple)[0],
										type  = Object.keys(fieldTuple)[0],
										err_field, fid, lbl = true;

									switch ( type ) {
										case 'signature':
											fid       = 'evf-signature-img-input-' + tuple;
											err_field = $( '#' + fid );
											break;

										case 'likert':
											fid       = 'everest_forms-' + form_id + '-field_' + tuple + '_';
											err_field = $( '[id^="' + fid + '"]' );
											lbl       = false;

											err_field.each( function( index, element ) {
												var tbl_header = $( element ).closest( 'tr.evf-' + form_id +'-field_' + tuple ).find( 'th' ),
													id         = 'everest_forms[form_fields][' + tuple + '][' + ( parseInt( tbl_header.closest( 'tr' ).index() ) + 1 ) + ']';

												if ( ! tbl_header.children().is( 'label' ) ) {
													tbl_header.append( '<label id="' + id + '" for="' + id + '" class="evf-error">' + everest_forms_ajax_submission_params.required + '</label>' );
												} else {
													tbl_header.children().find( '#' + id ).show();
												}
											});
											break;

										case 'address':
											fid       = 'evf-' + form_id + '-field_' + tuple;
											err_field = $( '[id^="' + fid + '"]' );

											err_field.each( function ( index, element ) {
												var fieldId =  String( $( element ).attr( 'id' ) );

												if ( fieldId.includes( '-container' ) || fieldId.includes( '-address2' ) ) {
													err_field.splice( index, 1 );
												} else  {
													if ( 'undefined' !== typeof $( element ).val() ) {
														err_field.splice( index, 1 );
													};
												}
											});
											break;

										default:
											fid       = 'evf-' + form_id + '-field_' + tuple;
											err_field = $( '#' + fid );
											break;
									}

									err_field.addClass( 'evf-error' );
									err_field.attr( 'required', true );
									err_field.attr( 'aria-invalid', true );
									err_field.first().closest( '.evf-field' ).addClass( 'everest-forms-invalid evf-has-error' );

									if ( true === lbl && ! err_field.is( 'label' ) ) {
										err_field.after( '<label id="' + err_field.attr( 'id' ) + '-error" class="evf-error" for="' + err_field.attr( 'id' ) + '">' + everest_forms_ajax_submission_params.required + '</label>' ).show();
									}
								});

							btn.attr( 'disabled', false ).html( everest_forms_ajax_submission_params.submit );
						}
					})
					.fail( function () {
						btn.attr( 'disabled', false ).html( everest_forms_ajax_submission_params.submit );
						formTuple.trigger( 'focusout' ).trigger( 'change' );
						formTuple.closest( '.everest-forms' ).find( '.everest-forms-notice' ).remove();
						formTuple.closest( '.everest-forms' ).prepend( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ everest_forms_ajax_submission_params.error  +'</div>' ).focus();
					})
					.always( function( xhr ) {
						if ( 'undefined' === typeof xhr.data.redirect_url ) {
							$( [document.documentElement, document.body] ).animate({
								scrollTop: $( '.everest-forms-notice' ).offset().top
							}, 800 );
						}
					});
				});
			});
		});
	};

	evf_ajax_submission_init();
});
