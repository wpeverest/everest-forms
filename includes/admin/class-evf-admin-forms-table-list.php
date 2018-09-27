<?php
/**
 * EverestForms Forms Table List
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Forms table list class.
 */
class EVF_Admin_Forms_Table_List extends WP_List_Table {

	/**
	 * Initialize the form table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'form',
			'plural'   => 'forms',
			'ajax'     => false,
		) );
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No Forms found.', 'everest-forms' );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __( 'Title', 'everest-forms' ),
			'shortcode' => __( 'Shortcode', 'everest-forms' ),
			'author'    => __( 'Author', 'everest-forms' ),
			'date'      => __( 'Date', 'everest-forms' ),
			'entries'   => __( 'Entries', 'everest-forms' ),
		);
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'author' => array( 'author', false ),
			'date'   => array( 'date', false ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  object $form Form object.
	 * @return string
	 */
	public function column_cb( $form ) {
		return sprintf( '<input type="checkbox" name="form_id[]" value="%1$s" />', esc_attr( $form->ID ) );
	}

	/**
	 * Return title column.
	 *
	 * @param  object $post Form object.
	 * @return string
	 */
	public function column_title( $posts ) {
		$edit_link        = admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $posts->ID );
		$title            = _draft_or_post_title( $posts->ID );
		$post_type_object = get_post_type_object( 'everest_form' );
		$post_status      = $posts->post_status;

		// Title
		$output = '<strong>';
		if ( 'trash' == $post_status ) {
			$output .= esc_html( $title );
		} else {
			$output .= '<a href="' . esc_url( $edit_link ) . '" class="row-title">' . esc_html( $title ) . '</a>';
		}
		$output .= '</strong>';

