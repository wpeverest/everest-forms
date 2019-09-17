<?php
/**
 * Welcome Class
 *
 * Takes new users to Welcome Page.
 *
 * @package     EverestForms/Admin
 * @version     5.2.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Welcome.
 */
class EVF_Admin_Welcome {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( apply_filters( 'everest_forms_show_welcome_page', true ) && current_user_can( 'manage_everest_forms' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		$welcome_page = add_dashboard_page(
			esc_html__( 'Welcome to Everest Forms', 'everest-forms' ),
			esc_html__( 'Welcome to Everest Forms', 'everest-forms' ),
			apply_filters( 'evf_welcome_cap', 'manage_options' ),
			'evf-welcome',
			array( $this, 'welcome_page' )
		);

		add_action( 'load-' . $welcome_page, array( $this, 'welcome_page_init' ) );
	}

	/**
	 * Welcome page init.
	 */
	public function welcome_page_init() {
		delete_transient( '_evf_activation_redirect' );
	}

	/**
	 * Show the welcome page.
	 */
	public function welcome_page() {
		?>
		<div id="everest-forms-welcome" >
			<div class="eveverest-forms-welcome-header">
				<div class="eveverest-forms-welcome-header__logo-wrap">
					<div class="eveverest-forms-welcome-header__logo-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.88,3l1.37,2.25H15.89L14.52,3ZM21,21H1L12,3.15l6.84,11.11H10.6L12,12H14.8L12,7.43,5,18.74H21.58L23,21ZM18.64,9.77,17.27,7.53h4.36L23,9.77Z"/></svg>
					</div>
					<span><?php esc_html_e( 'Getting Started', 'everest-forms' ); ?></span>
				</div>
				<a class="everest-forms-welcome__skip" href="#">
					<span class="dashicons dashicons-no-alt"></span>
				</a>
			</div>
			<div class="welcome-container">
				<div class="welcome-title-description">
					<h2><?php esc_html_e( 'Welcome to Everest Forms', 'everest-forms' ); ?></h2>
					<span class="description"><?php esc_html_e( 'Thank you for choosing Everest Forms, the most poweful and easy drag & drop WordPress form builder in the market.', 'everest-forms' ); ?></span>
				</div>
				<div class="welcome-video">
				</div>
				<div class="welcome-block-container">
					<a href="#" class="welcome-block">
						<h6><?php esc_html_e( 'Create Your First Form', 'everest-forms' ); ?></h6>
						<span><?php esc_html_e( 'Let\'s get started with the first contact forms for your site.', 'everest-forms' ); ?></span>
					</a>
					<a href="#" class="welcome-block">
						<h6><?php esc_html_e( 'Read The Full Guide', 'everest-forms' ); ?></h6>
						<span><?php esc_html_e( 'Read our step by step guide on how to create your first form.', 'everest-forms' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php
		exit();
	}

}

new EVF_Admin_Welcome();
