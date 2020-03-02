<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package EverestForms\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class EVF_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_menus' ), 100 );
		add_action( 'everest_forms_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'everest_forms_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'everest_form' ) ) {
			return;
		}

		do_action( 'everest_forms_register_post_type' );

		register_post_type(
			'everest_form',
			apply_filters(
				'everest_forms_register_post_type_product',
				array(
					'labels'              => array(
						'name'                  => __( 'Forms', 'everest-forms' ),
						'singular_name'         => __( 'Form', 'everest-forms' ),
						'all_items'             => __( 'All Forms', 'everest-forms' ),
						'menu_name'             => _x( 'Forms', 'Admin menu name', 'everest-forms' ),
						'add_new'               => __( 'Add New', 'everest-forms' ),
						'add_new_item'          => __( 'Add new form', 'everest-forms' ),
						'edit'                  => __( 'Edit', 'everest-forms' ),
						'edit_item'             => __( 'Edit form', 'everest-forms' ),
						'new_item'              => __( 'New form', 'everest-forms' ),
						'view_item'             => __( 'View form', 'everest-forms' ),
						'search_items'          => __( 'Search forms', 'everest-forms' ),
						'not_found'             => __( 'No forms found', 'everest-forms' ),
						'not_found_in_trash'    => __( 'No forms found in trash', 'everest-forms' ),
						'parent'                => __( 'Parent forms', 'everest-forms' ),
						'featured_image'        => __( 'Form image', 'everest-forms' ),
						'set_featured_image'    => __( 'Set form image', 'everest-forms' ),
						'remove_featured_image' => __( 'Remove form image', 'everest-forms' ),
						'use_featured_image'    => __( 'Use as form image', 'everest-forms' ),
						'insert_into_item'      => __( 'Insert into form', 'everest-forms' ),
						'uploaded_to_this_item' => __( 'Uploaded to this form', 'everest-forms' ),
						'filter_items_list'     => __( 'Filter forms', 'everest-forms' ),
						'items_list_navigation' => __( 'Forms navigation', 'everest-forms' ),
						'items_list'            => __( 'Forms list', 'everest-forms' ),
					),
					'public'              => false,
					'show_ui'             => true,
					'description'         => __( 'This is where you can add new forms.', 'everest-forms' ),
					'capability_type'     => 'everest_form',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_in_rest'        => true,
					'show_in_menu'        => false,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
				)
			)
		);

		do_action( 'everest_forms_after_register_post_type' );
	}

	/**
	 * Add "Everest Forms" link in admin bar main menu.
	 *
	 * @since 1.2.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_everest_forms' ) ) {
			return;
		}

		// Show only when the user is a member of this site, or they're a super admin.
		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		// Add an option to create new form.
		if ( apply_filters( 'everest_forms_show_admin_bar_menus', true ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'new-content',
					'id'     => 'everest-forms',
					'title'  => __( 'Everest Forms', 'everest-forms' ),
					'href'   => admin_url( 'admin.php?page=evf-builder&create-form=1' ),
				)
			);
		}
	}

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 1.2.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'everest_forms_queue_flush_rewrite_rules' ) ) {
			update_option( 'everest_forms_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
}

EVF_Post_Types::init();
