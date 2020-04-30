<?php
/**
 * Debug/Status page
 *
 * @package EverestForms/Admin/Tools
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Tools Class.
 */
class EVF_Admin_Tools {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-tools.php';
	}

	/**
	 * Show the logs page.
	 */
	public static function status_logs() {
		self::status_logs_file();
	}

	/**
	 * Show the import page.
	 */
	public static function import() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-import.php';
	}

	/**
	 * Show the export page.
	 */
	public static function export() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-export.php';
	}

	/**
	 * Show the log page contents for file log handler.
	 */
	public static function status_logs_file() {
		$logs   = self::scan_log_files();
		$handle = null;

		if ( ! empty( $_REQUEST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$viewed_log = $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ]; // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( ! empty( $logs ) ) {
			$viewed_log = current( $logs );
		}

		if ( isset( $_REQUEST['action'] ) && 'purge_all' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::purge_logs();
		} else {
			$handle = ! empty( $viewed_log ) ? self::get_log_file_handle( $viewed_log ) : '';

			if ( ! empty( $_REQUEST['handle'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				self::remove_log();
			}
		}

		include_once 'views/html-admin-page-tools-logs.php';
	}

	/**
	 * Retrieve metadata from a file. Based on WP Core's get_file_data function.
	 *
	 * @since  1.0.0
	 * @param  string $file Path to the file.
	 * @return string
	 */
	public static function get_file_version( $file ) {
		// Avoid notices if file does not exist.
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' ); // @codingStandardsIgnoreLine

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 ); // @codingStandardsIgnoreLine

		// PHP will close file handle, but we are good citizens.
		fclose( $fp ); // @codingStandardsIgnoreLine

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * Return the log file handle.
	 *
	 * @param string $filename Filename to get the handle for.
	 * @return string
	 */
	public static function get_log_file_handle( $filename ) {
		return substr( $filename, 0, strlen( $filename ) > 37 ? strlen( $filename ) - 37 : strlen( $filename ) - 4 );
	}

	/**
	 * Scan the template files.
	 *
	 * @param  string $template_path Path to the template directory.
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {
		$files  = @scandir( $template_path ); // @codingStandardsIgnoreLine
		$result = array();

		if ( ! empty( $files ) ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( '.', '..' ), true ) ) {

					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Scan the log files.
	 *
	 * @return array
	 */
	public static function scan_log_files() {
		$files  = @scandir( EVF_LOG_DIR ); // @codingStandardsIgnoreLine
		$result = array();

		if ( ! empty( $files ) ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( '.', '..' ), true ) ) {
					if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
						$result[ sanitize_title( $value ) ] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Remove/delete the chosen file.
	 */
	public static function remove_log() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'remove_log' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}

		if ( ! empty( $_REQUEST['handle'] ) ) {
			$log_handler = new EVF_Log_Handler_File();
			$log_handler->remove( wp_unslash( $_REQUEST['handle'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=evf-tools&tab=logs' ) ) );
		exit();
	}

	/**
	 * Purge all existing logs.
	 */
	public static function purge_logs() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'purge_logs' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}
		$log_handler = new EVF_Log_Handler_File();
		foreach ( $log_handler->get_log_files() as $index => $single_log ) {
			$file = realpath( trailingslashit( EVF_LOG_DIR ) . $single_log );
			if ( 0 === stripos( $file, realpath( trailingslashit( EVF_LOG_DIR ) ) ) && is_file( $file ) && is_writable( $file ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
				try {
					fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					do_action( 'everest_forms_logs_purge', $index, $single_log, $file );
					unlink( $file ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink

				} catch ( \Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Suppress Exceptions in case unlink/fclose fail. For PHP 8.0 compatibility.
				}
			}
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=evf-tools&tab=logs' ) ) );
		exit();
	}
}
