/* eslint-disable max-len */
/* global everest_forms_params */
jQuery( function ( $ ) {
	'use strict';

	// everest_forms_params is required to continue, ensure the object exists.
	if ( typeof everest_forms_params === 'undefined' ) {
		return false;
	}

	var getEnhancedSelectFormatString = function() {
		return {
			'language': {
				noResults: function() {
					return everest_forms_params.i18n_no_matches;
				}
			}
		};
	};

	var everest_forms = {
		$everest_form: $( 'form.everest-form' ),
		init: function() {
			this.init_inputMask();
			this.init_mailcheck();
			this.init_datepicker();
			this.load_validation();
			this.submission_scroll();
			this.randomize_elements();
			this.init_enhanced_select();

			// Inline validation.
			this.$everest_form.on( 'input validate change', '.input-text, select, input:checkbox, input:radio', this.validate_field );

			// Notify plugins that the core was loaded.
			$( document.body ).trigger( 'everest_forms_loaded' );
		},
		init_inputMask: function() {
			// Only load if jQuery inputMask library exists.
			if ( typeof $.fn.inputmask !== 'undefined' ) {
				$( '.evf-masked-input' ).inputmask();
			}
		},
		init_mailcheck: function() {
			// Only load if Mailcheck library exists and enabled.
			if ( typeof $.fn.mailcheck === 'undefined' || ! everest_forms_params.mailcheck_enabled ) {
				return;
			}

			// Setup default domains for Mailcheck.
			if ( everest_forms_params.mailcheck_domains.length > 0 ) {
				Mailcheck.defaultDomains = Mailcheck.defaultDomains.concat( everest_forms_params.mailcheck_domains );
			}

			// Setup default top level domains for Mailcheck.
			if ( everest_forms_params.mailcheck_toplevel_domains.length > 0 ) {
				Mailcheck.defaultTopLevelDomains = Mailcheck.defaultTopLevelDomains.concat( everest_forms_params.mailcheck_toplevel_domains );
			}

			// Mailcheck suggestion.
			$( document ).on( 'blur', '.evf-field-email input', function() {
				var $el = $( this ),
					id  = $el.attr( 'id' );

				$el.mailcheck( {
					suggested: function( el, suggestion ) {
						$( '#' + id + '_suggestion' ).remove();
						var suggestion_msg = everest_forms_params.i18n_messages_email_suggestion.replace( '{suggestion}', '<a href="#" class="mailcheck-suggestion" data-id="' + id + '" title="' + everest_forms_params.i18n_messages_email_suggestion_title + '">' + suggestion.full + '</a>' );
						$( el ).after( '<label class="evf-error mailcheck-error" id="' + id + '_suggestion">' + suggestion_msg + '</label>' );
					},
					empty: function() {
						$( '#' + id + '_suggestion' ).remove();
					},
				} );
			} );

			// Apply Mailcheck suggestion.
			$( document ).on( 'click', '.evf-field-email .mailcheck-suggestion', function( e ) {
				var $el = $( this ),
					id  = $el.attr( 'data-id' );
				e.preventDefault();
				$( '#' + id ).val( $el.text() );
				$el.parent().remove();
			} );
		},
		init_datepicker: function () {
			var evfDateField = $( '.evf-field-date-time' );
			if ( evfDateField.length > 0 ) {
				$( '.flatpickr-field' ).each( function() {
					var timeInterval = 5,
						inputData  	 = $( this ).data(),
						disableDates = [];

					// Extract list of disabled dates.
					if ( inputData.disableDates ) {
						disableDates = inputData.disableDates.split( ',' );
					}

					switch( inputData.dateTime ) {
						case 'date':
							// Apply flatpicker to field.
							$( this ).flatpickr({
								disableMobile : true,
								mode          : inputData.mode,
								minDate       : inputData.minDate,
								maxDate       : inputData.maxDate,
								dateFormat    : inputData.dateFormat,
								disable       : disableDates,
							});
						break;
						case 'time':
							if ( undefined !== inputData.timeInterval ) {
								timeInterval = parseInt( inputData.timeInterval, 10 );
							}

							// Apply flatpicker to field.
							$( this ).flatpickr({
								enableTime   	: true,
								noCalendar   	: true,
								minuteIncrement : timeInterval,
								dateFormat      : inputData.dateFormat,
								disableMobile	: true,
								time_24hr		: inputData.dateFormat.includes('H:i')
							});
						break;
						case 'date-time':
							if ( undefined !== inputData.timeInterval ) {
								timeInterval = parseInt( inputData.timeInterval, 10 );
							}

							// Apply flatpicker to field.
							$( this ).flatpickr({
								enableTime   	: true,
								noCalendar   	: false,
								disableMobile	: true,
								mode            : inputData.mode,
								minDate         : inputData.minDate,
								maxDate         : inputData.maxDate,
								minuteIncrement : timeInterval,
								dateFormat      : inputData.dateFormat,
								time_24hr		: inputData.dateFormat.includes( 'H:i' ),
								disable         : disableDates,
							});
						break;
						default:
					}
				});
			}
		},
		load_validation: function() {
			if ( typeof $.fn.validate === 'undefined' ) {
				return false;
			}

			// Prepend URL field contents with http:// if user input doesn't contain a schema.
			$( '.evf-field-url input[type=url]' ).on( 'change', function () {
				var url = $( this ).val();
				if ( ! url ) {
					return false;
				}
				if ( url.substr( 0, 7 ) !== 'http://' && url.substr( 0, 8 ) !== 'https://' ) {
					$( this ).val( 'http://' + url );
				}
			});

			// Validator messages.
			$.extend( $.validator.messages, {
				required: everest_forms_params.i18n_messages_required,
				url: everest_forms_params.i18n_messages_url,
				email: everest_forms_params.i18n_messages_email,
				number: everest_forms_params.i18n_messages_number
			});

			// Validate email addresses.
			$.validator.methods.email = function( value, element ) {
				/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
				var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
				return this.optional( element ) || pattern.test( value );
			};

			// Validate confirmations.
			$.validator.addMethod( 'confirm', function( value, element, param ) {
				return $.validator.methods.equalTo.call( this, value, element, param );
			}, everest_forms_params.i18n_messages_confirm );

			// Validate checkbox choice limit.
			$.validator.addMethod( 'check-limit', function( value, element ) {
				var $ul         = $( element ).closest( 'ul' ),
					$checked    = $ul.find( 'input[type="checkbox"]:checked' ),
					choiceLimit = parseInt( $ul.attr( 'data-choice-limit' ) || 0, 10 );

				if ( 0 === choiceLimit ) {
					return true;
				}

				return $checked.length <= choiceLimit;
			}, function( params, element ) {
				var	choiceLimit = parseInt( $( element ).closest( 'ul' ).attr( 'data-choice-limit' ) || 0, 10 );
				return everest_forms_params.i18n_messages_check_limit.replace( '{#}', choiceLimit );
			} );

			this.$everest_form.each( function() {
				var $this = $( this );

				// List messages to show for required fields. Use name of the field as key.
				var error_messages = {};
				$( '.evf-field' ).each( function() {
					var form_id       = $( this ).closest( 'form' ).data( 'formid' );
					var field_id      = $( this ).data( 'field-id' );
					var error_message = $( this ).data( 'required-field-message' );
					var key           = 'everest_forms[form_fields][' + field_id + ']'; // Name of the input field is used as a key.

					if ( $( this ).is( '.evf-field-payment-single' ) ) {
						if ( ! $( this ).find('.evf-payment-price').is( '.evf-payment-user-input' ) ) {
							$( this ).find('.evf-payment-price').attr( 'required', false );
							error_message = null;
						}
					} else if ( $( this ).is( '.evf-field-checkbox, .evf-field-payment-checkbox' ) ) {
						key = key + '[]';
					} else if ( $( this ).is( '.evf-field-image-upload' ) ) {
						key = 'evf_' + form_id + '_' + field_id;
					} else if ( $( this ).is( '.evf-field-signature' ) ) {
						key = 'everest_forms[form_fields][' + field_id + '][signature_image]';
					} else if ( $( this ).is( '.evf-field-phone' ) ) {
						key = key + '[phone_field]';
					} else if ( $( this ).is( '.evf-field-email' ) || $( this ).is( '.evf-field-password' ) ) {
						// For when the confirm is disabled.
						key = 'everest_forms[form_fields][' + field_id + ']';
						error_messages[ key ] = {
							required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
						};

						// For when the confirm is enabled.
						key = 'everest_forms[form_fields][' + field_id + '][primary]';
						error_messages[ key ] = {
							required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
						};
						key = 'everest_forms[form_fields][' + field_id + '][secondary]';
						error_messages[ key ] = {
							required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
						};
						error_message = null;
					} else if ( $( this ).is( '.evf-field-address' ) ) {
						var sub_field_error_messages = {
							'address1': $( this ).data( 'required-field-message-address1' ),
							'city'    : $( this ).data( 'required-field-message-city' ),
							'state'   : $( this ).data( 'required-field-message-state' ),
							'postal'  : $( this ).data( 'required-field-message-postal' ),
							'country' : $( this ).data( 'required-field-message-country' ),
						}

						var sub_field_types = Object.keys( sub_field_error_messages );
						for ( var i = 0; i < sub_field_types.length; i++ ) {
							var sub_field_type = sub_field_types[i],
								error_message = sub_field_error_messages[ sub_field_types[i] ];

							key                   = 'everest_forms[form_fields][' + field_id + '][' + sub_field_type + ']';
							error_messages[ key ] = {
								required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
							};
						}
						error_message = null;
					} else if ( $( this ).is( '.evf-field-likert' ) ) {
						var row_keys = $( this ).data( 'row-keys' );
						var sub_field_error_messages = {};

						if ( row_keys && Array.isArray( row_keys ) ) {
							for ( var i = 0; i < row_keys.length; i++ ) {
								var row_key = row_keys[i];
								sub_field_error_messages[ row_key ] = $( this ).data( 'required-field-message-' + row_key );
							}
							for ( var i = 0; i < row_keys.length; i++ ) {
								error_message         = sub_field_error_messages[ row_keys[i] ];
								key                   = 'everest_forms[form_fields][' + field_id + '][' + row_keys[i] + ']';
								error_messages[ key ] = {
									required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
								};
							}
						}
						error_message = null;
					} else if ( $( this ).is( '.evf-field-file-upload' ) ) {
						key = 'everest_forms_' + form_id + '_' + field_id;
					}

					/**
					 * Check if the error message has been already set (null value in error_message variable
					 * should indicate that the message has already been set).
					 */
					if ( error_message ) {
						error_messages[ key ] = {
							required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
						};
					}
				});

				$this.validate({
					messages: error_messages,
					ignore: '',
					errorClass: 'evf-error',
					validClass: 'evf-valid',
					errorPlacement: function( error, element ) {
						if ( element.closest( '.evf-field' ).is( '.evf-field-privacy-policy' ) ) {
							element.closest( '.evf-field' ).append( error );
						} else if ( element.closest( '.evf-field' ).is( '.evf-field-range-slider' ) ) {
							if ( element.closest( '.evf-field' ).find( '.evf-field-description' ).length ) {
								element.closest( '.evf-field' ).find( '.evf-field-description' ).before( error );
							} else {
								element.closest( '.evf-field' ).append( error );
							}
						} else if ( element.closest( '.evf-field' ).is( '.evf-field-scale-rating' ) ) {
							element.closest( '.evf-field' ).find( '.everest-forms-field-scale-rating' ).after( error );
						} else if ( 'radio' === element.attr( 'type' ) || 'checkbox' === element.attr( 'type' ) ) {
							if ( element.hasClass( 'everest-forms-likert-field-option' ) ) {
								element.closest( 'tr' ).children( 'th' ).append( error );
							} else {
								element.closest( '.evf-field-checkbox' ).find( 'label.evf-error' ).remove();
								element.parent().parent().parent().append( error );
							}
						} else if ( element.is( 'select' ) && element.attr( 'class' ).match( /date-month|date-day|date-year/ ) ) {
							if ( element.parent().find( 'label.evf-error:visible' ).length === 0 ) {
								element.parent().find( 'select:last' ).after( error );
							}
						} else if ( element.is( 'select' ) && element.hasClass( 'evf-enhanced-select' ) ) {
							if ( element.parent().find( 'label.evf-error:visible' ).length === 0 ) {
								element.parent().find( '.select2' ).after( error );
							}
						} else if ( element.hasClass( 'evf-smart-phone-field' ) || element.hasClass( 'everest-forms-field-password-primary' ) || element.hasClass( 'everest-forms-field-password-secondary' ) ) {
							element.parent().after( error );
						} else {
							error.insertAfter( element );
						}
					},
					highlight: function( element, errorClass, validClass ) {
						var $element  = $( element ),
							$parent   = $element.closest( '.form-row' ),
							inputName = $element.attr( 'name' );

						if ( $element.attr( 'type' ) === 'radio' || $element.attr( 'type' ) === 'checkbox' ) {
							$parent.find( 'input[name="' + inputName + '"]' ).addClass( errorClass ).removeClass( validClass );
						} else {
							$element.addClass( errorClass ).removeClass( validClass );
						}

						$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid evf-has-error' );
					},
					unhighlight: function( element, errorClass, validClass ) {
						var $element  = $( element ),
							$parent   = $element.closest( '.form-row' ),
							inputName = $element.attr( 'name' );

						if ( $element.attr( 'type' ) === 'radio' || $element.attr( 'type' ) === 'checkbox' ) {
							$parent.find( 'input[name="' + inputName + '"]' ).addClass( validClass ).removeClass( errorClass );
						} else {
							$element.addClass( validClass ).removeClass( errorClass );
						}

						$parent.removeClass( 'evf-has-error' );
					},
					submitHandler: function( form ) {
						var $form       = $( form ),
							$submit     = $form.find( '.evf-submit' ),
							processText = $submit.data( 'process-text' );

						// Process form.
						if ( processText ) {
							$submit.text( processText ).prop( 'disabled', true );
						}

						if ( 1 !== $form.data( 'ajax_submission' ) ) {
							form.submit();
						} else {
							return;
						}
					},
					onkeyup: function( element, event ) {
						// This code is copied from JQuery Validate 'onkeyup' method with only one change: 'everest-forms-novalidate-onkeyup' class check.
						var excludedKeys = [ 16, 17, 18, 20, 35, 36, 37, 38, 39, 40, 45, 144, 225 ];

						// Disable onkeyup validation for some elements (e.g. remote calls).
						if ( $( element ).hasClass( 'everest-forms-novalidate-onkeyup' ) ) {
							return;
						}

						if ( 9 === event.which && '' === this.elementValue( element ) || -1 !== $.inArray( event.keyCode, excludedKeys ) ) {
							return;
						} else if ( element.name in this.submitted || element.name in this.invalid ) {
							this.element( element );
						}
					},
					onfocusout: function( element ) {
						// This code is copied from JQuery Validate 'onfocusout' method with only one change: 'everest-forms-novalidate-onkeyup' class check.
						var validate = false;

						// Empty value error handling for elements with onkeyup validation disabled.
						if ( $( element ).hasClass( 'everest-forms-novalidate-onkeyup' ) && ! element.value ) {
							validate = true;
						}

						if ( ! this.checkable( element ) && ( element.name in this.submitted || ! this.optional( element ) ) ) {
							validate = true;
						}

						if ( validate ) {
							this.element( element );
						}
					},
					onclick: function( element ) {
						var validate = false,
							type = ( element || {} ).type,
							$el = $( element );

						if ( 'checkbox' === type ) {
							$el.closest( '.evf-field-checkbox' ).find( 'label.evf-error' ).remove();
							validate = true;
						} else if ( ! 'select-multiple' === type ) {
							$( element ).valid();
						}

						if ( validate ) {
							this.element( element );
						}
					}
				});
			});
		},
		validate_field: function ( e ) {
			var $this             = $( this ),
				$body             = $( 'body' ),
				$parent           = $this.closest( '.form-row' ),
				validated         = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email    = $parent.is( '.validate-email' ),
				event_type        = e.type;

			if ( $body.hasClass( 'everest-forms-is-offline' ) ) {
				$parent.removeClass( 'everest-forms-invalid everest-forms-invalid-required-field everest-forms-invalid-email everest-forms-validated' );
			} else if ( $parent.hasClass( 'evf-field-address' ) || $parent.hasClass( 'evf-field-payment-single' ) || $( 'body' ).hasClass( 'everest-forms-is-offline' ) ) {
				if ( 0 === $parent.find( 'input.evf-error' ).length ) {
					$parent.removeClass( 'everest-forms-invalid everest-forms-invalid-required-field everest-forms-invalid-email' ).addClass( 'everest-forms-validated' );
				}
			} else {
				if ( 'input' === event_type ) {
					$parent.removeClass( 'everest-forms-invalid everest-forms-invalid-required-field everest-forms-invalid-email everest-forms-validated' );
				}

				if ( 'validate' === event_type || 'change' === event_type ) {
					if ( validate_required ) {
						if ( $this.hasClass( 'everest-forms-likert-field-option' ) ) {
							if ( $parent.find( 'input.evf-error' ).length > 0 ) {
								$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid everest-forms-invalid-required-field' );
								validated = false;
							}
						} else if ( 'checkbox' === $this.attr( 'type' ) && 0 === $parent.find( 'input:checked' ).length ) {
							$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid everest-forms-invalid-required-field' );
							validated = false;
						} else if ( '' === $this.val() ) {
							$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid everest-forms-invalid-required-field' );
							validated = false;
						}
					}

					if ( validate_email ) {
						if ( $this.val() ) {
							/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
							var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i); // eslint-disable-line max-len

							if ( ! pattern.test( $this.val()  ) ) {
								$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid everest-forms-invalid-email' );
								validated = false;
							}
						}
					}
					if ( validated ) {
						$parent.removeClass( 'everest-forms-invalid everest-forms-invalid-required-field everest-forms-invalid-email' ).addClass( 'everest-forms-validated' );
					}
				}
			}
		},
		submission_scroll: function(){
			if ( $( 'div.everest-forms-submission-scroll' ).length ) {
				$( 'html,body' ).animate( {
					scrollTop: ( $( 'div.everest-forms-submission-scroll' ).offset().top ) - 100
				}, 1000 );
			}
		},
		randomize_elements: function() {
			$( '.everest-forms-randomize' ).each( function() {
				var $list      = $( this ),
					$listItems = $list.children();

				while ( $listItems.length ) {
					$list.append( $listItems.splice( Math.floor( Math.random() * $listItems.length ), 1 )[0] );
				}
			} );
		},
		init_enhanced_select: function() {
			// Only continue if SelectWoo library exists.
			try {
				$( document.body ).on( 'evf-frontend-enhanced-select-init', function() {
					if ( 'undefined' !== typeof $.fn.selectWoo ) {
						$( 'select.evf-enhanced-select:visible' ).filter( ':not(.evf-enhanced)' ).each( function() {
							var select2_args = $.extend({
								minimumResultsForSearch: 10,
								placeholder: $( this ).attr( 'placeholder' ) || '',
								allowClear: $( this ).prop( 'multiple' ) ? false : true,
							}, getEnhancedSelectFormatString() );

							$( this ).selectWoo( select2_args ).addClass( 'evf-enhanced' );
						});
					}
				}).trigger( 'evf-frontend-enhanced-select-init' );
			} catch( err ) {
				// If select2 failed (conflict?) log the error but don't stop other scripts breaking.
				window.console.log( err );
			}
		}
	};

	everest_forms.init();
});
