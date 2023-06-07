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
	 * @param string $path Assets path.
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
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = EVF_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @uses   wp_enqueue_script()
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = EVF_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Register a style for use.
	 *
	 * @uses   wp_register_style()
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
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
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = EVF_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register all EVF scripts.
	 */
	private static function register_scripts() {
		if ( evf_is_amp() ) {
			return;
		}

		$suffix           = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$register_scripts = array(
			'inputmask'                     => array(
				'src'     => self::get_asset_url( 'assets/js/inputmask/jquery.inputmask.bundle' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '4.0.0-beta.58',
			),
			'flatpickr'                     => array(
				'src'     => self::get_asset_url( 'assets/js/flatpickr/flatpickr' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '4.6.3',
			),
			'mailcheck'                     => array(
				'src'     => self::get_asset_url( 'assets/js/mailcheck/mailcheck' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '1.1.2',
			),
			'selectWoo'                     => array(
				'src'     => self::get_asset_url( 'assets/js/selectWoo/selectWoo.full' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '1.0.8',
			),
			'jquery-validate'               => array(
				'src'     => self::get_asset_url( 'assets/js/jquery-validate/jquery.validate' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '1.19.2',
			),
			'everest-forms'                 => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/everest-forms' . $suffix . '.js' ),
				'deps'    => array( 'jquery', 'inputmask', 'jquery-validate' ),
				'version' => EVF_VERSION,
			),
			'everest-forms-text-limit'      => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/text-limit' . $suffix . '.js' ),
				'deps'    => array(),
				'version' => EVF_VERSION,
			),
			'everest-forms-ajax-submission' => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/ajax-submission' . $suffix . '.js' ),
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
			'evf_select2' => array(
				'src'     => self::get_asset_url( 'assets/css/select2.css' ),
				'deps'    => array(),
				'version' => EVF_VERSION,
				'has_rtl' => false,
			),
			'flatpickr'   => array(
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
		$enqueue_styles = self::get_styles();
		if ( $enqueue_styles ) {
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
	 * @param string $handle Script handle the data will be attached to.
	 */
	private static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$wp_localize_scripts, true ) && wp_script_is( $handle ) ) {
			$data = self::get_script_data( $handle );

			if ( ! $data ) {
				return;
			}

			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
	 *
	 * @param  string $handle Script handle the data will be attached to.
	 * @return array|bool
	 */
	private static function get_script_data( $handle ) {
		switch ( $handle ) {
			case 'everest-forms':
				$params = array(
					'ajax_url'                             => evf()->ajax_url(),
					'submit'                               => esc_html__( 'Submit', 'everest-forms' ),
					'disable_user_details'                 => get_option( 'everest_forms_disable_user_details' ),
					'everest_forms_data_save'              => wp_create_nonce( 'everest_forms_data_save_nonce' ),
					'i18n_messages_required'               => get_option( 'everest_forms_required_validation' ),
					'i18n_messages_url'                    => get_option( 'everest_forms_url_validation' ),
					'i18n_messages_email'                  => get_option( 'everest_forms_email_validation' ),
					'i18n_messages_email_suggestion'       => get_option( 'everest_forms_email_suggestion', esc_html__( 'Did you mean {suggestion}?', 'everest-forms' ) ),
					'i18n_messages_email_suggestion_title' => esc_attr__( 'Click to accept this suggestion.', 'everest-forms' ),
					'i18n_messages_confirm'                => get_option( 'everest_forms_confirm_validation', __( 'Field values do not match.', 'everest-forms' ) ),
					'i18n_messages_check_limit'            => get_option( 'everest_forms_check_limit_validation', esc_html__( 'You have exceeded number of allowed selections: {#}.', 'everest-forms' ) ),
					'i18n_messages_number'                 => get_option( 'everest_forms_number_validation' ),
					'i18n_no_matches'                      => _x( 'No matches found', 'enhanced select', 'everest-forms' ),
					'mailcheck_enabled'                    => (bool) apply_filters( 'everest_forms_mailcheck_enabled', true ),
					'mailcheck_domains'                    => array_map( 'sanitize_text_field', (array) apply_filters( 'everest_forms_mailcheck_domains', array() ) ),
					'mailcheck_toplevel_domains'           => array_map( 'sanitize_text_field', (array) apply_filters( 'everest_forms_mailcheck_toplevel_domains', array( 'dev' ) ) ),
					'il8n_min_word_length_err_msg'         => esc_html__( 'Please enter at least {0} words.', 'everest-forms' ),
				);
				break;
			case 'everest-forms-text-limit':
				$params = array(
					'i18n_messages_limit_characters' => esc_html__( '{count} of {limit} max characters.', 'everest-forms' ),
					'i18n_messages_limit_words'      => esc_html__( '{count} of {limit} max words.', 'everest-forms' ),
				);
				break;
			case 'everest-forms-ajax-submission':
				$params = array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'evf_ajax_submission' => wp_create_nonce( 'everest_forms_ajax_form_submission' ),
					'submit'              => esc_html__( 'Submit', 'everest-forms' ),
					'error'               => esc_html__( 'Something went wrong while making an AJAX submission', 'everest-forms' ),
					'required'            => esc_html__( 'This field is required.', 'everest-forms' ),
					'pdf_download'        => esc_html__( 'Click here to download your pdf submission', 'everest-forms' ),
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
