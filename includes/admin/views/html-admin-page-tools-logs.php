<?php
/**
 * Admin View: Page - Status Logs
 *
 * @package EverestForms/Admin/Logs
 */

defined( 'ABSPATH' ) || exit;

?>
<?php if ( $logs ) : ?>
	<div id="log-viewer-select">
		<div class="alignleft">
			<h2>
				<?php echo esc_html( $viewed_log ); ?>
				<?php if ( ! empty( $handle ) ) : ?>
					<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => $handle ), admin_url( 'admin.php?page=evf-tools&tab=logs' ) ), 'remove_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'everest-forms' ); ?></a>
				<?php endif; ?>
			</h2>
		</div>
		<div class="alignright">
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=logs' ) ); ?>" method="post">
				<select name="log_file">
					<?php foreach ( $logs as $log_key => $log_file ) : ?>
						<?php
							$timestamp = filemtime( EVF_LOG_DIR . $log_file );
							/* translators: 1: last access date 2: last access time */
							$date = sprintf( __( '%1$s at %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
						?>
						<option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>><?php echo esc_html( $log_file ); ?> (<?php echo esc_html( $date ); ?>)</option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button" value="<?php esc_attr_e( 'View', 'everest-forms' ); ?>"><?php esc_html_e( 'View', 'everest-forms' ); ?></button>
			</form>
		</div>
		<div class="clear"></div>
	</div>
	<div id="log-viewer">
		<pre><?php echo esc_html( file_get_contents( EVF_LOG_DIR . $viewed_log ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?></pre>
	</div>
<?php else : ?>
	<div class="updated everest-forms-message inline"><p><?php esc_html_e( 'There are currently no logs to view.', 'everest-forms' ); ?></p></div>
<?php endif; ?>
