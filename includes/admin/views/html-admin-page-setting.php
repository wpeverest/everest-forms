<?php
/**
 * Admin View: Page - Setting
 *
 * @package EverestForms/Admin/Tool/System Info Settings
 */

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WP_Debug_Data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
}
?>
<div class="everest-forms-system-info-setting">
<button class="everest-forms-system-info-setting-copy"><?php esc_html_e( 'Copy Setting', 'everest-forms' ); ?></button>
	<table>
	<?php
	$license_key = get_option( 'everest-forms-pro_license_key' );
	if ( defined( 'EFP_PLUGIN_FILE' ) && is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ) ) {
		?>
		<tr>
			<th colspan="2">
			<?php
			$license_data = get_transient( 'evf_pro_license_plan' );
			if ( $license_key && $license_data ) {
				$name = isset( $license_data->item_name ) ? esc_html( $license_data->item_name ) : '-';
			} else {
				$name = esc_html__( 'Everest Form Pro', 'everest-forms' );
			}
			echo esc_html( $name );
			?>
		</th>

		</tr>
		<tr>
			<th><?php esc_html_e( 'Version', 'everest-forms' ); ?></th>
			<td>
			<?php
				$plugin_file =
					WP_PLUGIN_DIR . '/everest-forms-pro/everest-forms-pro.php';
			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file, array( 'Version' => 'Version' ) );
				if ( ! empty( $plugin_data['Version'] ) ) {
					$plugin_version = $plugin_data['Version'];
					echo esc_html( $plugin_version ) . ' ';
				}
			} else {
				$plugin_version = null;
			}
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Edition', 'everest-forms' ); ?></th>
			<td>
			<?php
				$license_data = get_transient( 'evf_pro_license_plan' );

			if ( $license_key && $license_data ) {
				$edition = isset( $license_data->item_plan ) ? esc_html__( 'PRO', 'everest-forms' ) : '-';
				echo esc_html( $edition );
			} else {
				echo esc_html__( 'Free', 'everest-forms' );
			}
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'License Key', 'everest-forms' ); ?></th>
			<td>
			<?php
				$license_data = get_transient( 'evf_pro_license_plan' );

			if ( $license_key && $license_data ) {
				$license_key = isset( $license_data->license ) ? esc_html__( 'Licensed', 'everest-forms' ) : '-';
				echo esc_html( $license_key );
			} else {
				echo esc_html__( 'Unlicensed', 'everest-forms' );
			}
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'License Activated', 'everest-forms' ); ?></th>
			<td>
			<?php
				$license_data = get_transient( 'evf_pro_license_plan' );

			if ( $license_key && $license_data ) {
				$license_status = isset( $license_data->success ) ? esc_html__( 'Yes', 'everest-forms' ) : '-';
				echo esc_html( $license_status );
			} else {
				echo esc_html__( 'No', 'everest-forms' );
			}
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'License Expires', 'everest-forms' ); ?></th>
			<td>
			<?php
				$license_data = get_transient( 'evf_pro_license_plan' );
			if ( $license_key && $license_data ) {
				$expires = isset( $license_data->expires ) ? esc_html( $license_data->expires ) : '-';
				echo esc_html( $expires );
			} else {
				echo esc_html__( '-', 'everest-forms' );
			}
			?>
			</td>
		</tr>
		<?php
	} elseif ( is_plugin_active( 'everest-forms/everest-forms.php' ) ) {
		?>
		<tr>
			<th colspan="2">
			<?php
				$plugin_name = esc_html__( 'Everest Form', 'everest-forms' );
				echo esc_html( $plugin_name );
			?>
			</th>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Version', 'everest-forms' ); ?></th>
			<td>
			<?php
				$plugin_file = WP_PLUGIN_DIR . '/everest-forms/everest-forms.php';
			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file, array( 'Version' => 'Version' ) );
				if ( ! empty( $plugin_data['Version'] ) ) {
					$plugin_version = $plugin_data['Version'];
					echo esc_html( $plugin_version );
				}
			} else {
				$plugin_version = null;
			}
			?>
			</td>
		</tr>
		<?php
	}
	?>
		<!-- WordPress -->
		<tr>
			<th colspan="2">
			<?php
			esc_html_e( 'WordPress', 'everest-forms' );
			?>
			</th>
		</tr>
		<tr>
		<th>
		<?php
			$plugin_file    = WP_PLUGIN_DIR . '/everest-forms/everest-forms.php';
			$require_wp     = get_plugin_data( $plugin_file, array( 'RequiresWP' => 'Requires WP' ) );
			$min_version_wp = $require_wp['RequiresWP'];
			esc_html_e( 'Version', 'everest-forms' );
		if ( is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ) ) {
			echo esc_html( '(Min:' . $min_version_wp . ')' );
		} elseif ( is_plugin_active( 'everest-forms/everest-forms.php' ) ) {
			echo ' ';
		}
		?>
		</th>
		<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'MultiSite Enabled', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( is_multisite() ? 'Yes' : 'No' ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Home URL', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( home_url() ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Site URL', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( site_url() ); ?></td>
		</tr>
		<tr>
		<th><?php esc_html_e( 'Theme', 'everest-forms' ); ?></th>
		<td>
		<?php
			$theme = wp_get_theme();
			echo isset( $theme->name ) && isset( $theme->version ) ? esc_html( $theme->name ) . ' (' . esc_html( $theme->version ) . ')' : '';
		?>
		</td>
		</tr>

		<tr>
			<th><?php esc_html_e( 'Plugins', 'everest-forms' ); ?></th>
			<td>
			<?php
				$evf_stats_obj = new \EVF_Stats();
				$plugin_lists  = $evf_stats_obj->get_plugin_lists();

			if ( ! empty( $plugin_lists ) ) {
				foreach ( $plugin_lists as $plugin_slug => $plugin_data ) {
					if ( isset( $plugin_data['product_name'] ) && isset( $plugin_data['product_version'] ) ) {
						echo esc_html( $plugin_data['product_name'] . ' (' . $plugin_data['product_version'] . ')' ) . '<br>';
					}
				}
			} else {
				esc_html_e( 'No plugin lists available.', 'everest-forms' );
			}
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Max Upload Size', 'everest-forms' ); ?></th>
			<td>
			<?php
				$max_upload_size_bytes = wp_max_upload_size();
				$max_upload_size_mb    = $max_upload_size_bytes / 1024 / 1024;
				echo esc_html( $max_upload_size_mb ) . ' MB';
			?>
			</td>
		</tr>
		<!-- PHP -->
		<tr>
			<th colspan="2"><?php esc_html_e( 'PHP', 'everest-forms' ); ?></th>
		</tr>
		<tr>
			<th>
			<?php
			$plugin_data     = get_plugin_data( $plugin_file, array( 'RequiresPHP' => 'Requires PHP' ) );
			$min_version_php = $plugin_data['RequiresPHP'];
			esc_html_e( 'Version', 'everest-forms' );
			if ( is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ) ) {
				echo esc_html( '(Min:' . $min_version_php . ')' );
			} elseif ( is_plugin_active( 'everest-forms/everest-forms.php' ) ) {
				echo ' ';
			}
			?>
			</th>
			<td><?php echo esc_html( phpversion() ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Default Timezone', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( date_default_timezone_get() ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Max Execution Time', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Memory Limit', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Max Upload Size', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Max Input Variables', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'max_input_vars' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'SMTP Hostname', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'SMTP' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'SMTP Port', 'everest-forms' ); ?></th>
			<td><?php echo esc_html( ini_get( 'smtp_port' ) ); ?></td>
		</tr>
		<!-- Web Server -->
		<tr>
			<th colspan="2"><?php esc_html_e( 'Web Server', 'everest-forms' ); ?></th>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Name', 'everest-forms' ); ?></th>
			<td>
				<?php
				$remote_addr = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
				echo esc_html( $remote_addr );
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'IP', 'everest-forms' ); ?></th>
			<td>
				<?php
				$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
				echo esc_html( $remote_addr );
				?>
			</td>
		</tr>
		<!-- MySQL -->
		<tr>
			<th colspan="2"><?php esc_html_e( 'MySQL', 'everest-forms' ); ?></th>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Version', 'everest-forms' ); ?></th>
			<td>
			<?php
				global $wpdb;
				echo esc_html( $wpdb->db_version() );
			?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Max Allowed Packet', 'everest-forms' ); ?></th>
			<td>
				<?php
						$max_packet_size_bytes = array(
							'label' => __( 'Max allowed packet size', 'everest-forms' ),
							'value' => WP_Debug_Data::get_mysql_var( 'max_allowed_packet' ),
						);

						$info['wp-database']['fields']['max_allowed_packet'] = $max_packet_size_bytes;

						$maxp_mb = isset( $max_packet_size_bytes['value'] ) ? $max_packet_size_bytes['value'] / 1024 / 1024 : '';
						echo esc_html( $maxp_mb ) . ' MB';

						?>
			</td>
		</tr>
	</table>
</div>
