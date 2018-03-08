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
 * @return EVF_Entry|null
 */
function evf_get_entry( $id ) {
	global $wpdb;

	$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE entry_id = %d LIMIT 1;", $id ) ); // WPCS: cache ok, DB call ok.

	if ( apply_filters( 'everest_forms_get_entry_metadata', true ) ) {
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
 * @return array
 */
function evf_get_entry_statuses() {
	return apply_filters( 'everest_forms_entry_statuses', array(
		'publish' => __( 'Published', 'everest-forms' ),
		'trash'   => __( 'Trash', 'everest-forms' ),
	) );
}

/**
 * Search entries.
 *
 * @param  array $args Search arguments.
 * @return array
 */
function evf_search_entries( $args ) {
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'limit'   => 10,
		'offset'  => 0,
		'order'   => 'DESC',
		'orderby' => 'entry_id',
	) );

	// Check if form ID is valid for entries.
	if ( ! array_key_exists( $args['form_id'], evf_get_all_forms() ) ) {
		return array();
	}

	$orderby       = isset( $args['orderby'] ) ? $args['orderby'] : 'entry_id';
	$limit         = -1 < $args['limit'] ? sprintf( 'LIMIT %d', $args['limit'] ) : '';
	$offset        = 0 < $args['offset'] ? sprintf( 'OFFSET %d', $args['offset'] ) : '';
	$status        = ! empty( $args['status'] ) ? "AND `status` = '" . sanitize_key( $args['status'] ) . "'" : '';
	$search        = ! empty( $args['search'] ) ? "AND `meta_value` LIKE '%" . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . "%'" : '';
	$include       = ! empty( $args['form_id'] ) ? "AND `form_id` = '" . absint( $args['form_id'] ) . "'" : '';
	$exclude       = '';
	$date_created  = '';
	$date_modified = '';

	if ( ! empty( $args['after'] ) || ! empty( $args['before'] ) ) {
		$args['after']  = empty( $args['after'] ) ? '0000-00-00' : $args['after'];
		$args['before'] = empty( $args['before'] ) ? current_time( 'mysql', 1 ) : $args['before'];

		$date_created = "AND `date_created_gmt` BETWEEN STR_TO_DATE('" . $args['after'] . "', '%Y-%m-%d %H:%i:%s') and STR_TO_DATE('" . $args['before'] . "', '%Y-%m-%d %H:%i:%s')";
	}

	if ( ! empty( $args['modified_after'] ) || ! empty( $args['modified_before'] ) ) {
		$args['modified_after']  = empty( $args['modified_after'] ) ? '0000-00-00' : $args['modified_after'];
		$args['modified_before'] = empty( $args['modified_before'] ) ? current_time( 'mysql', 1 ) : $args['modified_before'];

		$date_modified = "AND `date_modified_gmt` BETWEEN STR_TO_DATE('" . $args['modified_after'] . "', '%Y-%m-%d %H:%i:%s') and STR_TO_DATE('" . $args['modified_before'] . "', '%Y-%m-%d %H:%i:%s')";
	}

	$order = "ORDER BY {$orderby} " . strtoupper( sanitize_key( $args['order'] ) );

	$query = trim( "
		SELECT DISTINCT {$wpdb->prefix}evf_entries.entry_id
		FROM {$wpdb->prefix}evf_entries
		INNER JOIN {$wpdb->prefix}evf_entrymeta
		WHERE {$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id
		{$status}
		{$search}
		{$include}
		{$exclude}
		{$date_created}
		{$date_modified}
		{$order}
		{$limit}
		{$offset}
	" );

	$results = $wpdb->get_results( $query ); // WPCS: cache ok, DB call ok, unprepared SQL ok.

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
	$statuses = array_keys( evf_get_entry_statuses() );
	$counts   = array();

	foreach ( $statuses as $status ) {
		$count = count( evf_search_entries( array(
			'limit'   => -1,
			'status'  => $status,
			'form_id' => $form_id,
		) ) );

		$counts[ $status ] = $count;
	}

	return $counts;
}
