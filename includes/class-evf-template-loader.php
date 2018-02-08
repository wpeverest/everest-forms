<?php
/**
 * Template Loader
 *
 * @version 1.0.0
 * @package EverestForms\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Template_Loader Class.
 */
class EVF_Template_Loader {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. everest-forms looks for theme.
	 * overrides in /theme/everest-forms/ by default.
	 *
	 * For beginners, it also looks for a everest-forms.php template first. If the user adds.
	 * this to the theme (containing a everest-forms() inside) this will be used for all.
	 * everest-forms templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		if ( $default_file = self::get_template_loader_default_file() ) {
			/**
			 * Filter hook to choose which files to find before EverestForms does it's own logic.
			 *
			 * @since 1.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || EVF_TEMPLATE_DEBUG_MODE ) {
				$template = EVF()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		return '';
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  1.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'everest_forms_template_loader_files', array(), $default_file );
		$search_files[] = 'everest-forms.php';

		if ( is_page_template() ) {
			$search_files[] = get_page_template_slug();
		}

		$search_files[] = $default_file;
		$search_files[] = EVF()->template_path() . $default_file;

		return array_unique( $search_files );
	}
}
