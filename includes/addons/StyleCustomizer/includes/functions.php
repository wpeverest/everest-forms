<?php

/**
 * Enqueue fonts.
 *
 * @param string $font_family Font Family.
 * @param mixed  $load_locally Load font stylesheet locally.
 * @return void
 */
function evfsc_enqueue_fonts( $font_family = '' ) {
	die;

	if ( ! empty( $font_family ) ) {
		$font_url = 'https://fonts.googleapis.com/css?family=' . evf_clean( $font_family );

		$font_url = evf_maybe_get_local_font_url( $font_url );

		wp_enqueue_style( 'everest-forms-google-fonts', $font_url, array(), EVF_VERSION, 'all' );
	}
}

function evfsc_migration() {

	// if ( get_option( 'evfsc_migration_done' ) ) {
	// return;
	// }

	$customizer_data = get_option( 'everest_forms_styles' );
	$new_structure   = array();
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
				$new_structure[ $key ]['field_styles'][ $field_styles_container_key ] = $settings['wrapper'][ $field_styles_key ];
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
				$new_structure[ $key ]['file_upload_styles'][ $file_upload_container_key ] = $settings['wrapper'][ $file_upload_key ];
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
				$new_structure[ $key ]['button'][ $button_container_key ] = $settings['wrapper'][ $button_key ];
			}
		}
	}

	lg( $new_structure );
	// delete_option( 'everest_forms_styles' );
	// update_option( 'everest_forms_styles', $new_structure );
	// update_option( 'evfsc_migration_done', true );

	return $new_structure;
}

// Run the migration function
evfsc_migration();
