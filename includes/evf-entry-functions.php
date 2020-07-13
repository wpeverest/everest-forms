<?php
/**
 * EverestForms Entry Functions
 *
 * @package EverestForms\Functions
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get entry.
 *
 * @param  int|EVF_Entry $id Entry ID or object.
 * @param  bool          $with_fields True if empty data should be present.
 * @return EVF_Entry|null
 */
function evf_get_entry( $id, $with_fields = false ) {
	global $wpdb;

	$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE entry_id = %d LIMIT 1;", $id ) ); // WPCS: cache ok, DB call ok.

	// BW: Mark entry as read for older entries.
	if ( is_null( $entry->fields ) && empty( $entry->viewed ) ) {
		$is_viewed = $wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array(
				'viewed' => 1,
				'fields' => '{}',
			),
			array(
				'entry_id' => $entry->entry_id,
			)
		);

		if ( $is_viewed ) {
			$entry->viewed = 1;
		}
	}

	$fields = evf_decode( $entry->fields );

	if ( $with_fields && ! empty( $fields ) ) {
		foreach ( $fields as $field ) {
			if ( isset( $field['meta_key'], $field['value'] ) ) {
				$entry->meta[ $field['meta_key'] ] = maybe_serialize( $field['value'] );
			}
		}
	} elseif ( apply_filters( 'everest_forms_get_entry_metadata', true ) ) {
		$results     = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key,meta_value FROM {$wpdb->prefix}evf_entrymeta WHERE entry_id = %d", $id ), ARRAY_A );
		$entry->meta = wp_list_pluck( $results, 'meta_value', 'meta_key' );
	}

	return 0 !== $entry ? $entry : null;
}

/**
 * Get all entries IDs.
 *
 * @param  int $form_id Form ID.
 * @return int[]
 */
function evf_get_entries_ids( $form_id ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.

	return array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );
}

/**
 * Get entry statuses.
 *
 * @param array $form_data Form data.
 *
 * @return array
 */
function evf_get_entry_statuses( $form_data = array() ) {
	return apply_filters(
		'everest_forms_entry_statuses',
		array(
			'publish' => esc_html__( 'Published', 'everest-forms' ),
			'trash'   => esc_html__( 'Trash', 'everest-forms' ),
		),
		$form_data
	);
}

/**
 * Search entries.
 *
 * @param  array $args Search arguments.
 * @return array
 */
function evf_search_entries( $args ) {
	global $wpdb;

	$args = wp_parse_args(
		$args,
		array(
			'limit'   => 10,
			'offset'  => 0,
			'order'   => 'DESC',
			'orderby' => 'entry_id',
		)
	);

	// Check if form ID is valid for entries.
	if ( ! array_key_exists( $args['form_id'], evf_get_all_forms() ) ) {
		return array();
	}

	$query   = array();
	$query[] = "SELECT DISTINCT {$wpdb->prefix}evf_entries.entry_id FROM {$wpdb->prefix}evf_entries INNER JOIN {$wpdb->prefix}evf_entrymeta WHERE {$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id";

	if ( ! empty( $args['search'] ) ) {
		$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		$query[] = $wpdb->prepare( 'AND meta_value LIKE %s', $like );
	}

	if ( ! empty( $args['form_id'] ) ) {
		$query[] = $wpdb->prepare( 'AND form_id = %d', absint( $args['form_id'] ) );
	}

	if ( ! empty( $args['status'] ) ) {
		if ( 'unread' === $args['status'] ) {
			$query[] = $wpdb->prepare( 'AND `status` != %s AND `viewed` = 0', 'trash' );
		} elseif ( 'starred' === $args['status'] ) {
			$query[] = $wpdb->prepare( 'AND `status` != %s AND `starred` = 1', 'trash' );
		} else {
			$query[] = $wpdb->prepare( 'AND `status` = %s', $args['status'] );
		}
	}

	$valid_fields = array( 'date', 'form_id', 'title', 'status' );
	$orderby      = in_array( $args['orderby'], $valid_fields, true ) ? $args['orderby'] : 'entry_id';
	$order        = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
	$orderby_sql  = sanitize_sql_orderby( "{$orderby} {$order}" );
	$query[]      = "ORDER BY {$orderby_sql}";

	if ( -1 < $args['limit'] ) {
		$query[] = $wpdb->prepare( 'LIMIT %d', absint( $args['limit'] ) );
	}

	if ( 0 < $args['offset'] ) {
		$query[] = $wpdb->prepare( 'OFFSET %d', absint( $args['offset'] ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A );

	$ids = wp_list_pluck( $results, 'entry_id' );

	return $ids;
}

/**
 * Get total entries counts by status.
 *
 * @param  int $form_id Form ID.
 * @return array
 */
function evf_get_count_entries_by_status( $form_id ) {
	$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );
	$statuses  = array_keys( evf_get_entry_statuses( $form_data ) );
	$counts    = array();

	foreach ( $statuses as $status ) {
		$count = count(
			evf_search_entries(
				array(
					'limit'   => -1,
					'status'  => $status,
					'form_id' => $form_id,
				)
			)
		);

		$counts[ $status ] = $count;
	}

	return $counts;
}

/**
 * Get total next entries counts by last entry.
 *
 * @since 1.5.0
 *
 * @param  int $form_id    Form ID.
 * @param  int $last_entry Last Form ID.
 * @return int[]
 */
function evf_get_count_entries_by_last_entry( $form_id, $last_entry ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(entry_id) FROM {$wpdb->prefix}evf_entries WHERE form_id = %d AND entry_id > %d", $form_id, $last_entry ) );
}

/**
 * Get all the entries by form id between the start and end date.
 *
 * @since 1.7.0
 *
 * @param int    $form_id    Form ID.
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 *
 * @return array of entries by form ID.
 */
function evf_get_entries_by_form_id( $form_id, $start_date = '', $end_date = '' ) {
	global $wpdb;

	$query   = array();
	$query[] = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE form_id=%s", $form_id );

	if ( ! empty( $start_date ) ) {
		$query[] = $wpdb->prepare( 'AND date_created  >= %s', $start_date );
	}

	if ( ! empty( $end_date ) ) {
		$query[] = $wpdb->prepare( 'AND date_created  <= %s', $end_date );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A );

	return $results;
}
