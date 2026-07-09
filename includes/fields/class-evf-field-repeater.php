<?php
/**
 * Repeater field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Repeater Class.
 */
class EVF_Field_Repeater extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Repeater Fields', 'everest-forms' );
		$this->type     = 'repeater-fields';
		$this->icon     = 'evf-icon evf-icon-repeater';
		$this->order    = 190;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->addon    = 'everest-forms-repeater-fields';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'IBfI1wBxUVY',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'repeater_field_hide',
					'repeater_repeat_limit',
					'repeater_button_add_new_label',
					'repeater_button_remove_label',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder (matches the Pro repeater builder preview,
	 * which renders just the label + description; the repeatable rows are added by
	 * JS at runtime in the Pro addon).
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Option to show Repeater Field or not.
	 *
	 * @param array $field Field Data.
	 * @return void
	 */
	public function repeater_field_hide( $field ) {
		$input_field = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'repeater_field_hide',
				'value'   => isset( $field['repeater_field_hide'] ) ? $field['repeater_field_hide'] : 0,
				'desc'    => esc_html__( 'Hide Repeater Field?', 'everest-forms' ),
				'tooltip' => esc_html__( 'Hide the repeater field from the frontend form view.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'repeater_field_hide',
				'content' => $input_field,
			)
		);
	}

	/**
	 * Repeat Limit for Repeater Fields.
	 *
	 * @param array $field Field Data.
	 * @return void
	 */
	public function repeater_repeat_limit( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'repeater_repeat_limit',
				'value'   => esc_html__( 'Repeat Limit', 'everest-forms' ),
				'tooltip' => esc_html__( 'Max number of times a user can add repeater fields.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'repeater_repeat_limit',
				'class' => 'evf-input-number-repeater-repeat-limit',
				'value' => isset( $field['repeater_repeat_limit'] ) ? $field['repeater_repeat_limit'] : '',
				'min'   => 0,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'repeater_repeat_limit',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Customize label name for Add New Button.
	 *
	 * @param array $field Field Data.
	 * @return void
	 */
	public function repeater_button_add_new_label( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'repeater_button_add_new_label',
				'value'   => esc_html__( 'Add New Label', 'everest-forms' ),
				'tooltip' => esc_html__( 'Change label of Add button for repeater fields.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'text',
				'slug'  => 'repeater_button_add_new_label',
				'class' => 'evf-input-number-repeater-button-add-new-label',
				'value' => isset( $field['repeater_button_add_new_label'] ) ? $field['repeater_button_add_new_label'] : 'Add',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'repeater_button_add_new_label',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Customize label name for Remove Button.
	 *
	 * @param array $field Field Data.
	 * @return void
	 */
	public function repeater_button_remove_label( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'repeater_button_remove_label',
				'value'   => esc_html__( 'Remove Label', 'everest-forms' ),
				'tooltip' => esc_html__( 'Change label of Remove button for repeater fields.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'text',
				'slug'  => 'repeater_button_remove_label',
				'class' => 'evf-input-number-repeater-button-remove-new-label',
				'value' => isset( $field['repeater_button_remove_label'] ) ? $field['repeater_button_remove_label'] : 'Remove',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'repeater_button_remove_label',
				'content' => $label . $input_field,
			)
		);
	}
}
