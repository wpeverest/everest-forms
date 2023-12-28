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

		$importers[ $this->slug ] = array(
			'name'      => $this->name,
			'slug'      => $this->slug,
			'path'      => $this->path,
			'installed' => file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path ),
			'active'    => $this->is_active(),
		);

		return $importers;
	}

	/**
	 * If the importer source is available.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_active() {

		return is_plugin_active( $this->path );
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

		$imported = get_option( 'evf_forms_imported', array() );

		$imported[ $this->slug ][ $evf_forms_id ] = $source_id;

		update_option( 'evf_forms_imported', $imported, false );
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

		$form_id = wp_insert_post(
			array(
				'post_status' => 'publish',
				'post_type'   => 'everest_form',
			)
		);

		if ( empty( $form_id ) || is_wp_error( $form_id ) ) {
			wp_send_json_success(
				array(
					'error' => true,
					'name'  => sanitize_text_field( $form['settings']['form_title'] ),
					'msg'   => esc_html__( 'There was an error while creating a new form.', 'everest-forms' ),
				)
			);
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
		$this->track_import( $form['settings']['import_form_id'], $form_id );

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
}
