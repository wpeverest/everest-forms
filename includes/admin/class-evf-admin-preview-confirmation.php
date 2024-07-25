<?php
/**
 * Preview Confirmation.
 *
 * @package EverestForms/Admin
 * @version 2.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Embed Wizard Class.
 *
 * @since 2.0.8
 */
class EVF_Admin_Preview_Confirmation {

	/**
	 * Initialize class.
	 *
	 * @since 2.0.8
	 */
	public static function init() {
		add_action( 'everest_forms_preview_confirmation', array( __CLASS__, 'preview_confirmation' ), 20, 3 );
		add_filter( 'everest_forms_notice_types', array( __CLASS__, 'add_notice' ) );
	}

	/**
	 * Add notice type.
	 *
	 * @param [array] $notices notices type.
	 */
	public static function add_notice( $notices ) {
		$notices[] = 'preview';
		return $notices;
	}

	/**
	 * Show preview confirmation after form submission.
	 *
	 * @since 2.0.8
	 *
	 * @param  [array]  $form_data   Form Data.
	 * @param [array]  $form_fields Form Fields.
	 * @param [string] $preview_style Preview Style.
	 */
	public static function preview_confirmation( $form_data, $form_fields, $preview_style ) {

		$output  = '';
		$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '">';

		$exclude = array(
			'captcha',
			'password',
		);

		$labels = array();
		$fields = array();

		foreach ( $form_data['form_fields'] as $id => $data ) {
			if ( in_array( $data['type'], $exclude, true ) ) {
				continue;
			}

			$formatted_string = '';

			if ( isset( $form_fields[ $id ]['type'] ) && 'image-upload' !== $form_fields[ $id ]['type'] ) {
				if ( has_filter( "everest_forms_field_exporter_{$form_fields[ $id ]['type']}" ) ) {
					$formatted_string = apply_filters( "everest_forms_field_exporter_{$form_fields[ $id ]['type']}", $form_fields[ $id ] );

					if ( false === $formatted_string['value'] || empty( $formatted_string['value'] ) ) {
						continue; // Skip empty fields
					}
				}
			} elseif ( empty( $form_fields[ $id ]['value'] ) ) {
				continue; // Skip empty fields
			} elseif ( 'basic' === $preview_style ) {
				$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '_label">' . $form_fields[ $id ]['name'] . '<a href="' . $form_fields[ $id ]['value'] . '" rel="noopener noreferrer" target="_blank"><img src="' . $form_fields[ $id ]['value'] . '" style="width:200px;" /></a></div>';
				continue;
			}

			if ( isset( $form_fields[ $id ]['type'] ) && 'select' === $form_fields[ $id ]['type'] ) {
				$formatted_string = str_replace( '<br>', '', $formatted_string );
			}

			$label = isset( $formatted_string['label'] ) ? $formatted_string['label'] : '';

			if ( in_array( $label, $labels ) && empty( $formatted_string['value'] ) ) {
				continue; // Skip fields with duplicate labels and empty values
			}

			$labels[] = $label;
			$fields[] = $formatted_string;

		}

		$close_div = 'basic' === $preview_style ? '' : '</div>';

		foreach ( $fields as $formatted_string ) {
			if ( 'basic' === $preview_style ) {
				$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '_label">' . $formatted_string['label'] . ' : ' . $close_div;
				$output .= $formatted_string['value'] . '</div>';
			} else {
				$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '_label">' . $formatted_string['label'] . ' : ' . $close_div;
				$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '_value">' . $formatted_string['value'] . '</div>';
			}
		}

		$output .= '</div>';

		$ajax_form_submission = isset( $form_data['settings']['ajax_form_submission'] ) ? $form_data['settings']['ajax_form_submission'] : 0;

		if ( $ajax_form_submission ) {
			return $output;
		} else {
			evf_add_notice( $output, 'preview' );
		}
	}


}

EVF_Admin_Preview_Confirmation::init();
