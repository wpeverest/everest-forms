jQuery(function ($) {
	/**
	 * Save form applying theme style.
	 */
	$(document.body).on("change", "#evf_toggle_form_preview_theme", function () {
		$(".evf-frontend-form").toggleClass("evf-frontend-form-preview");
		$("#evf-form-save").toggleClass("hidden");
	});
	/**
	 * Toggle sidepanel.
	 */
	$(document.body).on(
		"click",
		".evf-form-preview-sidepanel-toggler",
		function () {
			$(".evf-form-side-panel").toggleClass("hidden");
			$(this).toggleClass("inactive");
			$(".evf-form-preview-main-content").toggleClass(
				"evf-form-preview-overlay"
			);
		}
	);

	/**
	 * Change form preview based on device selected.
	 */
	$(document.body).on("click", ".evf-form-preview-device", function () {
		var device = $(this).data("device");
		var container_wrapper = $(".evf-frontend-form");
		var preview_form = $(".evf-preview-content");
		$(this)
			.closest(".evf-form-preview-devices")
			.find(".evf-form-preview-device")
			.removeClass("active");
		$(this).parent().find("svg path").css("fill", "#383838");
		$(this).find("path").css("fill", "#475BB2");

		if (device === "desktop") {
			container_wrapper.addClass("evf-frontend-form-desktop-view");
			container_wrapper.removeClass("evf-frontend-form-table-view");
			container_wrapper.removeClass("evf-frontend-form-mobile-view");
			preview_form.removeClass("evf-preview-tablet-wrapper");
			preview_form.removeClass("evf-preview-mobile-wrapper");
			$(this).addClass("active");
		} else if (device === "tablet") {
			container_wrapper.addClass("evf-frontend-form-table-view");
			container_wrapper.removeClass("evf-frontend-form-desktop-view");
			container_wrapper.removeClass("evf-frontend-form-mobile-view");
			preview_form.addClass("evf-preview-tablet-wrapper");
			preview_form.removeClass("evf-preview-mobile-wrapper");
			$(this).addClass("active");
		} else if (device === "mobile") {
			container_wrapper.addClass("evf-frontend-form-mobile-view");
			container_wrapper.removeClass("evf-frontend-form-desktop-view");
			container_wrapper.removeClass("evf-frontend-form-table-view");
			preview_form.addClass("evf-preview-mobile-wrapper");
			preview_form.removeClass("evf-preview-tablet-wrapper");

			$(this).addClass("active");
		} else {
			container_wrapper.removeClass("evf-frontend-form-desktop-view");
			container_wrapper.removeClass("evf-frontend-form-table-view");
			container_wrapper.removeClass("evf-frontend-form-mobile-view");
			$(this).addClass("active");
		}
	});

	/**
	 * Save form preview settings.
	 */
	$(document.body).on("click", "#evf-form-save", function () {
		var form_id = $(this).data("id");
		var is_enabled = $("#evf_toggle_form_preview_theme").is(":checked");
		if (is_enabled) {
			form_theme = "theme";
		} else {
			form_theme = "default";
		}

		$.ajax({
			url: everest_forms_form_preview.ajax_url,
			type: "POST",
			data: {
				action: "everest_forms_form_preview_save",
				id: form_id,
				theme: form_theme,
				security: everest_forms_form_preview.form_preview_nonce
			},
			beforeSend: function () {
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$( '.evf-form-preview-save' ).append( spinner );
			},
			complete: function (response) {
				$(".evf-loading").remove();
				$("#evf-form-save").addClass("hidden");
				// $('.evf-form-preview-save').find('img').remove()
				// if (response.responseJSON.success === true) {
				// 	$(".evf-form-preview-save-title").html(  response.responseJSON.data.message);

				// } else {
				// 	$(".evf-form-preview-save-title").html(  response.responseJSON.data.message);
				// }
			}
		});
	});

	$(document).ready(function () {
		// $('#evf_toggle_form_preview_theme').is(":checked") ? $('link#evf-form-preview-theme-style-css').prop('disabled', true) : $('link#evf-form-preview-default-style-css').prop('disabled', false);
		$("#evf_toggle_form_preview_theme").is(":checked")
			? $(".evf-frontend-form").addClass("evf-frontend-form-preview")
			: $(".evf-frontend-form").removeClass("evf-frontend-form-preview");
	});

	$(document.body).on("click", ".evf-form-preview-upgrade", function () {
		window.open(everest_forms_form_preview.pro_upgrade_link, "_blank");
	});

	/**
	 * Copy shortcode to clipboard.
	 *
	 * @since xx.xx.xx
	 */
	$(document.body)
	.find('#copy-shortcode')
	.on('click', async function () {
		const $this = $(this);
		try {
			const textToCopy = $this.closest('.publishing-action').find('input').val();
			await navigator.clipboard.writeText(textToCopy);
			$this.trigger('aftercopy');
		} catch (error) {
			console.error('Failed to copy text:', error);
		}
	})
	.on('aftercopy', function () {
		const $this = $(this);

		if (!$this.data('tooltipster-initialized')) {
			$this.tooltipster({
				theme: 'tooltipster-noir',
				interactive: true,
				trigger: 'custom',
				maxWidth: 200,
				content: $this.attr('data-copied'),
				position: 'bottom'
			});
			$this.data('tooltipster-initialized', true);
		}

		$this.tooltipster('content', $this.attr('data-copied')).tooltipster('open');

		setTimeout(() => {
			$this.tooltipster('close');
		}, 2000);
	});
});
