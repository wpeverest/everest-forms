<?php
/**
 * Handles entry CSV export.
 *
 * @package EverestForms\Export
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'EVF_CSV_Exporter', false ) ) {
	require_once EVF_ABSPATH . 'includes/export/abstract-evf-csv-exporter.php';
}

/**
 * EVF_Entry_CSV_Exporter Class.
 */
class EVF_Entry_CSV_Exporter extends EVF_CSV_Exporter {

	/**
	 * Form ID.
	 *
	 * @var int|mixed
	 */
	public $form_id;

	/**
	 * Entry ID.
	 *
	 * @var int|mixed
	 */
	public $entry_id;

	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = 'entry';

	/**
	 * Constructor.
	 *
	 * @param int $form_id  Form ID.
	 * @param int $entry_id Entry ID.
	 */
	public function __construct( $form_id = '', $entry_id = '' ) {
		$this->form_id      = absint( $form_id );
		$this->entry_id     = absint( $entry_id );
		$this->column_names = $this->get_default_column_names();
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @return array
	 */
	public function get_default_column_names() {
		$columns   = array();
		$form_obj  = evf()->form->get( $this->form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		// Set Entry ID at first.
		$columns['entry_id'] = esc_html__( 'ID', 'everest-forms' );

		// Add whitelisted fields to export columns.
		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field ) {
				if ( ! in_array( $field['type'], array( 'html', 'title', 'captcha' ), true ) ) {
					$columns[ $field['meta-key'] ] = evf_clean( $field['label'] );
				}
			}
		}

		// Set the default columns.
		$columns['status']           = esc_html__( 'Status', 'everest-forms' );
		$columns['date_created']     = esc_html__( 'Date Created', 'everest-forms' );
		$columns['date_created_gmt'] = esc_html__( 'Date Created GMT', 'everest-forms' );

		// If user details are disabled globally discard the IP and UA.
		if ( 'yes' !== get_option( 'everest_forms_disable_user_details' ) ) {
			$columns['user_device']     = esc_html__( 'User Device', 'everest-forms' );
			$columns['user_ip_address'] = esc_html__( 'User IP Address', 'everest-forms' );
		}

		return apply_filters( "everest_forms_export_{$this->export_type}_default_columns", $columns );
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 1.6.0
	 */
	public function prepare_data_to_export() {
		$this->row_data = array();

		if ( $this->entry_id ) {
			$entry            = evf_get_entry( $this->entry_id );
			$this->row_data[] = $this->generate_row_data( $entry );
		} else {
			$entry_ids = evf_search_entries(
				array(
					'limit'   => -1,
					'order'   => 'ASC',
					'form_id' => $this->form_id,
				)
			);

			// Get the entries.
			$entries = array_map( 'evf_get_entry', $entry_ids );

			foreach ( $entries as $entry ) {
				$this->row_data[] = $this->generate_row_data( $entry );
			}
		}

		return $this->row_data;
	}

	/**
	 * Take a entry id and generate row data from it for export.
	 *
	 * @param  object $entry Entry object.
	 * @return array
	 */
	protected function generate_row_data( $entry ) {
		$columns = $this->get_column_names();
		$row     = array();
		foreach ( $columns as $column_id => $column_name ) {
			$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';

			if ( isset( $entry->meta[ $column_id ] ) ) {
				// Filter for entry meta data.
				$value = $entry->meta[ $column_id ];

				if ( is_serialized( $value ) ) {
					$value = $this->implode_values( maybe_unserialize( $value ) );
				}

				$value = apply_filters( 'everest_forms_html_field_value', $value, $entry->meta[ $column_id ], $entry, 'export-csv' );

			} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
				// Handle special columns which don't map 1:1 to entry data.
				$value = $this->{"get_column_value_{$column_id}"}( $entry );
			}

			$row[ $column_id ] = sanitize_text_field( $value );
		}

		return apply_filters( 'everest_forms_entry_export_row_data', $row, $entry );
	}

	/**
	 * Get entry id value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return int
	 */
	protected function get_column_value_entry_id( $entry ) {
		return absint( $entry->entry_id );
	}

	/**
	 * Get entry status value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_status( $entry ) {
		$statuses = evf_get_entry_statuses();

		if ( isset( $statuses[ $entry->status ] ) ) {
			return $statuses[ $entry->status ];
		}

		return $entry->status;
	}

	/**
	 * Get date created value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_date_created( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created );
		}

		/* translators: 1: entry date 2: entry time */
		return sprintf( esc_html__( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
	}

	/**
	 * Get GMT date created value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_date_created_gmt( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 );
		}

		/* translators: 1: entry date 2: entry time */
		return sprintf( esc_html__( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
	}

	/**
	 * Get entry user device value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_user_device( $entry ) {
		return sanitize_text_field( $entry->user_device );
	}

	/**
	 * Get entry user IP address value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_user_ip_address( $entry ) {
		return sanitize_text_field( $entry->user_ip_address );
	}
}
