<?php
/**
 * EverestForms Form Migrator WPforms Class
 *
 * @package EverestForms\Admin
 * @since   2.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Wpforms class.
 */
class EVF_Fm_Wpforms extends EVF_Admin_Form_Migrator {
	/**
	 * Define required properties.
	 *
	 * @since 2.0.6
	 */
	public function init() {

		$this->name = 'WPForms';
		$this->slug = 'wpforms';
		$this->path = 'wpforms/wpforms.php';
	}

	/**
	 * Get all the forms.
	 *
	 * @since 2.0.6
	 */
	public function get_forms() {

		$required_form_arr = array();
		if ( function_exists( 'wpforms' ) ) {
			$forms = wpforms()->form->get( '' );
			if ( empty( $forms ) ) {
				return $required_form_arr;
			}
			foreach ( $forms as $form ) {
				if ( empty( $form ) ) {
					continue;
				}
				$required_form_arr[ $form->ID ] = $form->post_title;
			}
		}
		return $required_form_arr;
	}

	/**
	 * Get a single form.
	 *
	 * @since 2.0.6
	 *
	 * @param int $id Form ID.
	 *
	 * @return array|bool
	 */
	public function get_form( $id ) {

		$forms = wpforms()->form->get( $id );
		if ( empty( $form ) ) {
			return false;
		}

		return $forms;
	}
}

new EVF_Fm_Wpforms();
