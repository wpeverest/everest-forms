( function ($) {
	const EVFCleanTalk = {

		init: function () {
			$( document ).ready( function () {
				EVFCleanTalk.bindCleanTalkInit();
				EVFCleanTalk.toggleCleanTalkSettings();

				$( document).on('click', '#everest-forms-clean-talk-save-settings', function (e) {
					e.preventDefault();
					EVFCleanTalk.saveCleanTalkSettings( $( this ) );
				});

				$(document).on('change', 'input[name="everest_forms_clean_talk_methods"]', function () {
					EVFCleanTalk.toggleCleanTalkSettings();
				});
			});
		},

		/**
		 * Bind CleanTalk toggle for form field settings.
		 */
		bindCleanTalkInit: function () {
			const cleanTalkEnabler = $('#everest-forms-panel-field-settings-cleantalk');
			EVFCleanTalk.cleanTalkToggle(cleanTalkEnabler);

			$(document).on('change', '#everest-forms-panel-field-settings-cleantalk', function () {
				EVFCleanTalk.cleanTalkToggle($(this));
			});
		},

		/**
		 * Show/hide CleanTalk-related settings based on selections.
		 */
		toggleCleanTalkSettings: function () {
			const selectedMethod = $('input[name="everest_forms_clean_talk_methods"]:checked').val();

			if ( 'rest_api' === selectedMethod ) {
				$( document ).find( '.evf-clean-talk-access-key' ).removeClass( 'everest-forms-hidden' );
			}else{
				$( document ).find( '.evf-clean-talk-access-key' ).addClass( 'everest-forms-hidden' );
			}
		},

		/**
		 * Toggle visibility of CleanTalk protection type in field settings.
		 */
		cleanTalkToggle: function (cleanTalkEnabler) {
			if ($(cleanTalkEnabler).is(':checked')) {
				$('.everest-forms-cleantalk-protection-type').show();
			} else {
				$('.everest-forms-cleantalk-protection-type').hide();
			}
		},
		/**
		 * Show/hide CleanTalk settings based on the selected method.
		 */
		saveCleanTalkSettings: function ( $el ) {

			const $form = $('#everest-forms-clean-talk-settings-form'),
				  formData = $form.serializeArray();
			const data = {
				action: 'everest_forms_save_clean_talk_settings',
				security: everest_forms_clean_talk.security,
				form_data: formData,
				is_clean_talk_enabled:'yes',
			};

			const $button = $el;

			$.ajax({
				type: 'POST',
				url: everest_forms_clean_talk.ajax_url,
				data: data,
				beforeSend : function() {
					var spinner = '<i class="evf-loading evf-loading-active"></i>';
					$button.append( spinner );
					$button.attr( 'disabled', true );
					$button.css({
						cursor: 'not-allowed',
						opacity: 0.5
					});
				},
				success: function (response) {
					$button.find('.evf-loading').remove();
					const killUnloadPrompt = setInterval(function () {
						window.onbeforeunload = null;
						$(window).off('beforeunload');
					}, 500);

					setTimeout(function () {
						clearInterval(killUnloadPrompt);
					}, 5000);

					const $messageBox = $( document ).find( '.evf-clean-talk-message' );
					$messageBox.empty();
					$messageBox.append( response.data.html );

					if (response.success) {
						$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-success-state' );
					} else {
						if ( 'empty' === response.data.error ) {
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-empty-state' );
						}else{
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-invalid-state' );
						}
					}

					$button.attr( 'disabled', false );
					$button.css({
						cursor: '',
						opacity: 1
					});

					setTimeout(function () {
						$messageBox.fadeOut(300, function () {
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-empty-state' );
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-invalid-state' );
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-success-state' );
							$messageBox.attr('style', '').text('').show();
						});
					}, 5000);
				},
				error: function () {
					alert('Error saving settings.');
					$button.val(originalText);
				}
			});
		},

	};

	EVFCleanTalk.init();
})(jQuery);
