<?php
/**
 * Privacy Policy field
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Privacy_Policy Class.
 */
class EVF_Field_Privacy_Policy extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Privacy Policy', 'everest-forms' );
		$this->type     = 'privacy-policy';
		$this->icon     = 'evf-icon evf-icon-privacy-policy';
		$this->order    = 150;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'consent_message',
					'add_local_page',
					'add_custom_link',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'label_hide',
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
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_field_value' ), 10, 5 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field Field Data.
	 *
	 * @return array Formatted field value and label.
	 */
	public function field_exporter( $field ) {
		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $field['value'] ) ? evf_process_syntaxes( $field['value'] ) : '',
		);
	}

	/**
	 * Format field value according to the context.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $value               Field value.
	 * @param  array  $field_value         Field settings.
	 * @param  array  $submitted_form_data Form data.
	 * @param  string $context             Value display context.
	 * @param  string $field               The field whose value is being passed.
	 *
	 * @return string $value Html Value.
	 */
	public function html_field_value( $value, $field_value, $submitted_form_data = array(), $context = '', $field = null ) {
		if ( in_array( $context, array( 'export-csv', 'entry-single', 'entry-table' ), true ) ) {
			$meta_key = '';
			$entry_id = false;
			$fields   = false;

			if ( isset( $_GET['view-entry'] ) && 'entry-single' === $context ) { // phpcs:ignore WordPress.Security.NonceVerification
				$entry_id = absint( $_GET['view-entry'] ); // phpcs:ignore WordPress.Security.NonceVerification
				$meta_key = array_search( $field_value, $submitted_form_data, true );
			} elseif ( 'entry-table' === $context && is_object( $submitted_form_data ) ) {
				$entry_id = absint( $submitted_form_data->entry_id );
				$meta_key = array_search( $field_value, $submitted_form_data->meta, true );
			} elseif ( 'export-csv' === $context && is_object( $submitted_form_data ) ) {
				$meta_key = array_search( $field_value, $submitted_form_data->meta, true );
				$fields   = json_decode( $submitted_form_data->fields, true );
			}

			$entry = $entry_id ? evf_get_entry( $entry_id, true ) : false;

			if ( ! $fields ) {
				$fields = isset( $entry->fields ) ? evf_decode( $entry->fields ) : array();
			}

			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( $this->type === $field['type'] && $meta_key === $field['meta_key'] && ! empty( $field_value ) ) {
						if ( 'export-csv' === $context ) {
							return evf_process_hyperlink_syntax( $field_value, true );
						}
						return evf_process_syntaxes( $field_value );
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Consent message.
	 *
	 * @since 1.7.0
	 * @param array $field Field Data.
	 */
	public function consent_message( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'consent_message',
				'value'   => esc_html__( 'Consent Message', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter a message for user consentment.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'textarea',
			$field,
			array(
				'slug'  => 'consent_message',
				'class' => 'evf-privacy-policy-consent-message',
				'value' => isset( $field['consent_message'] ) ? esc_attr( $field['consent_message'] ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'consent_message',
				'content' => $label . $input_field,
			)
		);

		// Info on creating a hyperlink.
		printf(
			'<p class="description"><strong>%s: </strong>%s <code>[%s](%s)</code> %s.',
			esc_html__( 'Note', 'everest-forms' ),
			esc_html__( 'Please use syntax', 'everest-forms' ),
			esc_html__( 'Link Label', 'everest-forms' ),
			esc_html__( 'Link URL', 'everest-forms' ),
			esc_html__( 'to create a hyperlink', 'everest-forms' )
		);
	}

	/**
	 * Get a list of published pages belonging to the current user.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_local_page_list() {
		$args          = array(
			'authors'     => get_current_user_id(),
			'post_status' => 'publish',
		);
		$pages         = array();
		$fetched_pages = (array) get_pages( $args );

		foreach ( $fetched_pages as $page ) {
			if ( $page instanceof WP_POST ) {
				$pages[ $page->ID ] = $page;
			}
		}
		return $pages;
	}

	/**
	 * Add a link to the consent message.
	 *
	 * @since 1.7.0
	 * @param array $field Field Data.
	 */
	public function add_custom_link( $field ) {
		ob_start();
		$this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'add_custom_link',
				'value'   => esc_html__( 'Add a custom URL', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add a custom URL, containing policy details, in the consent message.', 'everest-forms' ),
			)
		);
		echo '<div class="evf-field-option-inputs-container">';
		$this->field_element(
			'text',
			$field,
			array(
				'slug'        => 'add_custom_link_label',
				'class'       => 'evf-privacy-policy-custom-link-label',
				'placeholder' => 'Link Label',
				'value'       => '',
			)
		);
		$this->field_element(
			'text',
			$field,
			array(
				'slug'        => 'add_custom_link_url',
				'class'       => 'evf-privacy-policy-custom-link-url',
				'placeholder' => 'Link URL',
				'value'       => '',
			)
		);
		printf(
			'<button class="button button-secondary evf-privacy-policy-add-custom-url" type="button">%s</button>',
			esc_html__( 'Add Custom URL', 'everest-forms' )
		);
		echo '</div>';
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'add_custom_link',
				'content' => ob_get_clean(),
			)
		);
	}

	/**
	 * Add a local page to the consent message.
	 *
	 * @since 1.7.0
	 * @param array $field Field Data.
	 */
	public function add_local_page( $field ) {
		$local_pages  = self::get_local_page_list();
		$page_options = array();

		// Prepare page options.
		foreach ( $local_pages as $page ) {
			if ( $page instanceof WP_POST ) {
				$page_options[ $page->ID ] = $page->post_title;
			}
		}

		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'add_local_page',
				'value'   => esc_html__( 'Add a local Privacy Policy page', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add a local page residing in your site, containing policy details, in the consent message.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'add_local_page',
				'class'   => 'evf-select-local-privacy-policy-page',
				'options' => $page_options,
				'value'   => isset( $field['add_local_page'] ) ? esc_attr( $field['add_local_page'] ) : '',
			),
			false
		);
		$add_button  = sprintf(
			'<button class="button button-secondary evf-add-local-privacy-policy-page" type="button">%s</button>',
			esc_html__( 'Add page', 'everest-forms' )
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'add_local_page',
				'content' => $label . $input_field . $add_button,
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary Input.
		echo '<input type="checkbox" disabled>';

		// Consent message.
		$consent_message = ! empty( $field['consent_message'] ) ? esc_attr( $field['consent_message'] ) : '';
		printf(
			'<label class="evf-privacy-policy-consent-message">%s</label>',
			evf_process_syntaxes( $consent_message ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.7.0
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Define data.
		$primary         = $field['properties']['inputs']['primary'];
		$consent_message = ! empty( $field['consent_message'] ) ? esc_attr( $field['consent_message'] ) : '';
		$form_id         = $form_data['id'];
		$field_id        = $field['id'];
		$page_ids        = evf_extract_page_ids( $consent_message );

		// Primary field.
		printf(
			'<input type="checkbox" value="%s" %s %s />',
			esc_attr( trim( $consent_message ) ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);

		// Consent message.
		printf(
			'<label class="evf-privacy-policy-consent-message" for="evf-%s-field_%s">%s</label>',
			esc_attr( $form_id ),
			esc_attr( $field_id ),
			evf_process_syntaxes( $consent_message ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		// Local page contents for privacy policy detail.
		echo '<div class="evf-privacy-policy-local-page-contents">';

		if ( false !== $page_ids ) {
			$pages = self::get_local_page_list();

			foreach ( $page_ids as $page_id ) {
				if ( isset( $pages[ $page_id ] ) && $pages[ $page_id ] instanceof WP_POST ) {
					printf(
						'<div class="evf-privacy-policy-local-page-content evf-privacy-policy-local-page-content-%s" hidden>%s</div>',
						esc_attr( $page_id ),
						$pages[ $page_id ]->post_content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				}
			}
		}
		echo '</div>';
	}
}
