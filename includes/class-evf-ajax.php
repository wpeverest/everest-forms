<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EverestForms EVF_AJAX.
 *
 * AJAX Event Handler.
 *
 * @class    EVF_AJAX
 * @package  EverestForms/Classes
 * @category Class
 * @author   WPEverest
 */
class EVF_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_evf_ajax' ), 0 );
		self::add_ajax_events();
	}


	/**
	 * Set EVF AJAX constant and headers.
	 */
	public static function define_ajax() {
		if ( ! empty( $_GET['ev-ajax'] ) ) {
			evf_maybe_define_constant( 'DOING_AJAX', true );
			evf_maybe_define_constant( 'EVF_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for EVF Ajax Requests.
	 *
	 * @since      1.0.0
	 */
	private static function evf_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for EVF Ajax request and fire action.
	 */
	public static function do_evf_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['evf-ajax'] ) ) {
			$wp_query->set( 'evf-ajax', sanitize_text_field( $_GET['evf-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'evf-ajax' ) ) {
			self::evf_ajax_headers();
			do_action( 'evf_ajax_' . sanitize_text_field( $action ) );
			wp_die();
		}
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'save_form'   => false,
			'create_form' => false,
			'get_next_id' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_everest_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_everest_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// EVF AJAX can be used for frontend ajax requests.
				add_action( 'evf_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function get_next_id() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_get_next_id', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( $form_id < 1 ) {
			wp_send_json_error( array(
				'error' => __( 'Invalid form', 'everest-forms' )
			) );
		}
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error();
		}
		$field_key      = EVF()->form->field_unique_key( $form_id );
		$field_id_array = explode( '-', $field_key );
		$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
		wp_send_json_success(
			array(
				'field_id'  => $new_field_id,
				'field_key' => $field_key
			)
		);
	}

	/**
	 * AJAX create new form.
	 */
	public static function create_form() {
		ob_start();

		check_ajax_referer( 'everest_forms_create_form', 'security' );

		if ( ! current_user_can( 'edit_everest_forms' ) ) {
			wp_die( -1 );
		}

		$title    = isset( $_POST['title'] ) ? $_POST['title'] : __( 'Blank Form', 'everest-forms' );
		$template = isset( $_POST['template'] ) ? $_POST['template'] : 'blank';

		$form_id = EVF()->form->create( $title, $template );

		if ( $form_id ) {
			$data = array(
				'id'       => $form_id,
				'redirect' => add_query_arg(
					array(
						'tab'     => 'fields',
						'form_id' => $form_id,
					),
					admin_url( 'admin.php?page=edit-evf-form' )
				),
			);

			wp_send_json_success( $data );
		}

		wp_send_json_error( array(
			'error' => __( 'Something went wrong, please try again later', 'everest-forms' )
		) );
	}

	public static function save_form() {

		check_ajax_referer( 'everest_forms_save_form', 'security' );

		// Check for permissions
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			die( __( 'You do not have permission.', 'everest-forms' ) );
		}

		// Check for form data
		if ( empty( $_POST['form_data'] ) ) {
			die( __( 'No data provided', 'everest-forms' ) );
		}

		$form_post = json_decode( stripslashes( $_POST['form_data'] ) );

		$data = array();

		if ( ! is_null( $form_post ) && $form_post ) {
			foreach ( $form_post as $post_input_data ) {
				// For input names that are arrays (e.g. `menu-item-db-id[3][4][5]`),
				// derive the array path keys via regex and set the value in $_POST.
				preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

				$array_bits = array( $matches[1] );

				if ( isset( $matches[3] ) ) {
					$array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
				}

				$new_post_data = array();

				// Build the new array value from leaf to trunk.
				for ( $i = count( $array_bits ) - 1; $i >= 0; $i -- ) {
					if ( $i === count( $array_bits ) - 1 ) {
						$new_post_data[ $array_bits[ $i ] ] = wp_slash( $post_input_data->value );
					} else {
						$new_post_data = array(
							$array_bits[ $i ] => $new_post_data,
						);
					}
				}

				$data = array_replace_recursive( $data, $new_post_data );
			}
		}

		$form_id = EVF()->form->update( $data['id'], $data );

		do_action( 'everest_forms_save_form', $form_id, $data );

		if ( ! $form_id ) {
			die( __( 'An error occurred and the form could not be saved', 'everest-forms' ) );
		} else {
			wp_send_json_success(
				array(
					'form_name'    => esc_html( $data['settings']['form_title'] ),
					'redirect_url' => admin_url( 'admin.php?page=everest-forms' ),
				)
			);
		}
	}
}

EVF_AJAX::init();
