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
	 * Display the pagination.
	 *
	 * Enhanced pagination with numbered page links.
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

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();
		$current_url          = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$current_url          = remove_query_arg( $removable_query_args, $current_url );

		$per_page = $this->_pagination_args['per_page'];
		$start    = ( ( $current - 1 ) * $per_page ) + 1;
		$end      = min( $current * $per_page, $total_items );

		$output = '<span class="displaying-num">' . sprintf(
			/* translators: 1: start number, 2: end number, 3: total number */
			esc_html__( 'Showing %1$s-%2$s of %3$s entries', 'everest-forms' ),
			number_format_i18n( $start ),
			number_format_i18n( $end ),
			number_format_i18n( $total_items )
		) . '</span>';

		$page_links = array();

		if ( 1 === $current ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				esc_html__( 'Previous page', 'everest-forms' ),
				'&lsaquo;'
			);
		}

		$mid_size = 2;
		$end_size = 1;

		for ( $n = 1; $n <= $total_pages; $n++ ) {
			$show_link = false;

			if ( $n <= $end_size || $n > $total_pages - $end_size ) {
				$show_link = true;
			}

			if ( $n >= $current - $mid_size && $n <= $current + $mid_size ) {
				$show_link = true;
			}

			if ( $show_link ) {
				if ( $n === $current ) {
					$page_links[] = sprintf( "<span class='page-numbers current'>%s</span>", number_format_i18n( $n ) );
				} else {
					$page_links[] = sprintf(
						"<a class='page-numbers' href='%s'>%s</a>",
						esc_url( add_query_arg( 'paged', $n, $current_url ) ),
						number_format_i18n( $n )
					);
				}
			} elseif ( ! $show_link && isset( $page_links[ count( $page_links ) - 1 ] ) && strpos( $page_links[ count( $page_links ) - 1 ], 'dots' ) === false ) {
				$page_links[] = '<span class="page-numbers dots">...</span>';
			}
		}

		if ( $total_pages === $current ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				esc_html__( 'Next page', 'everest-forms' ),
				'&rsaquo;'
			);
		}

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
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( ! empty( $_REQUEST['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}
}
