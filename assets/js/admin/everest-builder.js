//Plugin made by Umesh Ghimire
(function ( $ ) {
	$.fn.EverestBuilder = function ( options ) {

		return this.each(function () {
			// Bob's default settings:
			var defaults = {
				fields: [],
				form_settings: {},
				appearance_settings: {},
				dragged_fields: {}
			};
			var settings = $.extend({}, defaults, options);


			var self = {
				draggableNode: function () {
					var draggable_node = $('<div class="eb-draggable"/>');
					$.each(settings.fields, function ( field_index, field_value ) {
						if ( 'undefined' !== typeof field_value.type ) {
							var field_node = $.fn.EverestBuilder.nodeGenerator(field_value.type, field_value, settings);
							draggable_node.append(field_node);
						} else {
							eb_notices('Undefined property type of field');
						}
					})

				},
				// It will render the builder node
				renderNode: function () {
					var draggable_node = this.draggableNode();
				},
				init: function () {

					this.renderNode();

				},

			};

			self.init();

		});

	};


	function eb_notices ( notice, type ) {

		var color = '#a94442';

		if ( 'undefined' !== typeof type && 'info' === type ) {
			color = '#31708f';
		}
		console.log('%c ' + 'EverestBuilder - Notice : ' + notice, 'color: ' + color);
	}

	function eb_error ( error ) {
		throw 'EverestBuilder - Error : ' + error;
	}
})(jQuery);

(function ( $ ) {
	// Node methods

	$.fn.EverestBuilder.nodeGenerator = function ( type, field, $this ) {

		if ( 'undefined' === typeof $.fn.EverestBuilder.nodeGenerator[ type ] ) {

			eb_error('Could not find method - ' + type);
		}
		$.fn.EverestBuilder.nodeGenerator[ type ](field, $this);

	};

	$.fn.EverestBuilder.nodeGenerator.text = function ( field, instance ) {

		var defaults = {
			type: 'text',
			field_settings: {
				required: true,
				default: 'LOL World',
				meta_key: 'this_is_field_meta_key',
			}
		};
		var options = $.extend({}, defaults, field);
		console.log(field);
		console.log(options);

	};
})(jQuery);



