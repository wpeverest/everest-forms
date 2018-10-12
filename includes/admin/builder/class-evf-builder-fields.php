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
	public static $parts = false;

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
		if ( $this->form ) {
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
					<h2 class="everest-forms-form-name"><?php echo esc_html( $this->form->post_title ); ?></h2>
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
		$form_fields = EVF()->form_fields->form_fields();

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $group => $form_field ) {
				?>
				<div class="everest-forms-add-fields-group open">
					<a href="#" class="everest-forms-add-fields-heading" data-group="<?php echo esc_attr( $group ); ?>"><?php echo evf_get_fields_group( $group ); ?><i class="handlediv"></i></a>
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
				if ( in_array( $field['type'], EVF()->form_fields->get_pro_form_field_types(), true ) ) {
					continue;
				}

				?>
				<div class="everest-forms-field-option everest-forms-field-option-<?php echo esc_attr( $field['type'] ); ?>" id="everest-forms-field-option-<?php echo esc_attr( $field['id'] ); ?>" data-field-id="<?php echo esc_attr( $field['id'] ); ?>" >
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
		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );

		// Allow Multi-Part to be customized.
		self::$parts = apply_filters( 'everest_forms_parts_data', self::$parts, $form_data );

		// Output the fields preview.
		echo '<div class="evf-admin-field-container">';
			echo '<div class="evf-admin-field-wrapper">';

				/**
				 * Hook: everest_forms_display_builder_fields_before.
				 *
				 * @hooked EverestForms_MultiPart::display_builder_fields_before() Multi-Part markup open.
				 */
				do_action( 'everest_forms_display_builder_fields_before', $form_data );

				foreach ( $structure as $row_id => $row_data ) {
					$row         = str_replace( 'row_', '', $row_id );
					$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
					$form_grid   = apply_filters( 'everest_forms_default_form_grid', 2 );
					$total_grid  = $form_grid;
					$active_grid = count( $row_grid ) > 0 ? count( $row_grid ) : $form_grid;
					$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;

					/**
					 * Hook: everest_forms_display_row_before.
					 */
					do_action( 'everest_forms_display_builder_row_before', $row_id, $form_data );

					echo '<div class="evf-admin-row" data-row-id="' . absint( $row ) . '">';
						echo '<div class="evf-toggle-row">';
							echo '<div class="evf-delete-row"><span class="dashicons dashicons-trash" title="Delete"></span></div>';
							echo '<div class="evf-show-grid"><span class="dashicons dashicons-edit" title="Edit"></span></div>';
							echo '<div class="evf-toggle-row-content">';
								echo '<span>' . __( 'Row Settings', 'everest-forms' ) . '</span>';
								echo '<small>' . __( 'Select the type of row', 'everest-forms' ) . '</small>';
								echo '<div class="clear"></div>';

								for ( $grid_active = 1; $grid_active <= $total_grid; $grid_active ++ ) {
									$class = 'evf-grid-selector';
									if ( $grid_active === $active_grid ) {
										$class .= ' active';
									}
									echo '<div class="' . $class . '" data-evf-grid="' . $grid_active . '">';
									$gaps   = 15;
									$width  = ( 100 - $gaps ) / $grid_active;
									$margin = ( $gaps / $grid_active ) / 2;
									for ( $row_icon = 1; $row_icon <= $grid_active; $row_icon ++ ) {
										echo '<span style="width:' . $width . '%; margin-left:' . $margin . '%; margin-right:' . $margin . '%"></span>';
									}
									echo '</div>';
								}

							echo '</div>';
						echo '</div>';

						echo '<div class="clear evf-clear"></div>';

						$grid_class = 'evf-admin-grid evf-grid-' . ( $active_grid );
						for ( $grid_start = 1; $grid_start <= $active_grid; $grid_start ++ ) {
							echo '<div class="' . $grid_class . ' " data-grid-id="' . $grid_start . '">';

							$grid_fields = isset( $row_grid[ 'grid_' . $grid_start ] ) && is_array( $row_grid[ 'grid_' . $grid_start ] ) ? $row_grid[ 'grid_' . $grid_start ] : array();

							foreach ( $grid_fields as $field_id ) {
								if ( isset( $fields[ $field_id ] ) && ! in_array( $fields[ $field_id ]['type'], EVF()->form_fields->get_pro_form_field_types(), true ) ) {
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
					do_action( 'everest_forms_display_builder_row_after', $row_id, $form_data );
				}

				/**
				 * Hook: everest_forms_display_builder_fields_after.
				 *
				 * @hooked EverestForms_MultiPart::display_builder_fields_after() Multi-Part markup open.
				 */
				do_action( 'everest_forms_display_builder_fields_after', $form_data );

			echo '</div>';
			echo '<div class="clear evf-clear"></div>';
			echo '<div class="evf-add-row" data-total-rows="' . count( $structure ) . '"><span class="everest-forms-btn dashicons dashicons-plus-alt">' . esc_html( 'Add Row', 'everest-forms' ) . '</span></div>';
		echo '</div >';
	}

	/**
	 * Single Field preview.
	 *
	 * @param array $field Field data.
	 */
	public function field_preview( $field ) {
		$css  = ! empty( $field['size'] ) ? 'size-' . esc_attr( $field['size'] ) : '';
		$css .= ! empty( $field['label_hide'] ) && $field['label_hide'] == '1' ? ' label_hide' : '';
		$css .= ! empty( $field['sublabel_hide'] ) && $field['sublabel_hide'] == '1' ? ' sublabel_hide' : '';
		$css .= ! empty( $field['required'] ) && $field['required'] == '1' ? ' required' : '';
		$css  = apply_filters( 'everest_forms_field_preview_class', $css, $field );

		printf( '<div class="everest-forms-field everest-forms-field-%s %s" id="everest-forms-field-%s" data-field-id="%s" data-field-type="%s">', $field['type'], $css, $field['id'], $field['id'], $field['type'] );
		printf( '<div class="evf-field-action">' );
			printf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'everest-forms' ) );
			printf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'everest-forms' ) );
		printf( '</div>' );

		do_action( 'everest_forms_builder_fields_preview_' . $field['type'], $field );

		echo '</div>';
	}
}

return new EVF_Builder_Fields();
