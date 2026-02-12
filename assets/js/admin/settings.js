/* global everest_forms_settings_params, jconfirm */
(function ($, params) {
	// Confirm defaults.
	$(document).ready(function () {
		// jquery-confirm defaults.
		jconfirm.defaults = {
			closeIcon: true,
			backgroundDismiss: true,
			escapeKey: true,
			animationBounce: 1,
			useBootstrap: false,
			theme: 'modern',
			boxWidth: '400px',
			columnClass: 'evf-responsive-class',
		};
	});

	$('.evf-colorpicker').wpColorPicker({
		change: function (event, ui) {
			// // Update the preview background color
			// $(this).closest('.everest-forms-global-settings--field').find('.colorpickpreview').css('background-color', ui.color.toString());
		},
		hide: true, // Keep the picker hidden initially
		border: true,
	});

	// Toggle the color picker visibility when the input field is clicked
	$('.evf-colorpicker').on('click', function (event) {
		event.stopPropagation(); // Prevent the body click from hiding the picker

		// Show the color picker container
		const pickerContainer = $(this)
			.closest('.everest-forms-global-settings--field')
			.find('.wp-picker-container .iris-picker');

		if (pickerContainer.is(':visible')) {
			pickerContainer.hide();
		} else {
			$('.iris-picker').hide();
			pickerContainer.show();
		}
	});

	// Hide the color picker when clicking anywhere outside
	$('body').on('click', function () {
		$('.iris-picker').hide();
	});

	// Prevent clicks inside the color picker from closing it
	$('.wp-picker-container').on('click', function (event) {
		event.stopPropagation();
	});

	// Edit prompt
	$(function () {
		var changed = false;

		$('input, textarea, select, checkbox').change(function () {
			changed = true;
		});

		$('.evf-nav-tab-wrapper a').click(function () {
			if (changed) {
				window.onbeforeunload = function () {
					return params.i18n_nav_warning;
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		$('.submit :input').click(function () {
			window.onbeforeunload = '';
		});
	});

	// Select all/none
	$('.everest-forms').on('click', '.select_all', function () {
		$(this).closest('td').find('select option').attr('selected', 'selected');
		$(this).closest('td').find('select').trigger('change');
		return false;
	});

	$('.everest-forms').on('click', '.select_none', function () {
		$(this).closest('td').find('select option').removeAttr('selected');
		$(this).closest('td').find('select').trigger('change');
		return false;
	});

	// Handle invisible reCAPTCHA v2 toggle
	$('input#everest_forms_recaptcha_v2_invisible')
		.on('change', function () {
			var v2_site_key = $('#everest_forms_recaptcha_v2_site_key').closest(
					'.everest-forms-global-settings',
				),
				v2_secret_key = $('#everest_forms_recaptcha_v2_secret_key').closest(
					'.everest-forms-global-settings',
				),
				v2_invisible_site_key = $(
					'#everest_forms_recaptcha_v2_invisible_site_key',
				).closest('.everest-forms-global-settings'),
				v2_invisible_secret_key = $(
					'#everest_forms_recaptcha_v2_invisible_secret_key',
				).closest('.everest-forms-global-settings');

			if ($(this).is(':checked')) {
				v2_site_key.hide();
				v2_secret_key.hide();

				v2_invisible_site_key.removeClass('everest-forms-hidden').show();
				v2_invisible_secret_key.removeClass('everest-forms-hidden').show();
			} else {
				v2_site_key.show();
				v2_secret_key.show();
				v2_invisible_site_key.addClass('everest-forms-hidden').hide();
				v2_invisible_secret_key.addClass('everest-forms-hidden').hide();
			}
		})
		.trigger('change');

	// Send Test Email.
	$('.everest_forms_send_email_test').on('click', function (e) {
		e.preventDefault();
		let email = $('#everest_forms_email_send_to').val();
		let data = {
			action: 'everest_forms_send_test_email',
			email: email,
			security: evf_email_params.ajax_email_nonce,
		};
		$.ajax({
			url: evf_email_params.ajax_url,
			data: data,
			type: 'post',
			beforeSend: function () {
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$('.everest_forms_send_email_test')
					.closest('.everest_forms_send_email_test')
					.append(spinner);
			},
			complete: function (response) {
				$('.everest_forms_send_email_test')
					.closest('.everest_forms_send_email_test')
					.find('.evf-loading')
					.remove();

				if (true === response.responseJSON.success) {
					window.evfShowToast(
						response.responseJSON.data.message,
						'success',
						5000,
					);
				} else {
					window.evfShowToast(
						response.responseJSON.data.message,
						'error',
						5000,
					);
				}
			},
		});
	});

	// Handles collapse of side menu.
	$('#evf-settings-collapse').on('click', function (e) {
		e.preventDefault();
		if ($(this).hasClass('close')) {
			$(this).closest('header').addClass('collapsed');
			$(this).removeClass('close').addClass('open');
			setStorageValue('evf-settings-navCollapsed', true); // set to localStorage
		} else {
			$(this).closest('header').removeClass('collapsed');
			$(this).removeClass('open').addClass('close');
			localStorage.removeItem('evf-settings-navCollapsed'); // remove from localStorage
		}
	});

	// Persist the collapsable state through page reload

	var isNavCollapsed =
		getStorageValue('evf-settings-navCollapsed') === true
			? 'collapsed'
			: 'not-collapsed';
	getStorageValue('evf-settings-navCollapsed');
	if (isNavCollapsed == 'collapsed') {
		$('.everest-forms-header').addClass('collapsed');
		$('#evf-settings-collapse').removeClass('close').addClass('open');
	} else {
		$('.everest-forms-header').removeClass('collapsed');
		$('#evf-settings-collapse').removeClass('open').addClass('close');
	}

	// Set localStorage with expiry
	function setStorageValue(key, value) {
		var current = new Date();

		var data = {
			value: value,
			expiry: current.getTime() + 86400000, // 1day of expiry time
		};

		localStorage.setItem(key, JSON.stringify(data));
	}

	// Get localStorage with expiry
	function getStorageValue(key) {
		var item = localStorage.getItem(key);

		if (!item) {
			return false;
		}

		var data = JSON.parse(item);
		var current = new Date();

		if (current.getTime() > data.expiry) {
			localStorage.removeItem(key);
			return false;
		}
		return true;
	}

	// Send Routine Report Test Email.
	$('.everest_forms_send_routine_report_test_email').on('click', function (e) {
		e.preventDefault();
		let email = $('#everest_forms_email_send_to').val();
		let data = {
			action: 'everest_forms_send_routine_report_test_email',
			email: email,
			security: evf_email_params.ajax_email_nonce,
		};
		$.ajax({
			url: evf_email_params.ajax_url,
			data: data,
			type: 'post',
			beforeSend: function () {
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$('.everest_forms_send_routine_report_test_email')
					.closest('.everest_forms_send_routine_report_test_email')
					.append(spinner);
				$('.everest-froms-send_test_email_notice').remove();
			},
			complete: function (response) {
				var message_string = '';

				$('.everest_forms_send_routine_report_test_email')
					.closest('.everest_forms_send_routine_report_test_email')
					.find('.evf-loading')
					.remove();
				$('.everest-froms-send_test_email_notice').remove();
				if (true === response.responseJSON.success) {
					message_string =
						'<div id="message" class="updated inline everest-froms-send_test_email_notice"><p><strong>' +
						response.responseJSON.data.message +
						'</strong></p></div>';
					$('.everest-forms-options-header').append(message_string);
				} else {
					message_string =
						'<div id="message" class="error inline everest-froms-send_test_email_notice"><p><strong>' +
						response.responseJSON.data.message +
						'</strong></p></div>';
				}

				$('.everest-forms-settings').find('h2').after(message_string);
			},
		});
	});

	$('.everest-forms-accordion-wrapper').on(
		'click',
		'.everest-forms-accordion-header',
		function (e) {
			e.preventDefault();

			var $accordionItem = $(this).closest('.everest-forms-accordion-item');
			var $accordionContent = $accordionItem.find(
				'.everest-forms-accordion-content',
			);

			$accordionItem.toggleClass('is-open');

			$accordionContent.slideToggle(300);
		},
	);

	// Initialize accordion items and CAPTCHA toggles on document ready
	$(document).ready(function () {
		// Initialize accordion items
		$('.everest-forms-accordion-item').each(function () {
			if (!$(this).hasClass('is-open')) {
				$(this).find('.everest-forms-accordion-content').hide();
			}
		});

		// Handle CAPTCHA enable/disable toggles - ensure only one can be enabled
		$(document).on(
			'change',
			'#everest_forms_recaptcha_v2_enable, #everest_forms_recaptcha_v3_enable, #everest_forms_recaptcha_hcaptcha_enable, #everest_forms_recaptcha_turnstile_enable',
			function () {
				var $currentToggle = $(this);
				var currentId = $currentToggle.attr('id');

				if ($currentToggle.is(':checked')) {
					// Get all other CAPTCHA toggles
					var otherToggles = [
						'#everest_forms_recaptcha_v2_enable',
						'#everest_forms_recaptcha_v3_enable',
						'#everest_forms_recaptcha_hcaptcha_enable',
						'#everest_forms_recaptcha_turnstile_enable',
					].filter(function (id) {
						return id !== '#' + currentId;
					});

					// Automatically uncheck all other toggles (disable them)
					otherToggles.forEach(function (toggleId) {
						var $toggle = $(toggleId);
						// Uncheck the toggle
						$toggle.prop('checked', false);

						var $description = $toggle
							.closest('.everest-forms-global-settings--field')
							.find('.description');
						if ($description.length) {
							$description.text(
								'Enable this CAPTCHA. Note: Enabling this will disable other CAPTCHA providers.',
							);
						}
					});

					// Update the current toggle description
					var $currentDescription = $currentToggle
						.closest('.everest-forms-global-settings--field')
						.find('.description');
					if ($currentDescription.length) {
						$currentDescription.text('This CAPTCHA is currently enabled.');
					}
				} else {
					// If this toggle was disabled, update description
					var $currentDescription = $currentToggle
						.closest('.everest-forms-global-settings--field')
						.find('.description');
					if ($currentDescription.length) {
						$currentDescription.text(
							'Enable this CAPTCHA. Note: Enabling this will disable other CAPTCHA providers.',
						);
					}
				}
			},
		);
	});

	disableFormChangeModal();

	/**
	 * Disable leave page before saving changes modal when hid/show sidebar is clicked.
	 */
	function disableFormChangeModal() {
		var form = $('.everest-forms').find('form')[0];

		var formChanged = false;

		$(form).on('change', function (event) {
			if (event.target.name !== 'everest-forms-enable-premium-sidebar') {
				formChanged = true;
			}
		});

		var skipBeforeUnloadPopup = false;
		$(form).on('submit', function () {
			skipBeforeUnloadPopup = true;
		});
		$(form)
			.find('.evf-nav__link')
			.on('click', function () {
				skipBeforeUnloadPopup = true;
			});

		$(window).on('beforeunload', function (event) {
			if (formChanged && !skipBeforeUnloadPopup) {
				event.preventDefault();
				event.returnValue = '';
			} else {
				event.stopImmediatePropagation();
			}
		});
	}
})(jQuery, everest_forms_settings_params);
