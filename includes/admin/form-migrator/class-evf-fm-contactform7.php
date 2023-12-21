<?php
/**
 * EverestForms Form Migrator ContactForm7 Class
 *
 * @package EverestForms\Admin
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Contactform7 class.
 */
class EVF_Fm_Contactform7 extends EVF_Admin_Form_Migrator {
	/**
	 * Define required properties.
	 *
	 * @since 2.0.6
	 */
	public function init() {

		$this->name = 'Contact Form 7';
		$this->slug = 'contact-form-7';
		$this->path = 'contact-form-7/wp-contact-form-7.php';
	}

	/**
	 * Get all the forms.
	 *
	 * @since 2.0.6
	 */
	public function get_forms() {

		$forms_final = [];

		if ( ! $this->is_active() ) {
			return $forms_final;
		}

		$forms = \WPCF7_ContactForm::find( [ 'posts_per_page' => - 1 ] );

		if ( empty( $forms ) ) {
			return $forms_final;
		}

		foreach ( $forms as $form ) {
			if ( ! empty( $form ) && ( $form instanceof \WPCF7_ContactForm ) ) {
				$forms_final[ $form->id() ] = $form->title();
			}
		}

		return $forms_final;
	}
}

new EVF_Fm_Contactform7();
