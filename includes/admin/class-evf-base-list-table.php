<?php
/**
 * EverestForms Base List Table
 *
 * Abstract base class for all EverestForms table list classes.
 * This class extends WP_List_Table and provides common functionality
 * for both forms and entries table lists.
 *
 * @package EverestForms\Admin
 * @since   3.4.3
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Base table list class.
 *
 * This abstract class provides a foundation for all EverestForms table implementations.
 * Child classes should extend this class and implement/override methods as needed.
 *
 * @abstract
 */
abstract class EVF_Base_List_Table extends WP_List_Table {

	/**
	 * Initialize the base table list.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * No items found text.
	 *
	 * Default implementation. Child classes should override this method.
	 */
	public function no_items() {
		esc_html_e( 'No items found.', 'everest-forms' );
	}

	/**
	 * Process bulk actions.
	 *
	 * Default implementation. Child classes should override this method.
	 */
	public function process_bulk_action() {
		// Child classes should implement their own bulk action logic.
	}

	/**
	 * Get bulk actions.
	 *
	 * Default implementation. Child classes should override this method.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * Default implementation. Child classes can override this method.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * Default implementation. Child classes can override this method.
	 *
	 * @param string $which The location of the extra table nav markup.
	 */
	protected function extra_tablenav( $which ) {
		// Child classes can implement their own extra tablenav logic.
	}

	/**
	 * Display content above the table.
	 *
	 * This method can be overridden by child classes to add custom content
	 * above the table structure (before tablenav and the actual table).
	 *
	 * @since 3.4.3
	 */
	protected function display_above_table() {
		/**
		 * Hook to add content above the table.
		 *
		 * @param EVF_Base_List_Table $table The current table instance.
		 */
		do_action( 'everest_forms_above_table', $this );

		// Default implementation - adds the everest-forms-extra-table-nav div
		?>
		<div class="everest-forms-extra-table-nav">
			<?php
			/**
			 * Hook to add content inside the extra table nav div.
			 *
			 * @param EVF_Base_List_Table $table The current table instance.
			 */
			do_action( 'everest_forms_extra_table_nav_content', $this );
			?>
		</div>
		<?php
	}

	/**
	 * Display the table with above-table content support.
	 *
	 * Overrides parent display() to add content above the table and wrap everything in a div.
	 */
	public function display() {
		// Opening wrapper div
		echo '<div class="everest-forms-table-wrapper">';

		// Display content above the table.
		$this->display_above_table();

		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<div class="everest-forms-table-container">
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"
					<?php
					if ( $singular ) {
						echo " data-wp-lists='list:$singular'";
					}
					?>
					>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>

				<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
				</tfoot>

			</table>
		</div>
		<?php
		$this->display_tablenav( 'bottom' );

		// Closing wrapper div
		echo '</div>';
	}

	/**
	 * Table list views.
	 *
	 * Default implementation. Child classes can override this method.
	 *
	 * @return array
	 */
	protected function get_views() {
		return array();
	}

