<?php
/**
 * Load assets
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin
 * @version     1.00
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EVF_Admin_Assets', false ) ) :

	/**
	 * EVF_Admin_Assets Class.
	 */
	class EVF_Admin_Assets {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'everest_forms_builder_scripts', array( $this, 'everest_forms_builder_scripts' ) );
		}

		/**
		 * Enqueue assets for the builder.
		 *
		 * @since      1.0.0
		 */
		public function everest_forms_builder_scripts() {

			// Remove conflicting scripts

			do_action( 'everest_forms_builder_enqueues_before' );

			wp_enqueue_style( 'everest_forms_admin_menu_styles', EVF()->plugin_url() . '/assets/css/everest-builder.css', array(), EVF_VERSION );
			wp_enqueue_style( 'jquery-confirm-style', EVF()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );

			wp_enqueue_script(
				'evf-admin-helper',
				EVF()->plugin_url() . '/assets/js/admin/admin-helper.js',
				array(

					'jquery',
					'jquery-blockui',
					'jquery-tiptip',
					'jquery-ui-sortable',
					'jquery-ui-widget',
					'jquery-ui-core',
					'jquery-ui-tabs',
					'jquery-ui-draggable',
					'jquery-ui-droppable',
					'jquery-tiptip',

				),
				EVF_VERSION
			);

			wp_enqueue_script(
				'evf-panel-builder',
				EVF()->plugin_url() . '/assets/js/admin/everest-panel-builder.js',
				array( 'evf-admin-helper', 'jquery-confirm-script' ),
				EVF_VERSION
			);

			// JS

			wp_enqueue_media();

			$strings = array(
				'ajax_url'                             => admin_url( 'admin-ajax.php' ),
				'evf_field_drop_nonce'                 => wp_create_nonce( 'everest_forms_field_drop' ),
				'evf_save_form'                        => wp_create_nonce( 'everest_forms_save_form' ),
				'evf_get_next_id'                      => wp_create_nonce( 'everest_forms_get_next_id' ),
				'form_id'                              => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,
				'are_you_sure_want_to_delete_field'    => __( 'Are you sure want to delete this field?', 'everest-forms' ),
				'field'                                => __( 'field', 'everest-forms' ),
				'confirm'                              => __( 'Confirm', 'everest-forms' ),
				'cancel'                               => __( 'Cancel', 'everest-forms' ),
				'are_you_sure_want_to_duplicate_field' => __( 'Are you sure want to duplicate this field?', 'everest-forms' ),
				'are_you_sure_want_to_delete_row'      => __( 'Are you sure want to delete this row?', 'everest-forms' ),
				'copy_of'                              => __( 'Copy of ', 'everest-forms' ),
				'ok'                                   => __( 'Ok', 'everest-forms' ),
				'could_not_delete_single_row_content'  => __( 'Could not delete single row.', 'everest-forms' ),
				'could_not_delete_single_choice'       => __( 'This item must contain at least one choice.', 'everest-forms' ),
				'tab'                                  => isset( $_GET['tab'] ) ? $_GET['tab'] : '',
			);
			$strings = apply_filters( 'everest_forms_builder_strings', $strings );

			wp_localize_script(
				'evf-panel-builder',
				'evf_data',
				$strings
			);
		}

		/**
		 * Enqueue styles.
		 */
		public function admin_styles() {
			global $wp_scripts;

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';


			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

			// Sitewide menu CSS
			wp_register_style( 'everest_forms_admin_menu_styles', EVF()->plugin_url() . '/assets/css/menu.css', array(), EVF_VERSION );

			wp_register_style ( 'evf_add_form_css', EVF()->plugin_url() . '/assets/css/evf-add-form.css', array(), EVF_VERSION );
			wp_enqueue_style('evf_add_form_css');

			// Add RTL support for admin styles
			wp_style_add_data( 'everest_forms_admin_menu_styles', 'rtl', 'replace' );

			// Register admin styles
			wp_register_style( 'evf-admin-entries-style', EVF()->plugin_url() . '/assets/css/admin-entries.css', array(), EVF_VERSION );
			wp_register_style( 'evf-admin-setting-style', EVF()->plugin_url() . '/assets/css/admin-settings.css', array(), EVF_VERSION );

			if ( in_array( $screen_id, array( 'toplevel_page_everest-forms', 'everest-forms_page_evf-entries' ), true ) ) {
				wp_enqueue_style( 'evf-admin-entries-style' );
			}

			if ( $screen_id === 'everest-forms_page_evf-settings' ) {
				wp_enqueue_style( 'evf-admin-setting-style' );
			}
		}

		/**
		 * Enqueue scripts.
		 */
		public function admin_scripts() {
			global $wp_query, $post;

			$screen        = get_current_screen();
			$screen_id     = $screen ? $screen->id : '';
			$evf_screen_id = sanitize_title( __( 'EverestForms', 'everest-forms' ) );
			$suffix        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register scripts
			wp_register_script( 'everest_forms_builder', EVF()->plugin_url() . '/assets/js/admin/everest-builder' . $suffix . '.js', array(
				'jquery',
			), EVF_VERSION );

			wp_register_script( 'everest_forms_settings', EVF()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array(
				'jquery',
			), EVF_VERSION );


			wp_register_script( 'jquery-confirm-script', EVF()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm.min.js', array(
				'jquery',
			), '3.3.0' );
			wp_register_script( 'everest_forms_admin', EVF()->plugin_url() . '/assets/js/admin/everest-forms-admin' . $suffix . '.js', array(
				'jquery',
				'jquery-blockui',
				'jquery-ui-sortable',
				'jquery-ui-widget',
				'jquery-ui-core',
				'jquery-tiptip'
			), EVF_VERSION );
			wp_register_script( 'jquery-blockui', EVF()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
			wp_register_script( 'jquery-tiptip', EVF()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
			wp_register_script( 'evf_add_form_js', EVF()->plugin_url() . '/assets/js/admin/evf-add-form' . $suffix . '.js', 'jquery' );
			wp_localize_script( 'evf_add_form_js', 'everest_add_form_params', array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'create_form_nonce' => wp_create_nonce( 'everest_forms_create_form' ),
			) );

			wp_enqueue_script('evf_add_form_js');

			if ( 'everest-forms_page_evf-settings' === $screen_id ) {
				wp_enqueue_script( 'everest_forms_settings' );

			}
			// EverestForms admin pages
			if ( in_array( $screen_id, evf_get_screen_ids() ) ) {

				wp_enqueue_script( 'everest_forms_admin' );

				$params = array(

					'urls' => array(
						'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
						'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
					),
				);

				wp_localize_script( 'everest_forms_admin', 'everest_forms_admin', $params );
			}
		}
	}

endif;

return new EVF_Admin_Assets();
