<?php
/**
 * Deprecated action hooks
 *
 * @package EverestForms\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles deprecation notices and triggering of legacy action hooks.
 */
class EVF_Deprecated_Action_Hooks extends EVF_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle. Format of 'new' => 'old'.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'everest_forms_builder_page_init' => array(
			'everest_forms_page_init',
			'everest_forms_builder_init',
		),
		'admin_enqueue_scripts'           => array(
			'everest_forms_page_init',
			'everest_forms_builder_scripts',
			'everest_forms_builder_enqueues_before',
		),
		'everest_forms_builder_tabs'               => 'everest_forms_builder_panel_buttons',
		'everest_forms_builder_output'             => 'everest_forms_builder_panels',
		'everest_forms_builder_fields_preview'     => 'everest_forms_builder_preview',
		'everest_forms_display_field_before'       => 'evf_display_field_before',
		'everest_forms_display_field_after'        => 'evf_display_field_after',
		'everest_forms_display_fields_before'      => 'evf_display_fields_before',
		'everest_forms_display_fields_after'       => 'evf_display_fields_after',
		'everest_forms_display_field_{field_type}' => 'evf_display_field_{field_type}',
		'everest_forms_frontend_output_before'     => 'evf_frontend_output_before',
		'everest_forms_frontend_output_success'    => 'evf_frontend_output_success',
		'everest_forms_frontend_output'            => 'evf_frontend_output',
		'everest_forms_frontend_output_after'      => 'evf_frontend_output_after',
		'everest_forms_display_submit_before'      => 'evf_display_submit_before',
		'everest_forms_display_submit_after'       => 'evf_display_submit_after',
	);

	/**
	 * Array of versions on each hook has been deprecated.
	 *
	 * @var array
	 */
	protected $deprecated_version = array(
		'everest_forms_page_init'               => '1.2.0',
		'everest_forms_builder_init'            => '1.2.0',
		'everest_forms_builder_scripts'         => '1.2.0',
		'everest_forms_builder_enqueues_before' => '1.2.0',
		'everest_forms_builder_panel_buttons'   => '1.2.0',
		'everest_forms_builder_panels'          => '1.2.0',
		'everest_forms_builder_preview'         => '1.2.0',
		'evf_display_field_before'              => '1.2.0',
		'evf_display_field_after'               => '1.2.0',
		'evf_display_fields_before'             => '1.3.0',
		'evf_display_fields_after'              => '1.3.0',
		'evf_frontend_output_before'            => '1.3.2',
		'evf_frontend_output_success'           => '1.3.2',
		'evf_frontend_output'                   => '1.3.2',
		'evf_frontend_output_after'             => '1.3.2',
		'evf_display_submit_before'             => '1.3.2',
		'evf_display_submit_after'              => '1.3.2',
	);

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_action( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_action( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		do_action_ref_array( $old_hook, $new_callback_args );
	}
}
