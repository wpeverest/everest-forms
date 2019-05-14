<?php
/**
 * EverestForms Form Fields
 *
 * Loads form fields via hooks for use in the builder.
 *
 * @package EverestForms\Classes\Fields
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form fields class.
 */
class EVF_Fields {

	/**
	 * Form fields classes.
	 *
	 * @var array
	 */
	public $form_fields = array();

	/**
	 * The single instance of the class.
	 *
	 * @var EVF_Fields
	 */
	protected static $_instance = null;

	/**
	 * Main EVF_Fields Instance.
	 *
	 * Ensures only one instance of EVF_Fields is loaded or can be loaded.
	 *
	 * @return EVF_Fields Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.2.0
	 */
	public function __clone() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'everest-forms' ), '1.2.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.2.0
	 */
	public function __wakeup() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'everest-forms' ), '1.2.0' );
	}

	/**
	 * Initialize form fields.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Load fields and hook in functions.
	 */
	public function init() {
		$load_fields = apply_filters(
			'everest_forms_fields',
			array(
				'EVF_Field_First_Name',
				'EVF_Field_Last_Name',
				'EVF_Field_Text',
				'EVF_Field_Textarea',
				'EVF_Field_Select',
				'EVF_Field_Radio',
				'EVF_Field_Checkbox',
				'EVF_Field_Number',
				'EVF_Field_Email',
				'EVF_Field_URL',
				'EVF_Field_Date_Time',
			)
		);

		// Get sort order.
		$order_end = 999;

		// Load form fields.
		foreach ( $load_fields as $field ) {
			$load_field = is_string( $field ) ? new $field() : $field;

			if ( isset( $load_field->order ) && is_numeric( $load_field->order ) ) {
				// Add in position.
				$this->form_fields[ $load_field->group ][ $load_field->order ] = $load_field;
			} else {
				// Add to end of the array.
				$this->form_fields[ $load_field->group ][ $order_end ] = $load_field;
				$order_end++;
			}

			ksort( $this->form_fields[ $load_field->group ] );
		}
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function form_fields() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( $this->form_fields as $group => $field ) {
				$_available_fields[ $group ] = $field;
			}
		}

		return $_available_fields;
	}

	/**
	 * Get array of registered field types.
	 *
	 * @return array of strings
	 */
	public function get_form_field_types() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( array_values( $this->form_fields ) as $form_field ) {
				foreach ( $form_field as $field ) {
					$_available_fields[] = $field->type;
				}
			}
		}

		return $_available_fields;
	}

	/**
	 * Get array of registered "Pro" field types.
	 *
	 * @return array of strings
	 */
	public function get_pro_form_field_types() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( array_values( $this->form_fields ) as $form_field ) {
				foreach ( $form_field as $field ) {
					if ( $field->is_pro ) {
						$_available_fields[] = $field->type;
					}
				}
			}
		}

		return $_available_fields;
	}
}
