
/* global wp, _wpCustomizeBackground, _evfCustomizeControlsL10n */
( function( $, api, data ) {
	'use strict';

	// Modify customize info.
	api.bind( 'ready', function() {
		$( '#customize-info' ).find( '.panel-title.site-title' ).text( data.panelTitle );
		$( '#customize-info' ).find( '.customize-panel-description:first' ).text( data.panelDescription );
		$('#customize-controls').addClass('wpeverest-customizer-style');

		function getQueryParam(param) {
			var queryString = window.location.search.substring(1);
			var params = new URLSearchParams(queryString);
			return params.get(param);
		}


		var formId = getQueryParam('form_id');
		var fieldLabelSelectorStart = '#customize-control-everest_forms_styles-' + formId + '-typography-field_labels_font_size';
		var fieldLabelSelectorEnd = '#customize-control-everest_forms_styles-' + formId + '-typography-field_labels_padding';
		var subfieldLabelSelectorStart = '#customize-control-everest_forms_styles-' + formId + '-typography-field_sublabels_font_size';
		var subfieldLabelSelectorEnd = '#customize-control-everest_forms_styles-' + formId + '-typography-field_sublabels_padding';
		var fontColorTypography = '#customize-control-everest_forms_styles-' + formId + '-typography-field_labels_font_color';
		var targetSelector = fontColorTypography + ' .wp-color-result';
		var currentStyle = $(targetSelector).attr('style');
		 if (currentStyle) {
        var styleArray = currentStyle.split(';');

        var updatedStyleArray = styleArray.map(function(style) {
            if (style.trim() !== '') {
                var [property, value] = style.split(':');
                return `${property.trim()}: ${value.trim()} !important`;
            }
            return '';
        });

        var updatedStyle = updatedStyleArray.join('; ');
		$(targetSelector).removeAttr('style');
        $(targetSelector).attr('style', updatedStyle);
    }
		if (formId) {
			setTimeout(function() {

				var globalSelectors = [
					{
						className: 'everest-forms-border_type_option',
						selectors: [
						  '-form_container-border_type',
						  '-field_styles-border_type',
						  '-file_upload_styles-border_type',
						  '-button-border_type',
						  '-success_message-border_type',
						  '-error_message-border_type',
						  '-validation_message-border_type'
						]
					},
					{
					  className: 'everest-forms-padding_option',
					  selectors: [
						'-form_container-padding',
						'-typography-field_labels_padding',
						'-typography-field_sublabels_padding',
						'-typography-field_styles_padding',
						'-typography-section_title_padding',
						'-typography-file_upload_padding',
						'-typography-button_padding'
					  ]
					},
					{
					  className: 'everest-forms-border_option',
					  selectors: [
						'-form_container-border_width',
						'-form_container-border_color',
						'-field_styles-border_width',
						'-file_upload_styles-border_width',
						'-button-border_width',
						'-success_message-border_width',
						'-success_message-border_color',
						'-error_message-border_width',
						'-error_message-border_color',
						'-validation_message-border_width',
						'-validation_message-border_color'
					  ]
					},
					{
					  className: 'everest-forms-background_image_option',
					  selectors: [
						'-form_container-background_preset',
						'-form_container-background_position',
						'-form_container-background_size',
						'-form_container-background_repeat',
						'-form_container-background_attachment',
						'-form_container-opacity'
					  ]
					},
					{
						className: 'everest-forms-typography_font_option',
						selectors: [
							'-typography-field_labels_font_size',
							'-typography-field_labels_font_color',
							'-typography-field_labels_font_style',
							'-typography-field_labels_text_alignment',
							'-typography-field_labels_line_height',
							'-typography-field_labels_margin',
							'-typography-field_sublabels_font_size',
							'-typography-field_sublabels_font_color',
							'-typography-field_sublabels_font_style',
							'-typography-field_sublabels_text_alignment',
							'-typography-field_sublabels_line_height',
							'-typography-field_sublabels_margin',
							'-typography-field_sublabels_font_size',
							'-typography-field_styles_font_size',
							'-typography-field_styles_font_color',
							'-typography-field_styles_font_size',
							'-typography-field_styles_placeholder_font_color',
							'-typography-field_styles_font_style',
							'-typography-field_styles_alignment',
							'-typography-field_styles_border_color',
							'-typography-field_styles_border_focus_color',
							'-typography-field_styles_background_color',
							'-typography-field_styles_margin',
							'-typography-field_description_font_size',
							'-typography-field_description_font_color',
							'-typography-field_description_font_style',
							'-typography-field_description_text_alignment',
							'-typography-field_description_line_height',
							'-typography-field_description_margin',
							'-typography-section_title_font_size',
							'-typography-section_title_font_color',
							'-typography-section_title_font_style',
							'-typography-section_title_text_alignment',
							'-typography-section_title_line_height',
							'-typography-section_title_margin',
							'-typography-file_upload_font_size',
							'-typography-file_upload_font_color',
							'-typography-file_upload_background_color',
							'-typography-file_upload_icon_background_color',
							'-typography-file_upload_icon_color',
							'-typography-file_upload_border_color',
							'-typography-file_upload_margin',
							'-typography-checkbox_radio_font_size',
							'-typography-checkbox_radio_font_color',
							'-typography-checkbox_radio_font_style',
							'-typography-checkbox_radio_alignment',
							'-typography-checkbox_radio_size',
							'-typography-checkbox_radio_color',
							'-typography-checkbox_radio_checked_color',
							'-typography-checkbox_radio_margin',
							'-typography-button_font_size',
							'-typography-button_font_style',
							'-typography-button_font_color',
							'-typography-button_hover_font_color',
							'-typography-button_background_color',
							'-typography-button_hover_background_color',
							'-typography-button_alignment',
							'-typography-button_border_color',
							'-typography-button_border_hover_color',
							'-typography-button_line_height',
							'-typography-button_margin',
							'-success_message-font_size',
							'-success_message-font_style',
							'-success_message-text_alignment',
							'-error_message-font_size',
							'-error_message-font_style',
							'-error_message-text_alignment',
							'-validation_message-font_size',
							'-validation_message-font_style',
							'-validation_message-text_alignment'
						]
					}
				  ];

				  globalSelectors.forEach(function(group) {
					group.selectors.forEach(function(selector) {
					  var allSelector = '#customize-control-everest_forms_styles-' + formId + selector;
					  $(allSelector).addClass(group.className);
					});
				  });
			}, 3000);

			if (_evfCustomizeControlsL10n.is_pro === "" ){
			const isTemplatePro = `image-radio-everest_forms_styles[${formId}][template]`;
			document.querySelectorAll(`input[name="${isTemplatePro}"]`)
				.forEach(element => {
					if (['layout-five','layout-six','layout-seven','layout-eight','layout-ten','layout-eleven'].includes(element.value)) {
						element.classList.add('everest-forms-pro-template');
					}
				});
			}
		}

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
		ready: function () {
			var control = this;
			if (control.container.find('label').hasClass('evf-pro-palette')) {
				return;
			}
			control.container.on('change', 'input[type="checkbox"]', function () {
				var key = $(this).data('key');
				var value = $(this).val();

				var $saveButtonWrapper = $('#customize-save-button-wrapper');

				var name = $(this).attr('name').match(/\[(\w+)\]$/)?.[1];

				$saveButtonWrapper
					.find('input[type="submit"]')
					.on('click', function () {
						if (name) {
							document.cookie = `color_palette=${localStorage.getItem('color_palette', name)}; path=/`;
						}
					});
				document.cookie = `color_palette_save=${name}; path=/`;
				localStorage.setItem('color_palette', name);
				$(this).closest('li').find('label').addClass('evf-active-color-palette');
				control.saveValue(key, value);
			});


			control.container.on('click', '.color-palette', function () {
				var $currentGroup = $(this).closest('.customize-control-evf-color_palette');

				$('.customize-control-evf-color_palette').not($currentGroup).find('label').removeClass('evf-active-color-palette');
				$('.customize-control-evf-color_palette').not($currentGroup).find('input[type="checkbox"]').prop('checked', false);
				$(this).find('input[type="checkbox"]').prop('checked', true).change();
			});

			control.container.on('click', '.color-palette-edit-icon', function () {
				var labelElement = $(this).closest('label');
				var dataCustom = labelElement.attr('data-custom');
				var iconElement = $(this);
				var editInterface = control.container.find('.color-palette-edit-interface');

				if (editInterface.length) {
					editInterface.remove();
				} else if (dataCustom === undefined) {
					control.showEditInterface();
				}

				if (dataCustom === 'evf-custom-color-palette') {
					if (iconElement.html() === '✎') {
						control.showEditInterface();
						iconElement.html('✖');
					} else {
						iconElement.html('✎');
					}
				} else if (dataCustom === '') {
					$.confirm({
						title: 'Edit Color Palette',
						content: _evfCustomizeControlsL10n.color_palette_edit_title +
							'<br>' +
							'<div style="color: #ff4d4f; padding-top: 10px;">' +
								'<i class="fa fa-exclamation-triangle" aria-hidden="true" style="margin-right: 2px;"></i>' +
								_evfCustomizeControlsL10n.color_palette_edit_description +
							'</div>',
						theme: 'modern',
						type: 'red',
						boxWidth: '30%',
						useBootstrap: false,
						backgroundDismiss: true,
						buttons: {
							cancel: {
								text: 'Cancel',
								btnClass: 'btn-light',
								action: function () {
									iconElement.html('✎');
								}
							},
							confirm: {
								text: 'Confirm',
								btnClass: 'btn-primary',
								action: function () {
									control.showEditInterface();
									iconElement.html('✖');
								}
							}
						},
						onOpenBefore: function() {
						}
					});
				}
			});
		},

		saveValue: function (property, value) {
			var control = this;
			var input = control.container.find('.color-palette-hidden-value');
			var val = control.setting.get();

			// Ensure val is an object
			if (typeof val !== 'object') {
				val = {};
			}


			if (value) {
				val[property] = value;
			}

			$(input).val(JSON.stringify(val)).trigger('change');

			$.each(val, (key, value) => {
				// Check if the key is a string representation of a number
				if (['0', '1', '2', '3', '4', '5'].includes(key) || value === true || value === false) {
					delete val[key];
				}
			});


			// Set the updated val object
			control.setting.set(val);
		},

		showEditInterface: function () {
			var control = this;
			var editInterfaceHtml = `
				<div class="color-palette-edit-interface">
					<input type="text" class="color-palette-name-input" value="${control.params.label}-${Math.floor(10 + Math.random() * 90)}" />
					<div class="color-palette-items">
						${Object.keys(control.params.choices).map(key => `
							<div class="color-palette-edit-item">
								<label for="color-edit-${control.params.id}-${key}" data-key="${control.params.choices[key].name}">
									${control.params.choices[key].name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
								</label>
								<input id="color-edit-${control.params.id}-${key}" type="text" value="${control.params.choices[key].color}" class="color-picker"/>
							</div>
						`).join('')}
					</div>
					<button class="color-palette-save-button">Save</button>
				</div>
			`;

			control.container.append(editInterfaceHtml);
			control.container.find('.color-picker').wpColorPicker();
			control.container.on('click', '.color-palette-save-button', function () {
				if ("disabled" === $("#save.save").attr("disabled")) {
					control.saveEditedColors();
				} else {
					alert("Please save the unsaved changes to create the color palettes.");
				}
			});

			control.container.find('.color-palette-name-input').on('change', function () {
				control.params.label = $(this).val();
			});
		},

		saveEditedColors: function () {
			var control = this;
			var editedColors = {};

			control.container.find('.color-palette-edit-item').each(function () {
				var key = $(this).find('label').data('key').trim().toLowerCase().replace(/color\s+.*/, '');
				var color = $(this).find('.color-picker').val();
				editedColors[key] = color;
			});

			control.setting.set(editedColors);
			control.container.find('.color-palette-hidden-value').val(JSON.stringify(editedColors)).trigger('change');
			$.post(_evfCustomizeControlsL10n.ajax_url, {
				action: 'save_custom_color_palette',
				form_id: _evfCustomizeControlsL10n.form_id,
				_nonce: _evfCustomizeControlsL10n.color_palette_nonce,
				colors: editedColors,
				label: control.params.label
			}).done(function (response) {
				if (response.success) {
					$.alert({
						title: '<span style="color: #28a745; font-weight: bold;"><span class="dashicons dashicons-yes"></span> Success!</span>',
						content: response.data,
						icon: '',
						theme: 'modern',
						type: 'green',
						boxWidth: '20%',
						useBootstrap: false,
						backgroundDismiss: true,
						buttons: {
							OK: {
								text: 'OK',
								btnClass: 'btn-green',
								action: function() {
									window.location.reload();
								}
							}
						},
						onOpenBefore: function() {
							this.$jconfirmBox.css({
								'background': '#ffffff',
								'border-top': '6px solid #198754',
								'border-radius': '10px',
								'box-shadow': '0px 4px 8px rgba(0, 0, 0, 0.1)',
								'padding': '20px',
							});

							this.$content.css({
								'color': '#383838',
								'font-size': '16px',
								'line-height': '24px',
								'text-align': 'center',
							});

							this.$title.css({
								'text-align': 'center',
								'margin-bottom': '10px',
								'color': '#222222'
							});

							this.$btnc.find('.btn-green').css({
								'background': '#2271b1',
								'color': '#fff',
								'border': '1px solid #2271b1',
								'padding': '10px 20px',
								'border-radius': '5px',
								'font-weight': 'bold'
							});
						}
					});
				}
			}).fail(function (error) {});
			control.container.find('.color-palette-edit-interface').remove();
		}
	});




	api.controlConstructor = $.extend(
		api.controlConstructor, {
			'evf-color_palette': api.ColorPaletteControl,
			'evf-color': api.ColorControl,
			'evf-toggle': api.ToggleControl,
			'evf-slider': api.SliderControl,
			'evf-select2': api.Select2Control,
			'evf-dimension': api.DimensionControl,
			'evf-background': api.BackgroundControl,
			'evf-image_checkbox': api.ImageCheckboxControl,
			'evf-background_image': api.BackgroundImageControl

		}
	);

	$( function() {

		// Control visibility for default controls.
		$.each( ['font','form_container','field_styles', 'checkbox_radio_styles', 'button', 'success_message', 'error_message', 'validation_message','typography'], function( i, type ) {
			$.each( {
				'show_theme_font': {
					controls: [ 'font_family'],
					callback: function( to ) { return ! to; }
				},
				'border_type': {
					controls: [ 'border_width', 'border_color' ],
					callback: function( to ) { return 'none' !== to; }
				},
				'checkbox_radio_style_variation': {
					controls: [ 'checkbox_radio_size', 'checkbox_radio_color', 'checkbox_radio_checked_color' ],
					callback: function( to ) { return 'default' !== to; },
				},
				'background_image': {
					controls: [ 'background_preset', 'background_position', 'background_size', 'background_repeat', 'background_attachment','opacity' ],
					callback: function( to ) { return !! to; }
				},
				'show_submission_message': {
					controls: [ 'font_size', 'font_style', 'text_alignment'],
					callback: function( to ) { return !! to; }
				},
				'field_labels' : {
					controls: [ 'field_labels_font_size','field_labels_font_color','field_labels_font_style','field_labels_text_alignment','field_labels_line_height','field_labels_margin','field_labels_padding'],
					callback: function( to ) { return !! to; }
				},
				'field_sublabels' : {
					controls: [ 'field_sublabels_font_size','field_sublabels_font_color','field_sublabels_font_style','field_sublabels_text_alignment','field_sublabels_line_height','field_sublabels_margin','field_sublabels_padding'],
					callback: function( to ) { return !! to; }
				},
				'field_description' : {
					controls: [ 'field_description_font_size','field_description_font_color','field_description_font_style','field_description_text_alignment','field_description_line_height','field_description_margin','field_description_padding'],
					callback: function( to ) { return !! to; }
				},
				'file_upload' : {
					controls: [ 'file_upload_font_size','file_upload_font_color','file_upload_font_style','file_upload_background_color','file_upload_icon_background_color','file_upload_icon_color','file_upload_border_color','file_upload_text_alignment','file_upload_line_height','file_upload_margin','file_upload_padding'],
					callback: function( to ) { return !! to; }
				},
				'checkbox_radio' : {
					controls: [ 'checkbox_radio_font_size','checkbox_radio_font_color','checkbox_radio_font_style','checkbox_radio_text_alignment','checkbox_radio_style_variation','checkbox_radio_line_height','checkbox_radio_margin','checkbox_radio_padding','checkbox_radio_alignment','checkbox_radio_size','checkbox_radio_color','checkbox_radio_checked_color'],
					callback: function( to ) { return !! to; }
				},
				'button' : {
					controls: [ 'button_font_size','button_font_color','button_font_style','button_text_alignment','button_line_height','button_margin','button_padding','button_border_hover_color','button_border_color','button_alignment','button_hover_background_color','button_hover_font_color','button_background_color'],
					callback: function( to ) { return !! to; }
				},
				'field_styles' : {
					controls: [ 'field_styles_font_size','field_styles_font_color','field_styles_font_style','field_styles_alignment','field_styles_border_width','field_styles_border_focus_color','field_styles_border_radius','field_styles_background_color','field_styles_margin','field_styles_padding','field_styles_border_type','field_styles_placeholder_font_color','field_styles_border_color'],
					callback: function( to ) { return !! to; }
				},
				'section_title' : {
					controls: [ 'section_title_font_size','section_title_font_color','section_title_font_style','section_title_text_alignment','section_title_line_height','section_title_margin','section_title_padding'],
					callback: function( to ) { return !! to; }
				},
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

		api.control( 'everest_forms_styles[' + data.form_id + '][form_container][background_preset]', function( control ) {
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
					var control = api.control( 'everest_forms_styles[' + data.form_id + '][form_container][' + controlId + ']' );
					if ( control ) {
						control.container.toggle( visibility[ preset ][ i ] );
					}
				} );
			};

			updateSettings = function( preset ) {
				_.each( [ 'background_position_x', 'background_position_y', 'background_size', 'background_repeat', 'background_attachment' ], function( settingId, i ) {
					var setting = api( 'everest_forms_styles[' + data.form_id + '][form_container][' + settingId + ']' );
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

		api.control( 'everest_forms_styles[' + data.form_id + '][form_container][background_repeat]', function( control ) {
			control.elements[0].unsync( api( 'everest_forms_styles[' + data.form_id + '][form_container][background_repeat]' ) );

			control.element = new api.Element( control.container.find( 'input' ) );
			control.element.set( 'no-repeat' !== control.setting() );

			control.element.bind( function( to ) {
				control.setting.set( to ? 'repeat' : 'no-repeat' );
			} );

			control.setting.bind( function( to ) {
				control.element.set( 'no-repeat' !== to );
			} );
		} );

		api.control( 'everest_forms_styles[' + data.form_id + '][form_container][background_attachment]', function( control ) {
			control.elements[0].unsync( api( 'everest_forms_styles[' + data.form_id + '][form_container][background_attachment]' ) );

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

					if ( ! ( ['default', 'layout-two','layout-three','layout-four','layout-five','layout-six','layout-seven','layout-eight','layout-nine','layout-ten','layout-eleven'].includes( $this.find('input').val() ) ) ) {
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
			 if (_evfCustomizeControlsL10n.is_pro === "1"){
			render_save_template();
			add_delete_template_icon();
			}
		});
	})

} )( jQuery, wp.customize, _evfCustomizeControlsL10n );
