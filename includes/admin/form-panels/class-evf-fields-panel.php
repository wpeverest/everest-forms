<?php
/**
 * EverestForms Admin
 *
 * @package EverestForms/Admin/FormPanel
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Fields_Panel class.
 */
class EVF_Fields_Panel extends EVF_Admin_Form_Panel {

	/**
	 * All systems go.
	 */
	public function init() {
		// Define panel information.
		$this->name    = __( 'Fields', 'everest-forms' );
		$this->slug    = 'fields';
		$this->icon    = 'dashicons dashicons-archive';
		$this->order   = 10;
		$this->sidebar = true;

		if ( $this->form ) {
			add_action( 'everest_forms_builder_fields', array( $this, 'fields' ) );
			add_action( 'everest_forms_builder_fields_options', array( $this, 'fields_options' ) );
			add_action( 'everest_forms_builder_preview', array( $this, 'preview' ) );
		}
		add_action( 'everest_forms_builder_fields', array( $this, 'fields' ) );
	}

	/**
	 * Enqueue assets for the Fields panel.
	 */
	public function enqueues() {}

	/**
	 * Outputs the Field panel sidebar.
	 */
	public function panel_sidebar() {
		?>
		<div class="everest-forms-add-fields everest-forms-tab-content">
			<?php do_action( 'everest_forms_builder_fields', $this->form ); ?>
		</div>
		<div class="everest-forms-field-options everest-forms-tab-content">
			<?php do_action( 'everest_forms_builder_fields_options', $this->form ); ?>
		</div>
		<?php
	}

	/**
	 * Outputs the Field panel primary content.
	 *
	 * @since      1.0.0
	 */
	public function panel_content() {
		?>
		<div class="everest-forms-preview-wrap">

			<div class="everest-forms-preview">

				<div class="everest-forms-title-desc">
					<h2 class="everest-forms-form-name"><?php echo esc_html( $this->form->post_title ); echo ' (ID #'.$this->form->ID.')'; ?></h2>
					
				</div>

				<div class="everest-forms-field-wrap">
					<?php do_action( 'everest_forms_builder_preview', $this->form ); ?>
				</div>

				<?php
				//$submit = ! empty( $this->form_data['settings']['submit_text'] ) ? esc_attr( $this->form_data['settings']['submit_text'] ) : __( 'Submit', 'everest-forms' );
				//printf( '<p class="everest-forms-field-submit"><input type="submit" value="%s" class="everest-forms-field-submit-button"></p>', $submit );
				?>

			</div>

		</div>
		<?php
	}