		// Get actions.
		if ( current_user_can( $post_type_object->cap->edit_post, $posts->ID ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'everest-forms' ) . '</a>';
			$actions['entries'] = '<a href="' . esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $posts->ID ) ) . '">' . __( 'Entries', 'everest-forms' ) . '</a>';
		}

		if ( current_user_can( $post_type_object->cap->delete_post, $posts->ID ) ) {
			if ( 'trash' == $post_status ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore this item from the Trash', 'everest-forms' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $posts->ID ) ), 'untrash-post_' . $posts->ID ) . '">' . esc_html__( 'Restore', 'everest-forms' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'everest-forms' ) . '" href="' . get_delete_post_link( $posts->ID ) . '">' . esc_html__( 'Trash', 'everest-forms' ) . '</a>';
			}
			if ( 'trash' == $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete this item permanently', 'everest-forms' ) . '" href="' . get_delete_post_link( $posts->ID, '', true ) . '">' . esc_html__( 'Delete permanently', 'everest-forms' ) . '</a>';
			}
		}

		if ( current_user_can( $post_type_object->cap->edit_post, $posts->ID ) ) {
			$preview_link   = add_query_arg( array(
				'form_id'     => absint( $posts->ID ),
				'evf_preview' => 'true',
			), home_url() );
			$duplicate_link = wp_nonce_url( admin_url( 'admin.php?page=evf-builder&action=duplicate_form&form_id=' . absint( $posts->ID ) ), 'everest-forms-duplicate-form_' . $posts->ID );

			if ( 'trash' !== $post_status ) {
				$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" rel="bookmark" target="_blank">' . __( 'Preview', 'everest-forms' ) . '</a>';
			}

			if ( 'publish' === $post_status ) {
				$actions['duplicate'] = '<a href="' . esc_url( $duplicate_link ) . '">' . __( 'Duplicate', 'everest-forms' ) . '</a>';
			}
		}

		$row_actions = array();

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * Return shortcode column.
	 *
	 * @param  object $post Form object.
	 * @return string
	 */
	function column_shortcode( $posts ) {
		$shortcode = '[everest_form id="' . $posts->ID . '"]';

		return sprintf( '<span class="shortcode"><input type="text" onfocus="this.select();" readonly="readonly" value=\'%s\' class="large-text code"></span>', $shortcode );
	}

	/**
	 * Return author column.
	 *
	 * @param  object $post Form object.
	 * @return string
	 */
	function column_author( $posts ) {
		$user = get_user_by( 'id', $posts->post_author );

		if ( ! $user ) {
			return '<span class="na">&ndash;</span>';
		}

		$user_name = ! empty( $user->data->display_name ) ? $user->data->display_name : $user->data->user_login;

		if ( current_user_can( 'edit_user' ) ) {
			return '<a href="' . esc_url( add_query_arg( array(
				'user_id' => $user->ID,
			), admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $user_name ) . '</a>';
		}

		return esc_html( $user_name );
	}

	/**
	 * Return date column.
	 *
	 * @param  object $post Form object.
	 * @return string
	 */
	function column_date( $posts ) {
		$post = get_post( $posts->ID );

		if ( ! $post ) {
			return;
		}

		$t_time = mysql2date( __( 'Y/m/d g:i:s A', 'everest-forms' ),
			$post->post_date, true );
		$m_time = $post->post_date;
		$time   = mysql2date( 'G', $post->post_date )
		          - get_option( 'gmt_offset' ) * 3600;

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
			$h_time = sprintf(
				__( '%s ago', 'everest-forms' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d', 'everest-forms' ), $m_time );
		}

		return '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
	}

	/**
	 * Return entries count.
	 *
	 * @param  object $post Form object.
	 * @return string
	 */
	public function column_entries( $posts ) {
		global $wpdb;

		$entries = count( $wpdb->get_results( $wpdb->prepare( "SELECT form_id FROM {$wpdb->prefix}evf_entries WHERE `status` != 'trash' AND form_id = %d", $posts->ID ) ) ); // WPCS: cache ok, DB call ok.

		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) {
			return '<strong>' . absint( $entries ) . '</strong>';
		} else {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $posts->ID ) ) . '">' . absint( $entries ) . '</a>';
		}
	}

	/**
	 * Get the status label for forms.
	 *
	 * @param string $status_name Status name.
	 * @param int    $amount      Amount of forms.
	 * @return array
	 */
	private function get_status_label( $status_name, $status ) {
		switch ( $status_name ) {
			case 'publish' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Published <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Published <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;
			case 'draft' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Draft <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Draft <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;
			case 'pending' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Pending <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Pending <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;
			default:
				$label = $status->label_count;
				break;
		}

		return $label;
	}

	/**
	 * Table list views.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links = array();
		$num_posts    = wp_count_posts( 'everest_form', 'readable' );
		$class        = '';
		$total_posts  = array_sum( (array) $num_posts );


		// Subtract post types that are not included in the admin all list.
		$post_stati = get_post_stati( array( 'show_in_admin_all_list' => false ) );
		foreach ( $post_stati as $state ) {
			$total_posts -= isset( $num_posts->$state ) ? $num_posts->$state : 0;
		}

		$class = empty( $class ) && empty( $_REQUEST['status'] ) ? ' class="current"' : '';
		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=evf-builder'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts', 'everest-forms' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class       = '';
			$status_name = $status->name;

			if ( ! in_array( $status_name, array( 'publish', 'draft', 'pending', 'trash', 'future', 'private', 'auto-draft' ) ) ) {
				continue;
			}

			if ( empty( $num_posts->$status_name ) ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && $status_name == $_REQUEST['status'] ) {
				$class = ' class="current"';
			}

			$label = $this->get_status_label( $status_name, $status );

			$status_links[ $status_name ] = "<a href='admin.php?page=evf-builder&amp;status=$status_name'$class>" . sprintf( translate_nooped_plural( $label, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' == $_GET['status'] ) {
			return array(
				'untrash' => __( 'Restore', 'everest-forms' ),
				'delete'  => __( 'Delete permanently', 'everest-forms' ),
			);
		}

		return array(
			'trash' => __( 'Move to trash', 'everest-forms' ),
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.2.0
	 */
	public function process_bulk_action() {
		$action   = $this->current_action();
		$form_ids = isset( $_REQUEST['form_id'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['form_id'] ) ) : array(); // WPCS: input var ok, CSRF ok.
		$count    = 0;

		if ( $form_ids ) {
			check_admin_referer( 'bulk-forms' );
		}

		switch ( $action ) {
			case 'trash':
				foreach ( $form_ids as $form_id ) {
					if ( wp_trash_post( $form_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					/* translators: %d: number of forms */
					sprintf( _n( '%d form moved to the Trash.', '%d forms moved to the Trash.', $count, 'everest-forms' ), $count ),
					'updated'
				);
				break;
			case 'untrash':
				foreach ( $form_ids as $form_id ) {
					if ( wp_untrash_post( $form_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					/* translators: %d: number of forms */
					sprintf( _n( '%d form restored from the Trash.', '%d forms restored from the Trash.', $count, 'everest-forms' ), $count ),
					'updated'
				);
				break;
			case 'delete':
				foreach ( $form_ids as $form_id ) {
					if ( wp_delete_post( $form_id, true ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					/* translators: %d: number of forms */
					sprintf( _n( '%d form permanently deleted.', '%d forms permanently deleted.', $count, 'everest-forms' ), $count ),
					'updated'
				);
				break;
		}
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		$num_posts = wp_count_posts( 'everest_form', 'readable' );

		if ( $num_posts->trash && isset( $_GET['status'] ) && 'trash' == $_GET['status'] && current_user_can( 'delete_posts' ) ) {
			echo '<div class="alignleft actions">';
				submit_button( __( 'Empty Trash', 'everest-forms' ), 'apply', 'delete_all', false );
			echo '</div>';
		}

	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'evf_forms_per_page' );
		$current_page = $this->get_pagenum();

		// Query args
		$args = array(
			'post_type'           => 'everest_form',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'paged'               => $current_page,
		);

		// Handle the status query
		if ( ! empty( $_REQUEST['status'] ) ) {
			$args['post_status'] = sanitize_text_field( $_REQUEST['status'] );
		}

		$args['s']       = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
		$args['orderby'] = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'date_created';
		$args['order']   = isset( $_REQUEST['order'] ) && 'ASC' === strtoupper( $_REQUEST['order'] ) ? 'ASC' : 'DESC';

		// Get the registrations
		$posts       = new WP_Query( $args );
		$this->items = $posts->posts;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $posts->found_posts,
			'per_page'    => $per_page,
			'total_pages' => $posts->max_num_pages,
		) );
	}
}
