<?php

/**
 * form handler.
 *
 * Contains a bunch of helper methods as well.
 *
 * @package    EverestForms
 * @author     WPEverest
 * @since      1.0.0
 */
class EVF_Form_Handler {

	/**
	 * Fetches forms
	 *
	 * @since  1.0.0
	 * @param  mixed $id
	 * @param  array $args
	 * @return array|bool|null|WP_Post
	 */
	public function get( $id = '', $args = array() ) {
		$forms = array();
		$args  = apply_filters( 'everest_forms_get_form_args', $args );

		if ( false === $id ) {
			return false;
		}

		if ( ! empty( $id ) ) {
			$the_post = get_post( absint( $id ) );

			if ( $the_post && 'everest_form' === $the_post->post_type ) {
				$forms = empty( $args['content_only'] ) ? $the_post : evf_decode( $the_post->post_content );
			}
		} else {
			// No ID provided, get multiple forms.
			$defaults = array(
				'post_type'     => 'everest_form',
				'orderby'       => 'id',
				'order'         => 'DESC',
				'no_found_rows' => true,
				'nopaging'      => true,
			);

			$args = wp_parse_args( $args, $defaults );

			$args['post_type'] = 'everest_form';

			$forms = get_posts( $args );
		}

		if ( empty( $forms ) ) {
			return false;
		}

		return $forms;
	}

