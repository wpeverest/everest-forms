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
 * @param int|EVF_Entry $id Entry ID or object.
 * @param bool          $with_fields True if empty data should be present.
 * @param array         $args    Additional arguments.
 * @return EVF_Entry|null
 */
function evf_get_entry( $id, $with_fields = false, $args = array() ) {
	global $wpdb;

	if ( ! isset( $args['cap'] ) && ( is_admin() && ! wp_doing_ajax() ) ) {
		$args['cap'] = 'everest_forms_view_entry';
	}

	if ( ! empty( $args['cap'] ) && ! current_user_can( $args['cap'], $id ) ) {
		return null;
	}

	$entry = wp_cache_get( $id, 'evf-entry' );
	if ( false === $entry ) {
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE entry_id = %d LIMIT 1;", $id ) ); // WPCS: cache ok, DB call ok.
		wp_cache_add( $id, $entry, 'evf-entry' );
	}

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
		$results = wp_cache_get( $id, 'evf-entrymeta' );

		if ( false === $results ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}evf_entrymeta WHERE entry_id = %d", $id ), ARRAY_A );
			wp_cache_add( $id, $results, 'evf-entrymeta' );
		}

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

	$results = wp_cache_get( $form_id, 'evf-entries-ids' );
	if ( false === $results ) {
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.
		wp_cache_add( $form_id, $results, 'evf-entries-ids' );
	}

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
			'form_id' => 0,
			'offset'  => 0,
			'order'   => 'DESC',
			'orderby' => 'entry_id',
		)
	);

	if ( ! isset( $args['cap'] ) ) {
		$args['cap'] = 'everest_forms_view_form_entries';
	}

	// Check if form ID is valid for entries.
	if ( ! array_key_exists( $args['form_id'], evf_get_all_forms() ) ) {
		return array();
	}

	// Check permission if we can view form entries.
	if ( ! empty( $args['cap'] ) && ! current_user_can( $args['cap'], $args['form_id'] ) ) {
		return array();
	}

	// WHERE clause.
	$where = array(
		'default' => "{$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id",
	);

	$allowed_forms = implode(
		',',
		array_map(
			'intval',
			evf()->form->get(
				'',
				array(
					'fields' => 'ids',
					'cap'    => $args['cap'],
				)
			)
		)
	);

	// Check if forms are allowed.
	if ( ! empty( $allowed_forms ) ) {
		$where['arg_form_id'] = "{$wpdb->prefix}evf_entries.form_id IN ( {$allowed_forms} )";
	} else {
		$where = array( 'return_empty' => '1=0' );
	}

	// Give developers an ability to modify WHERE (unset clauses, add new, etc).
	$where     = (array) apply_filters( 'everest_forms_search_entries_where', $where, $args );
	$where_sql = implode( ' AND ', $where );

	// Query object.
	$query   = array();
	$query[] = "SELECT DISTINCT {$wpdb->prefix}evf_entries.entry_id FROM {$wpdb->prefix}evf_entries INNER JOIN {$wpdb->prefix}evf_entrymeta WHERE {$where_sql}";

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

	// Removing Draft Entry (Save and Contd Add-on).
	if ( empty( $args['status'] ) || 'draft' !== $args['status'] ) {
		$query[] = $wpdb->prepare( 'AND `status` <> %s', 'draft' );
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

	$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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

	$results = wp_cache_get( $form_id, 'evf-last-entries-count' );

	if ( false === $results ) {
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(entry_id) FROM {$wpdb->prefix}evf_entries WHERE form_id = %d AND entry_id > %d", $form_id, $last_entry ) );
		wp_cache_add( $form_id, $results, 'evf-last-entries-count' );
	}

	return $results;
}

/**
 * Get all the entries by form id between the start and end date.
 *
 * @since 1.7.0
 *
 * @param int    $form_id    Form ID.
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @param bool   $hide_trashed   Exclude trashed entries.
 *
 * @return array of entries by form ID.
 */
function evf_get_entries_by_form_id( $form_id, $start_date = '', $end_date = '', $hide_trashed = false ) {
	global $wpdb;

	$query   = array();
	$query[] = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE form_id=%s", $form_id );

	if ( ! empty( $start_date ) ) {
		$query[] = $wpdb->prepare( 'AND date_created  >= %s', $start_date );
	}

	if ( ! empty( $end_date ) ) {
		$query[] = $wpdb->prepare( 'AND date_created  <= %s', $end_date );
	}

	$query[] = $wpdb->prepare( 'AND status != %s', 'draft' );

	if ( $hide_trashed ) {
		$query[] = $wpdb->prepare( 'AND status != %s', 'trash' );
	}

	$results = wp_cache_get( $form_id, 'evf-search-entries' );

	if ( false === $results ) {
		$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_add( $form_id, $results, 'evf-search-entries' );
	}

	return $results;
}
