<?php
/**
 * Admin View: Page - Status Logs
 *
 * @package EverestForms/Admin/Logs
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $logs ) : ?>


<!-- Log Selection Dropdown -->
<div id="log-viewer-select"
	style="margin-bottom: 24px; padding: 0 0 24px; border-bottom: 1px solid #DCDCDC;">
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=logs' ) ); ?>" method="post"
		style="display: flex; gap: 10px; align-items: center;">
		<select name="log_file"
			style="max-width: 700px; width: 100%; min-height: 38px; line-height: 20px; padding: 8px 14px; color: #383838; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
			<?php foreach ( $logs as $log_key => $log_file ) : ?>
			<?php
						$timestamp = filemtime( EVF_LOG_DIR . $log_file );
						$date      = sprintf( __( '%1$s at %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
				?>
			<option value="<?php echo esc_attr( $log_key ); ?>"
				<?php selected( sanitize_title( $viewed_log ), $log_key ); ?>>
				<?php echo esc_html( $log_file ); ?> (<?php echo esc_html( $date ); ?>)
			</option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="button button-primary"
			style="background: #7545BB;  color: #fff; border-color: #7545BB; font-size: 14px; line-height: 20px; font-weight: 500; padding: 8px 16px;">
			<?php esc_html_e( 'Apply', 'everest-forms' ); ?>
		</button>
	</form>
</div>

<!-- Top Toolbar -->
<div id="log-viewer-toolbar"
	style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
	<div class="alignleft" style="font-size: 16px; font-weight: 500;">
		<?php echo esc_html( $viewed_log ); ?>
	</div>
	<div class="alignright" style="display: flex; gap: 10px;">
		<!-- Delete All Logs Button -->
		<?php if ( 1 < count( $logs ) ) : ?>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle_all' => 'delete-all-logs' ), admin_url( 'admin.php?page=evf-tools&tab=logs' ) ), 'remove_all_logs' ) ); ?>"
			class="button button-secondary" style="border-color: #F25656; background: #F25656; color: #ffffff; font-size: 14px; line-height: 20px; padding: 8px 14px; font-weight: 500;">
			<?php esc_html_e( 'Delete All Logs', 'everest-forms' ); ?>
		</a>
		<?php endif; ?>
		<!-- Delete Log Button -->
		<?php if ( ! empty( $viewed_log ) ) : ?>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title( $viewed_log ) ), admin_url( 'admin.php?page=evf-tools&tab=logs' ) ), 'remove_log' ) ); ?>"
			class="button button-secondary" style="border-color: #475BB2; color: #475BB2; font-size: 14px; line-height: 20px; padding: 8px 14px; font-weight: 500;">
			<?php esc_html_e( 'Delete Log', 'everest-forms' ); ?>
		</a>
		<?php endif; ?>
	</div>
</div>

<!-- Log Viewer Content -->
<div id="log-viewer"
	style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #ffffff; font-size: 13px;">
	<pre
		style="white-space: pre-wrap; word-wrap: break-word; margin: 0;"><?php echo esc_html( file_get_contents( EVF_LOG_DIR . $viewed_log ) ); ?></pre>
</div>

<?php else : ?>
<!-- No Logs Found -->
<div class="notice notice-warning inline"
	style="padding: 15px; border-left: 4px solid #ffba00; background: #fff;">
	<p><?php esc_html_e( 'There are currently no logs to view.', 'everest-forms' ); ?></p>
</div>
<?php endif; ?>
