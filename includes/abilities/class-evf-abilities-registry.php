<?php
/**
 * Internal abilities registry.
 *
 * Used as a fallback when the WordPress Abilities API is not installed,
 * so the MCP endpoint always has a consistent set of abilities to serve.
 *
 * @package EverestForms\Abilities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight ability registry.
 */
class EVF_Abilities_Registry {

	/**
	 * Singleton.
	 *
	 * @var EVF_Abilities_Registry|null
	 */
	protected static $instance = null;

	/**
	 * Ability map keyed by fully qualified name (namespace/name).
	 *
	 * @var array<string, array>
	 */
	protected $abilities = array();

	/**
	 * Singleton accessor.
	 *
	 * @return EVF_Abilities_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Bulk register abilities from EVF_Abilities::ability_definitions().
	 *
	 * @param array $definitions Definitions list.
	 */
	public function bulk_register( array $definitions ) {
		foreach ( $definitions as $def ) {
			$full = EVF_Abilities::NAMESPACE_ID . '/' . $def['name'];
			$this->abilities[ $full ] = $def['args'];
		}
	}

	/**
	 * Get all known ability ids and metadata.
	 *
	 * Prefers the WP Abilities API registry when available.
	 *
	 * @return array<string, array>
	 */
	public function all() {
		return $this->abilities;
	}

	/**
	 * Execute an ability by id, returning either the result or a WP_Error.
	 *
	 * @param string $id    Fully qualified ability id (namespace/name).
	 * @param array  $input Input arguments.
	 * @return mixed
	 */
	public function execute( $id, array $input ) {
		if ( ! isset( $this->abilities[ $id ] ) ) {
			return new WP_Error( 'evf_ability_not_found', sprintf( 'Unknown ability: %s', $id ), array( 'status' => 404 ) );
		}
		$def = $this->abilities[ $id ];
		if ( isset( $def['permission_callback'] ) && is_callable( $def['permission_callback'] ) ) {
			$ok = call_user_func( $def['permission_callback'], $input );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}
			if ( ! $ok ) {
				return new WP_Error( 'evf_ability_forbidden', 'Permission denied.', array( 'status' => 403 ) );
			}
		}
		if ( ! isset( $def['execute_callback'] ) || ! is_callable( $def['execute_callback'] ) ) {
			return new WP_Error( 'evf_ability_no_callback', 'Ability has no executor.', array( 'status' => 500 ) );
		}
		return call_user_func( $def['execute_callback'], $input );
	}
}
