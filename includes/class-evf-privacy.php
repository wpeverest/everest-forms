<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @package EverestForms\Classes
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Privacy Class.
 */
class EVF_Privacy {

	/**
	 * Init - hook into events.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_privacy_message' ) );
	}

	/**
	 * Adds the privacy message on EVF privacy page.
	 */
	public function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = $this->get_privacy_message();

			if ( $content ) {
				wp_add_privacy_policy_content( __( 'Everest Forms', 'everest-forms' ), $this->get_privacy_message() );
			}
		}
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 *
	 * @since 1.3.1
	 */
	public function get_privacy_message() {
		$content = '
			<div contenteditable="false">' .
				'<p class="wp-policy-help">' .
					__( 'This sample policy includes the basics around what personal data you may be collecting, storing and sharing, as well as who may have access to that data. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your form will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'everest-forms' ) .
				'</p>' .
			'</div>' .
			'<p>' . __( 'We collect information about you during the form submission process on our site.', 'everest-forms' ) . '</p>' .
			'<h2>' . __( 'What we collect and store', 'everest-forms' ) . '</h2>' .
			'<p>' . __( 'While you visit our site, we’ll track:', 'everest-forms' ) . '</p>' .
			'<ul>' .
				'<li>' . __( 'Form Fields Data: Forms Fields data includes the available field types when creating a form. We’ll use this to, for example, collect informations like Name, Email and other available fields.', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Location, IP address and browser type: we’ll use this for purposes like geolocating users and reducing fraudulent activities.', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Transaction Details: we’ll ask you to enter this so we can, for instance, provide subscription packs, and keep track of your payment details for subscription packs!', 'everest-forms' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'We’ll also use cookies to keep track of form elements while you’re browsing our site.', 'everest-forms' ) . '</p>' .
			'<div contenteditable="false">' .
				'<p class="wp-policy-help">' . __( 'Note: you may want to further detail your cookie policy, and link to that section from here.', 'everest-forms' ) . '</p>' .
			'</div>' .
			'<p>' . __( 'When you fill up a form, we’ll ask you to provide information including your name, address, email, phone number, credit card/payment details and optional account information like username and password and any other form fields available in the form builder. We’ll use this information for purposes, such as, to:', 'everest-forms' ) . '</p>' .
			'<ul>' .
				'<li>' . __( 'Send you information about your account and order', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Respond to your requests, including transaction details and complaints', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Process payments and prevent fraud', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Set up your account for our site', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Comply with any legal obligations we have, such as calculating taxes', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Improve our form offerings', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Send you marketing messages, if you choose to receive them', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Or any other service the built form was created to comply with and it’s necessary information', 'everest-forms' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'If you create an account, we will store your name, address, email and phone number, which will be used to populate the form fields for future submissions.', 'everest-forms' ) . '</p>' .
			'<p>' . __( 'We generally store information about you for as long as we need the information for the purposes for which we collect and use it, and we are not legally required to continue to keep it. For example, we will store form submission information for XXX years for geolocating and marketting purposes. This includes your name, address, email, phone number.', 'everest-forms' ) . '</p>' .
			'<h2>' . __( 'Who on our team has access', 'everest-forms' ) . '</h2>' .
			'<p>' . __( 'Members of our team have access to the information you provide us. For example, both Administrators and Editors can access:', 'everest-forms' ) . '</p>' .
			'<ul>' .
				'<li>' . __( 'Form submission information and other details related to it', 'everest-forms' ) . '</li>' .
				'<li>' . __( 'Customer information like your name, email and address information.', 'everest-forms' ) . '</li>' .
			'</ul>' .
			'<p>' . __( 'Our team members have access to this information to help fulfill entries and support you.', 'everest-forms' ) . '</p>' .
			'<h2>' . __( 'What we share with others', 'everest-forms' ) . '</h2>' .
			'<div contenteditable="false">' .
				'<p class="wp-policy-help">' . __( 'In this section you should list who you’re sharing data with, and for what purpose. This could include, but may not be limited to, analytics, marketing, payment gateways, shipping providers, and third party embeds.', 'everest-forms' ) . '</p>' .
			'</div>' .
			'<p>' . __( 'We share information with third parties who help us provide our orders and store services to you; for example --', 'everest-forms' ) . '</p>' .
			'<h3>' . __( 'Payments', 'everest-forms' ) . '</h3>' .
			'<div contenteditable="false">' .
				'<p class="wp-policy-help">' . __( 'In this subsection you should list which third party payment processors you’re using to take payments on your site since these may handle customer data. We’ve included PayPal as an example, but you should remove this if you’re not using PayPal.', 'everest-forms' ) . '</p>' .
			'</div>' .
			'<p>' . __( 'We accept payments through PayPal. When processing payments, some of your data will be passed to PayPal, including information required to process or support the payment, such as the purchase total and billing information.', 'everest-forms' ) . '</p>' .
			'<p>' . __( 'Please see the <a href="https://www.paypal.com/us/webapps/mpp/ua/privacy-full">PayPal Privacy Policy</a> for more details.', 'everest-forms' ) . '</p>' .
			'<h3>' . __( 'Available Modules', 'everest-forms' ) . '</h3>' .
			'<div contenteditable="false">' .
				'<p class="wp-policy-help">' . __( 'In this subsection you should list which third party modules you’re using to increase functionality on your site since these may handle customer data. We’ve included MailChimp as an example, but you should remove this if you’re not using MailChimp.', 'everest-forms' ) . '</p>' .
			'</div>' .
			'<p>' . __( 'We send beautiful email through MailChimp. When processing emails, some of your data will be passed to MailChimp, including information required to process or support the email marketing services, such as the name, email address and any other information that you intend to pass or collect including all collected information through subscription.', 'everest-forms' ) . '</p>' .
			'<p>' . __( 'Please see the <a href="https://mailchimp.com/legal/privacy/">MailChimp Privacy Policy</a> for more details.', 'everest-forms' ) . '</p>';

		return apply_filters( 'everest_forms_privacy_policy_content', $content );
	}
}

new EVF_Privacy();
