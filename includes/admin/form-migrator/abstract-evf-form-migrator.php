<?php
/**
 * EverestForms Form Migrator Class
 *
 * @package EverestForms\Admin
 * @since   2.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Form_Migrator class.
 */
abstract class EVF_Admin_Form_Migrator {
	/**
	 * Importer name.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Importer name in slug format.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Importer plugin path.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.6
	 */
	public function __construct() {

		add_action( 'admin_notices', array( $this, 'show_fm_notice' ) );
		$this->init();
	}
	/**
	 * Undocumented function
	 *
	 * @since 2.0.6
	 */
	abstract public function init();

	/**
	 * Add to list of registered importers.
	 *
	 * @since 2.0.6
	 *
	 * @param array $importers List of supported importers.
	 *
	 * @return array
	 */
	public function register( $importers = array() ) {

		$importers = array(
			'name'      => $this->name,
			'slug'      => $this->slug,
			'path'      => $this->path,
			'installed' => $this->is_installed(),
			'active'    => $this->is_active(),
		);

		return $importers;
	}

	/**
	 * Get all the forms
	 *
	 * @return array
	 */
	abstract protected function get_forms();

	/**
	 * Get the form id
	 *
	 * @param int $id Form ID.
	 *
	 * @return array|object|bool
	 */
	abstract protected function get_form( $id );

	/**
	 * If the importer source is available.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	abstract protected function is_active();

	/**
	 * Check is the plugin installed or not.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	abstract protected function is_installed();

	/**
	 * Modify the field id for smart tags.
	 *
	 * @since 2.0.6
	 * @param [array] $field The field array.
	 */
	protected function get_field_id_for_smarttags( $field ) {
		$field_id    = $field['id'];
		$field_label = $field['label'];
		if ( $field_id !== 'fullname' && $field_id !== 'email' && $field_id !== 'subject' && $field_id !== 'message' ) {
			$field_label = preg_split( '/[\s\-\_]/', $field_label );
			foreach ( $field_label as $key => $value ) {
				if ( $key === 0 ) {
					$field_label[ $key ] = strtolower( $value );
				} else {
					$field_label[ $key ] = ucfirst( $value );
				}
			}
			$field_label = implode( '', $field_label );
			$field_id    = $field_label . '_' . $field_id;
		} else {
			$field_id = $field_id;
		}

		return $field_id;
	}
	/**
	 * Tracks the successful import of a form, allowing future alerts for attempts to
	 * import a form that has already been imported.
	 *
	 * @since 2.0.6
	 *
	 * @param int $source_id      Imported plugin form ID.
	 * @param int $evf_forms_id   Form ID.
	 */
	protected function track_import( $source_id, $evf_forms_id ) {

		$imported = get_option( 'evf_fm_' . $this->slug . '_imported_form_list', array() );

		$imported[ $evf_forms_id ] = $source_id;

		update_option( 'evf_fm_' . $this->slug . '_imported_form_list', $imported, false );
	}

