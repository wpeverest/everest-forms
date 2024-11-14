<?php
/**
 * EverestForms EVF_REST_API
 *
 * API Handler
 *
 * @class    EVF_REST_API
 * @version  2.0.8.1
 * @package  EverestForms/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_REST_API Class
 */
class EVF_REST_API {

	/**
	 * REST API classes and endpoints.
	 *
	 * @since 2.0.8.1
	 *
	 * @var array
	 */
	protected static $rest_classes = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
	 * @since 2.0.8.1
	 */
	public static function init() {
		// For Internal.
		include __DIR__ . '/controllers/version1/class-evf-modules.php';
		include __DIR__ . '/controllers/version1/class-evf-changelog.php';
		include __DIR__ . '/controllers/version1/class-evf-gutenberg-blocks.php';
		include __DIR__ . '/controllers/version1/class-evf-templates.php';
		include __DIR__ . '/controllers/version1/class-evf-plugin-status.php';
		// For external.
		include __DIR__ . '/controllers/version1/class-evf-entry-submission.php';

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.0.8.1
	 */
	public static function register_rest_routes() {
		foreach ( self::get_rest_classes() as $rest_namespace => $classes ) {
			foreach ( $classes as $class_name ) {
				self::$rest_classes[ $rest_namespace ][ $class_name ] = new $class_name();
				self::$rest_classes[ $rest_namespace ][ $class_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API Classes - new classes should be registered here.
	 *
	 * @since 3.1.6
	 *
	 * @return array List of Classes.
	 */
	protected static function get_rest_classes() {
		/**
		 * Filters rest API controller classes.
		 *
		 * @since 2.0.8.1
		 *
		 * @param array $rest_routes API namespace to API classes index array.
		 */
		return apply_filters(
			'everest_forms_rest_api_get_rest_namespaces',
			array(
				'everest-forms/v1' => self::get_v1_rest_classes(),
			)
		);
	}

	/**
	 * List of classes in the user-registration/v1 namespace.
	 *
	 * @since 2.0.8.1
	 * @static
	 *
	 * @return array
	 */
	protected static function get_v1_rest_classes() {
		return array(
			'modules'          => 'EVF_Modules',
			'changelog'        => 'EVF_Changelog',
			'gutenberg-blocks' => 'EVF_Gutenberg_Blocks',
			'templates'        => 'Everest_Forms_Template_Section_Data',
			'plugin'           => 'Everest_Forms_Plugin_Status',
			'entry-submission' => 'EVF_Entry_Submission',
		);
	}
}

EVF_REST_API::init();
