/* global wp, _wpCustomizeBackground, _evfCustomizeControlsL10n */
( function( $, api, data ) {
	'use strict';

	// Modify customize info.
	api.bind( 'ready', function() {
		$( '#customize-info' ).find( '.panel-title.site-title' ).text( data.panelTitle );
		$( '#customize-info' ).find( '.customize-panel-description:first' ).text( data.panelDescription );
	} );

	/**
	 * A toggle switch control.
	 *
	 * @class    wp.customize.ToggleControl
	 * @augments wp.customize.Control
	 */
	api.ToggleControl = api.Control.extend( {

		/**
		 * Initialize behaviors.
		 *
		 * @returns {void}
		 */
		ready: function() {
			var control = this;

			control.container.on( 'change', 'input:checkbox', function() {
				var value = this.checked ? true : false;
				control.setting.set( value );
			} );
		}
	});

	/**
	 * A range slider control.
	 *
	 * @class    wp.customize.SliderControl
	 * @augments wp.customize.Class
	 */
	api.SliderControl = api.Control.extend( {

		/**
		 * Initialize behaviors.
		 *
		 * @returns {void}
		 */
		ready: function ready() {
			var control    = this,
				$container = control.container,
				$slider    = $container.find( '.everest-forms-slider' ),
				$input     = $container.find( '.everest-forms-slider-input input[type="number"]' ),
				min        = Number( $input.attr( 'min' ) ),
				max        = Number( $input.attr( 'max' ) ),
				step       = Number( $input.attr( 'step' ) );

			$slider.slider( {
				range : 'min',
				min   : min,
				max   : max,
				value : $input.val(),
				step  : step,
				slide: function ( event, ui ) {
					// Trigger keyup in input.
					$input.val( ui.value ).keyup();
				},
				change: function ( event, ui ) {
					control.setting.set( ui.value );
				}
			} );

			control.container.on( 'click', '.reset', function(e) {
				e.preventDefault();
				$slider.slider( 'option', 'value', control.params.default );
			} );

			control.container.on( 'change keyup input', 'input.slider-input', function(e) {
				if ( ( 'keyup' === e.type || 'input' === e.type ) && '' === $( this ).val() ) {
					return;
				}
				$slider.slider( 'option', 'value', $( this ).val() );
			} );
		}
	} );

	/**
	 * A enhanced select2 control.
	 *
	 * @class    wp.customize.Select2Control
	 * @augments wp.customize.Class
	 */
	api.Select2Control = api.Control.extend( {

		/**
		 * Initialize behaviors.
		 *
		 * @returns {void}
		 */
		ready: function ready() {
			var control    = this,
			$container = control.container,
			$select_input = $container.find( '.evf-select2' );

			// Enhanced Select2.
			$select_input.select2({
				minimumResultsForSearch: 10,
				allowClear:  $select_input.data( 'allow_clear' ) ? true : false,
				placeholder: $select_input.data( 'placeholder' )
			});
		}
	} );

	/**
	 * A dimension control.
	 *
	 * @class    wp.customize.DimensionControl
	 * @augments wp.customize.Class
	 */
	api.DimensionControl = api.Control.extend( {

		/**
		 * Initialize behaviors.
		 *
		 * @returns {void}
		 */
		ready: function() {
			var control    = this,
				$container = control.container,
				$inputs    = $container.find( '.dimension-input' );

			// Hide except first responsive item
			control.container.find('.responsive-tabs li:not(:first)').hide();

			control.container.on( 'keyup input', '.dimension-input', function () {
				var this_input = $( this ),
					key        = this_input.attr('name'),
					min        = parseInt( this_input.attr('min') ),
					max        = parseInt( this_input.attr('max') );

				// Number validation for min or max value.
				if( this_input.val() < min ) {
					this_input.val( this_input.attr('min') );
				}
				if( this_input.val() > max ) {
					this_input.val( this_input.attr('max') );
				}
				if( control.is_anchor() ){
					$inputs.each( function(index, input) {
						$( input ).val( this_input.val() );
						control.saveValue( $( input ).attr('name'), this_input.val() );
					} );
				} else {
					control.saveValue( key, this_input.val() );
				}
			} );

			control.container.on( 'change', '.dimension-unit-item input[type="radio"]', function() {
				control.saveValue( 'unit', $( this ).val() );
			} );

			control.container.on( 'change', '.dimension-anchor', function() {
				if( $( this ).is( ':checked' ) ) {
					$( this ).parent( 'label' ).removeClass( 'unlinked' ).addClass( 'linked' );
					$inputs.first().trigger( 'keyup' );
				}else{
					$( this ).parent( 'label' ).removeClass( 'linked' ).addClass( 'unlinked' );
				}
			} );

			control.container.on( 'change', '.responsive-tab-item input[type="radio"]', function() {
				var value = control.get_value();
				var this_value = $(this).val();

				if ( value[this_value] !== undefined ) {
					$inputs.each( function( index, input ) {
						$( input ).val( value[this_value][$( input ).attr('name')] );
					} );
					control.container.find( '.dimension-unit-item input[value="' + value[this_value].unit + '"]' ).attr( 'checked', 'checked' );
				} else{
					$inputs.val( '' );
				}
				control.saveValue( 'top', $container.find( 'input[name="top"]' ).val() );
			} );

		// Dimension reset
		control.container.on("click", ".reset", function (e) {
			e.preventDefault();
			$inputs.each(function (index,input) {
			if(control.params.default.desktop) {
				$(input).val(control.params.default.desktop[$(input).attr("name")]);
				control.saveValue(
					$(input).attr("name"),
					control.params.default.desktop[$(input).attr("name")]
				);
			} else {
				$(input).val(control.params.default[$(input).attr("name")]);
				control.saveValue(
					$(input).attr("name"),
					control.params.default[$(input).attr("name")]
				);
			 }
			});
		});

			// Hide show buttons.
			control.container.on( 'click', '.responsive-tab-item input[type="radio"]', function() {
				var $this = $( this );
				var current_tab = $this.val();
				var $all_responsive_tabs = $('#customize-controls').find('.responsive-tab-item input[type="radio"][value="' + current_tab + '"]').prop('checked', true);
				$all_responsive_tabs.each(function(index, element) {
					var $tab_item = $( element ).closest( '.responsive-tab-item' ).closest('li');
					if( $tab_item.index() === 0 ){
						$tab_item.siblings().toggle();
					}
				} );
				// Set the toggled device.
				api.previewedDevice.set( current_tab );
			} );
		},

		/**
		 * Returns anchor status.
		 */
		is_anchor: function() {
			return $( this.container ).find( '.dimension-anchor' ).is(':checked');
		},

		/**
		 * Returns responsive selected.
		 */
		selected_responsive: function() {
			return $( this.container ).find( '.responsive-tab-item input[type="radio"]:checked' ).val();
		},

		/**
		 * Returns Unit selected.
		 */
		selected_unit: function() {
			return $( this.container ).find( '.dimension-unit-item input[type="radio"]:checked' ).val();
		},

		/**
		 * Returns Value Object.
		 */
		get_value: function() {
			return Object.assign({}, this.setting._value);
		},

		/**
		 * Saves the value.
		 */
		saveValue: function ( property, value ) {
			var control = this,
				input   = control.container.find('.dimension-hidden-value' ),
				val     = control.get_value();

			if ( control.params.responsive === true ) {
				if ( undefined === val[control.selected_responsive()] ) {
					val[control.selected_responsive()] = {};
				}

				val[control.selected_responsive()][property] = value;
				if ( control.params.unit_choices.length > 0 ) {
					val[control.selected_responsive()].unit = control.selected_unit();
				}
			} else{
				val[property] = value;
				if( Object.keys(control.params.unit_choices).length > 0 ) {
					val.unit = control.selected_unit();
				}
			}

			jQuery( input ).val( JSON.stringify( val ) ).trigger( 'change' );
			control.setting.set( val );
		}
	} );

	/**
	 * An image checkbox control.
	 *
	 * @class    wp.customize.ImageCheckboxControl
	 * @augments wp.customize.Class
	 */
	api.ImageCheckboxControl = api.Control.extend( {

		/**
		 * Initialize behaviors.
		 *
		 * @returns {void}
		 */
		ready: function ready() {
			var control    = this,
				$container = control.container;

			$container.on('change', 'input[type="checkbox"]', function() {
				control.saveValue( $( this ).val(), $( this ).is( ':checked' ) );
			} );
		},

		/**
		 * Saves the value.
		 */
		saveValue: function ( property, value ) {
			var control = this,
				input   = control.container.find('.image-checkbox-hidden-value' ),
				val     = control.params.value;

			val[property] = value;
			val = Object.assign({}, val);

			jQuery( input ).val( JSON.stringify( val ) ).trigger( 'change' );
			control.setting.set( val );
		}
	} );


	api.ColorPaletteControl = api.Control.extend({
        ready: function() {
            var control = this;

            // Handle changes to the color palette checkboxes
            control.container.on('change', 'input[type="checkbox"]', function() {
                var key = $(this).data('key');
                var value = $(this).is(':checked');
                control.saveValue(key, value);
            });

            // Check all checkboxes when the label is clicked
            control.container.on('click', '.color-palette-label', function() {
                control.container.find('input[type="checkbox"]').each(function() {
                    if (!$(this).is(':checked')) {
                        $(this).prop('checked', true).change();
                    }
                });
            });
        },
        saveValue: function(property, value) {
            var control = this;
            var val = control.setting.get();

            if (typeof val !== 'object') {
                val = {};
            }

            val[property] = value;
            control.setting.set(val);
        }
    });


	api.controlConstructor = $.extend(
		api.controlConstructor, {
			'evf-color': api.ColorControl,
			'evf-toggle': api.ToggleControl,
			'evf-slider': api.SliderControl,
			'evf-select2': api.Select2Control,
			'evf-dimension': api.DimensionControl,
			'evf-background': api.BackgroundControl,
			'evf-image_checkbox': api.ImageCheckboxControl,
			'evf-background_image': api.BackgroundImageControl,
			'evf-color-palette': api.ColorPaletteControl
		}
	);

	$( function() {

		// Control visibility for default controls.
		$.each( ['wrapper', 'field_styles', 'checkbox_radio_styles', 'button', 'success_message', 'error_message', 'validation_message'], function( i, type ) {
			$.each( {
				'border_type': {
					controls: [ 'border_width', 'border_color' ],
					callback: function( to ) { return 'none' !== to; }
				},
				'style_variation': {
					controls: [ 'size', 'color', 'checked_color' ],
					callback: function( to ) { return 'default' !== to; },
				},
				'background_image': {
					controls: [ 'background_preset', 'background_position', 'background_size', 'background_repeat', 'background_attachment' ],
					callback: function( to ) { return !! to; }
				},
				'show_submission_message': {
					controls: [ 'font_size', 'font_style', 'text_alignment', 'font_color', 'background_color', 'border_type', 'border_width', 'border_color', 'border_radius'  ],
					callback: function( to ) { return !! to; }
				}
			}, function( settingId, o ) {
				api( 'everest_forms_styles[' + data.form_id + '][' + type + '][' + settingId + ']', function( setting ) {
					$.each( o.controls, function( i, controlId ) {
						api.control( 'everest_forms_styles[' + data.form_id + '][' + type + '][' + controlId + ']', function( control ) {
							var visibility = function( to ) {
								control.container.toggle( o.callback( to ) );
							};

							visibility( setting.get() );
							setting.bind( visibility );
						} );
					} );
				} );
			} );
		} );

		api.control( 'everest_forms_styles[' + data.form_id + '][wrapper][background_preset]', function( control ) {
			var visibility, defaultValues, values, toggleVisibility, updateSettings, preset;

			visibility = { // position, size, repeat, attachment
				'default': [ false, false, false, false ],
				'fill': [ true, false, false, false ],
				'fit': [ true, false, true, false ],
				'repeat': [ true, false, false, true ],
				'custom': [ true, true, true, true ]
			};

			defaultValues = [
				_wpCustomizeBackground.defaults['default-position-x'],
				_wpCustomizeBackground.defaults['default-position-y'],
				_wpCustomizeBackground.defaults['default-size'],
				_wpCustomizeBackground.defaults['default-repeat'],
				_wpCustomizeBackground.defaults['default-attachment']
			];

			values = { // position_x, position_y, size, repeat, attachment
				'default': defaultValues,
				'fill': [ 'left', 'top', 'cover', 'no-repeat', 'fixed' ],
				'fit': [ 'left', 'top', 'contain', 'no-repeat', 'fixed' ],
				'repeat': [ 'left', 'top', 'auto', 'repeat', 'scroll' ]
			};

			// @todo These should actually toggle the active state, but without the preview overriding the state in data.activeControls.
			toggleVisibility = function( preset ) {
				_.each( [ 'background_position', 'background_size', 'background_repeat', 'background_attachment' ], function( i, controlId ) {
					var control = api.control( 'everest_forms_styles[' + data.form_id + '][wrapper][' + controlId + ']' );
					if ( control ) {
						control.container.toggle( visibility[ preset ][ i ] );
					}
				} );
			};

			updateSettings = function( preset ) {
				_.each( [ 'background_position_x', 'background_position_y', 'background_size', 'background_repeat', 'background_attachment' ], function( settingId, i ) {
					var setting = api( 'everest_forms_styles[' + data.form_id + '][wrapper][' + settingId + ']' );
					if ( setting ) {
						setting.set( values[ preset ][ i ] );
					}
				} );
			};

			preset = control.setting.get();
			toggleVisibility( preset );

			control.setting.bind( 'change', function( preset ) {
				toggleVisibility( preset );
				if ( 'custom' !== preset ) {
					updateSettings( preset );
				}
			} );
		} );

		api.control( 'everest_forms_styles[' + data.form_id + '][wrapper][background_repeat]', function( control ) {
			control.elements[0].unsync( api( 'everest_forms_styles[' + data.form_id + '][wrapper][background_repeat]' ) );

			control.element = new api.Element( control.container.find( 'input' ) );
			control.element.set( 'no-repeat' !== control.setting() );

			control.element.bind( function( to ) {
				control.setting.set( to ? 'repeat' : 'no-repeat' );
			} );

			control.setting.bind( function( to ) {
				control.element.set( 'no-repeat' !== to );
			} );
		} );

		api.control( 'everest_forms_styles[' + data.form_id + '][wrapper][background_attachment]', function( control ) {
			control.elements[0].unsync( api( 'everest_forms_styles[' + data.form_id + '][wrapper][background_attachment]' ) );

			control.element = new api.Element( control.container.find( 'input' ) );
			control.element.set( 'fixed' !== control.setting() );

			control.element.bind( function( to ) {
				control.setting.set( to ? 'scroll' : 'fixed' );
			} );

			control.setting.bind( function( to ) {
				control.element.set( 'fixed' !== to );
			} );
		} );

		api.control( 'everest_forms_styles[' + data.form_id + '][template]', function( control ) {
			control.elements[0].bind( function( newval ) {
				handleTemplate( newval );
			} );
		} );

		var handleTemplate = function (template) {
			var setting_link = 'everest_forms_styles[' + data.form_id + ']';

			if ( 'undefined' === typeof _evfCustomizeControlsL10n.templates[ template ] ) {
				return false;
			}

			var template_data = _evfCustomizeControlsL10n.templates[ template ].data;

			if ( template_data ) {
				$.each(template_data, function (section, section_values) {
					$.each(section_values, function (control_key, control_value) {
						renderControls(
							setting_link + "[" + section + "][" + control_key + "]",
							control_value
						);
					});
				});
			}
		};


		var renderControls = function (key, values) {
			api.control(key, function (control) {
				var $container = control.container;
				control.setting.set(values);

				switch (control.params.type) {
					case "evf-slider":
						var $slider = $container.find(
							".everest-forms-slider"
						);
						$slider.slider("option", "value", values);
						break;
					case "evf-select2":
						var $select = $container.find(".evf-select2");
						$select.trigger("change");
						break;
					case "evf-image_checkbox":
						var $input = $container.find(
							".image-checkbox-hidden-value"
						);

						var new_value = values;
						if ( 'string' !== typeof new_value ) {
							new_value = JSON.stringify( values );
						} else {
							values = JSON.parse( values );
						}
						console.log(values);
						$input.val( new_value ).trigger("change");

						$.each(values, function (index, value) {
							$container
								.find(
									'.image-checkbox-wrapper input[value="' +
										index +
										'"]'
								)
								.prop("checked", value);
						});
						break;
					case "evf-color-palette":
						var $input = $container.find(
							".color-palette-hidden-value"
						);

						var new_value = values;
						if ( 'string' !== typeof new_value ) {
							new_value = JSON.stringify( values );
						} else {
							values = JSON.parse( values );
						}

						$input.val( new_value ).trigger("change");

						$.each(values, function (index, value) {
							$container
								.find(
									'.color-palette-wrapper input[value="' +
										index +
										'"]'
								)
								.prop("checked", value);
						});
						break;
					case "evf-dimension":
						var selected_device = $container
							.find(
								'.responsive-tab-item input[type="radio"]:checked'
							)
							.val();

						if ("undefined" === typeof selected_device) {
							$.each(values, function (index, value) {
								$container
									.find(
										'input.dimension-input[name="' +
											index +
											'"]'
									)
									.val(value);
							});
						} else {
							if (
								"undefined" !== typeof values[selected_device]
							) {
								$.each(
									values[selected_device],
									function (index, value) {
										$container
											.find(
												'input.dimension-input[name="' +
													index +
													'"]'
											)
											.val(value);
									}
								);
							}
						}
						break;
				}
			});
		};

		$(function () {
			/**
			 * Render fields to create style templates.
			 */
			var render_save_template = function () {
				var form_id = _evfCustomizeControlsL10n.form_id;
				var templates_box = $(
					"#customize-control-everest_forms_styles-" +
						form_id +
						"-template"
				);
				var save_template_container = $(
					"<div id='everest-forms-save-template-container'></div>"
				);

				save_template_container.append(
					$(
						'<span class="customize-control-title">Create Style Template</span>'
					)
				);
				save_template_container.append(
					$(
						'<span class="description customize-control-description">Create a new style template from current styles.</span>'
					)
				);
				save_template_container.append(
					$(
						"<input type='text' id='everest-forms-new-template-name' placeholder='Template Name' />"
					)
				);
				save_template_container.append(
					$(
						"<div><button class='button button-primary' id='everest-forms-save-template-button'>Create</button></div>"
					)
				);

				templates_box.before(save_template_container);
				var save_template_btn = save_template_container.find("div button");

				save_template_btn.bind("click", function (e) {
					e.preventDefault();
					e.stopPropagation();

					if ("disabled" === $("#save.save").attr("disabled")) {
						send_save_template_request( this );
					} else {
						alert(
							"Please save the unsaved changes to create the template."
						);
					}
				});
			};


			/**
			 * Send post ajax request to save template.
			 */
			var send_save_template_request = function ( el ) {
				var template_name_el = $("#everest-forms-new-template-name");
				var template_name = template_name_el.val();

				if (template_name.length) {
					$.post(_evfCustomizeControlsL10n.ajax_url, {
						action: "save_template",
						name: template_name,
						form_id: _evfCustomizeControlsL10n.form_id,
						_nonce: _evfCustomizeControlsL10n.save_nonce,
					}).done(function (response) {


						if ( response.success ) {
							api.control(
								"everest_forms_styles[" + data.form_id + "][template]",
								function (control) {
									control.setting.set( response.data.template_id );
									api.previewer.save();
									api.bind( 'saved', function() {
										location.reload();
									});
								}
							);
						} else {
							alert( response.data.message );
						}
					});
				} else {
					alert("Please provide a suitable template name and try again.");
				}
			};

			/**
			 * Add delete icon to templates.
			 */
			var add_delete_template_icon = function() {
				var form_id = _evfCustomizeControlsL10n.form_id;
				var templates_box = $(
					"#customize-control-everest_forms_styles-" +
						form_id +
						"-template"
				);

				var templates = templates_box.find( '.image-radio-wrapper li' );
				var delete_btn = $( '<span class="evf-delete-template-btn dashicons dashicons-no" title="Delete Template"></span>' );

				templates.each(function() {
					var $this = $(this);

					if ( ! ( ['default', 'layout-two'].includes( $this.find('input').val() ) ) ) {
						var custom_delete_btn = delete_btn.clone().hide();

						custom_delete_btn.bind( 'click', function() {
							var confirm_delete = confirm( 'Are you sure you want to delete this template ?' );

							if ( confirm_delete ) {
								send_delete_template_request( $this );
							}
						});

						$this.append( custom_delete_btn );

						$this.bind( 'mouseover', function() {
							custom_delete_btn.show();
						});

						$this.bind( 'mouseout', function() {
							custom_delete_btn.hide();
						});
					}
				});


				/**
				 * Send post ajax request to delete template.
				 */
				var send_delete_template_request = function( el ) {
					var template = $( el );
					var template_name = template.find('input').val();

					$.post(_evfCustomizeControlsL10n.ajax_url, {
						action: "delete_template",
						name: template_name,
						_nonce: _evfCustomizeControlsL10n.delete_nonce,
					}).done(function (data) {
						if ( data.success ) {
							template.remove();
						}
					});
				}
			}

			render_save_template();
			add_delete_template_icon();
		});
	})

} )( jQuery, wp.customize, _evfCustomizeControlsL10n );
