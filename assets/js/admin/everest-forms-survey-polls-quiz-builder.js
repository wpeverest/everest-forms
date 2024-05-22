/**
 * EverestFormsSurvey JS
 */
( function ( $, params ) {
	var everest;
	var field_ids;
	var EverestFormsSurvey = {
		settings: {
			fieldReportData : {},
			charts: {},
			spinner: '<div class="evf-survey-loader"><i class="evf-loading evf-loading-active" /></div>'
		},

		init: function() {
			everest = this.settings;
			EverestFormsSurvey.bindUIActions();
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function() {

			var $single_page_container = $( document ).find('.everest-forms-survey-block-container[data-page-type="single-page"]' );
			var form_id = $( $single_page_container ).attr('data-form-id');
			if( $single_page_container.length > 0 ) {
				field_ids = JSON.parse( $( $single_page_container ).attr('data-field-ids') );
				EverestFormsSurvey.showSurveyQuestionReport( $single_page_container, field_ids[0], false, form_id, 0 );
			}

			$('.everest-forms-field-option-row-input_type select').trigger('change');

			$( document ).on( 'ready', function(e) {
				$('.everest-forms-survey-selection select#everest-forms-survey-question-list').trigger('change');
			});

			$( document.body ).on( 'init_field_options_toggle', function(e) {
				EverestFormsSurvey.choicesInit();
			});

			$( document ).on( 'click', '.evf-survey-choices a.add', function(e) {
				EverestFormsSurvey.fieldChoiceAdd(e, this);
			});

			$( document ).on( 'click', '.evf-survey-choices a.remove', function(e) {
				EverestFormsSurvey.fieldChoiceDelete(e, this);
			});
			$( document ).on( 'click', '.add_feedback', function(e) {
				EverestFormsSurvey.feedbackAdd(e, this);
			});
			$( document ).on( 'click', '.remove_feedback', function(e) {
				EverestFormsSurvey.feedbackRemove(e, this);
			});

			// Print all entried summary
			$( document ).on( 'click', '#everest-forms-print-entried-summary', function() {
				var entriedSummary = $( this ).closest('.everest-forms-survey-block-container');
				$( entriedSummary ).printThis ({
					debug: false,
					importCSS: true,
					importStyle: false,
					printContainer: true,
					pageTitle: "",
					removeInline: false,
					printDelay: 333,
					header: null,
					footer: null,
					base: false ,
					formValues: true,
					canvas: true,
					doctypeString: "...",
					removeScripts: false,
					copyTagClasses: false
				});
			});

			// Print the selected summary only
			$( document ).on( 'click', '#everest-forms-entried-summary-specific-field', function() {
				var entriedSummary = $( this ).closest('.everest-forms-survey-block');
				$( entriedSummary ).printThis ({
					debug: false,
					importCSS: true,
					importStyle: false,
					printContainer: true,
					pageTitle: "",
					removeInline: false,
					printDelay: 333,
					header: null,
					footer: null,
					base: false ,
					formValues: true,
					canvas: true,
					removeScripts: false,
					copyTagClasses: false
				});
			});

			$( document ).on( 'input', '.everest-forms-field-option-likert .evf-survey-choices input', function() {
				var $list   = $( this ).closest( '.evf-survey-choices' ),
					fieldID = $list.data( 'field-id' ),
					ul      = $( this ).closest( 'ul' );

				EverestFormsSurvey.fieldChoiceUpdate( fieldID );

				/**
				 * Update the error-message input field's label
				 */
				if ( 'likert_rows' === ul.data('choice-type') ) {
					EverestFormsSurvey.requiredFieldMessageInputFieldUpdate( ul, $( this ).closest( 'li' ), this );
				}
			});

			$( document ).on( 'change', '.everest-forms-field-option-row-input_type select', function() {
				var fieldID = $( this ).parent().data('field-id');
				EverestFormsSurvey.fieldChoiceUpdate( fieldID );
			});

			$( document ).on('change', '.evf-survey-choices input.default', function (e) {
				var fieldID = $( this ).closest('.evf-survey-choices').data('field-id');
				$(this).parent('li').siblings().children('.default').prop('checked',false);
				EverestFormsSurvey.fieldChoiceUpdate( fieldID );
			});

			$( document ).on('keyup', '.everest-forms-field-option-row-highest_rating_text input', function (e) {
				EverestFormsSurvey.scaleRatingText( e, this, 'highest' );
			});

			$( document ).on('keyup', '.everest-forms-field-option-row-lowest_rating_text input', function (e) {
				EverestFormsSurvey.scaleRatingText( e, this, 'lowest' );
			});

			// Rating point validation error tips.
			$( document.body )

				.on( 'blur', '.evf-input-highest-rating-point[type=number], .evf-input-lowest-rating-point[type=number], .score_to[type=number]', function() {
					$( '.evf_error_tip' ).fadeOut( '100', function() { $( this ).remove(); } );
				})

				.on( 'change click', '.evf-input-highest-rating-point[type=number]', function(e) {
					var highest_rating_point = parseInt( $( this ).val(), 10 ),
						lowest_rating_point  = parseInt( $( this ).parent().next().find( '.evf-input-lowest-rating-point[name*=lowest_rating_point]' ).val(), 10 );

					if ( highest_rating_point > 100 ) {
						$( this ).val('100');
						EverestFormsSurvey.scaleRatingPoint( e, $(this), 'highest');
					} else if ( highest_rating_point <= lowest_rating_point ) {
						$( this ).val( lowest_rating_point + 1 );
						EverestFormsSurvey.scaleRatingPoint( e, $(this), 'highest');
					}
				})

				.on( 'keyup click', '.evf-input-highest-rating-point[type=number]', function() {
					var highest_rating_point = parseInt( $( this ).val(), 10 ),
						lowest_rating_point  = parseInt( $( this ).parent().next().find( '.evf-input-lowest-rating-point[name*=lowest_rating_point]' ).val(), 10 );

					if ( highest_rating_point > 100 ) {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $(this), 'i18n_field_highest_rating_greater_than_max_value_error', params ] );
					} else if ( highest_rating_point <= lowest_rating_point ) {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $(this), 'i18n_field_greater_than_lowest_point_error', params ] );
					} else {
						if ( highest_rating_point <= lowest_rating_point ) {
							$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $(this), 'i18n_field_greater_than_lowest_point_error' ] );
						} else {
							$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $(this), 'i18n_field_highest_rating_greater_than_max_value_error' ] );
						}
					}
				})

				.on( 'change click', '.evf-input-lowest-rating-point[type=number]', function(e) {
					var lowest_rating_point  = parseInt( $( this ).val(), 10 ),
						highest_rating_point = parseInt( $( this ).parent().prev().find( '.evf-input-highest-rating-point[name*=highest_rating_point]' ).val(), 10 );

					if ( lowest_rating_point < 0 ) {
						$( this ).val('0');
						EverestFormsSurvey.scaleRatingPoint( e, $(this), 'lowest');
					}
					if ( lowest_rating_point >= highest_rating_point ) {
						$( this ).val( highest_rating_point - 1 );
						EverestFormsSurvey.scaleRatingPoint( e, $(this), 'lowest');
					}
				})

				.on( 'keyup click', '.evf-input-lowest-rating-point[type=number]', function() {
					var lowest_rating_point  = parseInt( $( this ).val(), 10 ),
						highest_rating_point = parseInt( $( this ).parent().prev().find( '.evf-input-highest-rating-point[name*=highest_rating_point]' ).val(), 10 );

					if ( lowest_rating_point < 0 ) {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $(this), 'i18n_field_lowest_rating_lower_than_min_value_error', params ] );
					}
					if ( lowest_rating_point >= highest_rating_point ) {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $(this), 'i18n_field_less_than_highest_point_error', params ] );
					} else {
						$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $(this), 'i18n_field_less_than_highest_point_error' ] );
					}
				});

			$( document ).on('keyup input', '.everest-forms-field-option-row-highest_rating_point input', function (e) {
				EverestFormsSurvey.scaleRatingPoint( e, this, 'highest');
			});

			$( document ).on('keyup input', '.everest-forms-field-option-row-lowest_rating_point input', function (e) {
				EverestFormsSurvey.scaleRatingPoint( e, this, 'lowest' );
			});

			$( document ).on('change', '#everest-forms-panel-field-settings-enable_quiz', function (e) {
				EverestFormsSurvey.showQuizFieldOption( e, this );
			});

			$( document ).on('change', '#everest-forms-panel-field-settings-enable_survey', function (e) {
				EverestFormsSurvey.showSurveyFieldOption( e, this );
			});
			$( document ).on('change', '.evf_quiz_enable_container input', function (e) {
				EverestFormsSurvey.showQuizOptionField( e, this );
			});
			$( document ).on('change', '.evf_survey_enable_container input', function (e) {
				EverestFormsSurvey.EnableSurveyOptionField( e, this );
			});
			$( document ).on( 'change', '#everest-forms-survey-question-list', function(e) {
				var field_id = $(this).val();
				EverestFormsSurvey.showSurveyQuestionReport( $(this).closest('.everest-forms-survey-header'), field_id );
			});

			$( document ).on( 'change', '#survey-filter-by-form', function(e) {
				EverestFormsSurvey.showSurveyQuestionReportByForm( e, this );
			});

			$( document ).on( 'change', '.evf-correct-answers input[type=radio]', function(e) {
				EverestFormsSurvey.clickOneRadio( e, this );
			});

			$( document ).on( 'input', '.evf-choices-list input[type="text"]', function(e) {
				EverestFormsSurvey.livePreviewOnCorrectAnswer(e, this);
			});

			$( document ).on( 'click', '.evf-choices-list .remove', function(e) {
				EverestFormsSurvey.removeRowCorrectAnswer(e, this);
			});

			$( document ).on( 'click', '.evf-choices-list .add', function(e) {
				EverestFormsSurvey.addNewRowCorrectAnswer(e, this);
			});

			$( document ).on('change', '#everest-forms-panel-field-settings-over_all_feedback', function (e) {
				EverestFormsSurvey.showQuizOverAllOption( e, this );
			});
			$( document ).on('change', '#everest-forms-panel-field-settings-quiz_reporting', function (e) {
				EverestFormsSurvey.showQuizReportingOption( e, this );
			});

			$( document ).on( 'click', '.survey-block-statistic-overview button', function(e) {
				var field_id = $(this).data('field-id'),
					type = $( this ).data('type');
					$( this ).siblings().removeClass('current-tab-active');
					$( this ).addClass('current-tab-active');
					everest.charts[field_id].destroy();
					var everestFormsSurveyCanvas = $( this ).parent().siblings('.survey-block-chart-overview').find('.everest-forms-chart-canvas');
						var canvas = everestFormsSurveyCanvas.get(0).getContext('2d');
						EverestFormsSurvey.generateChart( field_id, type, canvas );
			});

			$( document ).on( 'keyup input', '.score_to', function(e) {
				EverestFormsSurvey.changeScoreFrom( e, this );
			});

			$( document ).on( 'change click', '.score_to', function(e) {
				var $this = $( this ),
				to_score = parseInt( $this.val() ),
				from_score = parseInt( $this.closest('.evf-form-group').find('.score_from').val() );
				if( from_score >= to_score && to_score != '' ){
					$this.val( from_score + 1 );
				}
				if( to_score > 100 ){
					$this.val(100);
				}
				$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $this, 'i18n_field_to_score_is_empty_error' ] );
			});

			// Live effect for Yes No  field style option.
			$( document ).on( 'change', '.everest-forms-field-option-row-style select', function() {
				var $this            = $( this ),
					value            = $this.val(),
					id               = $this.parent().data( 'field-id' ),
					yes_color   = $( '#everest-forms-field-option-row-'+ id +'-yes_icon_color button[class="button wp-color-result"]' ).css( 'backgroundColor' ),
					no_color    = $( '#everest-forms-field-option-row-'+ id +'-no_icon_color button[class="button wp-color-result"]' ).css( 'backgroundColor' ),
					yes_label   = $( '#everest-forms-field-option-row-'+ id +'-yes_label input[type="text"]' ).val(),
					no_label    = $( '#everest-forms-field-option-row-'+ id +'-no_label input[type="text"]' ).val(),
					html_div    = $( '#everest-forms-field-' + id ).find( '.yes-no-preview' ),
					html        = '';

					EverestFormsSurvey.checkSelectedYesNoVariation( value );

					if ( 'with_icon' === value ) {
						html_div.removeClass( 'icon-text' ).removeClass( 'text-only' );

						html = '<span class="yes yes-no-icon"><svg width="32" height="32" viewBox="0 0 24 24" style="fill:' + yes_color + '"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z"></path></svg></span>';
						html += '<span class="no yes-no-icon"><svg width="32" height="32" viewBox="0 0 24 24" style="fill:' + no_color + '"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v1.91l.01.01L1 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg></span>';
					} else if ( 'with_text' === value ) {
						html_div.addClass( ' text-only' ).removeClass( 'icon-text' );

						html = '<input type="text" value="'+ yes_label +'" disabled /><input type="text" value="'+ no_label +'" disabled />';
					} else if ( 'with_icon_text' === value ) {
						html_div.addClass( ' icon-text' ).removeClass( 'text-only' );

						html = '<span class="yes yes-no-icon"><svg width="32" height="32" viewBox="0 0 24 24" style="fill:' + yes_color + '"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z"></path></svg><label>'+ yes_label +'</label></span>';
						html += '<span class="no yes-no-icon"><svg width="32" height="32" viewBox="0 0 24 24" style="fill:' + no_color + '"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v1.91l.01.01L1 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg><label>'+ no_label +'</label></span>';
					}

					html_div.html( html );
			});

			$( document ).on( 'click', '.everest-forms-field.everest-forms-field-yes-no', function() {
				var fieldId = $( this ).data( 'field-id' ),
					value   = $('#everest-forms-field-option-'+ fieldId +' option:selected').val();

				EverestFormsSurvey.checkSelectedYesNoVariation( value );
			} );

			$( document ).on( 'input', '.everest-forms-field-option-row-yes_label input[type="text"]', function() {
				var fieldId = $( this ).parent().data( 'field-id' ),
					value   = $( this ).val();
				$('#everest-forms-field-'+ fieldId +' .yes-no-preview :input:first' ).val( value );
				$('#everest-forms-field-'+ fieldId +' .yes-no-preview .yes.yes-no-icon label' ).text( value );
			} );

			$( document ).on( 'input', '.everest-forms-field-option-row-no_label input[type="text"]', function() {
				var fieldId = $( this ).parent().data( 'field-id' ),
					value   = $( this ).val();
				$('#everest-forms-field-'+ fieldId +' .yes-no-preview :input:last' ).val( value );
				$('#everest-forms-field-'+ fieldId +' .yes-no-preview .no.yes-no-icon label' ).text( value );
			} );

			// Yes No Colorpicker.
			$( document ).on( 'click', '.everest-forms-field-yes-no', function() {
				$( '.everest-forms-field-option-row-yes_icon_color input.evf-colorpicker' ).wpColorPicker({
					change: function( event ) {
						var $this     = $( this ),
							value     = $this.val(),
							id        = $this.closest( '.everest-forms-field-option-row' ).data( 'field-id' ),
							$icons    = $( '#everest-forms-field-'+id +' .yes svg' );
						$icons.css( 'fill', value );
					}
				});
			});

			$( document ).on( 'click', '.everest-forms-field-yes-no', function() {
				$( '.everest-forms-field-option-row-no_icon_color input.evf-colorpicker' ).wpColorPicker({
					change: function( event ) {
						var $this     = $( this ),
							value     = $this.val(),
							id        = $this.closest( '.everest-forms-field-option-row' ).data( 'field-id' ),
							$icons    = $( '#everest-forms-field-'+id +' .no svg' );
						$icons.css( 'fill', value );
					}
				});
			});

		},

		changeScoreFrom: function( e, el ){
			var $this = $( el );
			to_score = parseInt( $this.val() ),
			value = '';
			if ( to_score < 100 ){
			 value = to_score + 1;
			 $this.closest('.evf-form-group').next().find('.score_from').val( value );
			}
			from_score = parseInt( $this.closest('.evf-form-group').find('.score_from').val() );
			if( from_score >= to_score ){
				$( document.body ).triggerHandler( 'evf_add_error_tip', [ $this, 'i18n_field_from_score_greater_than_to_score', params ] );
			} else {
				$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $this, 'i18n_field_from_score_greater_than_to_score' ] );
			}
		},

		clickOneRadio: function( e, el ){
			var $this = $( el );
			$this.closest('.evf-correct-answers').find('input[type="radio"]').prop('checked', false);
			$this.prop('checked', true );
		},

		addNewRowCorrectAnswer: function( e, el ){
			var $this = $( el ),
			dataKey = $this.closest('li').attr('data-key'),
			nextDataKey = $this.closest('li').next('li').attr('data-key'),
			label = $this.closest('li').next('li').children('.label').val(),
			field_id = $this.closest('.evf-choices-list').attr('data-field-id');
			current = $this.closest('.everest-forms-field-option-group-basic').siblings('.everest-forms-field-option-group-quiz').find('.everest-forms-field-option-row-correct_answer .evf-correct-answers').find("li[data-key='" + dataKey + "']").clone();
			current.attr('data-key',nextDataKey);
			current.find('input').attr('name','form_fields['+field_id+'][correct_answer]['+nextDataKey+']');
			current.find('span').text(label);
			$( current ).insertAfter( $('.everest-forms-field-option-row-correct_answer .evf-correct-answers').find("li[data-key='" + dataKey + "']") );
		},

		removeRowCorrectAnswer: function( e, el ){
			var $this = $( el ),
			dataKey = $this.closest('li').attr('data-key');
			if( $this.closest( '.evf-choices-list' ).find( 'li' ).length > 1 ){
				$this.closest('.everest-forms-field-option-group-basic').siblings('.everest-forms-field-option-group-quiz').find('.everest-forms-field-option-row-correct_answer .evf-correct-answers').find("li[data-key='" + dataKey + "']").remove();
			}
		},

		livePreviewOnCorrectAnswer: function( e, el ){
			var $this = $( el ),
				dataKey = $this.closest('li').attr('data-key');
			if ( $('.everest-forms-field-option-row-correct_answer .evf-correct-answers').length > 0 ) {
				$this.closest('.everest-forms-field-option-group-basic').siblings('.everest-forms-field-option-group-quiz').find('.everest-forms-field-option-row-correct_answer .evf-correct-answers').find("li[data-key='" + dataKey + "']").find('span').text($this.val());
			}
		},

		showSurveyQuestionReportByForm: function ( e, el ){
		var $this = $( el ),
			form_id = $this.val();
			$this.siblings('i').remove();
			$this.siblings( '.everest-forms-survey-block').remove();
			$( everest.spinner ).insertAfter( $this );
			if( form_id ){
				var $url = params.admin_url+'admin.php?page=evf-entries&view=all-report&form-id='+form_id;
				window.location.href = $url;
				$this.siblings('.evf-survey-loader').remove();
			}
		},

		showSurveyQuestionReport: function ( container, field_id, single, form_id, currentIndex ){
			var $this = $( container );
			single = typeof single !== 'undefined' ? single : true;
			form_id = typeof form_id !== 'undefined' ? form_id : false;
			if ( ! form_id ) {
				var	form_id = $('#filter-by-form').val();
			}
			if( single ) {
				$this.siblings('.everest-forms-survey-block').remove();
			}
			// Fire AJAX
			var data =  {
				action  : 'everest_forms_survey_generate_report',
				field_id  : field_id,
				form_id : form_id,
				security: params.ajax_nonce
			}
			if( single ) {
				$this.siblings('.evf-survey-loader').remove();
				$( everest.spinner ).insertAfter( $this );
			}else{
				$this.append( everest.spinner );
			}
			$.ajax({
				url: params.ajax_url,
				data: data,
				type: 'POST',

				success: function( response ) {
					if( single ) {
						$this.siblings('.evf-survey-loader').remove();
						var newAppendedElements = $( response.data.html ).insertAfter( $this );
					}else{
						$this.find('.evf-survey-loader').remove();
						var newAppendedElements = $( response.data.html ).appendTo( $this );
					}

					if( $(newAppendedElements).find( '#survery-circle-'+field_id ).length ) {
						var progressData = $( '#survery-circle-'+field_id ).data('value');
						var bar = new ProgressBar.Circle( '#survery-circle-'+field_id, {
							color: '#333840',
							// This has to be the same size as the maximum width to
							// prevent clipping
							strokeWidth: 4,
							trailWidth: 4,
							easing: 'easeInOut',
							duration: 1400,
							text: {
							  autoStyleContainer: false
							},
							from: { color: '#8e42eb', width: 4 },
							to: { color: '#8e42eb', width: 4 },
							// Set default step function for all animate calls
							step: function(state, circle) {
							  circle.path.setAttribute('stroke', state.color);
							  circle.path.setAttribute('stroke-width', state.width);

							  var value = Math.round(circle.value() * 100);
							  if (value === 0) {
								circle.setText('');
							  } else {
								circle.setText(value);
							  }

							}
						  });
						  bar.text.style.fontFamily = '"Raleway", Helvetica, sans-serif';
						  bar.text.style.fontSize = '2rem';

						  bar.animate(progressData/100);  // Number from 0.0 to 1.0
					}
					everest.fieldReportData[field_id] = response.data.report;
					var everestFormsSurveyCanvas = $( newAppendedElements ).find('.survey-block-chart-overview .everest-forms-chart-canvas');
					if( everestFormsSurveyCanvas.length !== 0 ){
						var canvas = everestFormsSurveyCanvas.get(0).getContext('2d');
						EverestFormsSurvey.generateChart( field_id, 'bar', canvas );
					}
					if( field_ids && field_ids.length > currentIndex + 1 ) {
						currentIndex++;
						EverestFormsSurvey.showSurveyQuestionReport( container, field_ids[currentIndex], single, form_id, currentIndex );
					}
				}
			});
		},

		generateChart: function ( fieldID, chartType, canvas ){
			var config = false;
			reportData = everest.fieldReportData[fieldID];
			if( null != reportData || undefined != typeof reportData ) {
				// For Bar
				if ( 'bar' === chartType ) {
					config = {
						type: 'bar',
						data: {
							labels: Object.keys( reportData ),
							datasets: [{
								label: 'Data',
								hoverBackgroundColor: randomColor({ luminosity: 'light', count: Object.values( reportData ).length}),
								backgroundColor: randomColor({ luminosity: 'light', count: Object.values( reportData ).length}),
								data: Object.values( reportData )
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							legend: {
								display: false
							},
							tooltips: {
								callbacks: {
									label: function( item ) {
										return item.yLabel + '%';
									}
								}
							},
							scales: {
								xAxes: [{
									gridLines: {
										display: false
									},
									ticks: {
										fontSize: 14,
										callback: function( value ) {
											if ( typeof value === 'string' && value.length > 20 ) {
												return value.substring( 0,20 ) + '...';
											}
											return value;
										}
									}
								}],
								yAxes: [{
									ticks: {
										min: 0,
										max: 100,
										stepSize: 20,
										fontColor: '#999999',
										fontSize: 11,
										callback: function( value ) {
											return value + '%';
										}
									}
								}]
							}
						}
					};
				} else if ( 'pie' === chartType ) {
					config = {
						type: 'pie',
						data: {
							labels: Object.keys( reportData ),
							datasets: [{
								hoverBackgroundColor: '#2b8fd2',
								data: Object.values( reportData ),
								backgroundColor: randomColor({ luminosity: 'light', count: Object.values( reportData ).length})
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							legend: {
								position: 'right',
								labels : {
									fontSize: 14,
									padding: 15,
									generateLabels: function(chart) {
										var data    = chart.data;
										if (data.labels.length && data.datasets.length) {
											return data.labels.map(function(label, i) {
												var meta = chart.getDatasetMeta(0);
												var ds = data.datasets[0];
												var arc = meta.data[i];
												var custom = arc && arc.custom || {};
												var valueAtIndexOrDefault = Chart.helpers.valueAtIndexOrDefault;
												var arcOpts = chart.options.elements.arc;
												var fill = custom.backgroundColor ? custom.backgroundColor : valueAtIndexOrDefault(ds.backgroundColor, i, arcOpts.backgroundColor);
												var stroke = custom.borderColor ? custom.borderColor : valueAtIndexOrDefault(ds.borderColor, i, arcOpts.borderColor);
												var bw = custom.borderWidth ? custom.borderWidth : valueAtIndexOrDefault(ds.borderWidth, i, arcOpts.borderWidth);
												if ( label.length > 20 ) {
													label = label.substring( 0,20 ) + '...';
												}
												return {
													text: label,
													fillStyle: fill,
													strokeStyle: stroke,
													lineWidth: bw,
													hidden: isNaN(ds.data[i]) || meta.data[i].hidden,
													index: i
												};
											});
										}
										return [];
									}
								}
							},
							tooltips: {
								callbacks: {
									label: function( item, data ) {
										return data['labels'][item['index']] + ' - ' + data['datasets'][0]['data'][item['index']] + '%';
									}
								}
							}
						}
					};
				} else if ( 'line' === chartType ) {
					config = {
						type: 'line',
						data: {
							labels: Object.keys( reportData ),
							datasets: [{
								fill: false,
								data: Object.values( reportData )
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							legend: {
								display: false
							},
							tooltips: {
								callbacks: {
									label: function( item, data ) {
										return data['datasets'][0]['data'][item['index']] + '%';
									}
								}
							},
							scales: {
								xAxes: [{
									gridLines: {
										display: false
									},
									ticks: {
										fontSize: 14,
										callback: function( value ) {
											if ( typeof value === 'string' && value.length > 20 ) {
												return value.substring( 0,20 ) + '...';
											}
											return value;
										}
									}
								}],
								yAxes: [{
									ticks: {
										min: 0,
										max: 100,
										stepSize: 20,
										fontColor: '#999999',
										fontSize: 11,
										callback: function( value ) {
											return value + '%';
										}
									}
								}]
							}
						}
					}
				}
			}
			if ( config ) {
				everest.charts[fieldID] = new Chart(canvas, config );
			}

		},

		/**
		 * Show the Quiz Field option if quiz is enabled inside the field.
		 */
		showQuizOptionField: function ( e, el){
			e.preventDefault();
			var $this = $( el );

			if( $this.closest('.quiz-option').siblings('.survey-option').children('.evf_survey_enable_container' ).children('input').prop('checked') === true && $this.prop('checked') === true ){
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-hidden');
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').addClass('everest-forms-show');
				$.alert({
					title: false,
					content: "You do have survey enabled for this field. Do you want to change it on Quiz?",
					icon: 'dashicons dashicons-info',
					type: 'blue',
					closeIcon: false,
					backgroundDismiss: false,
					buttons: {
						"Yes": function() {
							$this.closest('.quiz-option').siblings('.survey-option').children('.evf_survey_enable_container' ).children('input').prop('checked', false);
							$this.prop('checked',true);
						},
    					"No": function() {
							$this.prop('checked', false);
							if( $this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').hasClass('everest-forms-show') ) {
								$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
							}
						 }

					}
				});
			}else if( $this.prop('checked') === true ) {
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-hidden');
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').addClass('everest-forms-show');
			}else{
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-show');
				$this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').addClass('everest-forms-hidden');
			}

			// $this.closest('.quiz-option').children('.everst-forms-field-quiz-settings').toggleClass('everest-forms-hidden everest-forms-show');

		},

		/**
		 * Show the Survey Field option if survey is enabled inside the field.
		 */
		EnableSurveyOptionField: function ( e, el){
			e.preventDefault();
			var $this = $( el );

			if( $this.closest('.survey-option').siblings('.quiz-option').children('.evf_quiz_enable_container' ).children('input').prop('checked') === true ){
				$.alert({
					title: false,
					content: "You do have Quiz enabled for this field. Do you want to change it on Survey?",
					icon: 'dashicons dashicons-info',
					type: 'blue',
					closeIcon: false,
					backgroundDismiss: false,
					buttons: {
						"Yes": function() {
							$this.closest('.survey-option').siblings('.quiz-option').children('.evf_quiz_enable_container' ).children('input').prop('checked', false);
							if ( $this.closest('.survey-option').siblings('.quiz-option').children('.everst-forms-field-quiz-settings').hasClass('everest-forms-show') ) {
								$this.closest('.survey-option').siblings('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
							}
							$this.prop('checked',true);
						},
						"No": function() {
							$this.prop('checked', false);
						 }
					}
				});
			}

		},

		/**
		 * Show the Quiz Field option if quiz is enabled
		 */
		showQuizFieldOption: function ( e, el){
			e.preventDefault();
			var $this = $( el );

			if( $this.prop('checked') === true ) {
				$('.quiz-option .evf_quiz_enable_container input').prop( 'checked', true );
				if ( $('#everest-forms-panel-field-settings-enable_survey').prop('checked') === true ) {
					$.alert({
						title: false,
						content: "You do have survey forms. Do you want to change it on Quiz Form?",
						icon: 'dashicons dashicons-info',
						closeIcon: false,
						backgroundDismiss: false,
						type: 'blue',
						buttons: {
							"Yes": function() {
								$('.quiz-option').children('.everst-forms-field-quiz-settings').removeClass('everest-forms-hidden').addClass('everest-forms-show');
								$('#everest-forms-panel-field-settings-enable_survey').prop('checked', false);
								$('.survey-option .evf_survey_enable_container input').prop('checked', false);
								$this.prop('checked',true);
								if( $this.closest('.evf-quiz-section').children('.evf-quiz-settings').hasClass('everest-forms-hidden') ) {
									$this.closest('.evf-quiz-section').children('.evf-quiz-settings').removeClass('everest-forms-hidden').addClass('everest-forms-show');
								}
							},
							"No": function() {
								$this.prop('checked', false);
								$('.quiz-option .evf_quiz_enable_container input').prop( 'checked', false );
								if( $this.closest('.evf-quiz-section').children('.evf-quiz-settings').hasClass('everest-forms-show') ) {
									$this.closest('.evf-quiz-section').children('.evf-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
								}
							}
						}
					});
				} else {
					$this.closest('.evf-quiz-section').children('.evf-quiz-settings').toggleClass('everest-forms-hidden everest-forms-show');
				}
			} else {
				$('.quiz-option .evf_quiz_enable_container input').prop( 'checked', false );
				if( $this.closest('.evf-quiz-section').children('.evf-quiz-settings').hasClass('everest-forms-show') ) {
					$this.closest('.evf-quiz-section').children('.evf-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
				}
			}
		},

		/**
		 * Show the overall feedback option if overall feedback is enabled.
		 */
		showQuizOverAllOption: function ( e, el){
			var $this = $( el );
			if( true == $this.prop('checked') ) {
				$this.closest('.everest-forms-overall-feedback').addClass('feedback-enabled');
				$this.closest('.everest-forms-overall-feedback').children('.overall-feedback-options-list').removeClass('everest-forms-hidden').addClass('everest-forms-show');
			} else {
				$this.closest('.everest-forms-overall-feedback').removeClass('feedback-enabled');
				// $this.closest('.everest-forms-overall-feedback').children('.overall-feedback-options-list').find('input, textarea').val('');
				$this.closest('.everest-forms-overall-feedback').children('.overall-feedback-options-list').removeClass('everest-forms-show').addClass('everest-forms-hidden');
			}
		},
		/**
		 * Show the quiz reporting options.
		 */
		showQuizReportingOption: function ( e, el){
			var $this = $( el );
			if( true == $this.prop('checked') ) {
				$this.closest('.evf-quiz-reporting').children('.evf-quiz-reporting-options').removeClass('everest-forms-hidden').addClass('everest-forms-show');
			} else {
				// $this.closest('.everest-forms-overall-feedback').children('.overall-feedback-options-list').find('input, textarea').val('');
				$this.closest('.evf-quiz-reporting').children('.evf-quiz-reporting-options').removeClass('everest-forms-show').addClass('everest-forms-hidden');
			}
		},

		/**
		 * Show the Quiz Field option if survey is enabled
		 */
		showSurveyFieldOption: function ( e, el){
			e.preventDefault();
			var $this = $( el );
			if ( $this.prop('checked') === true ) {
				$('.survey-option .evf_survey_enable_container input').prop( 'checked', true );
				if( $('#everest-forms-panel-field-settings-enable_quiz:checked').prop('checked') === true ) {
					$.alert({
						title: false,
						content: "You do have Quiz form. Do you want to change it on Survey Form?",
						icon: 'dashicons dashicons-info',
						type: 'blue',
						closeIcon: false,
						backgroundDismiss: false,
						buttons: {
							"Yes": function() {
								$('#everest-forms-panel-field-settings-enable_quiz').prop('checked', false);
								$('.quiz-option .evf_quiz_enable_container input').prop('checked', false);
								$this.prop('checked',true);
								if ( $('.evf-quiz-settings').hasClass('everest-forms-show') ) {
									$('.evf-quiz-settings input[type="checkbox"]').prop('checked', false);
									$('.evf-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
								}
								if ( $('.everst-forms-field-quiz-settings').hasClass('everest-forms-show') ) {
									$('.evf-quiz-settings input[type="text"]').val('');
									$('.evf-quiz-settings input[type="number"]').val('');
									$('.evf-quiz-settings input[type="radio"]').prop('checked', false);
									$('.evf-quiz-settings input[type="checkbox"]').prop('checked', false);
									$('.everst-forms-field-quiz-settings').removeClass('everest-forms-show').addClass('everest-forms-hidden');
								}
							},
							"No": function() {
								$this.prop('checked', false);
								$('.survey-option .evf_survey_enable_container input').prop( 'checked', false );
							}

						}
					});
				}
			} else {
				$('.survey-option .evf_survey_enable_container input').prop( 'checked', false );
			}

		},

		/**
		 * Live Preview of Scale Rating Rating Text
		 */
		scaleRatingText: function ( e, el, level){
			e.preventDefault();
			var $this = $( el ),
			container = '';
			previewVal = $this.val();

			if ( 'lowest' === level ) {
				var field_id = $this.closest('.everest-forms-field-option-row-lowest_rating_text').attr('data-field-id');
				container = $('#everest-forms-field-' + field_id +'').find('.lowest-rating');
			} else {
				var field_id = $this.closest('.everest-forms-field-option-row-highest_rating_text').attr('data-field-id');
				container = $('#everest-forms-field-' + field_id +'').find('.highest-rating');
			}

			container.text(previewVal);

		},

		/**
		 * Live Preview of Scale Rating Rating Point
		 */
		scaleRatingPoint: function ( e, el, level ){
			e.preventDefault();
			var $this = $( el ),
			field_id = $this.closest('.everest-forms-field-option-row-' + level + '_rating_point').data('field-id'),
			container = $('#everest-forms-field-' + field_id +'');
			lowest_point = parseInt( $('#everest-forms-field-option-' + field_id + '-lowest_rating_point').val() ),
			hightest_point = parseInt( $('#everest-forms-field-option-' + field_id + '-highest_rating_point').val() ),
			ratingDiff = hightest_point - lowest_point;
			previewVal = $this.val();

			if( hightest_point > lowest_point ) {
				$('#everest-forms-field-'+field_id+'').find('.everest-forms-scale-rating-table').find('.highest-rating').parent('th').attr( 'colspan', ratingDiff+1 );
				container.find('.everest-forms-scale-rating-table tbody tr').empty();
				for ( var $i = lowest_point; $i <= hightest_point; $i++ ) {
					container.find('.everest-forms-scale-rating-table tbody tr').append('<td><input type="radio" disabled><label>' + $i + '</label></td>');
				}
			}
		},

		/**
		 * Sortable Handler.
		 */
		choicesInit: function () {
			$( 'ul.evf-survey-choices' ).sortable({
				items: 'li',
				axis: 'y',
				helper: '.sort',
				scrollSensitivity: 40,
			});
		},

		/**
		 * Add field choice
		 *
		 */
		fieldChoiceAdd: function( e, el ) {
			e.preventDefault();
			var $this = $( el ),
			clone = $this.closest('li').clone();
			clone.find('input').val('');
			var ul = $this.closest('.evf-survey-choices');
			var field_id = ul.data('field-id');
			var next_id = ul.attr('data-next-id');
			var choice_type = ul.attr('data-choice-type');

			clone.attr('data-key', next_id);
			clone.find('.default').attr('name', 'form_fields[' + field_id + '][' + choice_type + '][' + next_id + '][default]');
			clone.find('.label').attr('name', 'form_fields[' + field_id + '][' + choice_type + '][' + next_id + ']');
			$this.closest('li').after(clone);
			next_id++;
			$this.closest('.evf-survey-choices').attr('data-next-id',next_id);
			EverestFormsSurvey.fieldChoiceUpdate( field_id );

			/**
			 * Add new required-field-message field if a new likert row was added
			 */
			if ( 'likert_rows' === ul.data( 'choice-type' ) ) {
				var next_to_li = $this.closest( 'li' );
				EverestFormsSurvey.requiredFieldMessageInputFieldAdd( ul, next_to_li, clone );
			}
		},

		/**
		 * Delete field choice.
		 */
		fieldChoiceDelete: function(e, el) {

			e.preventDefault();

			var $this = $(el),
				ul = $this.closest('.evf-survey-choices'),
				field_id = ul.data('field-id'),
				$list = $this.parent().parent(),
				total = $list.find('li').length;

			if (total == '1') {
				$.alert({
					title: false,
					content: evf_data.i18n_row_locked_msg,
					icon: 'dashicons dashicons-info',
					type: 'blue',
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				});
			} else {
				/**
				 * Remove the corresponding required-field-message field if a likert row is to be removed
				 */
				if ( 'likert_rows' === ul.data( 'choice-type' ) ) {
					var key = $this.parent().data( 'key' );
					EverestFormsSurvey.requiredFieldMessageInputFieldDelete( field_id, key );
				}

				$this.parent().remove();
				EverestFormsSurvey.fieldChoiceUpdate( field_id );
			}
		},

		/**
		 * Update required-field-message field's label
		 *
		 */
		requiredFieldMessageInputFieldUpdate: function(ul, li, target) {
			var key      = li.data( 'key' ),
				field_id = ul.data( 'field-id' ),
				id       = '#everest-forms-field-option-row-' + field_id + '-required-field-message-' + key;

			$( id ).find( 'label' )[0].firstChild.nodeValue = $( target ).val();
		},

		/**
		 * Add new required-field-message field
		 *
		 */
		requiredFieldMessageInputFieldAdd: function( ul, next_to_li, new_li ) {
			var field_id    = ul.data('field-id');
			var next_to_key = next_to_li.data('key');
			var new_key     = new_li.data('key');
			var $next_to    = $( '#everest-forms-field-option-row-' + field_id + '-required-field-message-' + next_to_key );
			var $new_field  = $next_to.clone();

			// Update container.
			var cloned_container_class = 'everest-forms-field-option-row-required-field-message-' + next_to_key;
			var new_container_class    = 'everest-forms-field-option-row-required-field-message-' + new_key;
			var new_container_id       = 'everest-forms-field-option-row-' + field_id + '-required-field-message-' + new_key;

			$new_field.attr( 'id', new_container_id ).removeClass( cloned_container_class ).addClass( new_container_class );

			// Update label.
			var new_for   = 'everest-forms-field-option-' + field_id + '-required-field-message-' + new_key;
			var new_label = new_li.find('input').val();

			$new_field.find( 'label' ).attr( 'for', new_for );
			$new_field.find( 'label' )[0].firstChild.nodeValue = new_label;

			// Update tooltip.
			var tooltip_text = 'Enter a message to show for this row if it\'s required.';

			$new_field.find('label').find('i').tooltipster({
				onlyOne  : false,
				position : 'bottom',
				content  : tooltip_text
			});

			// Update input field.
			var new_input_id    = 'everest-forms-field-option-' + field_id + '-required-field-message-' + new_key;
			var new_input_name  = 'form_fields[' + field_id + '][required-field-message-' + new_key + ']';
			var new_input_value = 'This field is required.';

			$new_field.find('input').attr( 'id', new_input_id ).attr( 'name', new_input_name ).val( new_input_value );

			// Insert the edited field.
			$next_to.after( $new_field );
		},

		/**
		 * Delete required-field-message field.
		 */
		requiredFieldMessageInputFieldDelete: function( field_id, key ) {
			var container_id = '#everest-forms-field-option-row-' + field_id + '-required-field-message-' + key;

			$( container_id ).remove();
		},

		/**
		 * Add feedback.
		 */
		feedbackAdd: function( e, el ) {
			e.preventDefault();
			var $this = $( el );
			if ( $this.siblings('.evf-form-group').find('.score_to').last().val() >= 100 ){
				$.alert({
					title: false,
					content: "You can only add feedback for the score 100% or below.",
					icon: 'dashicons dashicons-info',
					type: 'blue',
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				});
			} else if( $this.siblings('.evf-form-group').find('.score_to').last().val() === '') {
				$( document.body ).triggerHandler( 'evf_add_error_tip', [ $this.siblings('.evf-form-group').find('.score_to').last(), 'i18n_field_to_score_is_empty_error', params ] );
			} else {
				var clone = $this.siblings('.evf-form-group').last().clone();
				clone.find('input').val('');
				clone.find('textarea').val('');
				var container = $this.closest('.overall-feedback-option');
				var next_id = container.attr('data-next-id');
				clone.attr('data-key', next_id);
				clone.find('.score_from').attr('name', 'settings[score_feedback][' + next_id + '][from]');
				clone.find('.score_from').attr('value', parseInt( $this.siblings('.evf-form-group').find('.score_to').last().val() ) + 1 );
				clone.find('.score_to').attr('name', 'settings[score_feedback][' + next_id + '][to]');
				clone.find('.feedback-options-message').attr('name', 'settings[score_feedback][' + next_id + '][feedback]');
				$this.siblings('.evf-form-group').last().after(clone);
				next_id++;
				$this.closest('.overall-feedback-option').attr('data-next-id',next_id);
			}
		},

		/**
		 * Delete feedback.
		 */
		feedbackRemove: function(e, el) {
			e.preventDefault();
			var $this = $(el),
			container = $this.closest('.overall-feedback-option'),
			total = container.find('.evf-form-group').length;

			if (total == '1') {
				$.alert({
					title: false,
					content: evf_data.i18n_row_locked_msg,
					icon: 'dashicons dashicons-info',
					type: 'blue',
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				});
			} else {
				if( $this.closest('.evf-form-group').next('.evf-form-group').length != 0 ) {
					$this.closest('.evf-form-group').next('.evf-form-group').find('.score_from').val($this.closest('.evf-form-group').find('.score_from').val());
				}
				$this.closest('.evf-form-group').remove();
			}
		},

		/**
		 * Update Field choice.
		 *
		 */
		fieldChoiceUpdate: function( fieldID ) {
			$('.everest-forms-likert-dd-options').removeClass('everest-forms-show');
			$('.everest-forms-likert-dd-options').addClass('everest-forms-hidden');
			var selected_choice = $( '#everest-forms-field-option-row-' + fieldID + '-drop_down_choices' ).find('.evf-survey-choices .default:checked').next('.label').val();
			var first_choice = $( '#everest-forms-field-option-row-' + fieldID + '-drop_down_choices' ).find('.evf-survey-choices').find('.label').first().val();
			var data = {
					rows:        {},
					columns:     {},
					colCount:    0,
					inputType:  $( '#everest-forms-field-option-' + fieldID + '-input_type' ).val(),
					selected_choice: 'undefined' != typeof selected_choice ? selected_choice : first_choice,
				};

			if ( data.inputType === 'select') {
				$('.everest-forms-likert-dd-options').removeClass('everest-forms-hidden');
				$('.everest-forms-likert-dd-options').addClass('everest-forms-show');
			}

			// Get columns.
			$( '#everest-forms-field-option-row-'+fieldID + '-columns .evf-survey-choices li').each( function() {
				var $this = $( this ),
					key   = $this.data( 'key' ),
					value = $this.find( 'input' ).val();

				data.columns['c'+key] = {
					key:   key,
					value: value
				};

				data.colCount++;
			});

			// Get rows.
			$( '#everest-forms-field-option-row-'+fieldID + '-rows .evf-survey-choices li').each( function() {
				var $this = $( this ),
					key   = $this.data( 'key' ),
					value = $this.find( 'input' ).val();

				data.rows['r'+key] = {
					key:   key,
					value: value
				};
			});

			data.width = 80 / data.colCount;

			var likertPreview = wp.template( 'everest-forms-likert-field-preview' );
			$( '#everest-forms-field-' + fieldID ).find( 'table' ).replaceWith( likertPreview( data ) );
		},

		/**
		 * Check for Yes/No field variations
		 *
		 */
		 checkSelectedYesNoVariation: function( value ) {
			$( '.everest-forms-field-option-row-yes_icon_color' ).hide();
			$( '.everest-forms-field-option-row-no_icon_color' ).hide();
			$( '.everest-forms-field-option-row-yes_label' ).hide();
			$( '.everest-forms-field-option-row-no_label' ).hide();

			if ( 'with_icon_text' === value ) {
				$( '.everest-forms-field-option-row-yes_icon_color' ).show();
				$( '.everest-forms-field-option-row-no_icon_color' ).show();
				$( '.everest-forms-field-option-row-yes_label' ).show();
				$( '.everest-forms-field-option-row-no_label' ).show();
			}

			if ( 'with_icon' === value ) {
				$( '.everest-forms-field-option-row-yes_icon_color' ).show();
				$( '.everest-forms-field-option-row-no_icon_color' ).show();
			}

			if ( 'with_text' === value ) {
				$( '.everest-forms-field-option-row-yes_label' ).show();
				$( '.everest-forms-field-option-row-no_label' ).show();
			}
		}
	}
	EverestFormsSurvey.init(jQuery);
})( jQuery, everest_forms_survey_polls_quiz_builder );
