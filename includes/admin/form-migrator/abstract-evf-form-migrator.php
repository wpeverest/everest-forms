<?php
/**
 * EverestForms Form Migrator Class
 *
 * @package EverestForms\Admin
 * @since   2.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Form_Migrator class.
 */
abstract class EVF_Admin_Form_Migrator {
	/**
	 * Importer name.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Importer name in slug format.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Importer plugin path.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.6
	 */
	public function __construct() {

		$this->init();
	}
	/**
	 * Undocumented function
	 *
	 * @since 2.0.6
	 */
	abstract public function init();

	/**
	 * Add to list of registered importers.
	 *
	 * @since 2.0.6
	 *
	 * @param array $importers List of supported importers.
	 *
	 * @return array
	 */
	public function register( $importers = [] ) {

		$importers[ $this->slug ] = [
			'name'      => $this->name,
			'slug'      => $this->slug,
			'path'      => $this->path,
			'installed' => file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path ),
			'active'    => $this->is_active(),
		];

		return $importers;
	}

	/**
	 * If the importer source is available.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_active() {

		return is_plugin_active( $this->path );
	}
}
