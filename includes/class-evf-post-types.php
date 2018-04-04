<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package EverestForms/Classes
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Post_Types Class.
 */
class EVF_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
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

		register_post_type( 'everest_form',
			apply_filters( 'everest_forms_register_post_type_product',
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
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}


}

EVF_Post_Types::init();
