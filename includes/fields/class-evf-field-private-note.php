<?php
/**
 * First name field.
 *
 * @package EverestForms\Fields
 * @since   3.2.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_First_Name class.
 */
class EVF_Field_Private_Note extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Private Note', 'everest-forms' );
		$this->type     = 'private-note';
		$this->icon     = 'evf-icon evf-icon-private-note';
		$this->order    = 91;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'meta',
				),
			)
		);

		parent::__construct();

		add_filter( 'everest_forms_should_display_field_' . $this->type, array( $this, 'should_display_field' ), 10, 3 );
	}

	/**
	 * Should display field.
	 *
	 * @since 3.2.2
	 */
	public function should_display_field( $should_display, $field, $form_data ) {
		if ( isset( $field['type'] ) && $field['type'] === $this->type ) {
			return false;
		}
		return true;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 3.2.2
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Label.
		$this->field_preview_option( 'label', $field );
		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