	/**
	 * Builder field butttons.
	 *
	 * @since      1.0.0
	 */
	public function fields() {

		$fields = array(
			'general' => array(
				'group_name' => __( 'General Fields', 'everest-forms' ),
				'fields'     => array(),
			),
			'advanced' => array(
				'group_name' => __( 'Advanced Fields', 'everest-forms' ),
				'fields'     => array(),
			),
		);
		$fields = apply_filters( 'everest_forms_builder_fields_buttons', $fields );

		// Output the buttons
		foreach ( $fields as $id => $group ) {

			usort( $group['fields'], array( $this, 'field_order' ) );

			echo '<div class="everest-forms-add-fields-group open">';

				echo '<a href="#" class="everest-forms-add-fields-heading" data-group="' . esc_attr( $id ) . '">';

					echo esc_html( $group['group_name'] );

					echo '<i class="handlediv hidden"></i>';

				echo '</a>';

				echo '<div class="evf-registered-buttons">';

					foreach ( $group['fields'] as $field ) {

						$class = ! empty( $field['class'] ) ? sanitize_html_class( $field['class'] ) : '';

						echo '<button type="button" class="evf-registered-item ' . $class . '" id="everest-forms-add-fields-' . esc_attr( $field['type'] ) . '" data-field-type="' . esc_attr( $field['type'] ) . '">';
						if ( $field['icon'] ) {
							echo '<i class="' . esc_attr( $field['icon'] ) . '"></i> ';
						}
						echo esc_html( $field['name'] );
						echo '</button>';
					}

				echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * Editor Field Options.
	 *
	 * @since      1.0.0
	 */
	public function fields_options() {

		// Check to make sure the form actually has fields created already
		if ( empty( $this->form_data['form_fields'] ) ) {
			printf( '<p class="no-fields">%s</p>', __( "You don't have any fields yet.", 'everest-forms' ) );

			return;
		}

		$fields = $this->form_data['form_fields'];

		foreach ( $fields as $field ) {

			$class = apply_filters( 'everest_forms_builder_field_option_class', '', $field );

			printf( '<div class="everest-forms-field-option everest-forms-field-option-%s %s" id="everest-forms-field-option-%s" data-field-id="%s">', esc_attr( $field['type'] ), $class, $field['id'], $field['id'] );

			printf( '<input type="hidden" name="form_fields[%s][id]" value="%s" class="everest-forms-field-option-hidden-id">', $field['id'], $field['id'] );

			printf( '<input type="hidden" name="form_fields[%s][type]" value="%s" class="everest-forms-field-option-hidden-type">', $field['id'], esc_attr( $field['type'] ) );

			do_action( "everest_forms_builder_fields_options_{$field['type']}", $field );

			echo '</div>';
		}
	}

	/**
	 *
	 *
	 * @since      1.0.0
	 */
	public function preview() {


		$this->everest_forms_builder_preview();

	}

	/**
	 * @param $field
	 */
	public function field_preview( $field ) {

		$field['required'] = isset( $field['required'] ) ? $field['required'] : 0;
		$css               = ! empty( $field['size'] ) ? 'size-' . esc_attr( $field['size'] ) : '';
		$css               .= ! empty( $field['label_hide'] ) && $field['label_hide'] == '1' ? ' label_hide' : '';
		$css               .= ! empty( $field['sublabel_hide'] ) && $field['sublabel_hide'] == '1' ? ' sublabel_hide' : '';
		$css               .= ! empty( $field['required'] ) && $field['required'] == '1' ? ' required' : '';
		$css               = apply_filters( 'everest_forms_field_preview_class', $css, $field );
		printf( '<div class="everest-forms-field everest-forms-field-%s %s %s" id="everest-forms-field-%s" data-field-id="%s" data-field-type="%s">', $field['type'], $field['required'], 'test', $field['id'], $field['id'], $field['type'] );
		printf( '<div class="evf-field-action">' );
		printf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'everest-forms' ) );
		printf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'everest-forms' ) );
		printf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'everest-forms' ) );
		printf( '</div>' );
		do_action( "everest_forms_builder_fields_previews_{$field['type']}", $field );

		echo '</div>';

	}

	/**
	 * @param $form
	 */
	public function everest_forms_builder_preview() {

		$form_data = $this->form_data;

		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		echo '<div class="evf-admin-field-container">';
		echo '<div class="evf-admin-field-wrapper">';
		$number_of_rows = isset( $form_data['structure'] ) ? count( $form_data['structure'] ) : 1;
		$grid_number    = 1;
		for ( $row = 1; $row <= $number_of_rows; $row ++ ) {
			echo '<div class="evf-admin-row" data-row-id="' . $row . '">';
			$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
			$active_grid = count( $row_grid ) > 0 ? count( $row_grid ) : EVF()->form_grid;
			$total_grid  = EVF()->form_grid;
			$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;
			echo '<div class="evf-toggle-row">';
			echo '<div class="evf-delete-row"><span class="dashicons dashicons-trash"></span></div>';
			echo '<span class="evf-show-grid">' . __( 'Edit', 'everest-forms' ) . '</span>';
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

					if ( isset( $fields[ $field_id ] ) ) {
						$this->field_preview( $fields[ $field_id ] );
					}

				}

				echo '</div>';
				$grid_number ++;
			}
			echo '<div class="clear evf-clear"></div>';
			echo '</div >';

		}
		echo '</div>';
		echo '<div class="clear evf-clear"></div>';
		echo '<div class="evf-add-row"><span class="dashicons dashicons-plus-alt"></span></div>';
		echo '</div >';
	}

	/**
	 * Sort Add Field buttons by order provided.
	 *
	 * @since      1.0.0
	 */
	function field_order( $a, $b ) {
		return $a['order'] - $b['order'];
	}
}

new EVF_Fields_Panel;
