(function ( $ ) {

	var builder_node = $('#everest-builder');

	$(document).trigger("everest_builder_builder_node_filter", [ builder_node ]);

	var builder_config = {
		fields: [
			{
				type: 'text',
				field_settings: {
					required: true,
					default: 'Hello World',
					meta_key: 'this_is_field_meta_key',
				}
			},

		],
		form_settings: {
			form_shortcode: ''
		},
		appearance_settings: {},
		dragged_fields: {}


	};
	builder_node.EverestBuilder(builder_config);

})(jQuery);
