<?php
/**
 * Abstract Integration class
 *
 * Extension of the Settings API which in turn gets extended
 * by individual integrations to offer additional functionality.
 *
 * @package EverestForms\Abstracts
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Integration Class.
 */
abstract class EVF_Integration extends EVF_Settings_API {

	/**
	 * Yes or no based on whether the integration is enabled.
	 *
	 * @var string
	 */
	public $enabled = 'yes';

	/**
	 * Integration icon.
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * Integration title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Integration description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * Get integration ID
	 *
	 * @return array Integration stored data.
	 */
	public function get_integration() {
		$integrations = get_option( 'everest_forms_integrations', array() );

		return in_array( $this->id, array_keys( $integrations ), true ) ? $integrations[ $this->id ] : array();
	}

	/**
	 * Return the title for admin screens.
	 *
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'everest_forms_integration_title', $this->method_title, $this );
	}

	/**
	 * Return the description for admin screens.
	 *
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'everest_forms_integration_description', $this->method_description, $this );
	}

	/**
	 * Output the gateway settings screen.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';
		parent::admin_options();
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}

	/**
	 * Check if is integration page.
	 *
	 * @return bool
	 */
	public function is_integration_page() {
		return isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && 'evf-settings' === $_GET['page'] && 'integration' === $_GET['tab'] && (string) $this->id === $_GET['section']; // phpcs:ignore WordPress.Security.NonceVerification
	}
}
