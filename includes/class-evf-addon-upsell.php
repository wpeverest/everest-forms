<?php
/**
 * EverestForms Addon Upsell
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Addon_Upsell.
 */
class EVF_Addon_Upsell {

	/**
	 * Get addon registry.
	 *
	 * @return array
	 */
	private static function get_registry() {

		if ( defined( 'EFP_PLUGIN_FILE' ) ) {
			return array();
		}

		return apply_filters(
			'everest_forms_addon_upsell_registry',
			array(
				'pdf_submission'    => array(
					'category'     => 'utilities',
					'label'        => esc_html__( 'PDF Submission', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/pdf-submission.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Generate beautiful PDFs from form submissions and send them via email.', 'everest-forms' ),
					'active_check' => 'EVF_PDF_SUBMISSION_VERSION',
					'vedio_id'     => '37CtaxJzYis',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/pdf-form-submission/',
					'features'     => array(
						esc_html__( 'Send submitted form data as a PDF attachment', 'everest-forms' ),
						esc_html__( 'Print or share submission details more easily', 'everest-forms' ),
						esc_html__( 'Store important form data in a professional format ', 'everest-forms' ),
						esc_html__( 'Make record-keeping much simpler', 'everest-forms' ),
					),
				),

				'user_registration' => array(
					'category'     => 'utilities',
					'label'        => esc_html__( 'User Registration', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/user-registration.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Register WordPress users and enable social login directly from your forms.', 'everest-forms' ),
					'vedio_id'     => 'MEyuznG2Tok',
					'active_check' => 'EverestForms\\Pro\\Addons\\UserRegistration\\UserRegistration',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/user-registration/',
					'features'     => array(
						esc_html__( 'Let people register from the front end of your site ', 'everest-forms' ),
						esc_html__( 'Create WordPress user accounts from form submissions', 'everest-forms' ),
						esc_html__( 'Map form fields to user account details', 'everest-forms' ),
						esc_html__( 'Use one form for both data collection and signup', 'everest-forms' ),
					),
				),
			)
		);
	}

	/**
	 * Check whether an addon is currently active.
	 *
	 * @param  string $active_check Constant name or fully-qualified class name.
	 * @return bool
	 */
	private static function is_active( $active_check ) {
		if ( empty( $active_check ) ) {
			return false;
		}

		return defined( $active_check ) || class_exists( $active_check );
	}

	/**
	 * Build a upsell entry from a registry config.
	 *
	 * @param  array $config Registry config.
	 * @return array
	 */
	private static function build_upsell( array $config ) {
		return array(
			'label'       => $config['label'],
			'upsell'      => true,
			'icon'        => $config['icon'] ?? '',
			'description' => $config['description'] ?? '',
			'vedio_id'    => $config['vedio_id'] ?? '',
			'upgrade_url' => $config['upgrade_url'] ?? 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
			'docs_url'    => $config['docs_url'] ?? 'https://docs.everestforms.net/docs/',
			'features'    => $config['features'] ?? array(),
		);
	}

	/**
	 * Get upsell entries for inactive addons in a given category.
	 *
	 * @param  string $category Category key e.g. 'utilities', 'payment'.
	 * @return array
	 */
	public static function get_upsells_for_category( $category ) {
		$upsells = array();

		foreach ( self::get_registry() as $id => $config ) {
			if ( $config['category'] !== $category ) {
				continue;
			}

			if ( self::is_active( $config['active_check'] ) ) {
				continue;
			}

			$upsells[ $id ] = self::build_upsell( $config );
		}

		return $upsells;
	}

	/**
	 * Get upsell entries for all inactive addons across every category.
	 *
	 * @return array
	 */
	public static function get_all_upsells() {
		$upsells = array();

		foreach ( self::get_registry() as $id => $config ) {
			if ( self::is_active( $config['active_check'] ) ) {
				continue;
			}

			$upsell             = self::build_upsell( $config );
			$upsell['category'] = $config['category'];
			$upsells[ $id ]     = $upsell;
		}

		return $upsells;
	}
}
