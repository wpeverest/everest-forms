/* global everest_forms_params */
jQuery( function ( $ ) {
	'use strict';

	// everest_forms_params is required to continue, ensure the object exists.
	if ( typeof everest_forms_params === 'undefined' ) {
		return false;
	}

	var everest_forms = {
		$everest_form: $( 'form.everest-form' ),
		init: function() {
			this.init_inputMask();
			this.init_datepicker();
			this.load_validation();
			this.submission_scroll();

			// Inline validation
			this.$everest_form.on( 'input validate change', '.input-text, select, input:checkbox, input:radio', this.validate_field );
		},
		init_inputMask: function() {
			if ( typeof $.fn.inputmask !== 'undefined' ) {
				$( '.evf-masked-input' ).inputmask();
			}
		},
		init_datepicker: function () {
			var evfDateField = $( '.evf-field-date-time' );

			if ( evfDateField.length > 0 ) {
				$( '.flatpickr-field' ).each( function() {
					var timeInterval = 5,
						inputData  	 = $( this ).data();

					switch( inputData.dateTime ) {
						case 'date':
							// Apply flatpicker to field.
							$( this ).flatpickr({
								disableMobile : true,
								mode          : inputData.mode,
								dateFormat    : inputData.dateFormat
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
								minuteIncrement : timeInterval,
								dateFormat      : inputData.dateFormat,
								time_24hr		: inputData.dateFormat.includes( 'H:i' )
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

			// prepend http:// if user missed that.
			$( '.evf-field-url input[type=url]' ).change( function () {
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

			this.$everest_form.each( function() {
				var $this = $( this );

				$this.validate({
					ignore: '',
					errorClass: 'evf-error',
					validClass: 'evf-valid',
					errorPlacement: function( error, element ) {
						if ( 'radio' === element.attr( 'type' ) || 'checkbox' === element.attr( 'type' ) ) {
							if( element.hasClass( 'everest-forms-likert-field-option' ) ) {
								element.closest('tr').children('th').append( error );
							} else {
								element.parent().parent().parent().append( error );
							}
						} else if ( element.is( 'select' ) && element.attr( 'class' ).match( /date-month|date-day|date-year/ ) ) {
							if ( element.parent().find( 'label.evf-error:visible' ).length === 0 ) {
								element.parent().find( 'select:last' ).after( error );
							}
						} else {
							error.insertAfter( element );
						}
					},
					highlight: function( element, errorClass, validClass ) {
						var $element  = $( element ),
							$parent   = $element.closest( '.form-row' ),
							inputName = $element.attr( 'name' );

						if ( $element.attr( 'type' ) === 'radio' || $element.attr( 'type' ) === 'checkbox' ) {
							$parent.find( 'input[name=\''+inputName+'\']' ).addClass( errorClass ).removeClass( validClass );
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
							$parent.find( 'input[name=\''+inputName+'\']' ).addClass( validClass ).removeClass( errorClass );
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

						form.submit();
					}
				});
			});
		},
		validate_field: function ( e ) {
			var $this             = $( this ),
				$parent           = $this.closest( '.form-row' ),
				validated         = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email    = $parent.is( '.validate-email' ),
				event_type        = e.type;

			if ( $parent.hasClass( 'evf-field-address' ) ) {
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
							if ( $parent.find('input.evf-error').length > 0 ) {
								$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid everest-forms-invalid-required-field' );
								validated = false;
							}
						} else if ( 'checkbox' === $this.attr( 'type' ) && 0 === $parent.find('input:checked').length ) {
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
							var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

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
		}
	};

	everest_forms.init();
});
