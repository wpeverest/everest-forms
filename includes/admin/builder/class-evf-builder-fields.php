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
						<?php foreach ( $form_field as $field ) : ?>
							<button type="button" id="everest-forms-add-fields-<?php echo esc_attr( $field->type ); ?>" class="evf-registered-item <?php echo sanitize_html_class( $field->class ); ?>" data-field-type="<?php echo esc_attr( $field->type ); ?>">
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
		$fields = isset( $this->form_data['form_fields'] ) ? $this->form_data['form_fields'] : array();

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
	 * Outputs fields preview content.
	 */
	public function output_fields_preview() {
		$form_data = $this->form_data;
		$form_id   = absint( $form_data['id'] );
		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );
		$row_ids   = array_map(
			function( $row_id ) {
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

		foreach ( $structure as $row_id => $row_data ) {
			$row         = str_replace( 'row_', '', $row_id );
			$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
			$form_grid   = apply_filters( 'everest_forms_default_form_grid', 4 );
			$total_grid  = $form_grid;
			$active_grid = count( $row_grid ) > 0 ? count( $row_grid ) : 2;
			$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;

			/**
			 * Hook: everest_forms_display_row_before.
			 */
			do_action( 'everest_forms_display_builder_row_before', $row_id, $form_data, $form_id );

			$repeater_field = apply_filters( 'everest_forms_display_repeater_fields', false, $row_grid, $fields );

			echo '<div class="evf-admin-row" data-row-id="' . absint( $row ) . '"' . ( ! empty( $repeater_field ) ? esc_attr( $repeater_field ) : '' ) . '>';
			echo '<div class="evf-toggle-row">';
			if ( empty( $repeater_field ) ) {
				echo '<div class="evf-duplicate-row"><span class="dashicons dashicons-media-default" title="Duplicate Row"></span></div>';
				echo '<div class="evf-delete-row"><span class="dashicons dashicons-trash" title="Delete Row"></span></div>';
				echo '<div class="evf-show-grid"><span class="dashicons dashicons-edit" title="Edit"></span></div>';
			}
			echo '<div class="evf-toggle-row-content">';
			echo '<span>' . esc_html__( 'Row Settings', 'everest-forms' ) . '</span>';
			echo '<small>' . esc_html__( 'Select the type of row', 'everest-forms' ) . '</small>';
			echo '<div class="clear"></div>';

			for ( $grid_active = 1; $grid_active <= $total_grid; $grid_active ++ ) {
				$class = 'evf-grid-selector';

				if ( $grid_active === $active_grid ) {
					$class .= ' active';
				}

				echo '<div class="' . esc_attr( $class ) . '" data-evf-grid="' . absint( $grid_active ) . '">';

				$gaps   = 15;
				$width  = ( 100 - $gaps ) / $grid_active;
				$margin = ( $gaps / $grid_active ) / 2;

				for ( $row_icon = 1; $row_icon <= $grid_active; $row_icon ++ ) {
					echo '<span style="width:' . (float) $width . '%; margin-left:' . (float) $margin . '%; margin-right:' . (float) $margin . '%"></span>';
				}

				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
			echo '<div class="clear evf-clear"></div>';

			$grid_class = 'evf-admin-grid evf-grid-' . ( $active_grid );
			for ( $grid_start = 1; $grid_start <= $active_grid; $grid_start ++ ) {
				echo '<div class="' . esc_attr( $grid_class ) . ' " data-grid-id="' . absint( $grid_start ) . '">';
				$grid_fields = isset( $row_grid[ 'grid_' . $grid_start ] ) && is_array( $row_grid[ 'grid_' . $grid_start ] ) ? $row_grid[ 'grid_' . $grid_start ] : array();
				foreach ( $grid_fields as $field_id ) {
					if ( isset( $fields[ $field_id ] ) && ! in_array( $fields[ $field_id ]['type'], evf()->form_fields->get_pro_form_field_types(), true ) ) {
						$this->field_preview( $fields[ $field_id ] );
					}
				}
				echo '</div>';
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

		echo '<div class="evf-add-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Row', 'everest-forms' ) . '</span></div>';

		if ( defined( 'EVF_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="evf-add-row repeater-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Repeater Row', 'everest-forms' ) . '</span></div>';
			echo '</div>'; // Repeater Row Wrapper ends.
		}
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
			printf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', esc_html__( 'Settings', 'everest-forms' ) );
		} else {
			printf( '<a href="#" class="evf-duplicate-row" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Repeater', 'everest-forms' ) );
			printf( '<a href="#" class="evf-delete-row" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Repeater', 'everest-forms' ) );
		}
		printf( '</div>' );

		do_action( 'everest_forms_builder_fields_preview_' . $field['type'], $field );

		echo '</div>';
	}
}

return new EVF_Builder_Fields();
