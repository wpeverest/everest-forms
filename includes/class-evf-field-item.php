<?php
/**
 * EverestForms Fields
 *
 * @author   WPEverest
 * @category Classes
 * @package  EverestForms
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Everest Forms Field Class
 *
 * @class      EverestForms
 * @version    1.0.0
 */
class EVF_Field_Item {
	/**
	 * Primary class constructor.
	 *
	 * @since      1.0.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Load and init the base field class.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Parent class template
		require_once EVF_ABSPATH . 'includes/abstracts/abstract-evf-form-fields.php';

		// Load default fields on WP init
		add_action( 'init', array( $this, 'load' ) );
	}

	/**
	 * Load default field types.
	 *
	 * @since      1.0.0
	 */
	public function load() {

		$fields = apply_filters( 'everest_forms_load_fields', array(
			'first-name',
			'last-name',
			'text',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'email',
			'url',
			'hidden',
			'html',
			'name',
			'password',
			'address',
			'phone',
			'date-time',
			'number',

		) );

		foreach ( $fields as $field ) {

			$field_class_path = ( EVF_ABSPATH . 'includes/form-fields/class-evf-field-' . $field . '.php' );
			if ( file_exists( $field_class_path ) ) {
				require_once $field_class_path;
			}
		}
	}
}

new EVF_Field_Item;
