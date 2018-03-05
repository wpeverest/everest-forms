<?php
/**
 * EverestForms Admin Entries Class
 *
 * @package EverestForms\Admin
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Entries class.
 */
class EVF_Admin_Entries {

	/**
	 * Initialize the entries admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is usages page.
	 *
	 * @return bool
	 */
	private function is_entries_page() {
		return isset( $_GET['page'] ) && 'evf-entries' === $_GET['page']; // WPCS: input var okay, CSRF ok.
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		if ( isset( $_GET['view-entry'] ) ) {
			$entry_id = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry    = self::get_entry_data( $entry_id );

			include 'views/html-admin-page-entries-view.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		global $entries_table_list;

		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">
			<form id="entries-list" method="post">
				<?php
					$entries_table_list->views();
					$entries_table_list->search_box( __( 'Search Entries', 'everest-forms' ), 'everest-forms' );
					$entries_table_list->display();

					wp_nonce_field( 'save', 'everest-forms_nonce' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get entries from the database.
	 *
	 * @param  array $args
	 * @param  bool $count
	 * @return array|int
	 */
	public static function get_entries( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'select'        => 'all',
			'number'        => 30,
			'offset'        => 0,
			'form_id'       => 0,
			'entry_id'      => 0,
			'post_id'       => '',
			'user_id'       => '',
			'status'        => '',
			'type'          => '',
			'user_uuid'     => '',
			'date'          => '',
			'date_modified' => '',
			'ip_address'    => '',
			'orderby'       => 'entry_id',
			'order'         => 'DESC',
		);

		$args = apply_filters( 'everest_forms_entry_handler_get_entries_args', wp_parse_args( $args, $defaults ) );

		/*
		 * Modify the SELECT.
		 */
		$select = '*';

		$possible_select_values = apply_filters(
			'everest_forms_entry_handler_get_entries_select',
			array(
				'all'       => '*',
				'entry_ids' => '`entry_id`',
			)
		);
		if ( array_key_exists( $args['select'], $possible_select_values ) ) {
			$select = esc_sql( $possible_select_values[ $args['select'] ] );
		}

		/*
		 * Modify the WHERE.
		 *
		 * Always define a default WHERE clause.
		 * MySQL/MariaDB optimizations are clever enough to strip this out later before actual execution.
		 * But having this default here in the code will make everything a bit better to read and understand.
		 */
		$where = array(
			'default' => '1=1',
		);

		// Allowed int arg items.
		$keys = array( 'entry_id', 'form_id', 'post_id', 'user_id' );
		foreach ( $keys as $key ) {
			// Value `$args[ $key ]` can be a natural number and a numeric string.
			// We should skip empty string values, but continue working with '0'.
			// For sad reason using `==` makes various parts of the code work.
			if ( '' == $args[ $key ] ) {
				continue;
			}

			if ( is_array( $args[ $key ] ) && ! empty( $args[ $key ] ) ) {
				$ids = implode( ',', array_map( 'intval', $args[ $key ] ) );
			} else {
				$ids = intval( $args[ $key ] );
			}

			$where[ 'arg_' . $key ] = "`{$key}` IN ( {$ids} )";
		}

		// Allowed string arg items.
		$keys = array( 'status', 'type', 'user_uuid' );
		foreach ( $keys as $key ) {

			if ( '' !== $args[ $key ] ) {
				$where[ 'arg_' . $key ] = "`{$key}` = '" . esc_sql( $args[ $key ] ) . "'";
			}
		}

		// Process dates.
		$keys = array( 'date', 'date_modified' );
		foreach ( $keys as $key ) {
			if ( empty( $args[ $key ] ) ) {
				continue;
			}

			// We can pass array and treat it as a range from:to.
			if ( is_array( $args[ $key ] ) && count( $args[ $key ] ) === 2 ) {
				$date_start = evf_get_day_period_date( 'start_of_day', strtotime( $args[ $key ][0] ) );
				$date_end   = evf_get_day_period_date( 'end_of_day', strtotime( $args[ $key ][1] ) );

				if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
					$where[ 'arg_' . $key . '_start' ] = "`{$key}` >= '{$date_start}'";
					$where[ 'arg_' . $key . '_end' ]   = "`{$key}` <= '{$date_end}'";
				}
			} elseif ( is_string( $args[ $key ] ) ) {
				/*
				 * If we pass the only string representation of a date -
				 * that means we want to get records of that day only.
				 * So we generate start and end MySQL dates for the specified day.
				 */
				$timestamp  = strtotime( $args[ $key ] );
				$date_start = evf_get_day_period_date( 'start_of_day', $timestamp );
				$date_end   = evf_get_day_period_date( 'end_of_day', $timestamp );

				if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
					$where[ 'arg_' . $key . '_start' ] = "`{$key}` >= '{$date_start}'";
					$where[ 'arg_' . $key . '_end' ]   = "`{$key}` <= '{$date_end}'";
				}
			}
		}

		// Give developers an ability to modify WHERE (unset clauses, add new, etc).
		$where     = (array) apply_filters( 'everest_forms_entry_handler_get_entries_where', $where, $args );
		$where_sql = implode( ' AND ', $where );

		/*
		 * Modify the ORDER BY.
		 */
		$args['orderby'] = isset( $args['orderby'] ) ? $args['orderby'] : 'entry_id';

		if ( 'ASC' === strtoupper( $args['order'] ) ) {
			$args['order'] = 'ASC';
		} else {
			$args['order'] = 'DESC';
		}

		/*
		 * Modify the OFFSET / NUMBER.
		 */
		$args['offset'] = absint( $args['offset'] );
		if ( $args['number'] < 1 ) {
			$args['number'] = PHP_INT_MAX;
		}
		$args['number'] = absint( $args['number'] );

		/*
		 * Retrieve the results.
		 */

		if ( true === $count ) {

			// @codingStandardsIgnoreStart
			$results = absint( $wpdb->get_var(
				"SELECT COUNT(entry_id)
				FROM {$wpdb->prefix}evf_entries
				WHERE {$where_sql};"
			) );
			// @codingStandardsIgnoreEnd

		} else {

			// @codingStandardsIgnoreStart
			$results = $wpdb->get_results(
				"SELECT {$select}
				FROM {$wpdb->prefix}evf_entries
				WHERE {$where_sql}
				ORDER BY {$args['orderby']} {$args['order']}
				LIMIT {$args['offset']}, {$args['number']};"
			);
			// @codingStandardsIgnoreEnd
		}

		return $results;
	}

