<?php
/**
 * Deprecated filter hooks
 *
 * @package EverestForms\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles deprecation notices and triggering of legacy filter hooks.
 */
class EVF_Deprecated_Filter_Hooks extends EVF_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle. Format of 'new' => 'old'.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'everest_forms_fields'                        => 'everest_forms_load_fields',
		'everest_forms_show_media_button'             => 'evf_display_media_button',
		'everest_forms_show_admin_bar_menus'          => 'everest_forms_show_admin_bar',
		'everest_forms_builder_fields_groups'         => 'everest_forms_builder_fields_buttons',
		'everest_forms_field_data'                    => 'evf_field_data',
		'everest_forms_field_properties'              => 'evf_field_properties',
		'everest_forms_field_properties_{field_type}' => 'evf_field_properties_{field_type}',
		'everest_forms_field_submit'                  => 'evf_field_submit',
		'everest_forms_field_required_label'          => 'evf_field_required_label',
		'everest_forms_frontend_load'                 => 'evf_frontend_load',
		'everest_forms_frontend_form_action'          => 'evf_frontend_form_action',
		'everest_forms_process_smart_tags'            => 'evf_process_smart_tags',
		'everest_forms_recaptcha_disabled'            => 'everest_forms_logged_in_user_recaptcha_disabled',
		'everest_forms_welcome_cap'                   => 'evf_welcome_cap',
	);

	/**
	 * Array of versions on each hook has been deprecated.
	 *
	 * @var array
	 */
	protected $deprecated_version = array(
		'everest_forms_load_fields'                       => '1.2.0',
		'evf_display_media_button'                        => '1.2.0',
		'everest_forms_show_admin_bar'                    => '1.2.0',
		'everest_forms_builder_fields_buttons'            => '1.2.0',
		'evf_field_data'                                  => '1.3.0',
		'evf_field_properties'                            => '1.3.0',
		'evf_field_properties_{field_type}'               => '1.3.0',
		'evf_field_submit'                                => '1.3.2',
		'evf_field_required_label'                        => '1.3.2',
		'evf_frontend_load'                               => '1.3.2',
		'evf_frontend_form_action'                        => '1.3.2',
		'evf_process_smart_tags'                          => '1.4.2',
		'everest_forms_logged_in_user_recaptcha_disabled' => '1.7.0.1',
		'evf_welcome_cap'                                 => '1.7.5',
	);

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_filter( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
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
		if ( has_filter( $old_hook ) ) {
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
		return apply_filters_ref_array( $old_hook, $new_callback_args );
	}
}
