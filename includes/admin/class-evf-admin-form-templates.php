<?php
/**
 * EverestForms Form Templates
 *
 * @package  EverestForms /Admin/Form Templates
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * EVF_Admin_Form_Templates class
 */
class EVF_Admin_Form_Templates {

	/**
	 * Get default template.
	 *
	 * @return array
	 */
	private static function get_default_template() {
		$template        = new stdClass();
		$template->title = __( 'Start From Scratch', 'everest-forms' );
		$template->slug  = 'blank';
		$template->image = untrailingslashit( plugin_dir_url( EVF_PLUGIN_FILE ) ) . '/assets/images/templates/blank.png';
		$template->plan  = array( 'free', 'premium' );

		return array( $template );
	}

	/**
	 * Get section content for the template screen.
	 *
	 * @return array
	 */
	public static function get_template_data() {
		$template_data = get_transient( 'evf_template_section_list' );

		$template_url = 'https://d3m99fsxk070py.cloudfront.net/';

		if ( false === $template_data ) {

			$template_json_url = $template_url . 'templates.json';
			try {
				$content      = wp_remote_get( $template_json_url );
				$content_json = wp_remote_retrieve_body( $content );

				$template_data = json_decode( $content_json );
			} catch ( Exception $e ) {

			}

			// Removing directory so the templates can be reinitialized.
			$folder_path = untrailingslashit( plugin_dir_path( EVF_PLUGIN_FILE ) . '/assets/images/templates' );
			if ( isset( $template_data->templates ) ) {

				foreach ( $template_data->templates as $template_tuple ) {

					$image_url = isset( $template_tuple->image ) ? $template_tuple->image : ( $template_url . 'images/' . $template_tuple->slug . '.png' );

					$template_tuple->image = $image_url;

					$temp_name     = explode( '/', $image_url );
					$relative_path = $folder_path . '/' . end( $temp_name );
					$exists        = file_exists( $relative_path );

					// If it exists, utilize this file instead of remote file.
					if ( $exists ) {
						$template_tuple->image = untrailingslashit( plugin_dir_url( EVF_PLUGIN_FILE ) ) . '/assets/images/templates/' . untrailingslashit( $template_tuple->slug ) . '.png';
					}
				}

				set_transient( 'evf_template_section_list', $template_data, WEEK_IN_SECONDS );
			}
		}

		return isset( $template_data->templates ) ? apply_filters( 'everest_forms_template_section_data', $template_data->templates ) : self::get_default_template();
	}

	/**
	 * Load the template view.
	 *
	 * @since 1.0.0
	 */
	public static function load_template_view() {
		echo "<div id='evf-templates'></div>";
		wp_register_script( 'evf-templates', plugins_url( 'dist/templates.min.js', EVF_PLUGIN_FILE ), array( 'wp-element', 'react', 'react-dom', 'wp-api-fetch', 'wp-i18n', 'wp-blocks' ), EVF_VERSION, true );
		wp_localize_script(
			'evf-templates',
			'evf_templates_script',
			array(
				'security' => wp_create_nonce( 'wp_rest' ),
				'restURL'  => rest_url(),
			)
		);
		wp_enqueue_script( 'evf-templates' );
	}

}
