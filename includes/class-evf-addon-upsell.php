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

				'paypal_standard' => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'PayPal Standard', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/payment-paypal.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Integrate PayPal with Everest Forms to collect deposits, payments, and donations.', 'everest-forms' ),
					'active_check' => 'EVF_PAYPAL_STANDARD_VERSION',
					'vedio_id'     => 'pcwPjCt7N2I',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/paypal-standard/',
					'features'     => array(
						esc_html__( 'Redirect users to PayPal to complete payments', 'everest-forms' ),
						esc_html__( 'Accept PayPal balance, cards, and bank transfers', 'everest-forms' ),
						esc_html__( 'Trusted by hundreds of millions of buyers worldwide', 'everest-forms' ),
						esc_html__( 'Automatic payment confirmation synced to entries', 'everest-forms' ),
					),
				),

				'stripe'          => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'Stripe', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/payment-stripe.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Accept credit and debit card payments with Stripe.', 'everest-forms' ),
					'active_check' => 'EVF_STRIPE_VERSION',
					'vedio_id'     => 'wZXnaxLxdz4',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/stripe/',
					'features'     => array(
						esc_html__( 'Accept one-time and recurring Stripe payments', 'everest-forms' ),
						esc_html__( 'Supports credit cards, Apple Pay, and Google Pay', 'everest-forms' ),
						esc_html__( 'Real-time payment status updates in entries', 'everest-forms' ),
						esc_html__( 'PCI-compliant card collection via Stripe Elements', 'everest-forms' ),
					),
				),

				'square'          => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'Square', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/square-payment.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Process payments securely through Square.', 'everest-forms' ),
					'active_check' => 'EVF_SQUARE_VERSION',
					'vedio_id'     => '5ymZV-S0mMs',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/square/',
					'features'     => array(
						esc_html__( 'Accept debit and credit card payments', 'everest-forms' ),
						esc_html__( 'Seamless Square dashboard integration', 'everest-forms' ),
						esc_html__( 'Supports one-time payments in your forms', 'everest-forms' ),
						esc_html__( 'Secure, PCI-compliant card processing', 'everest-forms' ),
					),
				),

				'authorize_net'   => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'Authorize.Net', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/authorize-net.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Accept payments via Authorize.Net payment gateway.', 'everest-forms' ),
					'active_check' => 'EVF_AUTHORIZE_NET_VERSION',
					'vedio_id'     => 'a5EcKjwWD1A',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/authorize-net/',
					'features'     => array(
						esc_html__( 'Process credit cards and e-checks directly in your forms', 'everest-forms' ),
						esc_html__( 'Trusted by thousands of merchants worldwide', 'everest-forms' ),
						esc_html__( 'Advanced fraud detection and security', 'everest-forms' ),
						esc_html__( 'Works with your existing Authorize.Net account', 'everest-forms' ),
					),
				),

				'razorpay'        => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'Razorpay', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/Razorpay.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Add Razorpay payment to your forms to accept payments from India.', 'everest-forms' ),
					'active_check' => 'EVF_RAZORPAY_VERSION',
					'vedio_id'     => '2Og6b1JXDWs',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/razorpay/',
					'features'     => array(
						esc_html__( 'Accept payments in INR via cards, UPI, and net banking', 'everest-forms' ),
						esc_html__( 'Ideal for businesses targeting Indian customers', 'everest-forms' ),
						esc_html__( 'Supports one-time and recurring payments', 'everest-forms' ),
						esc_html__( 'Automatic payment status synced to form entries', 'everest-forms' ),
					),
				),

				'mollie'          => array(
					'category'     => 'payments',
					'label'        => esc_html__( 'Mollie', 'everest-forms' ),
					'icon'         => plugins_url( 'assets/extensions-json/sections/images/Mollie.png', EVF_PLUGIN_FILE ),
					'description'  => esc_html__( 'Collect payments using Mollie — ideal for European businesses.', 'everest-forms' ),
					'active_check' => 'EVF_MOLLIE_VERSION',
					'vedio_id'     => 'r263krbqzfo',
					'upgrade_url'  => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'     => 'https://docs.everestforms.net/docs/mollie/',
					'features'     => array(
						esc_html__( 'Supports iDEAL, credit cards, and more payment methods', 'everest-forms' ),
						esc_html__( 'Popular across Europe and the Netherlands', 'everest-forms' ),
						esc_html__( 'Simple API with no monthly fees', 'everest-forms' ),
						esc_html__( 'Automatic payment status syncing with entries', 'everest-forms' ),
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
	 * Get upsell entries for addons in a given category.
	 *
	 * @param  string $category     Category key e.g. 'utilities', 'payments'.
	 * @param  bool   $check_active Skip entries whose addon is already active when true.
	 * @return array
	 */
	public static function get_upsells_for_category( $category, $check_active = true ) {
		$upsells = array();

		foreach ( self::get_registry() as $id => $config ) {
			if ( $config['category'] !== $category ) {
				continue;
			}

			if ( $check_active && self::is_active( $config['active_check'] ) ) {
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
