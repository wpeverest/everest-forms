<?php

/**
 * Enqueue fonts.
 *
 * @param string $font_family Font Family.
 * @param mixed  $load_locally Load font stylesheet locally.
 * @return void
 */
function evfsc_enqueue_fonts( $font_family = '' ) {

	if ( ! empty( $font_family ) ) {
		$font_url = 'https://fonts.googleapis.com/css?family=' . evf_clean( $font_family );

		$font_url = evf_maybe_get_local_font_url( $font_url );

		wp_enqueue_style( 'everest-forms-google-fonts', $font_url, array(), EVF_VERSION, 'all' );
	}
}

function evfsc_migration() {

	if ( get_option( 'evfsc_migration_done' ) ) {
		return;
	}

	$customizer_data = get_option( 'everest_forms_styles' );

	if ( empty( $customizer_data ) ) {
		return;
	}
	$new_structure = array();
	foreach ( $customizer_data as $key => $settings ) {
		$new_structure[ $key ] = array();
		if ( isset( $settings['template'] ) ) {
			$new_structure[ $key ]['template'] = $settings['template'];
		}

		// Font Section.
		$font_keys = array(
			'font_family' => 'font_family',
		);

		foreach ( $font_keys as $font_key => $font_container_key ) {
			if ( isset( $settings['wrapper'][ $font_key ] ) ) {
				$new_structure[ $key ]['font'][ $font_container_key ] = $settings['wrapper'][ $font_key ];
			}
		}

		// Form Container Section.
		$wrapper_keys = array(
			'width'               => 'width',
			'border_type'         => 'border_type',
			'border_width'        => 'border_width',
			'border_radius'       => 'border_radius',
			'border_color'        => 'border_color',
			'background_image'    => 'background_image',
			'background_preset'   => 'background_preset',
			'opacity'             => 'opacity',
			'background_position' => 'background_position',
			'background_size'     => 'background_size',
			'margin'              => 'margin',
			'padding'             => 'padding',
		);

		foreach ( $wrapper_keys as $wrapper_key => $wrapper_container_key ) {
			if ( isset( $settings['wrapper'][ $wrapper_key ] ) ) {
				$new_structure[ $key ]['form_container'][ $wrapper_container_key ] = $settings['wrapper'][ $wrapper_key ];
			}
		}

		// Field Styles Section.
		$field_styles_keys = array(
			'width'         => 'width',
			'border_type'   => 'border_type',
			'border_width'  => 'border_width',
			'border_radius' => 'border_radius',
		);

		foreach ( $field_styles_keys as $field_styles_key => $field_styles_container_key ) {
			if ( isset( $settings['field_styles'][ $field_styles_key ] ) ) {
				$new_structure[ $key ]['field_styles'][ $field_styles_container_key ] = $settings['field_styles'][ $field_styles_key ];
			}
		}

		// file upload  Sections.
		$file_upload_keys = array(
			'width'         => 'width',
			'border_type'   => 'border_type',
			'border_width'  => 'border_width',
			'border_radius' => 'border_radius',
		);

		foreach ( $file_upload_keys as $file_upload_key => $file_upload_container_key ) {
			if ( isset( $settings['file_upload'][ $file_upload_key ] ) ) {
				$new_structure[ $key ]['file_upload_styles'][ $file_upload_container_key ] = $settings['file_upload'][ $file_upload_key ];
			}
		}

		// Button Section.
		$button_keys = array(
			'width'         => 'width',
			'border_type'   => 'border_type',
			'border_width'  => 'border_width',
			'border_radius' => 'border_radius',
		);

		foreach ( $button_keys as $button_key => $button_container_key ) {
			if ( isset( $settings['button'][ $button_key ] ) ) {
				$new_structure[ $key ]['button'][ $button_container_key ] = $settings['button'][ $button_key ];
			}
		}

		// field label typography.
		$field_label_typography_keys = array(
			'font_size'      => 'field_labels_font_size',
			'font_style'     => 'field_labels_font_style',
			'text_alignment' => 'field_labels_text_alignment',
			'line_height'    => 'field_labels_line_height',
			'margin'         => 'field_labels_margin',
			'padding'        => 'field_labels_padding',
		);
		foreach ( $field_label_typography_keys as $field_label_typography_key => $field_label_typography_container_key ) {
			if ( isset( $settings['field_label'][ $field_label_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $field_label_typography_container_key ] = $settings['field_label'][ $field_label_typography_key ];
			}
		}

		// field Sublabel typography.
		$field_sublabels_typography_keys = array(
			'font_size'      => 'field_sublabels_font_size',
			'font_style'     => 'field_sublabels_font_style',
			'text_alignment' => 'field_sublabels_text_alignment',
			'line_height'    => 'field_sublabels_line_height',
			'margin'         => 'field_sublabels_margin',
			'padding'        => 'field_sublabels_padding',
		);
		foreach ( $field_sublabels_typography_keys as $field_sublabels_typography_key => $field_sublabels_typography_container_key ) {
			if ( isset( $settings['field_sublabel'][ $field_sublabels_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $field_sublabels_typography_container_key ] = $settings['field_sublabel'][ $field_sublabels_typography_key ];
			}
		}

		// field style typography
		$field_styles_typography_keys = array(
			'font_size'              => 'field_styles_font_size',
			'font_color '            => 'field_styles_font_color',
			'placeholder_font_color' => 'field_styles_placeholder_font_color',
			'font_style'             => 'field_styles_font_style',
			'alignment'              => 'field_styles_alignment',
			'border_color'           => 'field_styles_border_color',
			'border_focus_color'     => 'field_styles_border_focus_color',
			'margin'                 => 'field_styles_margin',
			'padding'                => 'field_styles_padding',
		);
		foreach ( $field_styles_typography_keys as $field_styles_typography_key => $field_styles_typography_container_key ) {
			if ( isset( $settings['field_styles'][ $field_styles_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $field_styles_typography_container_key ] = $settings['field_styles'][ $field_styles_typography_key ];
			}
		}

		// Field Description typography.
		$field_description_typography_keys = array(
			'font_size'      => 'field_description_font_size',
			'font_color '    => 'field_description_font_color',
			'font_style'     => 'field_description_font_style',
			'text_alignment' => 'field_description_text_alignment',
			'line_height'    => 'field_description_line_height',
			'margin'         => 'field_description_margin',
			'padding'        => 'field_description_padding',
		);
		foreach ( $field_description_typography_keys as $field_description_typography_key => $field_description_typography_container_key ) {
			if ( isset( $settings['field_description'][ $field_description_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $field_description_typography_container_key ] = $settings['field_description'][ $field_description_typography_key ];
			}
		}

		// Section Title Typography.
		$section_title_typography_keys = array(
			'font_size'      => 'section_title_font_size',
			'font_color '    => 'section_title_font_color',
			'font_style'     => 'section_title_font_style',
			'text_alignment' => 'section_title_text_alignment',
			'line_height'    => 'section_title_line_height',
			'margin'         => 'section_title_margin',
			'padding'        => 'section_title_padding',
		);
		foreach ( $section_title_typography_keys as $section_title_typography_key => $section_title_typography_container_key ) {
			if ( isset( $settings['section_title'][ $section_title_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $section_title_typography_container_key ] = $settings['section_title'][ $section_title_typography_key ];
			}
		}

		// File Upload Typography.
		$file_upload_typography_keys = array(
			'font_size'             => 'file_upload_font_size',
			'font_color '           => 'file_upload_font_color',
			'background_color'      => 'file_upload_background_color',
			'icon_background_color' => 'file_upload_icon_background_color',
			'icon_color'            => 'file_upload_icon_color',
			'border_color'          => 'file_upload_border_color',
			'margin'                => 'file_upload_margin',
			'padding'               => 'file_upload_padding',
		);
		foreach ( $file_upload_typography_keys as $file_upload_typography_key => $file_upload_typography_container_key ) {
			if ( isset( $settings['file_upload_styles'][ $file_upload_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $file_upload_typography_container_key ] = $settings['file_upload_styles'][ $file_upload_typography_key ];
			}
		}

		// Radio Checkbox Typography.
		$checkbox_radio_typography_keys = array(
			'font_size'       => 'checkbox_radio_font_size',
			'font_color '     => 'checkbox_radio_font_color',
			'font_style'      => 'checkbox_radio_font_style',
			'alignment'       => 'checkbox_radio_alignment',
			'style_variation' => 'checkbox_radio_style_variation',
			'size'            => 'checkbox_radio_size',
			'color'           => 'checkbox_radio_color',
			'checked_color'   => 'checkbox_radio_checked_color',
			'margin'          => 'checkbox_radio_margin',
		);
		foreach ( $checkbox_radio_typography_keys as $checkbox_radio_typography_key => $checkbox_radio_typography_container_key ) {
			if ( isset( $settings['checkbox_radio_styles'][ $checkbox_radio_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $checkbox_radio_typography_container_key ] = $settings['checkbox_radio_styles'][ $checkbox_radio_typography_key ];
			}
		}

		// Button Typography
		$button_typography_keys = array(
			'font_size'              => 'button_font_size',
			'font_style'             => 'button_font_style',
			'hover_font_color'       => 'button_hover_font_color',
			'hover_background_color' => 'button_hover_background_color',
			'border_color'           => 'button_border_color',
			'alignment'              => 'button_button_alignment',
			'border_hover_color'     => 'button_border_hover_color',
			'line_height'            => 'button_line_height',
			'margin'                 => 'button_margin',
			'padding'                => 'button_padding',
		);
		foreach ( $button_typography_keys as $button_typography_key => $button_typography_container_key ) {
			if ( isset( $settings['button'][ $button_typography_key ] ) ) {
				$new_structure[ $key ]['typography'][ $button_typography_container_key ] = $settings['button'][ $button_typography_key ];
			}
		}

		// Success Message.
		$success_message_keys = array(
			'show_submission_message' => 'show_submission_message',
			'font_size'               => 'font_size',
			'text_alignment'          => 'text_alignment',
			'font_color'              => 'font_color',
			'background_color'        => 'background_color',
			'border_type'             => 'border_type',
			'border_width'            => 'border_width',
			'border_color'            => 'border_color',
			'border_radius'           => 'border_radius',
		);

		foreach ( $success_message_keys as $success_message_key => $success_message_container_key ) {
			if ( isset( $settings['success_message'][ $success_message_key ] ) ) {
				$new_structure[ $key ]['success_message'][ $success_message_container_key ] = $settings['success_message'][ $success_message_key ];
			}
		}

		// Validation Message.
		$validation_message_keys = array(
			'show_submission_message' => 'show_submission_message',
			'font_size'               => 'font_size',
			'text_alignment'          => 'text_alignment',
			'font_color'              => 'font_color',
			'background_color'        => 'background_color',
			'border_type'             => 'border_type',
			'border_width'            => 'border_width',
			'border_color'            => 'border_color',
			'border_radius'           => 'border_radius',
		);

		foreach ( $validation_message_keys as $validation_message_key => $validation_message_container_key ) {
			if ( isset( $settings['validation_message'][ $validation_message_key ] ) ) {
				$new_structure[ $key ]['validation_message'][ $validation_message_container_key ] = $settings['validation_message'][ $validation_message_key ];
			}
		}

		// Error Message.
		$error_message_keys = array(
			'show_submission_message' => 'show_submission_message',
			'font_size'               => 'font_size',
			'text_alignment'          => 'text_alignment',
			'font_color'              => 'font_color',
			'background_color'        => 'background_color',
			'border_type'             => 'border_type',
			'border_width'            => 'border_width',
			'border_color'            => 'border_color',
			'border_radius'           => 'border_radius',
		);

		foreach ( $error_message_keys as $error_message_key => $error_message_container_key ) {
			if ( isset( $settings['error_message'][ $error_message_key ] ) ) {
				$new_structure[ $key ]['error_message'][ $error_message_container_key ] = $settings['error_message'][ $error_message_key ];
			}
		}

		// Color Compatibility.
		$color_mappings = array(
			'wrapper'        => array( 'background_color' => 'form_background' ),
			'field_styles'   => array( 'background_color' => 'field_background' ),
			'field_label'    => array( 'font_color' => 'field_label' ),
			'field_sublabel' => array( 'font_color' => 'field_sublabel' ),
			'button'         => array(
				'font_color'       => 'button_text',
				'background_color' => 'button_background',
			),
		);

		foreach ( $color_mappings as $setting_key => $fields ) {
			foreach ( $fields as $field_key => $new_key ) {
				if ( isset( $settings[ $setting_key ][ $field_key ] ) ) {
					$new_structure[ $key ]['color_palette']['color_12'][ $new_key ] = $settings[ $setting_key ][ $field_key ];
				}
			}
		}
	}

		update_option( 'everest_forms_styles', array() );
		update_option( 'everest_forms_styles', $new_structure );
		update_option( 'evfsc_migration_done', true );

		return $new_structure;
}

// Run the migration function
evfsc_migration();
