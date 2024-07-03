<?php
/**
 * EVF_Background_Process_Import_Entries
 *
 * @package EverestForms\Classes
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/libraries/wp-background-process.php';
}

/**
 * EVF_Background_Process_Import_Entries Class.
 */
class EVF_Background_Process_Import_Entries extends WP_Background_Process {

	/**
	 * Action Name.
	 *
	 * @var string
	 */
	protected $action = 'evf_import_entries';

	/**
	 * Perform task with queued item.
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $item Queue item to iterate over.
	 */
	protected function task( $item ) {
		// Actions to perform.
		self::import_entry_to_form( $item );
		return false;
	}

	/**
	 * Schedule fallback event.
	 *
	 * @since 3.0.0
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @since 3.0.0
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}


	/**
	 * Imports an entry to a form.
	 *
	 * This function imports an entry to a form based on the provided data. It retrieves the mapping data and CSV column titles from the options. It then retrieves the form fields using the form ID from the mapping data. The function iterates over the mapping data and checks if the field ID exists in the form fields. If it does, it retrieves the corresponding CSV column title and sanitizes the data from the provided data array. The sanitized data is stored in the entry data array. If the entry data array is not empty, the function creates an entry array with the necessary data and saves it using the save_entry method.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data The data to import the entry from.
	 */
	public static function import_entry_to_form( $data ) {
		$map_fields_array = get_option( 'everest_forms_mapping_fields_array', array() );
		$csv_column_title = get_option( 'everest_forms_csv_titles', array() );
		$evf_fields       = ! empty( $map_fields_array ) ? evf_get_form_fields( $map_fields_array['form_id'] ) : array();
		$entry_data       = array();
		$entry            = array();

		if ( empty( $evf_fields ) ) {
			return;
		}

		foreach ( $map_fields_array as $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $evf_fields[ $value['field_id'] ] ) ) {
					$key                              = array_search( trim( str_replace( '"', '', $value['map_csv_column'] ) ), $csv_column_title, true );
					$entry_data[ $value['field_id'] ] = array(
						'id'       => sanitize_text_field( wp_unslash( $value['field_id'] ) ),
						'type'     => sanitize_text_field( wp_unslash( $evf_fields[ $value['field_id'] ]['type'] ) ),
						'meta_key' => $evf_fields[ $value['field_id'] ]['meta-key'],
						'value'    => sanitize_text_field( wp_unslash( $data[ $key ] ) ),
						'name'     => sanitize_text_field( wp_unslash( $evf_fields[ $value['field_id'] ]['label'] ) ),
					);
				}
			}
		}

		if ( ! empty( $entry_data ) ) {
			$entry['user_id']         = get_current_user_id();
			$entry['user_device']     = '';
			$entry['user_ip_address'] = '';
			$entry['form_id']         = $map_fields_array['form_id'];
			$entry['referer']         = '';
			$entry['fields']          = wp_json_encode( $entry_data );
			$entry['status']          = 'publish';
			$entry['viewed']          = 0;
			$entry['starred']         = 0;
			$entry['date_created']    = gmdate( 'Y-m-d H:i:s' );
			self::save_entry( $entry, $entry_data );
		}
	}

	/**
	 * Save an entry to the database.
	 *
	 * This function saves an entry to the database by inserting the entry data into the 'evf_entries' table.
	 * It also inserts the entry meta data into the 'evf_entrymeta' table.
	 *
	 * @since 3.0.0
	 *
	 * @param array $entry The entry data to be saved.
	 * @param array $entry_data The entry meta data to be saved.
	 */
	public static function save_entry( $entry, $entry_data ) {
		global $wpdb;

		$result = $wpdb->insert( $wpdb->prefix . 'evf_entries', $entry );

		if ( is_wp_error( $result ) || ! $result ) {
			return false;
		}

		$entry_id = $wpdb->insert_id;

		if ( ! empty( $entry_id ) ) {
			$entry_meta = array();
			foreach ( $entry_data as $key => $data ) {
				$entry_meta = array(
					'entry_id'   => $entry_id,
					'meta_key'   => $data['meta_key'],
					'meta_value' => maybe_serialize( $data['value'] ),
				);
				$wpdb->insert( $wpdb->prefix . 'evf_entrymeta', $entry_meta );
			}
		}
	}


	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		delete_option( 'everest_forms_mapping_fields_array' );
		delete_option( 'everest_forms_csv_titles' );
		parent::complete();
	}
}
