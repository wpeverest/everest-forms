<?php
/**
 * EverestForms Builder Fields
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Fields', false ) ) {
	return new EVF_Builder_Fields();
}

/**
 * EVF_Builder_Fields class.
 */
class EVF_Builder_Fields extends EVF_Builder_Page {

	/**
	 * Contains information for multi-part forms.
	 *
	 * Forms that do not contain parts return false, otherwise returns an array
	 * that contains the number of total pages and page counter used when
	 * displaying part rows.
	 *
	 * @since 1.3.2
	 *
	 * @var array
	 */
	public static $parts = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'fields';
		$this->label   = __( 'Fields', 'everest-forms' );
		$this->sidebar = true;

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		if ( is_object( $this->form ) ) {
			add_action( 'everest_forms_builder_fields', array( $this, 'output_fields' ) );
			add_action( 'everest_forms_builder_fields_options', array( $this, 'output_fields_options' ) );
			add_action( 'everest_forms_builder_fields_preview', array( $this, 'output_fields_preview' ) );
			add_action( 'everest_forms_builder_submit_options', array( $this, 'output_submit_options' ) );
		}
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		?>
		<div class="everest-forms-fields-tab">
			<a href="#" id="add-fields" class="fields active"><?php esc_html_e( 'Add Fields', 'everest-forms' ); ?></a>
			<a href="#" id="field-options" class="options"><?php esc_html_e( 'Field Options', 'everest-forms' ); ?></a>
			<a href="#" id="submit-settings" class="options evf-submit-settings" style="display: none;"><?php esc_html_e( 'Submit Settings', 'everest-forms' ); ?></a>
			<?php do_action( 'everest_forms_builder_fields_tab', $this->form ); ?>
		</div>
		<div class="everest-forms-tab-content">
			<div class="everest-forms-add-fields">
				<div class="everest-forms-input-group everest-forms-search-input evf-mb-3">
					<input id="everest-forms-search-fields" class="everest-forms-input-control everest-forms-search-fields" type="text" placeholder="<?php esc_attr_e( 'Search fields&hellip;', 'everest-forms' ); ?>" />
					<div class="everest-forms-input-group__append">
						<div class="everest-forms-input-group__text">
							<svg xmlns="http://www.w3.org/2000/svg" height="20px" width="20px" viewBox="0 0 24 24" fill="#a1a4b9"><path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z"/></svg>
						</div>
					</div>
				</div>
				<div class="hidden everest-forms-fields-not-found">
					<img src="<?php echo esc_attr( plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/fields-not-found.png' ); ?>" />
					<h3 class="everest-forms-fields-not-found__title"><?php esc_html_e( 'Oops!', 'everest-forms' ); ?></h3>
					<span><?php esc_html_e( 'There is not such field that you are searching for.', 'everest-forms' ); ?></span>
				</div>
				<?php do_action( 'everest_forms_builder_fields', $this->form ); ?>
			</div>
			<div class="everest-forms-field-options">
				<?php do_action( 'everest_forms_builder_fields_options', $this->form ); ?>
			</div>
			<div class="everest-forms-submit-options">
				<?php do_action( 'everest_forms_builder_submit_options', $this->form ); ?>
			</div>
			<?php do_action( 'everest_forms_builder_fields_tab_content', $this->form ); ?>
		</div>
		<?php
	}

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		?>
		<div class="everest-forms-preview-wrap">
			<div class="everest-forms-preview">
				<div class="everest-forms-title-desc">
					<input id= "evf-edit-form-name" type="text" class="everest-forms-form-name everest-forms-name-input" value ="<?php echo isset( $this->form->post_title ) ? esc_html( $this->form->post_title ) : esc_html__( 'Form not found.', 'everest-forms' ); ?>" disabled autocomplete="off" required>
					<span id="edit-form-name" class = "evf-icon dashicons dashicons-edit"></span>
				</div>
				<div class="everest-forms-field-wrap">
					<?php do_action( 'everest_forms_builder_fields_preview', $this->form ); ?>
				</div>
				<?php evf_debug_data( $this->form_data ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output fields group buttons.
	 */
	public function output_fields() {
		$form_fields = evf()->form_fields->form_fields();

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $group => $form_field ) {
				?>
				<div class="everest-forms-add-fields-group open">
					<a href="#" class="everest-forms-add-fields-heading" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( evf_get_fields_group( $group ) ); ?><i class="handlediv"></i></a>
					<div class="evf-registered-buttons">
						<?php
						foreach ( $form_field as $field ) :
							$field_plan  = isset( $field->plan ) ? $field->plan : '';
							$addon_slug  = isset( $field->addon ) ? $field->addon : '';
							$field_links = isset( $field->links ) ? json_encode( $field->links ) : '';
							?>
							<button type="button" id="everest-forms-add-fields-<?php echo esc_attr( $field->type ); ?>" class="evf-registered-item <?php echo sanitize_html_class( $field->class ); ?>" data-field-type="<?php echo esc_attr( $field->type ); ?>" data-field-plan="<?php echo esc_attr( $field_plan ); ?>" data-addon-slug="<?php echo esc_attr( $addon_slug ); ?>" data-links="<?php echo esc_attr( $field_links ); ?>">
								<?php if ( isset( $field->icon ) ) : ?>
									<i class="<?php echo esc_attr( $field->icon ); ?>"></i>
								<?php endif; ?>
								<?php echo esc_html( $field->name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Output fields setting options.
	 */
	public function output_fields_options() {
		$fields         = isset( $this->form_data['form_fields'] ) ? $this->form_data['form_fields'] : array();
		$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
		if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
			if ( 'v2' === $recaptcha_type || 'v3' === $recaptcha_type ) {
				$recaptcha_type = 'recaptcha';
			}
			$fields['IWX5HFxv2j-18'] = array(
				'id'       => 'IWX5HFxv2j-18',
				'type'     => $recaptcha_type,
				'label'    => '',
				'meta-key' => $recaptcha_type . '_7543',
			);
		}

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], evf()->form_fields->get_pro_form_field_types(), true ) ) {
					continue;
				}

				$field_option_class = apply_filters(
					'everest_forms_builder_field_option_class',
					array(
						'everest-forms-field-option',
						'everest-forms-field-option-' . esc_attr( $field['type'] ),
					),
					$field
				);

				?>
				<div class="<?php echo esc_attr( implode( ' ', $field_option_class ) ); ?>" id="everest-forms-field-option-<?php echo esc_attr( $field['id'] ); ?>" data-field-id="<?php echo esc_attr( $field['id'] ); ?>" >
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" class="everest-forms-field-option-hidden-id" />
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][type]" value="<?php echo esc_attr( $field['type'] ); ?>" class="everest-forms-field-option-hidden-type" />
					<?php do_action( 'everest_forms_builder_fields_options_' . $field['type'], $field ); ?>
				</div>
				<?php
			}
		} else {
			printf( '<p class="no-fields">%s</p>', esc_html__( 'You don\'t have any fields yet.', 'everest-forms' ) );
		}
	}

	/**
	 * Output submit button options.
	 *
	 * @since xx.xx.xx
	 */
	public function output_submit_options(){
		$settings = isset($this->form_data['settings']) ? $this->form_data['settings'] : array();

		$row_option_class = apply_filters(
				'everest_forms_builder_row_save_button_class',
				array(
					'everest-forms-save-option',
				)
			);
				?>
				<div class="<?php echo esc_attr( implode( ' ', $row_option_class ) ); ?>" id="everest-forms-save-option" >
					<div class="everest-forms-save-option-group open">
						<a href="#" class="everest-forms-save-option-group-toggle">
							<?php echo __( 'Submit Button Settings', 		'everest-forms'); ?></span> <i class="handlediv"></i>
						</a>
						<div class="everest-forms-save-option-group-inner ">
							<div class="everest-forms-save-option-row everest-forms-save-option-row-label ">
									<?php
										everest_forms_panel_field(
											'text',
											'settings',
											'submit_button_text',
											$this->form_data,
											esc_html__('Submit button text', 'everest-forms'),
											array(
												'default' => isset($settings['submit_button_text']) ? $settings['submit_button_text'] : __('Submit', 'everest-forms'),
												'tooltip' => esc_html__('Enter desired text for submit button.', 'everest-forms'),
											)
										);
										everest_forms_panel_field(
											'text',
											'settings',
											'submit_button_processing_text',
											$this->form_data,
											__('Submit button processing text', 'everest-forms'),
											array(
												'default' => isset($settings['submit_button_processing_text']) ? $settings['submit_button_processing_text'] : __('Processing&hellip;', 'everest-forms'),
												'tooltip' => esc_html__('Enter the submit button text that you would like the button to display while the form submission is processing.', 'everest-forms'),
											)
										);
										everest_forms_panel_field(
											'text',
											'settings',
											'submit_button_class',
											$this->form_data,
											esc_html__('Submit button class', 'everest-forms'),
											array(
												'default' => isset($settings['submit_button_class']) ? $settings['submit_button_class'] : '',
												'tooltip' => esc_html__('Enter CSS class names for submit button. Multiple class names should be separated with spaces.', 'everest-forms'),
											)
										);
										do_action('everest_forms_inline_submit_settings', $this, 'submit', 'connection_1');

										$submit_button_width = isset( $settings['submit_button_width'] ) ? $settings['submit_button_width'] : 'auto';
										$submit_button_position = isset( $settings['submit_button_position'] ) ? $settings['submit_button_position'] : 'left';

									?>
									<div class="everest-forms-submit-button-outer-wrapper everest-forms-submit-button-width-outer-wrapper" >
										<div class="everest-forms-submit-button-title-wrapper">
											<span><?php echo __( 'Button Width', 'everest-forms' ); ?></span>
										</div>
										<div class="everest-forms-submit-button-inner-wrapper">
											<div class="everest-forms-submit-button-inner-items everest-forms-submit-button-width <?php echo ( 'auto' === $submit_button_width ? 'active' : ''); ?>" data-width="auto">
												<div class="everest-forms-submit-button-item-img">
													<svg width="92" height="98" viewBox="0 0 92 98" fill="none" xmlns="http://www.w3.org/2000/svg">
													<rect x="0.437193" y="0.437193" width="91.1256" height="96.242" rx="2.18596" stroke="#EEE8F7" stroke-width="0.874386"/>
													<rect x="8.74414" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="36.4062" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="46.6406" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="70.9434" width="30.7006" height="10.2335" rx="1.4941" fill="#999999"/>
													</svg>
												</div>
												<div class="everest-forms-submit-button-item-title">
													<span><?php echo __( 'Auto', 'everest-forms' ); ?></span>
												</div>
											</div>
											<div class="everest-forms-submit-button-inner-items everest-forms-submit-button-width <?php echo ( 'fill' === $submit_button_width ? 'active' : ''); ?>" data-width="fill">
												<div class="everest-forms-submit-button-item-img">
													<svg width="92" height="98" viewBox="0 0 92 98" fill="none" xmlns="http://www.w3.org/2000/svg">
													<rect x="0.437193" y="0.437193" width="91.1256" height="96.242" rx="2.18596" stroke="#EEE8F7" stroke-width="0.874386"/>
													<rect x="8.74414" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="36.4062" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="46.6406" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="70.9434" width="74.5123" height="10.2335" rx="1.4941" fill="#999999"/>
													</svg>

												</div>
												<div class="everest-forms-submit-button-item-title">
													<span><?php echo __( 'Fill Width', 'everest-forms' ); ?></span>
												</div>
												<input type="hidden" id="everest-forms-submit_button_width" name="settings[submit_button_width]" value="<?php echo ( isset( $settings['submit_button_width'] ) ? $settings['submit_button_width'] : 'auto'  ) ?>"/>

											</div>
										</div>
									</div>
									<div class="everest-forms-submit-button-outer-wrapper everest-forms-submit-button-position-outer-wrapper" style="<?php echo ( 'fill' === $submit_button_width ? 'display:none' : '' ); ?>">
										<div class="everest-forms-submit-button-title-wrapper">
											<span><?php echo __( 'Button Position', 'everest-forms' ); ?></span>
										</div>
										<div class="everest-forms-submit-button-inner-wrapper">
											<div class="everest-forms-submit-button-inner-items everest-forms-submit-button-position  <?php echo ( 'left' === $submit_button_position ? 'active' : ''); ?>" data-position="left">
												<div class="everest-forms-submit-button-item-img">
												<svg width="92" height="98" viewBox="0 0 92 98" fill="none" xmlns="http://www.w3.org/2000/svg">
												<rect x="0.437193" y="0.437193" width="91.1256" height="96.242" rx="2.18596" stroke="#EEE8F7" stroke-width="0.874386"/>
												<rect x="8.74414" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="48.9883" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="8.74414" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="48.9883" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="8.74414" y="36.4062" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="8.74414" y="46.6406" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="8.74414" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="48.9883" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
												<rect x="8.74414" y="70.9434" width="30.7006" height="10.2335" rx="1.4941" fill="#999999"/>
												</svg>
												</div>
												<div class="everest-forms-submit-button-item-title">
													<span><?php echo __( 'Left', 'everest-forms' ); ?></span>
												</div>
											</div>
											<div class="everest-forms-submit-button-inner-items everest-forms-submit-button-position  <?php echo ( 'center' === $submit_button_position ? 'active' : ''); ?>" data-position="center">
												<div class="everest-forms-submit-button-item-img">
													<svg width="92" height="98" viewBox="0 0 92 98" fill="none" xmlns="http://www.w3.org/2000/svg">
													<rect x="0.437193" y="0.437193" width="91.1256" height="96.242" rx="2.18596" stroke="#EEE8F7" stroke-width="0.874386"/>
													<rect x="8.74414" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="36.4062" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="46.6406" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="8.74414" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="48.9883" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
													<rect x="30.6494" y="70.9434" width="30.7006" height="10.2335" rx="1.4941" fill="#999999"/>
												</svg>
											</div>
											<div class="everest-forms-submit-button-item-title">
												<span><?php echo __( 'Center', 'everest-forms' ); ?></span>
												</div>

											</div>
											<div class="everest-forms-submit-button-inner-items everest-forms-submit-button-position  <?php echo ( 'right' === $submit_button_position ? 'active' : ''); ?>" data-position="right">
												<div class="everest-forms-submit-button-item-img">
													<svg width="92" height="98" viewBox="0 0 92 98" fill="none" xmlns="http://www.w3.org/2000/svg">
														<rect x="0.437193" y="0.437193" width="91.1256" height="96.242" rx="2.18596" stroke="#EEE8F7" stroke-width="0.874386"/>
														<rect x="8.74414" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="48.9883" y="15.9395" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="8.74414" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="48.9883" y="26.1738" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="8.74414" y="36.4062" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="8.74414" y="46.6406" width="74.5123" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="8.74414" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="48.9883" y="56.873" width="34.2679" height="3.83758" rx="1.91879" fill="#DBDBDB"/>
														<rect x="52.5557" y="70.9434" width="30.7006" height="10.2335" rx="1.4941" fill="#999999"/>
													</svg>
												</div>
												<div class="everest-forms-submit-button-item-title">
													<span><?php echo __( 'Right', 'everest-forms' ); ?></span>
												</div>
											</div>
											<input type="hidden" id="everest-forms-submit_button_position" name="settings[submit_button_position]" value="<?php echo ( isset( $settings['submit_button_position'] ) ? $settings['submit_button_position'] : 'left'  ) ?>"/>
										</div>
									</div>
								</div>
							</div>
					</div>
				</div>
		<?php
	}

	/**
	 * Outputs fields preview content.
	 */
	public function output_fields_preview() {
		$form_data 		 = $this->form_data;
		$form_id   		 = absint( $form_data['id'] );
		$fields    		 = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure 		 = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );
		$col_width_lists = isset( $form_data['settings']['col_width_lists'] ) ? $form_data['settings']['col_width_lists'] : array();
		$auto_width_lists = isset( $form_data['settings']['auto_width'] ) ? $form_data['settings']['auto_width'] : array();

		$row_ids   = array_map(
			function ( $row_id ) {
				return str_replace( 'row_', '', $row_id );
			},
			array_keys( $structure )
		);

		/**
		 * BW compatiable for multi-parts form.
		 *
		 * @todo Remove in Major EVF version 1.6.0
		 */
		if ( defined( 'EVF_MULTI_PART_PLUGIN_FILE' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin_data = get_plugin_data( EVF_MULTI_PART_PLUGIN_FILE, false, false );

			if ( version_compare( $plugin_data['Version'], '1.3.0', '<' ) ) {
				$settings_defaults = array(
					'indicator'       => 'progress',
					'indicator_color' => '#7e3bd0',
					'nav_align'       => 'center',
				);

				if ( isset( $form_data['settings']['enable_multi_part'] ) && evf_string_to_bool( $form_data['settings']['enable_multi_part'] ) ) {
					$settings = isset( $form_data['settings']['multi_part'] ) ? $form_data['settings']['multi_part'] : array();

					if ( ! empty( $form_data['multi_part'] ) ) {
						self::$parts = array(
							'total'    => count( $form_data['multi_part'] ),
							'current'  => 1,
							'parts'    => array_values( $form_data['multi_part'] ),
							'settings' => wp_parse_args( $settings, $settings_defaults ),
						);
					}
				} else {
					self::$parts = array(
						'total'    => '',
						'current'  => '',
						'parts'    => array(),
						'settings' => $settings_defaults,
					);
				}
			}
		}

		// Allow Multi-Part to be customized.
		self::$parts[ $form_id ] = apply_filters( 'everest_forms_parts_data', self::$parts, $form_data, $form_id );

		// Output the fields preview.
		echo '<div class="evf-admin-field-container">';
		echo '<div class="evf-admin-field-wrapper">';

		/**
		 * Hook: everest_forms_display_builder_fields_before.
		 *
		 * @hooked EverestForms_MultiPart::display_builder_fields_before() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_builder_fields_before', $form_data, $form_id );
		if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
			$num_rows = count( $structure );

			// Create a new row with the next available row number.
			$new_row_key = 'row_' . ( $num_rows + 1 );
			$new_row     = array(
				$new_row_key => array(
					'grid_1' => array(
						'IWX5HFxv2j-18',
					),
				),
			);
			$structure   = array_merge( $structure, $new_row );
		}

		foreach ( $structure as $row_id => $row_data ) {
			$row         = str_replace( 'row_', '', $row_id );
			$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
			$settings    = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();
			$form_grid   = apply_filters( 'everest_forms_default_form_grid', 4 );
			$total_grid  = $form_grid;
			$active_grid = ( count( $row_grid ) > 0 ) ? count( $row_grid ) : ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ? 1 : 2 );
			$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;

			/**
			 * Hook: everest_forms_display_row_before.
			 */
			do_action( 'everest_forms_display_builder_row_before', $row_id, $form_data, $form_id );

			$repeater_field = apply_filters( 'everest_forms_display_repeater_fields', false, $row_grid, $fields );

			echo '<div class="evf-admin-row" data-row-id="' . absint( $row ) . '"' . ( ! empty( $repeater_field ) ? esc_attr( $repeater_field ) : '' ) . '>';
			echo '<div class="evf-toggle-row">';
			if ( empty( $repeater_field ) ) {
				echo '<div class="evf-drag-row"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 17 16" fill="none">
  <path d="M13.9954 4.66471C13.6273 4.66471 13.3288 4.96319 13.3288 5.33138C13.3288 5.69957 13.6273 5.99805 13.9954 5.99805C14.3636 5.99805 14.6621 5.69957 14.6621 5.33138C14.6621 4.96319 14.3636 4.66471 13.9954 4.66471Z" stroke="white" stroke-width="1.33333"/>
  <path d="M14.0501 9.7487C13.6819 9.7487 13.3835 10.0472 13.3835 10.4154C13.3835 10.7836 13.6819 11.082 14.0501 11.082C14.4183 11.082 14.7168 10.7836 14.7168 10.4154C14.7168 10.0472 14.4183 9.7487 14.0501 9.7487Z" stroke="white" stroke-width="1.33333"/>
  <path d="M8.66243 4.66471C8.29425 4.66471 7.99577 4.96319 7.99577 5.33138C7.99577 5.69957 8.29425 5.99805 8.66243 5.99805C9.03062 5.99805 9.3291 5.69957 9.3291 5.33138C9.3291 4.96319 9.03062 4.66471 8.66243 4.66471Z" stroke="white" stroke-width="1.33333"/>
  <path d="M8.66243 9.9987C8.29425 9.9987 7.99577 10.2972 7.99577 10.6654C7.99577 11.0336 8.29425 11.332 8.66243 11.332C9.03062 11.332 9.3291 11.0336 9.3291 10.6654C9.3291 10.2972 9.03062 9.9987 8.66243 9.9987Z" stroke="white" stroke-width="1.33333"/>
  <path d="M3.32845 4.66471C2.96026 4.66471 2.66178 4.96319 2.66178 5.33138C2.66178 5.69957 2.96026 5.99805 3.32845 5.99805C3.69664 5.99805 3.99512 5.69957 3.99512 5.33138C3.99512 4.96319 3.69664 4.66471 3.32845 4.66471Z" stroke="white" stroke-width="1.33333"/>
  <path d="M3.32845 9.9987C2.96026 9.9987 2.66178 10.2972 2.66178 10.6654C2.66178 11.0336 2.96026 11.332 3.32845 11.332C3.69664 11.332 3.99512 11.0336 3.99512 10.6654C3.99512 10.2972 3.69664 9.9987 3.32845 9.9987Z" stroke="white" stroke-width="1.33333"/>
</svg></div>';
				echo '<div class="evf-duplicate-row"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
  <path d="M14.4853 7.19336H9.0633C8.39785 7.19336 7.8584 7.73281 7.8584 8.39826V13.8203C7.8584 14.4858 8.39785 15.0252 9.0633 15.0252H14.4853C15.1508 15.0252 15.6902 14.4858 15.6902 13.8203V8.39826C15.6902 7.73281 15.1508 7.19336 14.4853 7.19336Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M5.44798 10.8104H4.84553C4.52597 10.8104 4.21949 10.6834 3.99353 10.4575C3.76757 10.2315 3.64063 9.92503 3.64062 9.60547V4.18342C3.64062 3.86386 3.76757 3.55739 3.99353 3.33142C4.21949 3.10546 4.52597 2.97852 4.84553 2.97852H10.2676C10.5871 2.97852 10.8936 3.10546 11.1196 3.33142C11.3455 3.55739 11.4725 3.86386 11.4725 4.18342V4.78587" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
</svg></div>';

				echo '<div class="evf-show-grid"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 21 20" fill="none">
  <mask id="path-1-inside-1_5077_34264" fill="white">
    <path d="M2.7334 5.53828C2.7334 4.71676 3.39937 4.05078 4.2209 4.05078H10.6667V15.9508H4.2209C3.39937 15.9508 2.7334 15.2848 2.7334 14.4633V5.53828Z"/>
  </mask>
  <path d="M1.7334 5.53828C1.7334 4.16447 2.84709 3.05078 4.2209 3.05078H11.1667L10.1667 5.05078H4.2209C3.95166 5.05078 3.7334 5.26904 3.7334 5.53828L1.7334 5.53828ZM11.1667 16.9508H4.2209C2.84709 16.9508 1.7334 15.8371 1.7334 14.4633L3.7334 14.4633C3.7334 14.7325 3.95166 14.9508 4.2209 14.9508H10.1667L11.1667 16.9508ZM4.2209 16.9508C2.84709 16.9508 1.7334 15.8371 1.7334 14.4633V5.53828C1.7334 4.16447 2.84709 3.05078 4.2209 3.05078L4.2209 5.05078C3.95166 5.05078 3.7334 5.26904 3.7334 5.53828V14.4633C3.7334 14.7325 3.95166 14.9508 4.2209 14.9508L4.2209 16.9508ZM11.1667 3.05078V16.9508L10.1667 14.9508V5.05078L11.1667 3.05078Z" fill="white" mask="url(#path-1-inside-1_5077_34264)"/>
  <mask id="path-3-inside-2_5077_34264" fill="white">
    <path d="M10.668 4.05078H17.1138C17.9353 4.05078 18.6013 4.71676 18.6013 5.53828V14.4633C18.6013 15.2848 17.9353 15.9508 17.1138 15.9508H10.668V4.05078Z"/>
  </mask>
  <path d="M10.168 3.05078H17.1138C18.4876 3.05078 19.6013 4.16447 19.6013 5.53828L17.6013 5.53828C17.6013 5.26904 17.383 5.05078 17.1138 5.05078H11.168L10.168 3.05078ZM19.6013 14.4633C19.6013 15.8371 18.4876 16.9508 17.1138 16.9508H10.168L11.168 14.9508H17.1138C17.383 14.9508 17.6013 14.7325 17.6013 14.4633L19.6013 14.4633ZM10.168 16.9508V3.05078L11.168 5.05078V14.9508L10.168 16.9508ZM17.1138 3.05078C18.4876 3.05078 19.6013 4.16447 19.6013 5.53828V14.4633C19.6013 15.8371 18.4876 16.9508 17.1138 16.9508V14.9508C17.383 14.9508 17.6013 14.7325 17.6013 14.4633V5.53828C17.6013 5.26904 17.383 5.05078 17.1138 5.05078V3.05078Z" fill="white" mask="url(#path-3-inside-2_5077_34264)"/>
</svg></div>';
				if ( defined( 'EFP_VERSION' ) ) {
					echo '<div class="evf-row-setting"><svg class="dashicons-admin-settings" xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 17 16" fill="none">
  <path d="M8.81268 1.33398H8.51935C8.16573 1.33398 7.82659 1.47446 7.57654 1.72451C7.32649 1.97456 7.18602 2.3137 7.18602 2.66732V2.78732C7.18578 3.02113 7.12405 3.25078 7.00704 3.45321C6.89003 3.65564 6.72184 3.82374 6.51935 3.94065L6.23268 4.10732C6.02999 4.22434 5.80007 4.28595 5.56602 4.28595C5.33197 4.28595 5.10204 4.22434 4.89935 4.10732L4.79935 4.05398C4.49339 3.87749 4.12991 3.82961 3.78868 3.92086C3.44746 4.0121 3.15638 4.23501 2.97935 4.54065L2.83268 4.79398C2.65619 5.09994 2.60831 5.46343 2.69956 5.80465C2.7908 6.14587 3.01371 6.43695 3.31935 6.61398L3.41935 6.68065C3.62087 6.79699 3.78843 6.96404 3.90538 7.1652C4.02234 7.36637 4.08461 7.59463 4.08602 7.82732V8.16732C4.08695 8.40226 4.02578 8.63329 3.90871 8.83699C3.79164 9.04069 3.62282 9.20984 3.41935 9.32732L3.31935 9.38732C3.01371 9.56435 2.7908 9.85543 2.69956 10.1967C2.60831 10.5379 2.65619 10.9014 2.83268 11.2073L2.97935 11.4607C3.15638 11.7663 3.44746 11.9892 3.78868 12.0804C4.12991 12.1717 4.49339 12.1238 4.79935 11.9473L4.89935 11.894C5.10204 11.777 5.33197 11.7154 5.56602 11.7154C5.80007 11.7154 6.02999 11.777 6.23268 11.894L6.51935 12.0607C6.72184 12.1776 6.89003 12.3457 7.00704 12.5481C7.12405 12.7505 7.18578 12.9802 7.18602 13.214V13.334C7.18602 13.6876 7.32649 14.0267 7.57654 14.2768C7.82659 14.5268 8.16573 14.6673 8.51935 14.6673H8.81268C9.16631 14.6673 9.50544 14.5268 9.75549 14.2768C10.0055 14.0267 10.146 13.6876 10.146 13.334V13.214C10.1463 12.9802 10.208 12.7505 10.325 12.5481C10.442 12.3457 10.6102 12.1776 10.8127 12.0607L11.0993 11.894C11.302 11.777 11.532 11.7154 11.766 11.7154C12.0001 11.7154 12.23 11.777 12.4327 11.894L12.5327 11.9473C12.8386 12.1238 13.2021 12.1717 13.5433 12.0804C13.8846 11.9892 14.1756 11.7663 14.3527 11.4607L14.4993 11.2006C14.6758 10.8947 14.7237 10.5312 14.6325 10.19C14.5412 9.84876 14.3183 9.55768 14.0127 9.38065L13.9127 9.32732C13.7092 9.20984 13.5404 9.04069 13.4233 8.83699C13.3062 8.63329 13.2451 8.40226 13.246 8.16732V7.83398C13.2451 7.59904 13.3062 7.36802 13.4233 7.16431C13.5404 6.96061 13.7092 6.79146 13.9127 6.67398L14.0127 6.61398C14.3183 6.43695 14.5412 6.14587 14.6325 5.80465C14.7237 5.46343 14.6758 5.09994 14.4993 4.79398L14.3527 4.54065C14.1756 4.23501 13.8846 4.0121 13.5433 3.92086C13.2021 3.82961 12.8386 3.87749 12.5327 4.05398L12.4327 4.10732C12.23 4.22434 12.0001 4.28595 11.766 4.28595C11.532 4.28595 11.302 4.22434 11.0993 4.10732L10.8127 3.94065C10.6102 3.82374 10.442 3.65564 10.325 3.45321C10.208 3.25078 10.1463 3.02113 10.146 2.78732V2.66732C10.146 2.3137 10.0055 1.97456 9.75549 1.72451C9.50544 1.47446 9.16631 1.33398 8.81268 1.33398Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M8.66602 10C9.77059 10 10.666 9.10457 10.666 8C10.666 6.89543 9.77059 6 8.66602 6C7.56145 6 6.66602 6.89543 6.66602 8C6.66602 9.10457 7.56145 10 8.66602 10Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
</svg></div>';
				}

			echo '<div class="evf-delete-row"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 21 20" fill="none">
  <path d="M15.6641 5.00195L5.66406 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M5.66406 5.00195L15.6641 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
</svg></div>';
			}
			echo '<div class="evf-toggle-row-content">';
			echo '<div class="clear"></div>';

			for ( $grid_active = 1; $grid_active <= $total_grid; $grid_active++ ) {
				$class = 'evf-grid-selector';

				if ( $grid_active === $active_grid ) {
					$class .= ' active';
				}

				echo '<div class="' . esc_attr( $class ) . '" data-evf-grid="' . absint( $grid_active ) . '">';

				$gaps   = 15;
				$width  = ( 100 - $gaps ) / $grid_active;
				$margin = ( $gaps / $grid_active ) / 2;

				for ( $row_icon = 1; $row_icon <= $grid_active; $row_icon++ ) {
					echo '<span style="width:' . (float) $width . '%; margin-left:' . (float) $margin . '%; margin-right:' . (float) $margin . '%"></span>';
				}

				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
			echo '<div class="clear evf-clear"></div>';

			$grid_class = 'evf-admin-grid evf-grid-' . ( $active_grid );
			for ( $grid_start = 1; $grid_start <= $active_grid; $grid_start++ ) {
				$has_width 	  = isset( $col_width_lists[ $row_id ][ 'grid_' . $grid_start ] );
				$inline_style = 'style="width:' . ( $has_width ? esc_attr( $col_width_lists[ $row_id ][ 'grid_' .$grid_start ] ) : ( 100 / $active_grid ) ) . '%; flex-basis:auto;"';

				echo '<div class="' . esc_attr( $grid_class ) . ' " data-grid-id="' . absint( $grid_start ) . '"' . $inline_style . '>';
				$grid_fields    = isset( $row_grid[ 'grid_' . $grid_start ] ) && is_array( $row_grid[ 'grid_' . $grid_start ] ) ? $row_grid[ 'grid_' . $grid_start ] : ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ? array(
					'IWX5HFxv2j-18',
				) : array() );
				$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
				if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
					if ( 'v2' === $recaptcha_type || 'v3' === $recaptcha_type ) {
						$recaptcha_type = 'recaptcha';
					}
					$fields['IWX5HFxv2j-18'] = array(
						'id'       => 'IWX5HFxv2j-18',
						'type'     => $recaptcha_type,
						'label'    => '',
						'meta-key' => $recaptcha_type . '_7543',
					);
				}
				foreach ( $grid_fields as $field_id ) {
					if ( isset( $fields[ $field_id ] ) && ! in_array( $fields[ $field_id ]['type'], evf()->form_fields->get_pro_form_field_types(), true ) ) {
						$this->field_preview( $fields[ $field_id ] );
					}
				}
				echo '</div>';

				if ( $grid_start != $active_grid ) {
					echo '<div class="evf-col-divider-wrapper" ' . ( isset( $auto_width_lists[ $row_id ] ) ? "style='display:none;'" : '' ) . '>';
					echo '<div class="evf-col-divider" data-row-id="' .  esc_attr( $row_id ) . '">';
					echo '<svg width="6" height="32" viewBox="0 0 6 32" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5 0H4.5V32H5H5.5V0H5ZM1 32H1.5V0H1H0.5V32H1Z" fill="#999999"/>
						</svg>';
					echo '</div>';
					echo '</div>';
				}

			}
			echo '<div class="clear evf-clear"></div>';
			echo '</div >';

			/**
			 * Hook: everest_forms_display_builder_row_after.
			 *
			 * @hooked EverestForms_MultiPart::display_builder_row_after() Multi-Part markup (close previous part, open next).
			 */
			do_action( 'everest_forms_display_builder_row_after', $row_id, $form_data, $form_id );
		}

		/**
		 * Hook: everest_forms_display_builder_fields_after.
		 *
		 * @hooked EverestForms_MultiPart::display_builder_fields_after() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_builder_fields_after', $form_data, $form_id );

		echo '</div>';
		echo '<div class="clear evf-clear"></div>';
		if ( defined( 'EVF_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="evf-repeater-row-wrapper">'; // Repeater Row Wrapper starts.
		}

		echo '<div class="evf-add-row-new" data-is_add_row="yes" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><div class="evf-add-row-content" data-is_add_row="yes" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect y="0.5" width="38" height="38" rx="19" fill="#7E3BD0"/>
<path d="M19.0002 12.8242V26.2492V12.8242ZM12.3252 19.4992H25.6752H12.3252Z" fill="#0E0E0E"/>
<path d="M19.0002 12.8242V26.2492M12.3252 19.4992H25.6752" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg><div><span>Drag Form Field here</span></div></div>
<div id="evf-select-row-type-outer-wrapper" class="everest-forms-hidden">
<div class="evf-add-row-cancel-btn">
	<span>
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M12.0008 13.4008L7.10078 18.3008C6.91745 18.4841 6.68411 18.5758 6.40078 18.5758C6.11745 18.5758 5.88411 18.4841 5.70078 18.3008C5.51745 18.1174 5.42578 17.8841 5.42578 17.6008C5.42578 17.3174 5.51745 17.0841 5.70078 16.9008L10.6008 12.0008L5.70078 7.10078C5.51745 6.91745 5.42578 6.68411 5.42578 6.40078C5.42578 6.11745 5.51745 5.88411 5.70078 5.70078C5.88411 5.51745 6.11745 5.42578 6.40078 5.42578C6.68411 5.42578 6.91745 5.51745 7.10078 5.70078L12.0008 10.6008L16.9008 5.70078C17.0841 5.51745 17.3174 5.42578 17.6008 5.42578C17.8841 5.42578 18.1174 5.51745 18.3008 5.70078C18.4841 5.88411 18.5758 6.11745 18.5758 6.40078C18.5758 6.68411 18.4841 6.91745 18.3008 7.10078L13.4008 12.0008L18.3008 16.9008C18.4841 17.0841 18.5758 17.3174 18.5758 17.6008C18.5758 17.8841 18.4841 18.1174 18.3008 18.3008C18.1174 18.4841 17.8841 18.5758 17.6008 18.5758C17.3174 18.5758 17.0841 18.4841 16.9008 18.3008L12.0008 13.4008Z" fill="#383838"/>
		</svg>
	</span>
</div>
<div class="evf-select-row-type-wrapper" >
<div class="evf-select-row-type-inner"><p class="evf-select-row-type-inner-title">Row Settings</p>
<p class="evf-select-row-type-inner-desc">Select The type of row</p></div>
<div class="evf-select-row-type-inner-content-wrapper" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '" data-is_select_row_type="yes">
	<div class="evf-select-row-type evf-grid-1" data-col_num="1"></div>
	<div class="evf-select-row-type evf-grid-2" data-col_num="2">
		<div class="col"></div>
		<div class="col"></div>
	</div>
	<div class="evf-select-row-type evf-grid-3" data-col_num="3">
		<div class="col"></div>
		<div class="col"></div>
		<div class="col"></div>
	</div>
	<div class="evf-select-row-type evf-grid-4" data-col_num="4">
		<div class="col"></div>
		<div class="col"></div>
		<div class="col"></div>
		<div class="col"></div>
	</div>
</div>
</div>
</div>
</div>';

		if ( defined( 'EVF_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="evf-add-row repeater-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span><svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.33398 8.5H12.6673" stroke="#7545BB" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M8 3.83203V13.1654" stroke="#7545BB" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
</svg>' . esc_html__( 'Add Repeater Row', 'everest-forms' ) . '</span></div>';
			echo '</div>'; // Repeater Row Wrapper ends.
		}

		$settings['submit_button_width'] = isset($settings['submit_button_width']) ? $settings['submit_button_width'] : 'auto';
		$submit_button_position = isset($settings['submit_button_position']) ? $settings['submit_button_position'] : 'left';

		$button_class = ($settings['submit_button_width'] === 'auto') ? 'button' : '';
		$button_text  = isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'everest-forms' );

		echo '<div class="evf-submit-btn-outer-wrapper ' . $submit_button_position . '">';
		echo '<div class="evf-submit-btn ' . $button_class . '" data-total-rows="' . count($structure) . '" data-next-row-id="' . (int) max($row_ids) . '">';
		echo '<span>' . $button_text . '</span>';
		echo '</div></div>';

		echo '</div >';
	}

	/**
	 * Single Field preview.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$css  = ! empty( $field['size'] ) ? 'size-' . esc_attr( $field['size'] ) : '';
		$css .= ! empty( $field['label_hide'] ) && '1' === $field['label_hide'] ? ' label_hide' : '';
		$css .= ! empty( $field['sublabel_hide'] ) && '1' === $field['sublabel_hide'] ? ' sublabel_hide' : '';
		$css .= ! empty( $field['required'] ) && '1' === $field['required'] ? ' required' : '';
		$css .= ! empty( $field['input_columns'] ) && '2' === $field['input_columns'] ? ' everest-forms-list-2-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && '3' === $field['input_columns'] ? ' everest-forms-list-3-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && 'inline' === $field['input_columns'] ? ' everest-forms-list-inline' : '';
		$css  = apply_filters( 'everest_forms_field_preview_class', $css, $field );
		printf( '<div class="everest-forms-field everest-forms-field-%1$s %2$s" id="everest-forms-field-%3$s" data-field-id="%3$s" data-field-type="%4$s">', esc_attr( $field['type'] ), esc_attr( $css ), esc_attr( $field['id'] ), esc_attr( $field['type'] ) );
		printf( '<div class="evf-field-action">' );
		if ( 'repeater-fields' !== $field['type'] ) {
			printf( '<a href="#" class="everest-forms-field-drag" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 17 16" fill="none">
				<path d="M13.9954 4.66471C13.6273 4.66471 13.3288 4.96319 13.3288 5.33138C13.3288 5.69957 13.6273 5.99805 13.9954 5.99805C14.3636 5.99805 14.6621 5.69957 14.6621 5.33138C14.6621 4.96319 14.3636 4.66471 13.9954 4.66471Z" stroke="white" stroke-width="1.33333"/>
				<path d="M14.0501 9.7487C13.6819 9.7487 13.3835 10.0472 13.3835 10.4154C13.3835 10.7836 13.6819 11.082 14.0501 11.082C14.4183 11.082 14.7168 10.7836 14.7168 10.4154C14.7168 10.0472 14.4183 9.7487 14.0501 9.7487Z" stroke="white" stroke-width="1.33333"/>
				<path d="M8.66243 4.66471C8.29425 4.66471 7.99577 4.96319 7.99577 5.33138C7.99577 5.69957 8.29425 5.99805 8.66243 5.99805C9.03062 5.99805 9.3291 5.69957 9.3291 5.33138C9.3291 4.96319 9.03062 4.66471 8.66243 4.66471Z" stroke="white" stroke-width="1.33333"/>
				<path d="M8.66243 9.9987C8.29425 9.9987 7.99577 10.2972 7.99577 10.6654C7.99577 11.0336 8.29425 11.332 8.66243 11.332C9.03062 11.332 9.3291 11.0336 9.3291 10.6654C9.3291 10.2972 9.03062 9.9987 8.66243 9.9987Z" stroke="white" stroke-width="1.33333"/>
				<path d="M3.32845 4.66471C2.96026 4.66471 2.66178 4.96319 2.66178 5.33138C2.66178 5.69957 2.96026 5.99805 3.32845 5.99805C3.69664 5.99805 3.99512 5.69957 3.99512 5.33138C3.99512 4.96319 3.69664 4.66471 3.32845 4.66471Z" stroke="white" stroke-width="1.33333"/>
				<path d="M3.32845 9.9987C2.96026 9.9987 2.66178 10.2972 2.66178 10.6654C2.66178 11.0336 2.96026 11.332 3.32845 11.332C3.69664 11.332 3.99512 11.0336 3.99512 10.6654C3.99512 10.2972 3.69664 9.9987 3.32845 9.9987Z" stroke="white" stroke-width="1.33333"/>
				</svg></a>', esc_html__( 'Drag Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
				<path d="M14.4853 7.19336H9.0633C8.39785 7.19336 7.8584 7.73281 7.8584 8.39826V13.8203C7.8584 14.4858 8.39785 15.0252 9.0633 15.0252H14.4853C15.1508 15.0252 15.6902 14.4858 15.6902 13.8203V8.39826C15.6902 7.73281 15.1508 7.19336 14.4853 7.19336Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M5.44798 10.8104H4.84553C4.52597 10.8104 4.21949 10.6834 3.99353 10.4575C3.76757 10.2315 3.64063 9.92503 3.64062 9.60547V4.18342C3.64062 3.86386 3.76757 3.55739 3.99353 3.33142C4.21949 3.10546 4.52597 2.97852 4.84553 2.97852H10.2676C10.5871 2.97852 10.8936 3.10546 11.1196 3.33142C11.3455 3.55739 11.4725 3.86386 11.4725 4.18342V4.78587" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				</svg></a>', esc_html__( 'Duplicate Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-setting" title="%s"><svg class="dashicons-admin-generic" xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 17 16" fill="none">
				<path d="M8.81268 1.33398H8.51935C8.16573 1.33398 7.82659 1.47446 7.57654 1.72451C7.32649 1.97456 7.18602 2.3137 7.18602 2.66732V2.78732C7.18578 3.02113 7.12405 3.25078 7.00704 3.45321C6.89003 3.65564 6.72184 3.82374 6.51935 3.94065L6.23268 4.10732C6.02999 4.22434 5.80007 4.28595 5.56602 4.28595C5.33197 4.28595 5.10204 4.22434 4.89935 4.10732L4.79935 4.05398C4.49339 3.87749 4.12991 3.82961 3.78868 3.92086C3.44746 4.0121 3.15638 4.23501 2.97935 4.54065L2.83268 4.79398C2.65619 5.09994 2.60831 5.46343 2.69956 5.80465C2.7908 6.14587 3.01371 6.43695 3.31935 6.61398L3.41935 6.68065C3.62087 6.79699 3.78843 6.96404 3.90538 7.1652C4.02234 7.36637 4.08461 7.59463 4.08602 7.82732V8.16732C4.08695 8.40226 4.02578 8.63329 3.90871 8.83699C3.79164 9.04069 3.62282 9.20984 3.41935 9.32732L3.31935 9.38732C3.01371 9.56435 2.7908 9.85543 2.69956 10.1967C2.60831 10.5379 2.65619 10.9014 2.83268 11.2073L2.97935 11.4607C3.15638 11.7663 3.44746 11.9892 3.78868 12.0804C4.12991 12.1717 4.49339 12.1238 4.79935 11.9473L4.89935 11.894C5.10204 11.777 5.33197 11.7154 5.56602 11.7154C5.80007 11.7154 6.02999 11.777 6.23268 11.894L6.51935 12.0607C6.72184 12.1776 6.89003 12.3457 7.00704 12.5481C7.12405 12.7505 7.18578 12.9802 7.18602 13.214V13.334C7.18602 13.6876 7.32649 14.0267 7.57654 14.2768C7.82659 14.5268 8.16573 14.6673 8.51935 14.6673H8.81268C9.16631 14.6673 9.50544 14.5268 9.75549 14.2768C10.0055 14.0267 10.146 13.6876 10.146 13.334V13.214C10.1463 12.9802 10.208 12.7505 10.325 12.5481C10.442 12.3457 10.6102 12.1776 10.8127 12.0607L11.0993 11.894C11.302 11.777 11.532 11.7154 11.766 11.7154C12.0001 11.7154 12.23 11.777 12.4327 11.894L12.5327 11.9473C12.8386 12.1238 13.2021 12.1717 13.5433 12.0804C13.8846 11.9892 14.1756 11.7663 14.3527 11.4607L14.4993 11.2006C14.6758 10.8947 14.7237 10.5312 14.6325 10.19C14.5412 9.84876 14.3183 9.55768 14.0127 9.38065L13.9127 9.32732C13.7092 9.20984 13.5404 9.04069 13.4233 8.83699C13.3062 8.63329 13.2451 8.40226 13.246 8.16732V7.83398C13.2451 7.59904 13.3062 7.36802 13.4233 7.16431C13.5404 6.96061 13.7092 6.79146 13.9127 6.67398L14.0127 6.61398C14.3183 6.43695 14.5412 6.14587 14.6325 5.80465C14.7237 5.46343 14.6758 5.09994 14.4993 4.79398L14.3527 4.54065C14.1756 4.23501 13.8846 4.0121 13.5433 3.92086C13.2021 3.82961 12.8386 3.87749 12.5327 4.05398L12.4327 4.10732C12.23 4.22434 12.0001 4.28595 11.766 4.28595C11.532 4.28595 11.302 4.22434 11.0993 4.10732L10.8127 3.94065C10.6102 3.82374 10.442 3.65564 10.325 3.45321C10.208 3.25078 10.1463 3.02113 10.146 2.78732V2.66732C10.146 2.3137 10.0055 1.97456 9.75549 1.72451C9.50544 1.47446 9.16631 1.33398 8.81268 1.33398Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M8.66602 10C9.77059 10 10.666 9.10457 10.666 8C10.666 6.89543 9.77059 6 8.66602 6C7.56145 6 6.66602 6.89543 6.66602 8C6.66602 9.10457 7.56145 10 8.66602 10Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				</svg></a>', esc_html__( 'Settings', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-delete" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 21 20" fill="none">
				<path d="M15.6641 5.00195L5.66406 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M5.66406 5.00195L15.6641 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				</svg></a>', esc_html__( 'Delete Field', 'everest-forms' ) );
		} else {
			printf( '<a href="#" class="evf-duplicate-row" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
				<path d="M14.4853 7.19336H9.0633C8.39785 7.19336 7.8584 7.73281 7.8584 8.39826V13.8203C7.8584 14.4858 8.39785 15.0252 9.0633 15.0252H14.4853C15.1508 15.0252 15.6902 14.4858 15.6902 13.8203V8.39826C15.6902 7.73281 15.1508 7.19336 14.4853 7.19336Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M5.44798 10.8104H4.84553C4.52597 10.8104 4.21949 10.6834 3.99353 10.4575C3.76757 10.2315 3.64063 9.92503 3.64062 9.60547V4.18342C3.64062 3.86386 3.76757 3.55739 3.99353 3.33142C4.21949 3.10546 4.52597 2.97852 4.84553 2.97852H10.2676C10.5871 2.97852 10.8936 3.10546 11.1196 3.33142C11.3455 3.55739 11.4725 3.86386 11.4725 4.18342V4.78587" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				</svg></a>', esc_html__( 'Duplicate Repeater', 'everest-forms' ) );
			printf( '<a href="#" class="evf-delete-row" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 21 20" fill="none">
				<path d="M15.6641 5.00195L5.66406 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M5.66406 5.00195L15.6641 15.002" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
				</svg></a>', esc_html__( 'Delete Repeater', 'everest-forms' ) );
		}
		printf( '</div>' );

		do_action( 'everest_forms_builder_fields_preview_' . $field['type'], $field );

		echo '</div>';
	}
}

return new EVF_Builder_Fields();
