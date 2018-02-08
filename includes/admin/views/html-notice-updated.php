<?php
/**
 * Admin View: Notice - Updated
 *
 * @package EverestForms\Admin\Notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated everest-forms-message evf-connect everest-forms-message--success">
	<a class="everest-forms-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'evf-hide-notice', 'update', remove_query_arg( 'do_update_everest_forms' ) ), 'everest_forms_hide_notices_nonce', '_evf_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'everest-forms' ); ?></a>

	<p><?php esc_html_e( 'Everest Forms data update complete. Thank you for updating to the latest version!', 'everest-forms' ); ?></p>
</div>
