<?php
/**
 * Entry Submission Controller Class.
 *
 * @since xx.xx.xx
 *
 * @package  EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * UR_AddonsClass
 */
class EVF_Entry_Submission {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'entry';

	/**
	 * Register routes.
	 *
	 * @since xx.xx.xx
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_entry' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}
	/**
	 * Save the entry.
	 *
	 * @since xx.xx.xx
	 * @param WP_REST_Request $request Full data about the request.
	 */
	public static function save_entry( $request ) {
		global $wpdb;

		$entry = $request->get_params();
		if ( empty( $entry['form_fields'] ) ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'No entry data found!', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}

		$form_id = isset( $entry['id'] ) ? absint( $entry['id'] ) : 0;
		if ( empty( $form_id ) ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Form id is missing!', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}
		$form = evf()->form->get( $form_id );
		if ( empty( $form ) ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Form is not found!', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}
		$form_data = apply_filters( 'everest_forms_process_before_form_data', evf_decode( $form->post_content ), $entry );

		if ( isset( $form_data['form_enabled'] ) && ! $form_data['form_enabled'] ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Form is disalbed!', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}

		if ( empty( $form_data['form_fields'] ) ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Form is empty!', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}

		if ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Save entris is enable! Please disable to save the entry.', 'everest-forms' ),
					'data'    => $entry,
				),
				400
			);
		}
		$errors      = array();
		$form_fields = array();
		$entry       = apply_filters( 'everest_forms_process_before_save_entry', $entry, $form_data );

		$form_data['entry'] = $entry;

		foreach ( $entry['form_fields'] as $field_id => $field_value ) {
			if ( array_key_exists( $field_id, $form_data['form_fields'] ) ) {
				$field_type = $form_data['form_fields'][ $field_id ]['type'];
				if ( 'signature' === $field_type ) {
					$field_submit = isset( $field_value['signature_image'] ) ? $field_value['signature_image'] : '';
				}

				$exclude = array( 'title', 'html', 'captcha', 'image-upload', 'file-upload', 'divider', 'reset', 'recaptcha', 'hcaptcha', 'turnstile' );

				if ( ! in_array( $field_type, $exclude, true ) ) {
					$form_fields[ $field_id ] = array(
						'name'     => sanitize_text_field( $form_data['form_fields'][ $field_id ]['label'] ),
						'value'    => $field_value,
						'id'       => $field_id,
						'type'     => $field_type,
						'meta_key' => $form_data['form_fields'][ $field_id ]['meta-key'],
					);

					if ( 'checkbox' === $field_type ) {
						$form_fields[ $field_id ]['value']     = array(
							'name'  => sanitize_text_field( $form_data['form_fields'][ $field_id ]['label'] ),
							'type'  => $field_type,
							'label' => $field_value,
						);
						$form_fields[ $field_id ]['value_raw'] = $field_value;
					}

					if ( 'likert' === $field_type ) {
						$likert_rows    = $form_data['form_fields'][ $field_id ]['likert_rows'];
						$likert_columns = $form_data['form_fields'][ $field_id ]['likert_columns'];
						$combined_value = '';
						foreach ( $field_value as $key => $value ) {
							if ( array_key_exists( $key, $likert_rows ) ) {

								$combined_value .= "$likert_rows[$key]:\n";
							}
							if ( array_key_exists( $key, $likert_columns ) ) {

								$combined_value .= "$likert_columns[$key]:\n";
							}
						}
						$form_fields[ $field_id ]['value'] = $combined_value;
					}

					if ( 'address' === $field_type ) {
						$form_fields[ $field_id ]['value'] = implode( '\n', $field_value );
					}

					if ( 'country' === $field_type ) {
						$form_fields[ $field_id ]['value'] = array(
							'type'         => $field_type,
							'country_code' => $field_value,
						);
					}
				}
			}
		}
		// Validate fields.
		foreach ( $form_data['form_fields'] as $field ) {
			$field_id   = $field['id'];
			$field_type = $field['type'];

			$field_value = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';
			do_action( "everest_forms_process_validate_{$field_type}", $field_id, $field_value, $form_data, $field_type );

		}

		$errors = isset( evf()->task->errors[ $form_data['id'] ] ) ? evf()->task->errors[ $form_data['id'] ] : array();

		if ( ! empty( $errors ) ) {
			return new \WP_REST_Response(
				array(
					'message' => esc_html__( 'Error found!!', 'everest-forms' ),
					'errors'  => $errors,
				),
				400
			);
		}

		$task_instance = new EVF_Form_Task();
		$entry_id      = $task_instance->entry_save( $form_fields, $entry, $form_data['id'], $form_data );

		return new \WP_REST_Response(
			array(
				'entry_id' => $entry_id,
			),
			200
		);
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		return true;
	}
}
