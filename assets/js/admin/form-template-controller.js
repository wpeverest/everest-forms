/* global evf_template_controller */
jQuery(function ($) {
	/**
	 * Template actions.
	 */
	var evf_template_controller = {

		init: function () {
			this.wrapper = $('.everest-forms-form-template-wrapper');
			this.wrapper.find('.everest-forms-tab-nav a').on('click', this.form_template_plan_type);
		},
		form_template_plan_type: function (e) {
			e.preventDefault();
 			var $this = $(e.target);
			var plan_type = $this.attr('data-plan');
			$this.closest('ul').find('li').removeClass('active');
			$this.closest('li').addClass('active');
			var template_wrap = evf_template_controller.wrapper.find('.everest-forms-form-template' );
			template_wrap.attr('data-filter-template', plan_type);
		},
	};
	$(document).ready(function() {
		evf_template_controller.init();
	});

});
