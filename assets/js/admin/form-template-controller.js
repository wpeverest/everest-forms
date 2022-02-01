/* global evf_template_controller */
jQuery(function ($) {
	/**
	 * Template actions.
	 */
	var evf_template_controller = {
		all: "#evf-form-all",
		basic: "#evf-form-basic",
		pro: "#evf-form-pro",
		results: evf_templates.evf_template_all,
		init: function () {
			evf_template_controller.latch_hooks();
		},
		latch_hooks: function () {
			$(document.body).ready(function () {
				$(evf_template_controller.all).click(function (e) {
					e.preventDefault();
					evf_template_controller.sort_all(this);
				});
				$(evf_template_controller.basic).click(function (e) {
					e.preventDefault();
					evf_template_controller.sort_basic(this);
				});
				$(evf_template_controller.pro).click(function (e) {
					e.preventDefault();
					evf_template_controller.sort_pro(this);
				});
				$(".page-title-action").click(function (e) {
					e.stopImmediatePropagation();

					$(this).html(
						evf_templates.template_refresh +
							' <div  class="evf-loading evf-loading-active"></div>'
					);
				});
			});
		},
		sort_all: function (el) {
			evf_template_controller.class_update($(el));
			evf_template_controller.render_results(
				evf_template_controller.results,
				"all"
			);
		},
		sort_basic: function (el) {
			evf_template_controller.class_update($(el));
			evf_template_controller.render_results(
				evf_template_controller.results,
				"free"
			);
		},
		sort_pro: function (el) {
			evf_template_controller.class_update($(el));
			evf_template_controller.render_results(
				evf_template_controller.results,
				"pro"
			);
		},
		class_update: function ($el) {
			$(".everest-forms-tab-nav").removeClass("active");
			$el.parent().addClass("active");
		},
		render_results: function (template, allow) {
			var el_to_append = $(".evf-setup-templates"),
				error = '<div  class="evf-loading evf-loading-active"></div>';

			if (!template) {
				$("#message").remove();
				el_to_append.html(error);

				// Adds a loading screen so the async results is populated.
				window.setTimeout(function () {
					evf_template_controller.render_results(
						evf_template_controller.results,
						allow
					);
				}, 1000);

				return;
			}

			$(".everest-forms-form-template").html("");

			template.forEach(function (tuple) {
				var toAppend = "",
					plan = tuple.plan.includes("free") ? "free" : "pro",
					data_plan = $(".everest-forms-form-template").data(
						"license-type"
					);

				if ("all" === allow || "blank" === tuple.slug) {
					toAppend = evf_template_controller.template_snippet(
						tuple,
						plan,
						data_plan
					);
				} else if (plan === allow) {
					toAppend = evf_template_controller.template_snippet(
						tuple,
						plan,
						data_plan
					);
				}

				el_to_append.append(toAppend);
			});
		},
		template_snippet: function (template, plan, data_plan) {
			var html = "",
				modal = "evf-template-select";
			data_plan =
				"" === data_plan ? "free" : data_plan.replace("-lifetime", "");
			if (
				!template.plan.includes("free") &&
				!template.plan.includes(data_plan)
			) {
				modal = "upgrade-modal";
			}

			html +=
				'<div class="everest-forms-template-wrap evf-template" id="everest-forms-template-' +
				template.slug +
				'" data-plan="' +
				plan +
				'">';

			if ("blank" !== template.slug) {
				html += '<figure class="everest-forms-screenshot" ';
			} else {
				html +=
					'<figure class="everest-forms-screenshot evf-template-select" ';
			}

			html +=
				'data-template-name-raw="' +
				template.title +
				'" data-template="' +
				template.slug +
				'" data-template-name="' +
				template.title +
				' template">';
			html +=
				'<img src=" ' +
				evf_templates.evf_plugin_url +
				"/assets/" +
				template.image +
				' ">';

			if ("blank" !== template.slug) {
				html +=
					'<div class="form-action"><a href="#" class="everest-forms-btn everest-forms-btn-primary ' +
					modal +
					'" data-licence-plan="' +
					data_plan +
					'" data-template-name-raw="' +
					template.title +
					'" data-template-name="' +
					template.title +
					' template" data-template="' +
					template.slug +
					'">' +
					evf_templates.i18n_get_started +
					"</a>";
				html +=
					'<a href="' +
					template.preview_link +
					'" target="_blank" class="everest-forms-btn everest-forms-btn-secondary">' +
					evf_templates.i18n_get_preview +
					"</a></div>";
			}

			if (!template.plan.includes("free")) {
				var $badge_text = "";
				if (template.plan.includes("personal")) {
					$badge_text = "Personal";
				} else if (template.plan.includes("plus")) {
					$badge_text = "Plus";
				} else if (template.plan.includes("professional")) {
					$badge_text = "Professional";
				} else {
					$badge_text = "Agency";
				}

				html +=
					'<span class="everest-forms-badge everest-forms-badge--success">' +
					$badge_text +
					"</span>";
			}

			html += '</figure><div class="everest-forms-form-id-container">';
			html +=
				'<a class="everest-forms-template-name ' +
				modal +
				'" href="#" data-template-name-raw="' +
				template.title +
				'" data-licence-plan="' +
				data_plan +
				'" data-template="' +
				template.slug +
				'" data-template-name="' +
				template.title +
				' template">' +
				template.title +
				"</a></div>";

			return html;
		},
	};

	evf_template_controller.init();
});
