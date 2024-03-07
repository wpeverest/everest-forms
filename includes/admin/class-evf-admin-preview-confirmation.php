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
		add_filter( 'everest_forms_preview_confirmation', array( __CLASS__, 'preview_confirmation' ), 20, 4 );
	}

	/**
	 * Show preview confirmation after form submission.
	 *
	 * @since 2.0.8
	 *
	 * @param  [object] $preview_confirmation Preview Confirmation.
	 * @param  [array]  $form_data   Form Data.
	 * @param  [array]  $entry Entry.
	 * @param [string] $preview_style Preview Style.
	 */
	public static function preview_confirmation( $preview_confirmation, $form_data, $entry, $preview_style ) {
		$output  = '';
		$output .= '<div class="everest_forms_preview_confirmation_' . $preview_style . '">';
		foreach ( $form_data['form_fields'] as $key => $data ) {
			$output .= '<div class="everest_forms_preview_confirmation_row_' . $preview_style . '">' . $data['label'] . ': ' . $entry['form_fields'][ $key ] . '</div>';
		}
		$output .= '</div>';

		return $output;
	}
}
EVF_Admin_Preview_Confirmation::init();
