<?php
/**
 * EverestForms Admin Forms Class
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Forms Class.
 */
class EVF_Admin_Forms {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		global $forms_table_list;

		$forms_table_list->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=edit-evf-form' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
			<hr class="wp-header-end">
			<form id="form-list" method="post">
				<input type="hidden" name="page" value="everest-forms"/>
				<?php
					$forms_table_list->views();
					$forms_table_list->search_box( __( 'Search Forms', 'everest-forms' ), 'everest-forms' );
					$forms_table_list->display();

					wp_nonce_field( 'save', 'everest-forms_nonce' );
				?>
			</form>
		</div>
		<?php
		if ( isset( $_GET['edit-evf-form'] ) ) { // WPCS: input var okay, CSRF ok.
			add_action( 'everest_form_list_admin_footer', array( __CLASS__, 'everest_form_list_admin_footer' ), 10, 1 );
		}
	}

	public static function everest_form_list_admin_footer( $screen_id ) {
		if ( $screen_id === 'toplevel_page_everest-forms' ) {
			include_once( dirname( __FILE__ ) . '/views/html-admin-form-modal.php' );

			wp_enqueue_style( 'evf-form-modal-style', EVF()->plugin_url() . '/assets/css/evf-form-modal.css', array(), EVF_VERSION );
			wp_enqueue_script( 'evf-admin-form-modal', EVF()->plugin_url() . '/assets/js/admin/evf-form-modal.js', array( 'underscore', 'backbone', 'wp-util' ), EVF_VERSION );

			$strings = apply_filters( 'everest_forms_builder_modal_strings', array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'evf_new_form_nonce' => wp_create_nonce( 'evf_new_form' ),
			) );

			wp_localize_script( 'evf-admin-form-modal', 'evf_form_modal_data', $strings );
		}
	}
}
