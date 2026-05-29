/* global everest_forms_admin_tools */
jQuery(function ($) {

	$(document).ready(function () {
		const tabs = $('.everest-forms-tabs li');
		const contents = $('.everest-forms-tab-content');

		tabs.on('click', function () {
			tabs.removeClass('active');
			contents.removeClass('active');

			$(this).addClass('active');
			$('#' + $(this).data('tab')).addClass('active');
		});
	});

	// collapse

	// Handles collapse of side menu.
	$("#evf-settings-collapse").on("click", function (e) {
		e.preventDefault();
		if ($(this).hasClass("close")) {
			$(this).closest("header").addClass("collapsed");
			$(this).removeClass("close").addClass("open");
			setStorageValue("evf-settings-navCollapsed", true); // set to localStorage
		} else {
			$(this).closest("header").removeClass("collapsed");
			$(this).removeClass("open").addClass("close");
			localStorage.removeItem("evf-settings-navCollapsed"); // remove from localStorage
		}
	});

	// Persist the collapsable state through page reload

	var isNavCollapsed =
		getStorageValue("evf-settings-navCollapsed") === true
			? "collapsed"
			: "not-collapsed";
			getStorageValue("evf-settings-navCollapsed");
	if (isNavCollapsed == "collapsed") {
		$(".everest-forms-header").addClass("collapsed");
		$("#evf-settings-collapse").removeClass("close").addClass("open");
	} else {
		$(".everest-forms-header").removeClass("collapsed");
		$("#evf-settings-collapse").removeClass("open").addClass("close");
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


	// Delete All Logs.
	$("#log-viewer-select").on(
		"click",
		"h2 a.page-title-action-all",
		function (evt) {
			evt.stopImmediatePropagation();
			return window.confirm(
				everest_forms_admin_tools.delete_all_log_confirmation
			);
		}
	);

	$("#log-viewer-select").on(
		"click",
		"h2 a.page-title-action",
		function (evt) {
			evt.stopImmediatePropagation();
			return window.confirm(
				everest_forms_admin_tools.delete_log_confirmation
			);
		}
	);
	$("#everest-forms-form-migrator").on("change", function () {
		var $this = $(this),
			formSlug = $this.val();
		if (typeof formSlug === "undefined" || formSlug === "") {
			return;
		}

		var data = {
			action: "everest_forms_form_migrator_forms_list",
			form_slug: formSlug,
			security:
				everest_forms_form_migrator.evf_form_migrator_forms_list_nonce,
		};

		$.ajax({
			url: everest_forms_form_migrator.ajax_url,
			type: "POST",
			dataType: "JSON",
			data: data,
			beforeSend: function () {
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$this.closest(".evf-fm-select-popular-form").append(spinner);
			},
			success: function (res) {
				if (res.success === true) {
					$(document)
						.find("#evf-fm-forms-list-container")
						.html(res.data.forms_list_table);
				} else {
					$(document)
						.find("#evf-fm-forms-list-container")
						.html(res.data.message);
				}
				$this
					.closest(".evf-fm-select-popular-form")
					.find(".evf-loading")
					.remove();
			},
		});
	});
	$(document).ready(function () {
		$(document).on("click", "#evf-fm-select-all", function () {
			var checkList = $(document).find(".evf-fm-select-single:visible");
			if ($(this).prop("checked") == true) {
				$.each(checkList, function (index, value) {
					$(value).attr("checked", true);
					$(value).prop("checked", true);
				});
			} else {
				$.each(checkList, function (index, value) {
					$(value).attr("checked", false);
					$(value).prop("checked", false);
				});
			}
		});

		$(document).on("click", ".evf-fm-select-single", function () {
			$(document).find("#evf-fm-select-all").prop("checked", false);
		});

		$(document).on("click", ".evf-fm-page", function () {
			if ($(this).hasClass("evf-fm-btn-active")) {
				return;
			}

			$(".evf-fm-page").removeClass("evf-fm-btn-active");
			$(this).addClass("evf-fm-btn-active");

			var formTable = $(document).find(".evf-fm-forms-table");

			var formPerPage = $(this)
				.closest(".evf-fm-pagination")
				.data("form-per-page");
			var currentPage = $(this).data("page");

			var start = (currentPage - 1) * formPerPage + 1;
			var end = start + formPerPage - 1;

			var formRows = formTable.find(".evf-fm-row");

			formRows.addClass("evf-fm-hide-row");

			formRows.slice(start - 1, end).removeClass("evf-fm-hide-row");
		});

		//Import Single form.
		$(document).on("click", ".evf-fm-import-single", function (e) {
			e.preventDefault();
			var form_id = $(this).data("form-id");
			evf_fm_import_forms($(this), [form_id]);
		});
		//Import Selected form.
		$(document).on("click", ".evf-fm-import-selected-btn", function (e) {
			e.preventDefault();
			var table = $(document).find(".evf-fm-forms-table"),
				selected_forms = $(table).find(
					"td .evf-fm-select-single:checked"
				),
				formIds = [];
			$.each(selected_forms, function (index, form) {
				var formID = $(form).data("form-id");
				formIds.push(formID);
			});
			if (formIds.length === 0) {
				return;
			}
			evf_fm_import_forms($(this), formIds);
		});
		/**
		 *Form migrator impot forms ajax request.
		 * @param {object} $this
		 * @param {array} form_ids
		 * @returns
		 */
		function evf_fm_import_forms($this, form_ids) {
			var formSlug = $(document)
				.find(".evf-fm-forms-table")
				.data("form-slug");

			if (typeof formSlug === "undefined" || formSlug === "") {
				return;
			}

			var data = {
				action: "everest_forms_form_migrator",
				form_slug: formSlug,
				form_ids: form_ids,
				security: everest_forms_form_migrator.evf_form_migrator_nonce,
			};

			$.ajax({
				url: everest_forms_form_migrator.ajax_url,
				type: "POST",
				dataType: "JSON",
				data: data,
				beforeSend: function () {
					var spinner =
						'<i class="evf-loading evf-loading-active"></i>';
					$this.closest("td").append(spinner);
					$(".everest-froms-import_notice").remove();
				},
				success: function (res) {
					$(".everest-froms-import_notice").remove();
					$(".evf-loading").remove();
					var message_string = "";
					if (true === res.success) {
						var formDatas = res.data.form_data;
						$.each(formDatas, function (formId, formData) {
							if (formData !== false) {
								var entryButton = $(
									'button[data-form-id="' + formId + '"]'
								);
								var importedNotice = $(
									'p[data-form-id="' + formId + '"]'
								);

								$(entryButton).prop("disabled", false);
								$(importedNotice).text("Yes");
							}
						});
						message_string =
							'<div id="message" class="updated inline everest-froms-import_notice"><p><strong>' +
							res.data.message +
							"</strong></p></div>";
					} else {
						message_string =
							'<div id="message" class="error inline everest-froms-import_notice"><p><strong>' +
							res.data.message +
							"</strong></p></div>";
					}
					$(document)
						.find("#evf-fm-forms-list-container")
						.prepend(message_string);
				},
			});
		}

		//Import Entry.
		$(document).on("click", ".evf-fm-import-entry", function (e) {
			e.preventDefault();
			var $this = $(this),
				formSlug = $(document)
					.find(".evf-fm-forms-table")
					.data("form-slug"),
				formID = $this.data("form-id");

			if (typeof formSlug === "undefined" || formSlug === "") {
				return;
			}

			var data = {
				action: "everest_forms_form_entry_migrator",
				form_slug: formSlug,
				form_id: formID,
				security:
					everest_forms_form_migrator.evf_form_entry_migrator_nonce,
			};
			$.ajax({
				url: everest_forms_form_migrator.ajax_url,
				type: "POST",
				dataType: "JSON",
				data: data,
				beforeSend: function () {
					var spinner =
						'<i class="evf-loading evf-loading-active"></i>';
					$this.closest("td").append(spinner);
					$(".everest-froms-import_notice").remove();
				},
				success: function (res) {
					$(".everest-froms-import_notice").remove();
					$(".evf-loading").remove();
					var message_string = "";
					if (true === res.success) {
						message_string =
							'<div id="message" class="updated inline everest-froms-import_notice"><p><strong>' +
							res.data.message +
							"</strong></p></div>";
					} else {
						message_string =
							'<div id="message" class="error inline everest-froms-import_notice"><p><strong>' +
							res.data.message +
							"</strong></p></div>";
					}
					$(document)
						.find("#evf-fm-forms-list-container")
						.prepend(message_string);
				},
			});
		});
	});
});