	/**
	 * Delete forms.
	 *
	 * @since  1.0.0
	 * @param  array $ids
	 * @return boolean
	 */
	public function delete( $ids = array() ) {
		// Check for permissions.
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			return false;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$form = wp_delete_post( $id, true );

			if ( class_exists( 'EVF_Entry_Handler' ) ) {
				// Delete entry if exists.
			}

			if ( ! $form ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create new form.
	 *
	 * @since  1.0.0
	 * @param  string $title
	 * @param  array  $args
	 * @param  array  $data
	 * @return mixed
	 */
	public static function create( $title = '', $template = 'blank', $args = array(), $data = array() ) {
		if ( empty( $title ) || ! current_user_can( 'manage_everest_forms' ) ) {
			return false;
		}

		$args         = apply_filters( 'everest_forms_create_form_args', $args, $data );
		$form_content = array(
			'form_field_id' => '1',
			'settings'      => array(
				'form_title' => sanitize_text_field( $title ),
				'form_desc'  => '',
			),
		);

		// Check for template and format the form content.
		if ( in_array( $template, array( 'contact' ), true ) ) {
			include_once dirname( __FILE__ ) . "/templates/{$template}.php";
			$form_content = $form_template[ $template ];
		}

		// Prevent content filters from corrupting JSON in post_content.
		$has_kses = ( false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' ) );
		if ( $has_kses ) {
			kses_remove_filters();
		}
		$has_targeted_link_rel_filters = ( false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' ) );
		if ( $has_targeted_link_rel_filters ) {
			wp_remove_targeted_link_rel_filters();
		}

		// Create a form.
		$form_id = wp_insert_post(
			array(
				'post_title'   => esc_html( $title ),
				'post_status'  => 'publish',
				'post_type'    => 'everest_form',
				'post_content' => '{}',
			)
		);

		if ( $form_id ) {
			$form_data = wp_parse_args(
				$args,
				array(
					'ID'           => $form_id,
					'post_title'   => esc_html( $title ),
					'post_content' => evf_encode( array_merge( array( 'id' => $form_id ), $form_content ) ),
				)
			);

			wp_update_post( $form_data );
		}

		// Restore removed content filters.
		if ( $has_kses ) {
			kses_init_filters();
		}
		if ( $has_targeted_link_rel_filters ) {
			wp_init_targeted_link_rel_filters();
		}

		do_action( 'everest_forms_create_form', $form_id, $form_data, $data );

		return $form_id;
	}

	/**
	 * Updates form
	 *
	 * @since    1.0.0
	 * @param    string $form_id
	 * @param    array  $data
	 * @param    array  $args
	 * @return   mixed
	 * @internal param string $title
	 */
	public function update( $form_id = '', $data = array(), $args = array() ) {
		// Check for permissions.
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			return false;
		}

		if ( empty( $data ) ) {
			return false;
		}

		if ( empty( $form_id ) ) {
			$form_id = $data['form_id'];
		}

		$data = wp_unslash( $data );

		if ( ! empty( $data['settings']['form_title'] ) ) {
			$title = $data['settings']['form_title'];
		} else {
			$title = get_the_title( $form_id );
		}

		if ( ! empty( $data['settings']['form_desc'] ) ) {
			$desc = $data['settings']['form_desc'];
		} else {
			$desc = '';
		}

		$data['form_field_id'] = ! empty( $data['form_field_id'] ) ? absint( $data['form_field_id'] ) : '0';

		// This filter can destroy the JSON when messing with HTML.
		remove_filter( 'content_save_pre', 'balanceTags', 50 );

		// Don't allow tags for users who do not have appropriate cap.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$data = map_deep( $data, 'wp_strip_all_tags' );
		}

		// Prevent content filters from corrupting JSON in post_content.
		$has_kses = ( false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' ) );
		if ( $has_kses ) {
			kses_remove_filters();
		}
		$has_targeted_link_rel_filters = ( false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' ) );
		if ( $has_targeted_link_rel_filters ) {
			wp_remove_targeted_link_rel_filters();
		}

		$form    = array(
			'ID'           => $form_id,
			'post_title'   => esc_html( $title ),
			'post_excerpt' => $desc,
			'post_content' => evf_encode( $data ),
		);
		$form    = apply_filters( 'everest_forms_save_form_args', $form, $data, $args );
		$form_id = wp_update_post( $form );

		// Restore removed content filters.
		if ( $has_kses ) {
			kses_init_filters();
		}
		if ( $has_targeted_link_rel_filters ) {
			wp_init_targeted_link_rel_filters();
		}

		do_action( 'everest_forms_save_form', $form_id, $form );

		return $form_id;
	}

	/**
	 * Duplicate forms.
	 *
	 * @since  1.0.0
	 * @param  array $ids
	 * @return boolean
	 */
	public function duplicate( $ids = array() ) {
		// Check for permissions.
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			return false;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {

			// Get original entry.
			$form = get_post( $id );

			// Confirm form exists.
			if ( ! $form || empty( $form ) ) {
				return false;
			}

			// Get the form data.
			$new_form_data = evf_decode( $form->post_content );

			// Remove form ID from title if present.
			$new_form_data['settings']['form_title'] = str_replace( '(ID #' . absint( $id ) . ')', '', $new_form_data['settings']['form_title'] );

			// Create the duplicate form.
			$new_form    = array(
				'post_author'  => $form->post_author,
				'post_content' => evf_encode( $new_form_data ),
				'post_excerpt' => $form->post_excerpt,
				'post_status'  => $form->post_status,
				'post_title'   => $new_form_data['settings']['form_title'],
				'post_type'    => $form->post_type,
			);
			$new_form_id = wp_insert_post( $new_form );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}

			// Set new form name.
			$new_form_data['settings']['form_title'] .= ' (ID #' . absint( $new_form_id ) . ')';

			// Set new form ID.
			$new_form_data['id'] = absint( $new_form_id );

			// Update new duplicate form.
			$new_form_id = $this->update( $new_form_id, $new_form_data );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}

			return $new_form_id;
		}

		return true;
	}

	/**
	 * Get private meta information for a form.
	 *
	 * @since 1.1.0
	 *
	 * @param string $form_id
	 * @param string $field
	 *
	 * @return bool
	 */
	public function get_meta( $form_id, $field = '' ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		$data = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( isset( $data['meta'] ) ) {
			if ( empty( $field ) ) {
				return $data['meta'];
			} elseif ( isset( $data['meta'][ $field ] ) ) {
				return $data['meta'][ $field ];
			}
		}

		return false;
	}

	/**
	 * Get the next available field ID and increment by one.
	 *
	 * @since  1.0.0
	 * @param  int $form_id
	 * @return mixed int or false
	 */
	public function field_unique_key( $form_id ) {
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			return false;
		}

		if ( empty( $form_id ) ) {
			return false;
		}

		$form = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( ! empty( $form['form_field_id'] ) ) {
			$form_field_id = absint( $form['form_field_id'] );
			$form['form_field_id'] ++;
		} else {
			$form_field_id         = '0';
			$form['form_field_id'] = '1';
		}

		$this->update( $form_id, $form );

		$field_id = evf_get_random_string() . '-' . $form_field_id;

		return $field_id;
	}


	/**
	 * Get private meta information for a form field.
	 *
	 * @since  1.0.0
	 * @param  string $form_id
	 * @param  string $field_id
	 * @return bool
	 */
	public function get_field( $form_id, $field_id = '' ) {

		if ( empty( $form_id ) ) {
			return false;
		}

		$data = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		return isset( $data['form_fields'][ $field_id ] ) ? $data['form_fields'][ $field_id ] : false;
	}

	/**
	 * Get private meta information for a form field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $form_id
	 * @param string $field
	 *
	 * @return bool
	 */
	public function get_field_meta( $form_id, $field = '' ) {

		$field = $this->get_field( $form_id, $field );
		if ( ! $field ) {
			return false;
		}

		return isset( $field['meta'] ) ? $field['meta'] : false;
	}
}
