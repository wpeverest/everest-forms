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

		$entry       = $request->get_params();
		$errors      = array();
		$form_fields = array();
		$form_id     = absint( $entry['id'] );
		$form        = evf()->form->get( $form_id );

		$form_data = apply_filters( 'everest_forms_process_before_form_data', evf_decode( $form->post_content ), $entry );

		$entry = apply_filters( 'everest_forms_process_before_filter', $entry, $form_data );

		$logger = evf_get_logger();

		$logger->info(
			__( 'Everest Forms Process Before.', 'everest-forms' ),
			array( 'source' => 'form-submission' )
		);
		$form_data['entry'] = $entry;

		// Validate fields.
		foreach ( $entry['form_fields'] as $field_id => $field_value ) {
			if ( array_key_exists( $field_id, $form_data['form_fields'] ) ) {
				$form_fields[ $field_id ] = array(
					'name'     => $form_data['form_fields'][ $field_id ]['label'],
					'value'    => $field_value,
					'id'       => $field_id,
					'type'     => $form_data['form_fields'][ $field_id ]['type'],
					'meta_key' => $form_data['form_fields'][ $field_id ]['meta-key'],
				);
			}
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
