<?php
/**
 * Handle frontend scripts
 *
 * @class   EVF_Frontend_Scripts
 * @version 1.0.0
 * @package EverestForms/Classes/
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Frontend_Scripts Class.
 */
class EVF_Frontend_Scripts {

	/**
	 * Contains an array of script handles registered by EVF.
	 *
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of script handles registered by EVF.
	 *
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Contains an array of script handles localized by EVF.
	 *
	 * @var array
	 */
	private static $wp_localize_scripts = array();

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
	}

	/**
	 * Get styles for the frontend.
	 *
	 * @return array
	 */
	public static function get_styles() {
		return apply_filters(
			'everest_forms_enqueue_styles',
			array(
				'everest-forms-general' => array(
					'src'     => self::get_asset_url( 'assets/css/everest-forms.css' ),
					'deps'    => '',
					'version' => EVF_VERSION,
					'media'   => 'all',
					'has_rtl' => true,
				),
			)
		);
	}

	/**
	 * Return asset URL.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	private static function get_asset_url( $path ) {
		return apply_filters( 'everest_forms_get_asset_url', plugins_url( $path, EVF_PLUGIN_FILE ), $path );
	}

	/**
	 * Register a script for use.
	 *
	 * @uses   wp_register_script()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  boolean  $in_footer
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = EVF_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @uses   wp_enqueue_script()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  boolean  $in_footer
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = EVF_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Register a style for use.
	 *
	 * @uses   wp_register_style()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 * @param  boolean  $has_rtl
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = EVF_VERSION, $media = 'all', $has_rtl = false ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @uses   wp_enqueue_style()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 * @param  boolean  $has_rtl
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = EVF_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register all EVF scripts.
	 */
	private static function register_scripts() {
		$suffix           = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$register_scripts = array(
			'inputmask'       => array(
				'src'     => self::get_asset_url( 'assets/js/inputmask/jquery.inputmask.bundle' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '4.0.0-beta.58',
			),
			'flatpickr'       => array(
				'src'     => self::get_asset_url( 'assets/js/flatpickr/flatpickr' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '4.5.1',
			),
			'jquery-validate' => array(
				'src'     => self::get_asset_url( 'assets/js/jquery-validate/jquery.validate' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '1.17.0',
			),
			'everest-forms'   => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/everest-forms' . $suffix . '.js' ),
				'deps'    => array( 'jquery', 'inputmask', 'jquery-validate' ),
				'version' => EVF_VERSION,
			),
		);
		foreach ( $register_scripts as $name => $props ) {
			self::register_script( $name, $props['src'], $props['deps'], $props['version'] );
		}
	}

	/**
	 * Register all EVF sty;es.
	 */
	private static function register_styles() {
		$register_styles = array(
			'select2'   => array(
				'src'     => self::get_asset_url( 'assets/css/select2.css' ),
				'deps'    => array(),
				'version' => EVF_VERSION,
				'has_rtl' => false,
			),
			'flatpickr' => array(
				'src'     => self::get_asset_url( 'assets/css/flatpickr.css' ),
				'deps'    => array(),
				'version' => EVF_VERSION,
				'has_rtl' => false,
			),
		);
		foreach ( $register_styles as $name => $props ) {
			self::register_style( $name, $props['src'], $props['deps'], $props['version'], 'all', $props['has_rtl'] );
		}
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function load_scripts() {
		global $post;

		if ( ! did_action( 'before_everest_forms_init' ) ) {
			return;
		}

		self::register_scripts();
		self::register_styles();

		// Enqueue dashicons.
		wp_enqueue_style( 'dashicons' );

		// CSS Styles.
		if ( $enqueue_styles = self::get_styles() ) {
			foreach ( $enqueue_styles as $handle => $args ) {
				if ( ! isset( $args['has_rtl'] ) ) {
					$args['has_rtl'] = false;
				}

				self::enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'], $args['has_rtl'] );
			}
		}
	}

	/**
	 * Localize a EVF script once.
	 *
	 * @access private
	 *
	 * @param  string $handle
	 */
	private static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$wp_localize_scripts ) && wp_script_is( $handle ) && ( $data = self::get_script_data( $handle ) ) ) {
			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
	 *
	 * @access private
	 *
	 * @param  string $handle
	 *
	 * @return array|bool
	 */
	private static function get_script_data( $handle ) {
		switch ( $handle ) {
			case 'everest-forms':
				$params = array(
					'ajax_url'                => EVF()->ajax_url(),
					'everest_forms_data_save' => wp_create_nonce( 'everest_forms_data_save_nonce' ),
					'i18n_messages_required'  => get_option( 'everest_forms_required_validation' ),
					'i18n_messages_url'       => get_option( 'everest_forms_url_validation' ),
					'i18n_messages_email'     => get_option( 'everest_forms_email_validation' ),
					'i18n_messages_number'    => get_option( 'everest_forms_number_validation' ),
				);
				break;
			default:
				$params = false;
		}

		return apply_filters( 'everest_forms_get_script_data', $params, $handle );
	}

	/**
	 * Localize scripts only when enqueued.
	 */
	public static function localize_printed_scripts() {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}
}

EVF_Frontend_Scripts::init();
