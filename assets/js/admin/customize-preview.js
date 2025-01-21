/* global _evfCustomizePreviewL10n */
( function( $, data ) {
	var settings = 'everest_forms_styles[' + data.form_id + ']',
		container = $( '.everest-forms .evf-container' ),
		field_container = container.find( '.evf-field-container' ),
		field_label = field_container.find( '.evf-field-label' ),
		section_title = field_container.find( '.evf-field-title h3' ),
		field_sub_label = field_container.find( '.everest-forms-field-sublabel' ),
		field_description = field_container.find( '.evf-field-description' ),
		button = container.find('.evf-submit-container button, .evf-submit-container input[type=submit], .evf-submit-container input[type="reset"], .everest-forms-part button' ),
		controls_wrapper = $( parent.document ).find( '#customize-controls' ),
		preview_buttons = controls_wrapper.find( '#customize-footer-actions .devices button' );
		control_selector = 'customize-control-everest_forms_styles-' + data.form_id + '-',
		dimension_directions = ['top', 'right', 'bottom', 'left'];

		// color palette.
		controls_wrapper.find('.color-palette-item input[type="checkbox"]').on('change', function() {
            var parentLi = $(this).closest('.customize-control-evf-color_palette');
            parentLi.find('input[type="checkbox"]:checked').each(function() {
                var color = $(this).val();
				var colorElements = $(this).data('title').replace(/\s+/g, '_').toLowerCase();


				switch (colorElements) {
                    case 'form_background':
						container.css( 'background-color', color );
                        break;
                    case 'field_background':
						container.find( 'input, textarea, select, canvas.evf-signature-canvas, .StripeElement' ).css( 'background-color', color );
                        break;
					case 'field_sublabel':
						field_sub_label.css( 'color', color );
						break;
					case 'field_label':
						field_label.css( 'color', color );
						break;
					case 'button_text':
						button.css( 'color', color );
						break;
					case 'button_background':
						button.css( 'background-color', color );
						break;

                }
            });
        });




	/**
	 * Add Google font link into header.
	 *
	 * @param {string} font_name Google Font Name.
	 */
	function addGoogleFont( font_name ) {
		var font_plus = '',
			font_name = font_name.split( ' ' );

		if ( $.isArray( font_name ) ) {
			font_plus = font_name[0];
			for ( var i = 1; i < font_name.length; i++ ) {
				font_plus = font_plus + '+' + font_name[i];
			}
		}

		if ( 'yes' === data.load_fonts_locally ) {
			$.ajax(
				{
					type: 'POST',
					url: data.ajax_url,
					data: {
						action: 'everest_forms_get_local_font_url',
						font_url: 'https://fonts.googleapis.com/css?family=' + font_plus
					},
					success: response => {
						if ( response.success ) {
							$( '<link href="' + response.data + '" rel="stylesheet" type="text/css">' ).appendTo( 'head' );
						}
					}
				}
			);
		} else {
			$( '<link href="https://fonts.googleapis.com/css?family=' + font_plus + '" rel="stylesheet" type="text/css">' ).appendTo( 'head' );
		}

		$( '<link href="https://fonts.googleapis.com/css?family=' + font_plus + '" rel="stylesheet" type="text/css">' ).appendTo( 'head' );
	}

	/**
	 * Add placeholder styles for form fields.
	 *
	 * @param {string} id         Field ID.
	 * @param {string} color      Color for placeholder.
	 * @param {string} decoration Text decoration for placeholder.
	 */
	function addPlaceholderStyles( id, color, decoration ) {
		var style = "#" + id + " input::placeholder, #" + id + " textarea::placeholder {\n";
		if ( '' !== color ) {
			style += "color: " + color + " !important;\n";
		}
		if ( decoration ) {
			style += "text-decoration: " + decoration + " !important;\n";
		}
		style += "}\n";

		return style;
	}

	function addInputStylesOutlineVariation( id, color, checked_color ){
		var style = "#" + id + " input[type='radio'], #" + id + " input[type='checkbox'] {\n" ;
			style += "border: 1px solid " + color + " !important;\n";
			style += "}\n";

			style = "#" + id + " input[type='radio']:checked, #" + id + " input[type='checkbox']:checked {\n" ;
			style += "border: 1px solid " + checked_color + " !important;\n";
			style += "}\n";

			style += "#" + id + " input[type='radio']:checked::before, #" + id + " input[type='checkbox']:checked::before {\n" ;
			style += "content: '';\n";
			style += "}\n";

			style += "#" + id + " input[type='radio']:checked::before {\n" ;
			style += "height: 50%;\n";
			style += "width: 50%;\n";
			style += "border-radius: 50%;\n";
			style += "background-color: " + checked_color + " !important;\n";
			style += "}\n";

			style += "#" + id + " input[type='checkbox']:checked::before {\n" ;
			style += "height: 50%;\n";
			style += "width: 25%;\n";
			style += "border: solid " + checked_color + " !important;\n";
			style += "border-width: 0 2px 2px 0 !important;\n";
			style += "transform: rotate(45deg);\n";
			style += "margin-top: -12%;\n";
			style += "}\n";

		return style;
	}

	function addInputStylesFilledVariation( id, checked_color ){
		var style = "#" + id + " input[type='radio']:checked, #" + id + " input[type='checkbox']:checked {\n" ;
			style += "background-color: " + checked_color + " !important;\n";
			style += "}\n";

			style += "#" + id + " input[type='radio']:checked::before, #" + id + " input[type='checkbox']:checked::before {\n" ;
			style += "content: '';\n";
			style += "height: 50%;\n";
			style += "}\n";

			style += "#" + id + " input[type='radio']:checked::before {\n" ;
			style += "width: 50%;\n";
			style += "border-radius: 50%;\n";
			style += "background-color: #fff !important;\n";
			style += "}\n";

			style += "#" + id + " input[type='checkbox']:checked::before {\n" ;
			style += "width: 25%;\n";
			style += "border: solid #fff !important;\n";
			style += "border-width: 0 2px 2px 0 !important;\n";
			style += "transform: rotate(45deg);\n";
			style += "margin-top: -12%;\n";
			style += "}\n";

		return style;
	}
	function addInputStylesDefaultVariation( id ){
		var style  = "#" + id + " input[type='radio']:checked::before, #" + id + " input[type='checkbox']:checked::before {\n" ;
			style += "content: none !important;\n";

		return style;
	}

	// Render style for live previews.
	$(document).ready( function() {
		var id = container.attr('id');
		var style = "<style id='placeholder-" + id + "'>\n";
		style += "</style>"
		style += "<style id='inputstyle-" + id + "'>\n";
		style += "</style>"
		container.prepend(style);
	});

	// Active template.
	wp.customize( settings + '[template]', function( value ) {
		value.bind( function( newval ) {
			controls_wrapper.find( '.control-section-evf-templates' ).find( '.customize-template-name' ).text( data.templates[ newval ] );
		} );
	} );

	//Show the clone template on hover
	$(document).ready(function() {
		controls_wrapper.find('input[name="image-radio-everest_forms_styles[563][template]"]').parent().hover(function() {
			var $img = $(this).find('img');
			var $clone = $img.clone().addClass('everest-forms-clone-image');
			$(".everest-forms").append($clone);
			$clone.css({
				'position': 'absolute',
				'left': 0,
				'top': '50%',
				'opacity': 0,
				'transform': 'translateY(-50%)',
				'transition': 'opacity 0.3s, transform 0.3s',
				'z-index': 1000
			});

			setTimeout(function() {
				$clone.css({
					'opacity': 1,
					'transform': 'scale(1)'
				});
			}, 0);
		}, function() {
			var $clone = $(".everest-forms").find('.everest-forms-clone-image');
			$clone.css({
				'opacity': 0
			});
			setTimeout(function() {
				  $clone.remove();
			}, 300);
		});
	});


	/* Form Wrapper start */

	// Form Wrapper: width
	wp.customize( settings + '[form_container][width]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'width', newval + '%' );
		} );
	} );

	// Form Wrapper: font_family
	wp.customize( settings + '[font][font_family]', function( value ) {
		value.bind( function( newval ) {
			if ( '' === newval ) {
				container.css( 'font-family', 'inherit' );
				container.find('.evf-field-title h3').css( 'font-family', 'inherit' );
			} else {
				addGoogleFont( newval );
				container.css( 'font-family', newval );
				container.find('.evf-field-title h3').css( 'font-family', 'inherit' );
			}
		} );
	} );

	// Form Wrapper: background_color
	wp.customize( settings + '[form_container][background_color]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'background-color', newval );
		} );
	} );

	// Form Wrapper: background_image
	wp.customize( settings + '[form_container][opacity]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'opacity', newval );
		} );
	} );

	// Form Wrapper: Opacity
	wp.customize( settings + '[form_container][background_image]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'background-image', 'url(' + newval + ')' );
		} );
	} );

	// Form Wrapper: background_size
	wp.customize( settings + '[form_container][background_size]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'background-size', newval );
		} );
	} );

	// Form Wrapper: background_position_x
	wp.customize( settings + '[form_container][background_position_x]', function( value ) {
		value.bind( function( newval ) {
			var position = newval;
			wp.customize( settings + '[form_container][background_position_y]', function( value ) {
				position += ' ' + value.get();
			} );
			container.css( 'background-position', position );
		} );
	} );

	// Form Wrapper: background_position_y
	wp.customize( settings + '[form_container][background_position_y]', function( value ) {
		value.bind( function( newval ) {
			var position = '';
			wp.customize( settings + '[form_container][background_position_x]', function( value ) {
				position += value.get();
			} );
			position += ' ' + newval;
			container.css( 'background-position', position );
		} );
	} );

	// Form Wrapper: background_repeat
	wp.customize( settings + '[form_container][background_repeat]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'background-repeat', newval );
		} );
	} );

	// Form Wrapper: background_attachment
	wp.customize( settings + '[form_container][background_attachment]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'background-attachment', newval );
		} );	wp.customize( settings + '[form_container][background_color]', function( value ) {
			value.bind( function( newval ) {
				container.css( 'background-color', newval );
			} );
		} );

	} );

	// Form Wrapper: border_type
	wp.customize( settings + '[form_container][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'border-style', newval );

			wp.customize( settings + '[form_container][border_color]', function( value ) {
				container.css( 'border-color', value.get() );
			} );
		} );
	} );

	// Form Wrapper: border_width
	wp.customize( settings + '[form_container][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// Form Wrapper: border_color
	wp.customize( settings + '[form_container][border_color]', function( value ) {
		value.bind( function( newval ) {
			container.css( 'border-color', newval );
		} );
	} );

	// Form Wrapper: border_radius
	wp.customize( settings + '[form_container][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

	// Form Wrapper: margin
	wp.customize( settings + '[form_container][margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				container.css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Form Wrapper: padding
	wp.customize( settings + '[form_container][padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				container.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Form Wrapper End */

	/* Field Labels Start */

	// Field Labels: font_size
	wp.customize( settings + '[typography][field_labels_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			field_label.css( 'font-size', newval + default_unit );
		} );
	} );

	// Field Labels: font_color
	wp.customize( settings + '[typography][field_labels_font_color]', function( value ) {
		value.bind( function( newval ) {
			field_label.css( 'color', newval );
		} );
	} );

	// Field Labels: font_style
	wp.customize( settings + '[typography][field_labels_font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						field_label.find( '.evf-label' ).css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						field_label.find( '.evf-label' ).css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						field_label.find( '.evf-label' ).css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						field_label.find( '.evf-label' ).css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Field Labels: text_alignment
	wp.customize( settings + '[typography][field_labels_text_alignment]', function( value ) {
		value.bind( function( newval ) {
			field_label.css( 'text-align', newval );
		} );
	} );

	// Field Labels: line_height
	wp.customize( settings + '[typography][field_labels_line_height]', function( value ) {
		value.bind( function( newval ) {
			field_label.css( 'line-height', newval );
		} );
	} );

	// Field Labels: margin
	wp.customize( settings + '[typography][field_labels_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_label.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_label.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				field_label.css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Field Labels: padding
	wp.customize( settings + '[typography][field_labels_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_label.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_label.css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				field_label.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Field Labels End */

	/* Field Sublabels Start */

	// Field Sublabels: font_size
	wp.customize( settings + '[typography][field_sublabels_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			field_sub_label.css( 'font-size', newval + default_unit );
		} );
	} );

	// Field Sublabels: font_color
	wp.customize( settings + '[typography][field_sublabels_font_color]', function( value ) {
		value.bind( function( newval ) {
			field_sub_label.css( 'color', newval );
		} );
	} );

	// Field Sublabels: font_style
	wp.customize( settings + '[typography][field_sublabels_font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						field_sub_label.css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						field_sub_label.css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						field_sub_label.css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						field_sub_label.css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Field Sublabels: text_alignment
	wp.customize( settings + '[typography][field_sublabels_text_alignment]', function( value ) {
		value.bind( function( newval ) {
			field_sub_label.css( 'text-align', newval );
		} );
	} );

	// Field Sublabels: line_height
	wp.customize( settings + '[typography][field_sublabels_line_height]', function( value ) {
		value.bind( function( newval ) {
			field_sub_label.css( 'line-height', newval );
		} );
	} );

	// Field Sublabels: margin
	wp.customize( settings + '[typography][field_sublabels_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_sub_label.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_sub_label.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				field_sub_label.css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Field Sublabels: padding
	wp.customize( settings + '[typography][field_sublabels_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_sub_label.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_sub_label.css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				field_sub_label.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Field Sublabels End */

	/* Field Description Start */

	// Field Descriptin: font_size
	wp.customize( settings + '[typography][field_description_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			field_description.css( 'font-size', newval + default_unit );
		} );
	} );

	// Field Description: font_color
	wp.customize( settings + '[typography][field_description_font_color]', function( value ) {
		value.bind( function( newval ) {
			field_description.css( 'color', newval );
		} );
	} );

	// Field Description: font_style
	wp.customize( settings + '[typography][field_description_font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						field_description.css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						field_description.css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						field_description.css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						field_description.css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Field Description: text_alignment
	wp.customize( settings + '[typography][field_description_text_alignment]', function( value ) {
		value.bind( function( newval ) {
			field_description.css( 'text-align', newval );
		} );
	} );

	// Field Description: line_height
	wp.customize( settings + '[typography][field_description_line_height]', function( value ) {
		value.bind( function( newval ) {
			field_description.css( 'line-height', newval );
		} );
	} );

	// Field Description: margin
	wp.customize( settings + '[typography][field_description_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_description.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_description.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				field_description.css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Field Description: padding
	wp.customize( settings + '[typography][field_description_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			field_description.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				field_description.css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				field_description.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Field Description End*/


	/* Field Styles Start */

	// Field Styles: font_size
	wp.customize( settings + '[typography][field_styles_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'font-size', newval + default_unit );
		} );
	} );

	// Field Styles: font_color
	var prev_field_style_font_color = '';
	wp.customize( settings + '[typography][field_styles_font_color]', function( value ) {
		value.bind( function( newval ) {
			prev_field_style_font_color = newval;
			container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'color', newval );
		} );
	} );

	// Field Styles: placeholder_font_color
	wp.customize( settings + '[typography][field_styles_placeholder_font_color]', function( value ) {
		var id = container.attr('id');
		value.bind( function( newval ) {
			container.find('style#placeholder-'+id).html( addPlaceholderStyles( id, newval ) );
		} );
	} );

	// Field Styles: font_style
	wp.customize( settings + '[typography][field_styles_font_style]', function( value ) {
		value.bind( function( newval ) {
			var id = container.attr('id');
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						container.find('style#'+id).html( addPlaceholderStyles( id, '', (val === true) ? 'underline' : 'none' ) );
						break;
					case 'uppercase':
						container.find('input, textarea, select, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Field Styles: alignment
	wp.customize( settings + '[typography][field_styles_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find('input, textarea, .evf-payment-total, .evf-single-item-price').css( 'text-align', newval );
			container.find('select').css( 'text-align-last', newval );

			var option_direction = 'ltr';
			if ( 'right' == newval ) {
				option_direction = 'rtl';
			}

			container.find( 'option' ).css( 'direction', option_direction );
		} );
	} );

	// Field Styles: border_type
	wp.customize( settings + '[field_styles][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-style', newval );
		} );
	} );

	// Field Styles: border_width
	wp.customize( settings + '[field_styles][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// Field Styles: border_color
	var prev_border_color='';
	wp.customize( settings + '[typography][field_styles_border_color]', function( value ) {
		value.bind( function( newval ) {
			prev_border_color = newval;
			container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-color', newval );
		} );
	} );

	// Field Styles: border_focus_color
	wp.customize( settings + '[typography][field_styles_border_focus_color]', function( value ) {
		container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement ').on('focus blur', function(e) {
			if ( 'focus' == e.type ) {
				var control_value = value.get();
				$( this ).css( 'border-color', control_value );
			} else {
				$( this ).css( 'border-color', prev_border_color );
			}
		} );
	} );

	// Field Styles: border_radius
	wp.customize( settings + '[field_styles][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

	// Field Styles: background_color


	// Field Styles: margin
	wp.customize( settings + '[typography][field_styles_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.find('input, textarea, select, canvas.evf-signature-canvas, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.find('input, textarea, select, canvas.evf-signature-canvas, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				container.find('input, textarea, select, canvas.evf-signature-canvas, .evf-payment-total, .evf-single-item-price, .StripeElement').css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Field Styles: padding
	wp.customize( settings + '[typography][field_styles_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				container.find('input, textarea, select, canvas.evf-signature-canvas, .StripeElement').css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Field Styles End */

	/* File Upload Styles Start */

	// File Upload Styles: font_size
	wp.customize( settings + '[typography][file_upload_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find('.everest-forms-uploader .everest-forms-upload-title, .everest-forms-uploader .everest-forms-upload-hint, .everest-forms-uploader .dz-details, .everest-forms-uploader .dz-error-message').css( 'font-size', newval + default_unit );
		} );
	} );

	// File Upload Styles: font_color
	var prev_file_upload_style_font_color = '';
	wp.customize( settings + '[typography][file_upload_font_color]', function( value ) {
		value.bind( function( newval ) {
			prev_file_upload_style_font_color = newval;
			container.find('.everest-forms-uploader .everest-forms-upload-title, .everest-forms-uploader .everest-forms-upload-hint, .everest-forms-uploader .dz-details').css( 'color', newval );
		} );
	} );

	// File Upload Styles: background_color
	wp.customize( settings + '[typography][file_upload_background_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-uploader' ).css( 'background-color', newval );
		} );
	} );

	// File Upload Icon Styles: background_color
	wp.customize( settings + '[typography][file_upload_icon_background_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-uploader .dz-message > svg' ).css( 'background-color', newval );
		} );
	} );

	// File Upload Icon Styles: fill_color
	var prev_file_upload_style_icon_fill_color = '';
	wp.customize( settings + '[typography][file_upload_icon_color]', function( value ) {
		value.bind( function( newval ) {
			prev_file_upload_style_icon_fill_color = newval;
			container.find('.everest-forms-uploader .dz-message > svg').css( 'fill', newval );
		} );
	} );

	// File Upload Styles: border_type
	wp.customize( settings + '[file_upload_styles][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.find('.everest-forms-uploader').css( 'border-style', newval );
		} );
	} );

	// File Upload Styles: border_width
	wp.customize( settings + '[file_upload_styles][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.find('.everest-forms-uploader').css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// File Upload Styles: border_color
	var prev_border_color='';
	wp.customize( settings + '[typography][file_upload_border_color]', function( value ) {
		value.bind( function( newval ) {
			prev_border_color = newval;
			container.find('.everest-forms-uploader').css( 'border-color', newval );
		} );
	} );

	// File Upload Styles: border_radius
	wp.customize( settings + '[file_upload_styles][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.find('.everest-forms-uploader').css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.find('.everest-forms-uploader').css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.find('.everest-forms-uploader').css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.find('.everest-forms-uploader').css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

	// File Upload Styles: margin
	wp.customize( settings + '[typography][file_upload_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.find('.everest-forms-uploader').css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.find('.everest-forms-uploader').css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				container.find('.everest-forms-uploader').css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// File Upload Styles: padding
	wp.customize( settings + '[typography][file_upload_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.find('.everest-forms-uploader').css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.find('.everest-forms-uploader').css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				container.find('.everest-forms-uploader').css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Checkbox and Radio Styles Starts */

	// Checkbox and Radio: font_size
	wp.customize( settings + '[typography][checkbox_radio_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'font-size', newval + default_unit );
		} );
	} );

	// Checkbox and Radio: font_style
	wp.customize( settings + '[typography][checkbox_radio_font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Checkbox and Radio: font_color
	wp.customize( settings + '[typography][checkbox_radio_font_color]', function( value ) {
		value.bind( function( newval ) {
			container.find('input[type="radio"] + label, input[type="checkbox"] + label').css( 'color', newval );
		} );
	} );

	// Checkbox and Radio Styles: alignment
	wp.customize( settings + '[typography][checkbox_radio_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'text-align', newval );

			var option_direction = 'ltr';
			if ( 'right' == newval ) {
				option_direction = 'rtl';
			}

			container.find( 'option' ).css( 'direction', option_direction );
		} );
	} );

	// Checkbox and Radio Styles: margin
	wp.customize( settings + '[typography][checkbox_radio_margin]', function( value ) {
		var inline_style = 'default';
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';
			wp.customize( settings + '[typography][inline_style]', function( value ) {
				inline_style = value.get();
			});

			container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				if( 'two_columns' === inline_style && ( 'right' === prop || 'left' === prop ) ) {
					val = 0;
				}
				container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			wp.customize( settings + '[typography][inline_style]', function( value ) {
				inline_style = value.get();
			});

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval[active_responsive_device], function( prop, val ) {
				if( 'two_columns' === inline_style && ( 'right' === prop || 'left' === prop ) ) {
					val = 0;
				}
				container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Checkbox and Radio Styles: padding
	wp.customize( settings + '[typography][checkbox_radio_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				container.find('.evf-field-checkbox ul li, .evf-field-radio ul li, .evf-field-payment-multiple ul li, .evf-field-payment-checkbox ul li').css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	// Checkbox and Radio Styles: default_style, inline_style, two_columns_style
	wp.customize( settings + '[typography][checkbox_radio_inline_style]', function( value ) {
		value.bind( function( newval ) {
			var ul = container.find('.evf-field-checkbox ul, .evf-field-radio ul, .evf-field-payment-multiple ul, .evf-field-payment-checkbox ul');
			ul.addClass(newval);
			switch(newval) {
				case 'inline':
					ul.css({
						'display' : 'flex',
						'flex-wrap' : 'wrap',
					});
					ul.find('li').css({
						'display' : 'flex',
						'flex' : 'auto',
					});
					ul.find('li label').css({
						'flex' : '1',
					});
				break;
				case 'two_columns':
					ul.css({
						'display' : 'flex',
						'flex-wrap' : 'wrap',
					});
					ul.find('li').css({
						'display' : 'flex',
						'flex' : '0 50%',
					});
					ul.find('li label').css({
						'flex' : '1',
					});
				break;
				default:
				ul.css({
					'display' : 'inherit',
					'flex-wrap' : '',
				});
				ul.find('li').css({
					'flex' : '',
					'max-width' : '',
				});
				ul.find('li label').css({
					'flex' : '',
				});
				break;
			}
		} );
	} );

	// Checkbox and Radio Styles: design_style
	wp.customize( settings + '[typography][checkbox_radio_style_variation]', function( value ) {
		var id = container.attr('id');
		value.bind( function( newval ) {
			var size = 0;
			var color = '';
			var checked_color = '';
			wp.customize( settings + '[typography][checkbox_radio_size]', function( value ) {
				var default_unit = 'px';
				size = value.get() + default_unit;
			} );
			wp.customize( settings + '[typography][checkbox_radio_color]', function( value ) {
				color = value.get();
			});
			wp.customize( settings + '[typography][checkbox_radio_checked_color]', function( value ) {
				checked_color = value.get();
			});
			var input = container.find('input[type="radio"], input[type="checkbox"]');
			switch(newval) {
				case 'outline':
					container.find('style#placeholder-'+id).html( addInputStylesOutlineVariation( id, color, checked_color ) );
					input.css({
						'width' : size,
						'height' : size,
						'display' : 'inline-flex',
						'align-items' : 'center',
						'justify-content' : 'center',
						'background-color' : 'transparent',
						'border' : '1px solid '+color,
					});
				break;
				case 'filled':
					container.find('style#placeholder-'+id).html( addInputStylesFilledVariation( id, checked_color ) );
					input.css({
						'border' : '',
						'width' : size,
						'height' : size,
						'background-color' : color,
						'border' : 'none',
					});
				break;
				default:
					container.find('style#placeholder-'+id).html( addInputStylesDefaultVariation( id ) );
					input.css({
						'width' : 'auto',
						'height' : 'inherit',
						'display' : '',
						'align-items' : '',
						'justify-content' : '',
						'background-color' : '',
						'border' : '',
					});
				break;
			}
			var input = container.find('input[type="checkbox"]');
			switch(newval) {
				case 'outline':
					input.css({
						'-webkit-appearance' : 'none',
					});
				break;
				case 'filled':
					input.css({
						'-webkit-appearance' : 'none',
					});
				break;
				default:
					input.css({
						'-webkit-appearance' : 'checkbox',
					});
				break;
			}
			var input = container.find('input[type="radio"]');
			switch(newval) {
				case 'outline':
					input.css({
						'-webkit-appearance' : 'none',
					});
				break;
				case 'filled':
					input.css({
						'-webkit-appearance' : 'none',
					});
				break;
				default:
					input.css({
						'-webkit-appearance' : 'radio',
					});
				break;
			}
		} );
	} );

	// Checkbox and Radio: size
	wp.customize( settings + '[typography][checkbox_radio_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find('input[type="radio"], input[type="checkbox"]').css( {'width': newval + default_unit, 'height': newval + default_unit });
		} );
	} );

	// Checkbox and Radio: color
	wp.customize( settings + '[typography][checkbox_radio_color]', function( value ) {
		var style_variation = 'default';
		value.bind( function( newval ) {
			wp.customize( settings + '[checkbox_radio_styles][checkbox_radio_style_variation]', function( value ) {
				style_variation = value.get();
			});
			if( 'outline' === style_variation ){
				container.find('input[type="radio"], input[type="checkbox"]').css( 'border-color', newval ).css( 'background-color', 'transparent' );
			}else if( 'filled' === style_variation ){
				container.find('input[type="radio"], input[type="checkbox"]').css( 'border-color', newval ).css( 'background-color', newval );
			}else{
				container.find('input[type="radio"], input[type="checkbox"]').css( 'border-color', 'inherit' ).css( 'background-color', 'inherit' );
			}
		} );
	} );

	// Checkbox and Radio: checked_color
	wp.customize( settings + '[typography][checkbox_radio_checked_color]', function( value ) {
		var id = container.attr('id');
		var style_variation = 'default';
		var color = '';
		value.bind( function( newval ) {
			wp.customize( settings + '[typography][checkbox_radio_color]', function( value ) {
				color = value.get();
			});
			wp.customize( settings + '[typography][checkbox_radio_style_variation]', function( value ) {
				style_variation = value.get();
			});
			if( 'outline' === style_variation ){
				container.find('style#placeholder-'+id).html( addInputStylesOutlineVariation( id, color, newval ) );
			}else if( 'filled' === style_variation ){
				container.find('style#placeholder-'+id).html( addInputStylesFilledVariation( id, newval ) );
			}else{
				container.find('style#placeholder-'+id).html( addInputStylesDefaultVariation( id ) );
			}
		} );
	} );

	/* Checkbox and Radio Styles Ends */

	/* Section Title Start */

	// Section Title: font_size
	wp.customize( settings + '[typography][section_title_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			section_title.css( 'font-size', newval + default_unit );
		} );
	} );

	// Section Title: font_color
	wp.customize( settings + '[typography][section_title_font_color]', function( value ) {
		value.bind( function( newval ) {
			section_title.css( 'color', newval );
		} );
	} );

	// Section Title: font_style
	wp.customize( settings + '[typography][section_title_font_style]', function( value ) {
		value.bind( function( newval ) {
			var id = container.attr('id');
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						section_title.css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						section_title.css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						section_title.css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						section_title.find('style#'+id).html( addPlaceholderStyles( id, '', (val === true) ? 'underline' : 'none' ) );
						break;
					case 'uppercase':
						section_title.css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Section Title: text_alignment
	wp.customize( settings + '[typography][section_title_text_alignment]', function(value) {
		value.bind( function ( newval ) {
			section_title.css( 'text-align', newval );
		} );
	} );

	// Section Title: line_height
	wp.customize( settings + '[typography][section_title_line_height]', function(value) {
		value.bind( function ( newval ) {
			section_title.css( 'line-height', newval );
		} );
	} );

	// Section Title: margin
	wp.customize( settings + '[typography][section_title_margin]', function(value) {
		preview_buttons.on( 'click', function () {
			var control_value = value.get();
			var active_responsive_device = $( this ).data( 'device' );
			var default_unit = 'px';

			section_title.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined' ) {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				section_title.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data( 'device' );
			var default_unit = 'px';

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				section_title.css( 'margin-' + prop, val + default_unit );
			} );
		} );
	} );

	// Section Title: padding
	wp.customize( settings + '[typography][section_title_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			section_title.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined' ) {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				section_title.css('padding-' + prop, val + default_unit);
			} );
		} );
		value.bind( function( newval ) {
			var default_unit = 'px';
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data( 'device' );

			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				section_title.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Section Title End */

	/* Button Styles Start */

	// Button Styles: font_size
	wp.customize( settings + '[typography][button_font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			button.css( 'font-size', newval + default_unit );
		} );
	} );

	// Button Styles: font_style
	wp.customize( settings + '[typography][button_font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						button.css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						button.css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						button.css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						button.css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Button Styles: font_color
	var button_pev_hover_font_color = '';
	wp.customize( settings + '[typography][button_font_color]', function( value ) {
		value.bind( function( newval ) {
			button_pev_hover_font_color = newval;
			button.css( 'color', newval );
		} );
	} );

	// Button Styles: hover_font_color
	wp.customize( settings + '[typography][button_hover_font_color]', function( value ) {
		button.on( 'mouseover mouseleave', function(e) {
			if ( 'mouseover' == e.type ) {
				var control_value = value.get();
				$( this ).css( 'color', control_value );
			} else {
				$( this ).css( 'color', button_pev_hover_font_color );
			}
		} );
	} );

	// Button Styles: background_color
	var button_pev_color = '';
	wp.customize( settings + '[typography][button_background_color]', function( value ) {
		value.bind( function( newval ) {
			button_pev_color = newval;
			button.css( 'background-color', newval );
		} );
	} );

	// Button Styles: hover_background_color
	wp.customize( settings + '[typography][button_hover_background_color]', function( value ) {
		button.on( 'mouseover mouseleave', function(e) {
			if ( 'mouseover' == e.type ) {
				var control_value = value.get();
				$( this ).css( 'background-color', control_value );
			} else {
				$( this ).css( 'background-color', button_pev_color );
			}
		} );
	} );

	// Button Styles: alignment
	wp.customize( settings + '[typography][button_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find('.evf-submit-container:not(.everest-forms-multi-part-actions)').css( 'text-align', newval );
		} );
	} );

	// Button Styles: border_type
	wp.customize( settings + '[button][border_type]', function( value ) {
		value.bind( function( newval ) {
			button.css( 'border-style', newval );
		} );
	} );


	// Button Styles: border_color
	var button_pev_border_hover_color = '';
	wp.customize( settings + '[typography][button_border_color]', function( value ) {
		value.bind( function( newval ) {
			button_pev_border_hover_color = newval;
			button.css( 'border-color', newval );
		} );
	} );

	// Button Styles: border_hover_color
	wp.customize( settings + '[typography][button_border_hover_color]', function( value ) {
		button.on( 'mouseover mouseleave', function(e) {
			if ( 'mouseover' == e.type ) {
				var control_value = value.get();
				$( this ).css( 'border-color', control_value );
			} else {
				$( this ).css( 'border-color', button_pev_border_hover_color );
			}
		} );
	} );

	// Button Styles: border_radius
	wp.customize( settings + '[button][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						button.css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						button.css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						button.css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						button.css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

		// Button Styles: border_width
		wp.customize( settings + '[button][border_width]', function( value ) {
			value.bind( function( newval ) {
				var default_unit = 'px';

				if ( typeof newval != 'object' ) {
					newval = JSON.parse( newval );
				}

				$.each( newval, function( prop, val ) {
					if ( dimension_directions.indexOf( prop ) != -1 ) {
						button.css( 'border-' + prop + '-width', val + default_unit );
					}
				} );
			} );
		} );


	// Button Styles: line_height
	wp.customize( settings + '[typography][button_line_height]', function( value ) {
		value.bind( function( newval ) {
			button.css( 'line-height', newval );
		} );
	} );

	// Button Styles: margin
	wp.customize( settings + '[typography][button_margin]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			button.css( 'margin', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				button.css( 'margin-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				button.css( 'margin-' + prop, val + default_unit );
			} );

			var submit_button = $( '.evf-submit-container.everest-forms-multi-part-actions button[type="submit"]' );
			var button_height = submit_button.outerHeight( true );

			submit_button.closest( '.evf-submit-container.everest-forms-multi-part-actions:not(.everest-forms-nav-align--left, .everest-forms-nav-align--center)' ).css( 'margin-top', '-' + button_height + 'px' );
		} );
	} );

	// Button Styles: padding
	wp.customize( settings + '[typography][button_padding]', function( value ) {
		preview_buttons.on( 'click', function() {
			var control_value = value.get();
			var active_responsive_device = $(this).data('device');
			var default_unit = 'px';

			button.css( 'padding', '' );
			if ( typeof control_value[active_responsive_device] == 'undefined') {
				active_responsive_device = 'desktop';
			}
			$.each( control_value[active_responsive_device], function( prop, val ) {
				button.css( 'padding-' + prop, val + default_unit );
			} );
		} );
		value.bind( function( newval ) {
			var active_responsive_device = controls_wrapper.find( '#customize-footer-actions .devices button.active' ).data('device');
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval[active_responsive_device], function( prop, val ) {
				button.css( 'padding-' + prop, val + default_unit );
			} );
		} );
	} );

	/* Button Styles End */

	/**
	 * Submission Message Start
	 *
	 * This includes success, error and validation message.
	 */

	/* Submission Success Message Starts */

	// Show dummy submission success message.
	wp.customize( settings + '[success_message][show_submission_message]', function( value ) {
		var toggle_success_message = function( display ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).remove();

			if ( true === display ) {
				container.prepend( '<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + data.notices.success + '</div>' );
			}
		};

		toggle_success_message( value.get() );
		value.bind( function( val ) {
			toggle_success_message( val );
		} );
	} );

	// Submission Success Message: font_size
	wp.customize( settings + '[success_message][font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'font-size', newval + default_unit );
		} );
	} );

	// Submission Success Message: font_style
	wp.customize( settings + '[success_message][font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Submission Success Message: text_alignment
	wp.customize( settings + '[success_message][text_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'text-align', newval );
		} );
	} );

	// Submission Success Message: font_color
	wp.customize( settings + '[success_message][font_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'color', newval );
		} );
	} );

	// Submission Success Message: background_color
	wp.customize( settings + '[success_message][background_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'background-color', newval );
		} );
	} );

	// Submission Success Message: border_type
	wp.customize( settings + '[success_message][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-style', newval );
		} );
	} );

	// Submission Success Message: border_width
	wp.customize( settings + '[success_message][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// Submission Success Message: border_color
	wp.customize( settings + '[success_message][border_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-color', newval );
		} );
	} );

	// Submission Success Message: border_radius
	wp.customize( settings + '[success_message][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.find( '.everest-forms-notice.everest-forms-notice--success' ).css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

	/* Submission Success Message Styles ends */

	/* Submission Error Message Starts */

	// Show dummy submission error message.
	wp.customize( settings + '[error_message][show_submission_message]', function( value ) {
		var toggle_error_message = function( display ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).remove();

			if ( true === display ) {
				container.prepend( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">' + data.notices.error + '</div>' );
			}
		};

		toggle_error_message( value.get() );
		value.bind( function( val ) {
			toggle_error_message( val );
		} );
	} );

	// Submission Error Message: font_size
	wp.customize( settings + '[error_message][font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'font-size', newval + default_unit );
		} );
	} );

	// Submission Error Message: font_style
	wp.customize( settings + '[error_message][font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Submission Error Message: text_alignment
	wp.customize( settings + '[error_message][text_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'text-align', newval );
		} );
	} );

	// Submission Error Message: font_color
	wp.customize( settings + '[error_message][font_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'color', newval );
		} );
	} );

	// Submission Error Message: background_color
	wp.customize( settings + '[error_message][background_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'background-color', newval );
		} );
	} );

	// Submission Error Message: border_type
	wp.customize( settings + '[error_message][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-style', newval );
		} );
	} );

	// Submission Error Message: border_width
	wp.customize( settings + '[error_message][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// Submission Error Message: border_color
	wp.customize( settings + '[error_message][border_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-color', newval );
		} );
	} );

	// Submission Error Message: border_radius
	wp.customize( settings + '[error_message][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.find( '.everest-forms-notice.everest-forms-notice--error' ).css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

	/* Field validation Message Starts */

	// Show dummy field validation message.
	wp.customize( settings + '[validation_message][show_submission_message]', function( value ) {
		var toggle_validation_message = function( display ) {
			container.find( '.evf-error' ).remove();

			if ( true === display ) {
				container.find( '.evf-field' ).append( '<label class="evf-error" for="dummy-validation">This field is required.</label>' );
			}
		};

		toggle_validation_message( value.get() );
		value.bind( function( val ) {
			toggle_validation_message( val );
		} );
	} );

	// Validation Message: font_size
	wp.customize( settings + '[validation_message][font_size]', function( value ) {
		var default_unit = 'px';
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'font-size', newval + default_unit );
		} );
	} );


	// Validation Message: font_style
	wp.customize( settings + '[validation_message][font_style]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'bold':
						container.find( '.evf-error' ).css( 'font-weight', (val === true) ? 'bold' : 'normal' );
						break;
					case 'italic':
						container.find( '.evf-error' ).css( 'font-style', (val === true) ? 'italic' : 'normal' );
						break;
					case 'underline':
						container.find( '.evf-error' ).css( 'text-decoration', (val === true) ? 'underline' : 'none' );
						break;
					case 'uppercase':
						container.find( '.evf-error' ).css( 'text-transform', (val === true) ? 'uppercase' : 'none' );
						break;
				}
			} );
		} );
	} );

	// Validation Message: text_alignment
	wp.customize( settings + '[validation_message][text_alignment]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'text-align', newval );
		} );
	} );

	// Validation Message: font_color
	wp.customize( settings + '[validation_message][font_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'color', newval );
		} );
	} );

	// Validation Message: background_color
	wp.customize( settings + '[validation_message][background_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'background-color', newval );
		} );
	} );

	// Validation Message: border_type
	wp.customize( settings + '[validation_message][border_type]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'border-style', newval );
		} );
	} );

	// Validation Message: border_width
	wp.customize( settings + '[validation_message][border_width]', function( value ) {
		value.bind( function( newval ) {
			var default_unit = 'px';
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}
			$.each( newval, function( prop, val ) {
				if ( dimension_directions.indexOf( prop ) != -1 ) {
					container.find( '.evf-error' ).css( 'border-' + prop + '-width', val + default_unit );
				}
			} );
		} );
	} );

	// Validation Message: border_color
	wp.customize( settings + '[validation_message][border_color]', function( value ) {
		value.bind( function( newval ) {
			container.find( '.evf-error' ).css( 'border-color', newval );
		} );
	} );

	// Validation Message: border_radius
	wp.customize( settings + '[validation_message][border_radius]', function( value ) {
		value.bind( function( newval ) {
			if ( typeof newval != 'object' ) {
				newval = JSON.parse( newval );
			}

			var unit = newval['unit'];

			$.each( newval, function( prop, val ) {
				switch( prop ) {
					case 'top':
						container.find( '.evf-error' ).css( 'border-top-left-radius', val + unit );
						break;
					case 'right':
						container.find( '.evf-error' ).css( 'border-top-right-radius', val + unit );
						break;
					case 'bottom':
						container.find( '.evf-error' ).css( 'border-bottom-right-radius', val + unit );
						break;
					case 'left':
						container.find( '.evf-error' ).css( 'border-bottom-left-radius', val + unit );
						break;
				}
			} );
		} );
	} );

		// Button Styles: alignment
		wp.customize(settings + '[button][alignment]', function (value) {
			value.bind(function (newval) {
				container.find(".evf-submit-container ").css("text-align", newval);
				container.find(".evf-submit-container ").css({
					display: "block",
				});
				container.find(".evf-submit-container button").css({
					float: "none",
				});
			});
		});

} )( jQuery, _evfCustomizePreviewL10n );
