/*global jQuery, Backbone, _ */
( function ( $, Backbone, _, evf_form_modal_data ) {
	'use strict';

	/**
	 * RestaurantPress Backbone Modal plugin
	 *
	 * @param {object} options
	 */
	$.fn.EVFBackboneModal = function ( options ) {
		return this.each(function () {
			( new $.EVFBackboneModal($(this), options) );
		});
	};

	/**
	 * Initialize the Backbone Modal
	 *
	 * @param {object} element [description]
	 * @param {object} options [description]
	 */
	$.EVFBackboneModal = function ( element, options ) {
		// Set settings
		var settings = $.extend({}, $.EVFBackboneModal.defaultOptions, options);

		if ( settings.template ) {
			new $.EVFBackboneModal.View({
				target: settings.template,
				string: settings.variable
			});
		}
	};

	/**
	 * Set default options
	 *
	 * @type {object}
	 */
	$.EVFBackboneModal.defaultOptions = {
		template: '',
		variable: {}
	};

	/**
	 * Create the Backbone Modal
	 *
	 * @return {null}
	 */
	$.EVFBackboneModal.View = Backbone.View.extend({
		tagName: 'div',
		id: 'evf-backbone-modal-dialog',
		_target: undefined,
		_string: undefined,
		events: {
			'click .modal-close': 'closeButton',
			'click #btn-ok': 'addButton',
			'touchstart #btn-ok': 'addButton',
			'keydown': 'keyboardActions'
		},
		resizeContent: function () {
			var $content = $('.evf-backbone-modal-content').find('article');
			var max_h = $(window).height() * 0.75;

			$content.css({
				'max-height': max_h + 'px'
			});
		},
		initialize: function ( data ) {
			var view = this;
			this._target = data.target;
			this._string = data.string;
			_.bindAll(this, 'render');
			this.render();

			$(window).resize(function () {
				view.resizeContent();
			});
		},
		render: function () {
			var template = wp.template(this._target);

			this.$el.append(
				template(this._string)
			);

			$(document.body).css({
				'overflow': 'hidden'
			}).append(this.$el);

			this.resizeContent();
			this.$('.evf-backbone-modal-content').attr('tabindex', '0').focus();

			$(document.body).trigger('init_tooltips');

			$(document.body).trigger('evf_backbone_modal_loaded', this._target);
		},
		closeButton: function ( e ) {
			e.preventDefault();
			$(document.body).trigger('evf_backbone_modal_before_remove', this._target);
			this.undelegateEvents();
			$(document).off('focusin');
			$(document.body).css({
				'overflow': 'auto'
			});
			this.remove();
			$(document.body).trigger('evf_backbone_modal_removed', this._target);
		},
		addButton: function ( e ) {
			$(document.body).trigger('evf_backbone_modal_response', [ this._target, this.getFormData() ]);

			var data = {
				action: 'everest_forms_new_form',
				security: evf_form_modal_data.evf_new_form_nonce,
				form_name: $('#evf-modal-form-name').val()
			};
			$.ajax({
				url: evf_form_modal_data.ajax_url,
				data: data,
				type: 'POST',
				beforeSend: function () {

				},
				success: function ( response ) {
					var message = '';
					var type = 'success';
					debugger;
					if ( typeof response.success !== 'undefined' ) {
						if ( response.data.id > 0 ) {

							message = 'Form successfully created. Redirecting....';
							setTimeout(function () {
								window.location = response.data.redirect;
							}, 1000)
							//
						} else {
							message = 'Unknown error ! Could not create a form';
							type = 'error';
						}

					} else {

						type = 'error';
						message = 'Unknown error ! Could not create a form';

					}
					var message_node = '<div id="message" class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
					$('#evf-backbone-modal-dialog').find('#message').remove();
					$('#evf-backbone-modal-dialog').find('article').append(message_node);
				}
			});
			//this.closeButton(e);
		},
		getFormData: function () {
			var data = {};

			$(document.body).trigger('evf_backbone_modal_before_update', this._target);

			$.each($('form', this.$el).serializeArray(), function ( index, item ) {
				if ( item.name.indexOf('[]') !== -1 ) {
					item.name = item.name.replace('[]', '');
					data[ item.name ] = $.makeArray(data[ item.name ]);
					data[ item.name ].push(item.value);
				} else {
					data[ item.name ] = item.value;
				}
			});

			return data;
		},
		keyboardActions: function ( e ) {
			var button = e.keyCode || e.which;

			// Enter key
			if ( 13 === button && !( e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) ) ) {
				this.addButton(e);
			}

			// ESC key
			if ( 27 === button ) {
				this.closeButton(e);
			}
		}
	});

	$('body').on('click', '.evf-add-new', function ( event ) {
		$(this).EVFBackboneModal({
			template: 'evf-add-new-form',
			variable: {
				test: 'tet'
			}
		});
		return false;

	})
}(jQuery, Backbone, _, window.evf_form_modal_data));
