<?php
/**
 * Admin View: Custom Notices
 *
 * @package EverestForms\Admin\Notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated everest-forms-message">
	<a class="everest-forms-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'evf-hide-notice', $notice ), 'everest_forms_hide_notices_nonce', '_evf_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'everest-forms' ); ?></a>

	<?php echo wp_kses_post( wpautop( $notice_html ) ); ?>
</div>