	/**
	 * Column cb (checkbox).
	 *
	 * Default implementation for checkbox column.
	 * Child classes can override this method.
	 *
	 * @param  object $item Item object.
	 * @return string
	 */
	public function column_cb( $item ) {
		if ( ! isset( $item->ID ) ) {
			return '';
		}
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID );
	}

	/**
	 * Renders the columns.
	 *
	 * Default implementation for rendering columns.
	 * Child classes can override this method.
	 *
	 * @param  object $item        Item object.
	 * @param  string $column_name Column Name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Helper method to sanitize text field from request.
	 *
	 * @param string $key The request parameter key.
	 * @return string Sanitized value or empty string.
	 */
	protected function get_sanitized_request( $key ) {
		return isset( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Helper method to get integer value from request.
	 *
	 * @param string $key The request parameter key.
	 * @return int Sanitized integer value or 0.
	 */
	protected function get_int_request( $key ) {
		return isset( $_REQUEST[ $key ] ) ? absint( $_REQUEST[ $key ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Helper method to check if a request parameter exists.
	 *
	 * @param string $key The request parameter key.
	 * @return bool True if parameter exists, false otherwise.
	 */
	protected function has_request( $key ) {
		return isset( $_REQUEST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Get a list of CSS classes for the table element.
	 *
	 * @return array List of CSS class names.
	 */
	protected function get_table_classes() {
		$classes   = parent::get_table_classes();
		$classes[] = 'evf-base-list-table';
		return $classes;
	}

	/**
	 * Get the current URL with sanitized server variables.
	 *
	 * @return string Current URL.
	 */
	protected function get_current_url() {
		$http_host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( empty( $http_host ) || empty( $request_uri ) ) {
			return admin_url();
		}

		$current_url          = set_url_scheme( 'http://' . $http_host . $request_uri );
		$removable_query_args = wp_removable_query_args();

		return remove_query_arg( $removable_query_args, $current_url );
	}

	/**
	 * Get the display text for pagination showing current range of items.
	 *
	 * @param int $current     Current page number.
	 * @param int $per_page    Number of items per page.
	 * @param int $total_items Total number of items.
	 * @return string HTML output for displaying item range.
	 */
	protected function get_pagination_display_text( $current, $per_page, $total_items ) {
		$start = ( ( $current - 1 ) * $per_page ) + 1;
		$end   = min( $current * $per_page, $total_items );

		return '<span class="displaying-num">' . sprintf(
			/* translators: 1: start number, 2: end number, 3: total number */
			esc_html__( 'Showing %1$s-%2$s of %3$s entries', 'everest-forms' ),
			number_format_i18n( $start ),
			number_format_i18n( $end ),
			number_format_i18n( $total_items )
		) . '</span>';
	}

	/**
	 * Get first and last page navigation buttons.
	 *
	 * @param int    $current     Current page number.
	 * @param int    $total_pages Total number of pages.
	 * @param string $current_url Current URL without pagination args.
	 * @return array Array of HTML strings for navigation buttons.
	 */
	protected function get_first_last_buttons( $current, $total_pages, $current_url ) {
		$buttons = array();

		// First page button.
		if ( 1 === $current ) {
			$buttons['first'] = '<span class="tablenav-pages-navspan button disabled first-page" aria-hidden="true" title="' . esc_attr__( 'First page', 'everest-forms' ) . '">&laquo;</span>';
		} else {
			$buttons['first'] = sprintf(
				"<a class='first-page button' href='%s' title='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', 1, $current_url ) ),
				esc_attr__( 'First page', 'everest-forms' ),
				esc_html__( 'First page', 'everest-forms' ),
				'&laquo;'
			);
		}

		// Last page button.
		if ( $total_pages === $current ) {
			$buttons['last'] = '<span class="tablenav-pages-navspan button disabled last-page" aria-hidden="true" title="' . esc_attr__( 'Last page', 'everest-forms' ) . '">&raquo;</span>';
		} else {
			$buttons['last'] = sprintf(
				"<a class='last-page button' href='%s' title='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				esc_attr__( 'Last page', 'everest-forms' ),
				esc_html__( 'Last page', 'everest-forms' ),
				'&raquo;'
			);
		}

		return $buttons;
	}

	/**
	 * Get previous and next page navigation buttons.
	 *
	 * @param int    $current     Current page number.
	 * @param int    $total_pages Total number of pages.
	 * @param string $current_url Current URL without pagination args.
	 * @return array Array of HTML strings for navigation buttons.
	 */
	protected function get_prev_next_buttons( $current, $total_pages, $current_url ) {
		$buttons = array();

		// Previous page button.
		if ( 1 === $current ) {
			$buttons['prev'] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$buttons['prev'] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				esc_html__( 'Previous page', 'everest-forms' ),
				'&lsaquo;'
			);
		}

		// Next page button.
		if ( $total_pages === $current ) {
			$buttons['next'] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$buttons['next'] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				esc_html__( 'Next page', 'everest-forms' ),
				'&rsaquo;'
			);
		}

		return $buttons;
	}

	/**
	 * Get numbered page links for pagination.
	 *
	 * @param int    $current     Current page number.
	 * @param int    $total_pages Total number of pages.
	 * @param string $current_url Current URL without pagination args.
	 * @return array Array of HTML strings for page number links.
	 */
	protected function get_page_number_links( $current, $total_pages, $current_url ) {
		$page_links = array();
		$mid_size   = 2;
		$end_size   = 1;

		for ( $n = 1; $n <= $total_pages; $n++ ) {
			$show_link = false;

			// Show first and last pages.
			if ( $n <= $end_size || $n > $total_pages - $end_size ) {
				$show_link = true;
			}

			// Show pages around current page.
			if ( $n >= $current - $mid_size && $n <= $current + $mid_size ) {
				$show_link = true;
			}

			if ( $show_link ) {
				if ( $n === $current ) {
					$page_links[] = sprintf(
						"<span class='page-numbers current' aria-current='page'>%s</span>",
						number_format_i18n( $n )
					);
				} else {
					$page_links[] = sprintf(
						"<a class='page-numbers' href='%s' aria-label='%s'>%s</a>",
						esc_url( add_query_arg( 'paged', $n, $current_url ) ),
						/* translators: %s: page number */
						esc_attr( sprintf( __( 'Page %s', 'everest-forms' ), number_format_i18n( $n ) ) ),
						number_format_i18n( $n )
					);
				}
			} elseif ( ! empty( $page_links ) && strpos( $page_links[ count( $page_links ) - 1 ], 'dots' ) === false ) {
				$page_links[] = '<span class="page-numbers dots" aria-hidden="true">...</span>';
			}
		}

		return $page_links;
	}

	/**
	 * Display the pagination.
	 *
	 * Enhanced pagination with numbered page links, first/last buttons, and items per page selector.
	 * Only displays at the bottom of the table.
	 *
	 * @param string $which The location of the pagination nav.
	 */
	protected function pagination( $which ) {
		if ( 'top' === $which ) {
			return;
		}

		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items     = $this->_pagination_args['total_items'];
		$total_pages     = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;

		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		$current     = $this->get_pagenum();
		$per_page    = $this->_pagination_args['per_page'];
		$current_url = $this->get_current_url();

		// Get display text showing current range.
		$output = $this->get_pagination_display_text( $current, $per_page, $total_items );

		// Build page links array.
		$page_links = array();

		// Get first/last buttons.
		$first_last   = $this->get_first_last_buttons( $current, $total_pages, $current_url );
		$page_links[] = $first_last['first'];

		// Get prev/next buttons.
		$prev_next    = $this->get_prev_next_buttons( $current, $total_pages, $current_url );
		$page_links[] = $prev_next['prev'];

		// Get numbered page links.
		$number_links = $this->get_page_number_links( $current, $total_pages, $current_url );
		$page_links   = array_merge( $page_links, $number_links );

		// Add next and last buttons.
		$page_links[] = $prev_next['next'];
		$page_links[] = $first_last['last'];

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get current page number.
	 *
	 * @return int Current page number.
	 */
	public function get_pagenum() {
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
			$pagenum = $this->_pagination_args['total_pages'];
		}

		return max( 1, $pagenum );
	}

	/**
	 * Display the search box.
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) . '" />';
		}
		?>
	<div class="search-box evf-search">
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
			<?php echo esc_html( $text ); ?>:
		</label>

		<input type="search"
				id="<?php echo esc_attr( $input_id ); ?>"
				name="s"
				value="<?php _admin_search_query(); ?>"
				placeholder="<?php echo esc_attr( $text ); ?>" />

		<button type="submit" id="search-submit" class="evf-search-submit button">
			<span class="screen-reader-text"><?php echo esc_html( $text ); ?></span>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
				<path fill="currentColor" fill-rule="evenodd"
						d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z"
						clip-rule="evenodd"></path>
			</svg>
		</button>
	</div>
		<?php
	}
}
