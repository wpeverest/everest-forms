<?php
/**
 * EverestForms Form Panels.
 *
 * Loads form panels.
 *
 * @package EverestForms\Classes\Panels
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form panels class.
 */
class EVF_Form_Panels {

	/**
	 * Form Panels classes.
	 *
	 * @var array
	 */
	public $form_panels = array();

	/**
	 * The single instance of the class.
	 *
	 * @var EVF_Form_Panels
	 */
	protected static $_instance = null;

	/**
	 * Main EVF_Form_Panels Instance.
	 *
	 * Ensures only one instance of EVF_Form_Panels is loaded or can be loaded.
	 *
	 * @return EVF_Form_Panels Main instance.
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
	 * Initialize form panels.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Load panels and hook in functions.
	 */
	public function init() {
		$load_panels = apply_filters( 'everest_forms_panels', array(
			'EVF_Panel_Fields',
			'EVF_Panel_Settings',
		) );

		// Get sort order.
		$order_end = 999;

		// Load form panels.
		foreach ( $load_panels as $panel ) {
			$load_panel = is_string( $panel ) ? new $panel() : $panel;

			if ( isset( $load_panel->order ) && is_numeric( $load_panel->order ) ) {
				// Add in position.
				$this->form_panels[ $load_panel->order ] = $load_panel;
			} else {
				// Add to end of the array.
				$this->form_panels[ $order_end ] = $load_panel;
				$order_end++;
			}
		}

		ksort( $this->form_panels );
	}

	/**
	 * Get panels.
	 *
	 * @return array
	 */
	public function form_panels() {
		$_available_panels = array();

		if ( count( $this->form_panels ) > 0 ) {
			foreach ( $this->form_panels as $panel ) {
				$_available_panels[ $panel->id ] = $panel;
			}
		}

		return $_available_panels;
	}

	/**
	 * Get array of registered panel ids
	 *
	 * @return array of strings
	 */
	public function get_form_panels_ids() {
		return wp_list_pluck( $this->form_panels, 'id' );
	}
}
