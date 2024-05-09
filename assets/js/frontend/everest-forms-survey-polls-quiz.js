/**
 * EverestFormsSurveyFrontEnd JS
 */
jQuery( function ( $ ) {

	var EverestFormsSurveyFrontEnd = {

		init: function() {
			EverestFormsSurveyFrontEnd.bindUIActions();

			$(document).ready(function () {
				$( '.everest-forms-field-yes-no' ).each(function () {
					var $this 	   = $( this ),
						title 	   = $this.find( 'input' ).attr( 'aria-label' ),
						id 	 	   = $this.find( 'input' ).attr( 'id' ),
						title_elem = `<title id="${id+'_svg_title'}">${title}</title>`;

					$this.find( 'path' ).html( title_elem );
				});
			});

			EverestFormsSurveyFrontEnd.checkYesNofieldActiveInactive();
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function() {
			$( document ).on( 'change', '.evf-immidiate-feedback input', function(e) {
				EverestFormsSurveyFrontEnd.disableallInputs(e, this);
			});

			$( document ).on( 'change', '.evf-immidiate-feedback select', function(e) {
				EverestFormsSurveyFrontEnd.disableallSelect(e, this);
			});

			$( document ).on( 'change', '.everest-forms-field-yes-no input[type="radio"]', function( e ){
				EverestFormsSurveyFrontEnd.checkYesNofieldActiveInactive();
			} );
		},

		disableallInputs: function( e, el ){
			var $this = $( el ),
			field_id  = $this.closest('.evf-immidiate-feedback').attr('data-field-id'),
			user_answer = $this.val(),
			form_id = $this.closest('form').attr('data-formid');
			container = $this.closest('.evf-immidiate-feedback');
			if( container.attr( 'data-click-count' ) === undefined ) {
				container.attr('data-click-count', 1);
			} else {
				container.attr('data-click-count', parseInt( container.attr('data-click-count') ) + 1 );
			}
			if ( $this.attr('type') === 'checkbox' ) {
				user_answer = container.find('input:checked').map( function() {
					return $( this ).val();
				}).get();
			}
			container.find('input').prop('disabled', true );
			// Fire AJAX
			var data =  {
				action  : 'everest_forms_survey_immidiate_feedback_quiz',
				field_id  : field_id,
				user_answer : user_answer,
				form_id : form_id,
				security: everest_forms_survey_polls_quiz_script_params.ajax_nonce
			}

			$.ajax({
				url: everest_forms_survey_polls_quiz_script_params.ajax_url,
				data: data,
				type: 'POST',

				success: function( response ) {
					if ( container.attr('data-click-count') == response.data.answer_count ) {
						var clone = container.find('input:checked').clone();
						clone.attr('type','hidden');
						clone.prop( 'disabled', false );
						clone.insertAfter( container );
						$( response.data.html ).insertAfter( container );
						if ( response.data.status === 'correct'){
							$this.addClass('correct_answer');
						} else {
							$this.addClass('incorrect_answer');
						}
					} else {
						container.find('input').prop('disabled', false );
					}
				}
			});
		},

		disableallSelect: function( e, el ){
			var $this = $( el ),
			field_id  = $this.closest('.evf-immidiate-feedback').attr('data-field-id'),
			user_answer = $this.val(),
			form_id = $this.closest('form').attr('data-formid');
			container = $this.closest('.evf-immidiate-feedback');

			container.find('select').prop('disabled', true );

						// Fire AJAX
						var data =  {
							action  : 'everest_forms_survey_immidiate_feedback_quiz',
							field_id  : field_id,
							user_answer : user_answer,
							form_id : form_id,
							security: everest_forms_survey_polls_quiz_script_params.ajax_nonce
						}

						$.ajax({
							url: everest_forms_survey_polls_quiz_script_params.ajax_url,
							data: data,
							type: 'POST',

							success: function( response ) {
								$( response.data.html ).insertAfter( container );
								var select = container.find('select').clone();
								var selectName = select.attr('name');
								var selectValue = $this.val();
								var selectInput = '<input type="hidden" name="'+ selectName+'" value="'+ selectValue +'">';
								$( selectInput ).insertAfter( container );
								if ( response.data.status === 'correct'){
									$this.addClass('correct_answer');
								} else {
									$this.addClass('incorrect_answer');
								}
							}
						});
		},
		checkYesNofieldActiveInactive: function() {
			$( '.everest-forms-field-yes-no-container .everest-forms-field-yes-no' ).each(function () {
				$this = $( this );

				if( $this.find( 'input[type="radio"]' ).is( ':checked' ) ) {
					$this.addClass( 'active' );
				} else {
					$this.removeClass( 'active' );
				}
			});
		}
	}
	EverestFormsSurveyFrontEnd.init(jQuery);
});
