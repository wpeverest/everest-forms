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

					const $messageBox = $(document).find('.evf-clean-talk-message');

					$messageBox.attr('style', '');
					const baseStyle = `
						padding: 10px 15px;
						border-radius: 4px;
						margin-top: 10px;
						font-weight: bold;
						display: block;
					`;

					if (response.success) {
						$messageBox
							.attr('style', baseStyle + `
								color: #155724;
								background-color: #d4edda;
								border: 1px solid #c3e6cb;
							`)
							.text(response.data.message || 'Form submitted successfully!');
					} else {
						$messageBox
							.attr('style', baseStyle + `
								color: #721c24;
								background-color: #f8d7da;
								border: 1px solid #f5c6cb;
							`)
							.text(response.data.message || 'An error occurred. Please try again.');
					}

					setTimeout(function () {
						$messageBox.fadeOut(300, function () {
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
