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
	 * Likert output
	 *
	 * @since 0
	 *
	 * @param  [array]  $form_data Form Data.
	 * @param  [array]  $entry Entry Data.
	 * @param  [string] $id Id for entry and form data.
	 */
	public static function likert_output( $form_data, $entry, $id ) {
		$output = '';

		$i = 0;
		foreach ( $form_data['form_fields'][ $id ]['likert_rows'] as $key => $question ) {
			$output .= '<div class="class="everest_forms_preview_confirmation_likert_question"> ' . $question . '</div>';
			if ( array_key_exists( $key, $entry['form_fields'][ $id ] ) ) {
				$output .= '<div class="everest_forms_preview_confirmation_likert_answer">' . $form_data['form_fields'][ $id ]['likert_columns'][ $entry['form_fields'][ $id ][ $key ] ] . '</div>';
			} else {
				$output .= '<div class="everest_forms_preview_confirmation_likert_answer">No Answer</div>';
			}
		}

		return $output;
	}

	/**
	 * Show preview confirmation after form submission.
	 *
	 * @since 2.0.8
	 *
	 * @param  [array]  $form_data   Form Data.
	 * @param  [array]  $entry Entry.
	 * @param [string] $preview_style Preview Style.
	 */
	public static function preview_confirmation( $form_data, $entry, $preview_style ) {
		$output  = '';
		$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '">';
		foreach ( $form_data['form_fields'] as $id => $data ) {
			$output .= '<div class="everest_forms_preview_confirmation_row_title_' . $preview_style . '">' . $data['label'] . ': ';
			if ( 'basic' === $preview_style ) {
				$output .= '</div>';

				if ( is_array( $entry['form_fields'][ $id ] ) ) {

					if ( 'likert' === $form_data['form_fields'][ $id ]['type'] ) {
						$output .= self::likert_output( $form_data, $entry, $id );
					}

					if ( 'checkbox' === $form_data['form_fields'][ $id ]['type'] ) {
						$output .= '<div class="everest_forms_preview_confirmation_row_data_' . $preview_style . '">';
						foreach ( $entry['form_fields'][ $id ] as $value ) {
							$output .= $value . '</div>';
						}
					}
				} else {
					$output .= '<div class="everest_forms_preview_confirmation_row_data_' . $preview_style . '">' . $entry['form_fields'][ $id ] . '</div>';
				}
			} else {
				if ( is_array( $entry['form_fields'][ $id ] ) ) {

					$entry_count = count( $entry['form_fields'][ $id ] );
					if ( 'likert' === $form_data['form_fields'][ $id ]['type'] ) {
						$output .= self::likert_output( $form_data, $entry, $id );
					}
					if ( 'checkbox' === $form_data['form_fields'][ $id ]['type'] ) {
						foreach ( $entry['form_fields'][ $id ] as $value ) {
							--$entry_count;
							$output .= $value;
							if ( $entry_count > 0 ) {
								$output .= ', ';
							}
						}
						$output .= '</div>';
					}
				} else {
					$output .= $entry['form_fields'][ $id ] . '</div>';
				}
			}
		}
		$output .= '</div>';

		evf_add_notice( $output, 'preview' );
	}
}
EVF_Admin_Preview_Confirmation::init();
