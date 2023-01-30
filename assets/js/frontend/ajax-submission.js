/* global everest_forms_ajax_submission_params */
jQuery( function( $ ) {
	'use strict';
	var evf_ajax_submission_init = function(){
		var form = $( 'form[data-ajax_submission="1"]' );
		form.each( function( i, v ) {
			$( document ).ready( function() {
				var formTuple = $( v ),
					btn = formTuple.find( '.evf-submit' ),
				 	 razorpayForms = formTuple.find( "[data-gateway='razorpay']" );


				btn.on( 'click', function( e ) {
					var paymentMethod = formTuple.find( ".everest-forms-stripe-gateways-tabs .evf-tab" ).has( 'a.active' ).data( 'gateway' );
					if(undefined === paymentMethod) {
						paymentMethod = formTuple.find( ".everest-forms-gateway[data-gateway='stripe']" ).data( 'gateway' );
					}

					if (formTuple.find( ".everest-forms-gateway[data-gateway='stripe']").hasClass('StripeElement--empty') && $(".evf-field-credit-card ").is(':visible') ){
						$( '#card-errors' ).html( 'This field is required' ).show();
						return false;
					}

					if ( typeof tinyMCE !== 'undefined' ) {
						tinyMCE.triggerSave();
					}

					var	recaptchaID = btn.get( 0 ).recaptchaID;

					if (  recaptchaID === 0 ) {
						grecaptcha.execute( recaptchaID );
						return false;
					}

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
						var redirect_url = ( xhr.data && xhr.data.redirect_url ) ? xhr.data.redirect_url : '';
						if ( redirect_url ) {
							formTuple.trigger( 'reset' );
							window.location = redirect_url;
							return;
						}
						if ( 'success' === xhr.data.response || true === xhr.success ) {
							let pdf_download_message = '';
							let quiz_reporting = '';
							if(xhr.data.form_id !== undefined && xhr.data.entry_id !== undefined && xhr.data.pdf_download == true){
								pdf_download_message = '<br><small><a href="/?page=evf-entries-pdf&form_id='+ xhr.data.form_id+'&entry_id='+xhr.data.entry_id+'">' + xhr.data.pdf_download_message + '</a></small>';
							}
							if( xhr.data.quiz_result_shown == true){
								quiz_reporting = xhr.data.quiz_reporting;
							}

							var paymentMethod = formTuple.find( ".everest-forms-stripe-gateways-tabs .evf-tab" ).has( 'a.active' ).data( 'gateway' );


							if(undefined === paymentMethod) {
								paymentMethod = formTuple.find( ".everest-forms-gateway[data-gateway='ideal']" ).data( 'gateway' );
								if ('ideal' === paymentMethod ){
									paymentMethod = 'ideal';
								}else{
									paymentMethod = formTuple.find( ".everest-forms-gateway[data-gateway='stripe']" ).data( 'gateway' );
								}
							}

							if( 'stripe' === paymentMethod && 'none' !== formTuple.find( ".everest-forms-gateway[data-gateway='stripe']" ).closest( '.evf-field' ).css( 'display' ) ) {
								formTuple.trigger( 'everest_forms_frontend_before_ajax_complete_success_message', xhr.data );
								return;
							}

							if(undefined === paymentMethod) {
								paymentMethod = formTuple.find( ".everest-forms-gateway[data-gateway='ideal']" ).data( 'gateway' );
							}


							if( 'ideal' === paymentMethod && 'none' !== formTuple.find( ".everest-forms-gateway[data-gateway='ideal']" ).closest( '.evf-field' ).css( 'display' )  ) {
								formTuple.trigger( 'evf_process_payment', xhr.data );
								return;
							}
							formTuple.trigger( 'reset' );
							formTuple.closest( '.everest-forms' ).html( '<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + xhr.data.message + pdf_download_message + '</div>' + quiz_reporting ).focus();
							localStorage.removeItem(formTuple.attr('id'));

							// Trigger for form submission success.
							var event = new CustomEvent("everest_forms_ajax_submission_success", {
								detail: {
									formId: 'evf-form-' + xhr.data.form_id,
								}
							  });
							document.dispatchEvent(event);
						} else {
							var	form_id = formTuple.data( 'formid' );
							var err     =  JSON.parse( errorThrown.responseText );
							if( 'undefined' !== typeof err.data[form_id] ) {
								var error =  err.data[form_id].header;
							} else{
								var	error   =  everest_forms_ajax_submission_params.error;
							}
							var fields  = err.data.error;

								if ( 'string' === typeof err.data.message ) {
									error =  err.data.message;
								}

								formTuple.closest( '.everest-forms' ).find( '.everest-forms-notice' ).remove();
								formTuple.closest( '.everest-forms' ).prepend( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ error  +'</div>' ).focus();

								// Begin fixing the tamper.
								$( fields ).each( function( index, fieldTuple ) {
									var err_msg = Object.values(fieldTuple)[0],
										fld_id  = Object.keys(fieldTuple)[0],
										err_field, fid, lbl = true;

									var fld_container_id = 'evf'-+ form_id +'-field_' + fld_id +'-container';

									if($('#'+fld_container_id).hasClass('evf-field-signature')) { //When field type is signature
										fid       = 'evf-signature-img-input-' + fld_id;
										err_field = $( '#' + fid );
									} else if ($('#'+fld_container_id).hasClass('evf-field-likert')) { //When field type is likert
										fid       = 'everest_forms-' + form_id + '-field_' + fld_id + '_';
										err_field = $( '[id^="' + fid + '"]' );
										lbl       = false;

										err_field.each( function( index, element ) {
											var tbl_header = $( element ).closest( 'tr.evf-' + form_id +'-field_' + fld_id ).find( 'th' ),
												id         = 'everest_forms[form_fields][' + fld_id + '][' + ( parseInt( tbl_header.closest( 'tr' ).index() ) + 1 ) + ']';

											if ( ! tbl_header.children().is( 'label' ) ) {
												if( tbl_header.parents( 'span.input-wrapper' ).length ) {
													tbl_header.parents( 'span.input-wrapper' ).append( '<label id="' + id + '" for="' + id + '" class="evf-error">' + everest_forms_ajax_submission_params.required + '</label>' );
												} else {
													tbl_header.append( '<label id="' + id + '" for="' + id + '" class="evf-error">' + everest_forms_ajax_submission_params.required + '</label>' );
												}
											} else {
												tbl_header.children().find( '#' + id ).show();
											}
										});
									} else if ($('#'+fld_container_id).hasClass('evf-field-address')) { //When field type is address
										fid       = 'evf-' + form_id + '-field_' + fld_id;
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
									} else {
										fid       = 'evf-' + form_id + '-field_' + fld_id;
										err_field = $( '#' + fid );
									}

									err_field.addClass( 'evf-error' );
									err_field.attr( 'aria-invalid', true );
									err_field.first().closest( '.evf-field' ).addClass( 'everest-forms-invalid evf-has-error' );

									if ( true === lbl && ! err_field.is( 'label' ) ) {
										if( err_field.parents( 'span.input-wrapper' ).length ){
											err_field.parents( 'span.input-wrapper' ).after( '<label id="' + err_field.attr( 'id' ) + '-error" class="evf-error" for="' + err_field.attr( 'id' ) + '">' + err_msg + '</label>' ).show();
										} else {
											err_field.after( '<label id="' + err_field.attr( 'id' ) + '-error" class="evf-error" for="' + err_field.attr( 'id' ) + '">' + err_msg + '</label>' ).show();
										}
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
						var redirect_url = ( xhr.data && xhr.data.redirect_url ) ? xhr.data.redirect_url : '';
						if ( ! redirect_url && $( '.everest-forms-notice' ).length ) {
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
