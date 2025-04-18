( function ($) {
	const EVFCleanTalk = {

		init: function () {
			$( document ).ready( function () {
				EVFCleanTalk.bindCleanTalkInit();
				EVFCleanTalk.toggleCleanTalkSettings();

				$('#everest_forms_enable_cleantalk_spam_protection').on('change', function (e) {
					EVFCleanTalk.toggleCleanTalkSettings();
				});

				$( document ).on('change', '#everest_forms_clean_talk_methods', function () {
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
			const isEnabled = $('#everest_forms_enable_cleantalk_spam_protection').is(':checked');
			const selectedMethod = $('#everest_forms_clean_talk_methods:checked').val();

			const $methodSetting = $('.evf-clean-talk-method').closest('.everest-forms-global-settings');
			const $accessKeySetting = $('#everest_forms_recaptcha_cleantalk_access_key').closest('.everest-forms-global-settings');

			if (isEnabled) {
				$methodSetting.show();

				if (selectedMethod === 'rest_api') {
					$accessKeySetting.show();
				} else {
					$accessKeySetting.hide();
				}
			} else {
				$methodSetting.hide();
				$accessKeySetting.hide();
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
		}
	};

	EVFCleanTalk.init();
})(jQuery);
