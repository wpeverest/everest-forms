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
				'name'            => __( 'Everest Forms', 'everest-forms-pro' ),
				'description'     => __( 'Renders the everest form', 'everest-forms-pro' ),
				'category'        => __( 'Everest Forms', 'everest-forms-pro' ),
				'dir'             => __DIR__,
				'url'             => __DIR__,
				'editor_export'   => true,
				'enabled'         => true,
				'partial_refresh' => false,
				'include_wrapper' => false,
			)
		);
	}

}
