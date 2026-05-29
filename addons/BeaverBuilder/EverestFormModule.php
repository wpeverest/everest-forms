<?php
/**
 * Custom Course Lists Module Class
 *
 * @since 1.10.0 [Free]
 */


namespace EverestForms\Addons\BeaverBuilder;

class EverestFormModule extends \FLBuilderModule {
	public function __construct() {
		parent::__construct(
			array(
				'name'            => __( 'Everest Forms', 'everest-forms' ),
				'description'     => __( 'Renders the everest form', 'everest-forms' ),
				'category'        => __( 'Everest Forms', 'everest-forms' ),
				'dir'             => __DIR__,
				'url'             => __DIR__,
				'editor_export'   => true,
				'enabled'         => true,
				'partial_refresh' => false,
				'include_wrapper' => false,
			)
		);
	}

	/**
	 * Icon for the Everest Forms module.
	 *
	 * @since 3.0.5
	 *
	 * @param  string $icon
	 */
	public function get_icon( $icon = '' ) {
		return file_get_contents( evf()->plugin_path() . '/assets/images/icons/everest_forms_black_logo.svg' );
	}
}