	public function actions() {
		global $wpdb;

		if ( ! $this->is_entries_page() ) {
			return;
		}

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'trash' ) {
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "trash" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset($_POST['action'] ) && $_POST['action'] == 'untrash' ){
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "publish" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset( $_POST['action'] ) && $_POST['action'] == 'delete' ){
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'DELETE FROM wp_evf_entries WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                $query = 'DELETE FROM wp_evf_entrymeta WHERE evf_entry_id = '. $entry .'';
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'trash' ) {
            $query = 'UPDATE `wp_evf_entries` SET status = "trash" WHERE id = '. $_GET['id'] .'' ;
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
            $query = 'DELETE FROM wp_evf_entries WHERE id = '. $_GET['id'] .'';
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'untrash' ) {
            $query = 'UPDATE `wp_evf_entries` SET status = "publish" WHERE id = '. $_GET['id'] .'' ;
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' && isset( $_GET['empty_trash'] ) && $_GET['empty_trash'] == 1 ) {
            $query = 'DELETE FROM wp_evf_entries';
            $wpdb->get_results( $query );

            $query = 'DELETE FROM wp_evf_entrymeta';
            $wpdb->get_results( $query );

            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }
    }

    public function get_single_entry( $id ) {

    }
}

new EVF_Admin_Entries();
