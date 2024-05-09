<?php
/**
 * HTML block text field
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_HTML Class.
 */
class EVF_Field_HTML extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Custom HTML', 'everest-forms' );
		$this->type     = 'html';
		$this->icon     = 'evf-icon evf-icon-custom-html';
		$this->order    = 80;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'code',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Remove input attributes references.
		$properties['inputs']['primary']['attr'] = array();

		// Add code value.
		$properties['inputs']['primary']['code'] = ! empty( $field['code'] ) ? $field['code'] : '';

		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		?>
		<div class="code-block">
			<label class="label-title"> <i class="fa fa-code"></i> <?php esc_html_e( 'HTML / Code Block', 'everest-forms' ); ?></label>
			<div class="description"><?php esc_html_e( 'Contents of this field are not displayed in the admin area.', 'everest-forms' ); ?></div>
		</div>
		<?php
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Define data.
		$primary = $field['properties']['inputs']['primary'];
		if ( function_exists( 'apply_shortcodes' ) ) {
			$shortcode = apply_shortcodes( apply_filters( 'everest_forms_process_smart_tags', $primary['code'], $form_data, $field ) );
		} else {
			// @todo Remove when start supporting WP 5.4 or later.
			$shortcode = do_shortcode( apply_filters( 'everest_forms_process_smart_tags', $primary['code'], $form_data, $field ) );
		}

		// Primary field.
		printf(
			'<div %s>%s</div>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			$shortcode // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Formats field.
	 *
	 * @param int    $field_id     Field ID.
	 * @param array  $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 * @param string $meta_key     Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {}
}