	/**
	 * Import the new form to the database and return AJAX data.
	 *
	 * @since 2.0.6
	 *
	 * @param array $form          Form to import.
	 * @param array $unsupported   List of unsupported fields.
	 * @param array $upgrade_plan List of fields, that are supported inside the EVF Forms Pro, but not in Free.
	 * @param array $upgrade_omit  No field alternative in EVF.
	 */
	protected function import_form( $form, $unsupported = array(), $upgrade_plan = array(), $upgrade_omit = array() ) {
		$imported_form_list = get_option( 'evf_fm_' . $this->slug . '_imported_form_list', array() );
		if ( empty( $form ) ) {
			return false;
		}

		$form_id = array_search( $form['settings']['imported_from']['form_id'], $imported_form_list );
		if ( false === $form_id ) {
			// $form_id = wp_insert_post(
			// array(
			// 'post_status' => 'publish',
			// 'post_type'   => 'everest_form',
			// )
			// );
			$form_id = evf()->form->create( $form['settings']['form_title'] );

			if ( empty( $form_id ) || is_wp_error( $form_id ) ) {
				wp_send_json_success(
					array(
						'error' => true,
						'name'  => sanitize_text_field( $form['settings']['form_title'] ),
						'msg'   => esc_html__( 'There was an error while creating a new form.', 'everest-forms' ),
					)
				);
			}
		}
		$form['id']       = $form_id;
		$form['field_id'] = count( $form['form_fields'] ) + 1;

		// Update the form with all our compiled data.
		$form_id     = evf()->form->update( $form['id'], $form );
		$form_styles = get_option( 'everest_forms_styles', array() );
		$logger      = evf_get_logger();
		$logger->info(
			__( 'Saving form.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		do_action( 'everest_forms_save_form', $form_id, $form, array(), ! empty( $form_styles[ $form_id ] ) );

		if ( ! $form_id ) {
			$logger->error(
				__( 'An error occurred while saving the form.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
			wp_send_json_error(
				array(
					'errorTitle'   => esc_html__( 'Form not found', 'everest-forms' ),
					'errorMessage' => esc_html__( 'An error occurred while saving the form.', 'everest-forms' ),
				)
			);
		} else {
			$logger->info(
				__( 'Form Imported successfully.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
		}

		// Make note that this form has been imported.
		$this->track_import( $form['settings']['imported_from']['form_id'], $form_id );

		// Build and send final AJAX response!
		$final_response = array(
			'name'         => $form['settings']['form_title'],
			'evf_form_id'  => $form_id,
			'edit'         => esc_url_raw( admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ) ),
			'preview'      => '',
			'unsupported'  => $unsupported,
			'upgrade_plan' => $upgrade_plan,
			'upgrade_omit' => $upgrade_omit,
		);
		return apply_filters( 'evf_fm_cf7_final_response', $final_response );
	}
	/**
	 * Show form migrator notice in admin area of everest form if the plugin found
	 *
	 * @since 2.0.6
	 *
	 * @return void
	 */
	public function show_fm_notice() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( ! in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			return;
		}
		if ( ! file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path ) ) {
			return;
		}

		if ( $this->is_dimissed() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
			<div class="notice notice-info is-dismissible evf-fm-notice">
				<p><?php printf( wp_kses_post( 'Hey, it seems that you have <strong>%s</strong> installed. Are you interested in <strong>migrating</strong> your forms to Everest Form?', 'everest-forms' ), wp_kses_post( $this->name ) ); ?></p>
				<p>
					<a href="<?php printf( admin_url( 'admin.php?page=evf-tools&tab=form_migrator' ) ); ?>" class="button button-primary evf-fm-<?php echo esc_attr( $this->slug ); ?>" id="evf-fm-<?php echo esc_attr( $this->slug ); ?>"><?php esc_html_e( 'Form Migrator', 'everest-forms' ); ?></a>
					<a href="#" class="button evf-fm-dismiss-notice" data-option-id="evf_fm_dismiss_xnotice_<?php echo esc_attr( $this->slug ); ?>" id="evf-fm-dimiss-<?php echo esc_attr( $this->slug ); ?>"><?php esc_html_e( 'No Thanks', 'everest-forms' ); ?></a>
					<a href="<?php printf( esc_url( 'https://docs.everestforms.net/' ) ); ?>" target="_blank" class="button evf-fm-<?php echo esc_attr( $this->slug ); ?>" id="evf-fm-dimiss-<?php echo esc_attr( $this->slug ); ?>"><?php esc_html_e( 'For More', 'everest-forms' ); ?></a>
				</p>
			</div>
		<?php
	}
	/**
	 * If the prompt is dismissed
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	public function is_dimissed() {
		return evf_string_to_bool( get_option( 'evf_fm_dismiss_xnotice_' . $this->slug ) );
	}
}
