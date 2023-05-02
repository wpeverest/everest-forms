/* global evf_plugins_params */
jQuery(function ($) {
	var evf_deactivation_feedback = {
		init: function () {
			this.event_init();
		},
		event_init: function () {
			var _that = this;

			$(document.body).on(
				"click",
				'tr[data-plugin="everest-forms/everest-forms.php"] span.deactivate a',
				function (e) {
					e.preventDefault();
					$("#evf-deactivate-feedback-popup-wrapper").addClass(
						"active"
					);
				}
			);

			$("#evf-deactivate-feedback-popup-wrapper").click(function (event) {
				var $target = $(event.target);
				if (
					!$target.closest(".evf-deactivate-feedback-popup-inner")
						.length
				) {
					$("#evf-deactivate-feedback-popup-wrapper").removeClass(
						"active"
					);
				}
			});

			$("form.evf-deactivate-feedback-form").on("submit", function (e) {
				e.preventDefault();
				_that.send_data($(this));
			});

			$('#evf-deactivate-feedback-popup-wrapper').on('click', '.close-deactivate-feedback-popup', function(){
				$('#evf-deactivate-feedback-popup-wrapper').removeClass('active');
			});

			$('input.evf-deactivate-feedback-input').on( 'click', function() {
				var $this = $(this);
				var inputTextBox = $('input[name="reason_other"]');
				if ( 'other' === $this.val() ) {
					inputTextBox.attr('required', 'required')
				} else {
					inputTextBox.removeAttr('required');
				}
			} );
		},
		send_data: function (form) {
			var reason_slug = form
				.find('input[name="reason_slug"]:checked')
				.val();

			if (reason_slug === undefined) {
				alert("Please select at least one option from the list");
				return;
			}

			if (form.find("button.submit").hasClass("button-disabled")) {
				return;
			}

			var reason_text = "";
			var reason_text_el = form.find(
				'input[name="reason_' + reason_slug + '"]'
			);

			if (reason_text_el.length > 0) {
				reason_text = reason_text_el.val();
			}

			var data = {
				reason_slug: "everest_forms_deactivation_notice",
			};

			data["reason_" + reason_slug] = reason_text;

			$.ajax({
				url: evf_plugins_params.ajax_url,
				data: form.serializeArray(),
				type: "post",
				beforeSend: function () {
					form.find("button.submit").addClass(
						"button-disabled button updating-message"
					);
				},
			}).done(function () {
				window.location = form.find("a.skip").attr("href");
			});
		},
	};

	evf_deactivation_feedback.init();
});
