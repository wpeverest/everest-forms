<?php
/**
 * Welcome Class
 *
 * Takes new users to Welcome Page.
 *
 * @package EverestForms/Admin
 * @version 1.5.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Welcome class.
 */
class EVF_Admin_Welcome {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		if ( apply_filters( 'everest_forms_show_welcome_page', true ) && current_user_can( 'manage_everest_forms' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
			add_action( 'admin_head', array( __CLASS__, 'hide_menu' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public static function add_menu() {
		$welcome_page = add_dashboard_page(
			esc_html__( 'Welcome to Everest Forms', 'everest-forms' ),
			esc_html__( 'Welcome to Everest Forms', 'everest-forms' ),
			apply_filters( 'evf_welcome_cap', 'manage_options' ),
			'evf-welcome',
			array( __CLASS__, 'welcome_page' )
		);

		add_action( 'load-' . $welcome_page, array( __CLASS__, 'welcome_page_init' ) );
	}

	/**
	 * Removed the dashboard pages from the admin menu.
	 *
	 * This means the pages are still available to us, but hidden.
	 *
	 * @since 1.0.0
	 */
	public static function hide_menu() {
		remove_submenu_page( 'index.php', 'evf-welcome' );
	}

	/**
	 * Welcome page init.
	 */
	public static function welcome_page_init() {
		delete_transient( '_evf_activation_redirect' );
	}

	/**
	 * Show the welcome page.
	 */
	public static function welcome_page() {
		?>
		<div id="everest-forms-welcome" >
			<div class="eveverest-forms-welcome-header">
				<div class="eveverest-forms-welcome-header__logo-wrap">
					<div class="eveverest-forms-welcome-header__logo-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.88,3l1.37,2.25H15.89L14.52,3ZM21,21H1L12,3.15l6.84,11.11H10.6L12,12H14.8L12,7.43,5,18.74H21.58L23,21ZM18.64,9.77,17.27,7.53h4.36L23,9.77Z"/></svg>
					</div>
					<span><?php esc_html_e( 'Getting Started', 'everest-forms' ); ?></span>
				</div>
				<a class="everest-forms-welcome__skip" href="<?php echo esc_url( admin_url() ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</a>
			</div>
			<div class="everest-forms-welcome-container">
				<div class="everest-forms-welcome-container__header">
					<h2><?php esc_html_e( 'Welcome to Everest Forms', 'everest-forms' ); ?></h2>
					<p><?php esc_html_e( 'Thank you for choosing Everest Forms, the most powerful and easy drag & drop WordPress form builder in the market.', 'everest-forms' ); ?></p>
				</div>
				<a class="everest-forms-welcome-video welcome-video-play">
					<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/welcome-video-thumb.png' ); ?>" alt="<?php esc_attr_e( 'Watch how to create your first form with Everest Forms', 'everest-forms' ); ?>" class="everest-froms-welcome-thumb">
					<button class="everest-forms-welcome-video__button dashicons dashicons-controls-play"></button>
				</a>
				<div class="everest-forms-welcome-container__action">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>" class="everest-forms-welcome-container__action-card">
						<figure class="everest-forms-welcome-container__action-card-img">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><style>.cls-1{fill:none;}.cls-1,.cls-2{stroke:#3D8FC9;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}.cls-2{fill:#E1F0FA;}</style></defs><rect class="cls-1" x="12" y="11" width="40" height="48" rx="2" ry="2"/><rect class="cls-2" x="18" y="46" width="6" height="6"/><circle class="cls-2" cx="12" cy="52" r="7"/><line class="cls-1" x1="12" y1="49" x2="12" y2="55"/><line class="cls-1" x1="9" y1="52" x2="15" y2="52"/><path class="cls-2" d="M35,8a3,3,0,0,0-6,0H25a2,2,0,0,0-2,2v2a2,2,0,0,0,2,2H39a2,2,0,0,0,2-2V10a2,2,0,0,0-2-2Z"/><rect class="cls-2" x="49" y="29" width="6" height="18"/><polygon class="cls-2" points="52 57 49 51 49 47 55 47 55 51 52 57"/><path class="cls-2" d="M52,23h0a3,3,0,0,1,3,3v3a0,0,0,0,1,0,0H49a0,0,0,0,1,0,0V26A3,3,0,0,1,52,23Z"/><line class="cls-1" x1="59" y1="25" x2="59" y2="39"/><line class="cls-1" x1="49.5" y1="29" x2="58.5" y2="29"/><rect class="cls-2" x="18" y="22" width="28" height="6"/><rect class="cls-2" x="18" y="34" width="6" height="6"/><line class="cls-1" x1="28" y1="37" x2="42" y2="37"/><line class="cls-1" x1="28" y1="49" x2="42" y2="49"/></svg>
						</figure>
						<div class="everest-forms-welcome-container__action-card-content">
							<h3><?php esc_html_e( 'Create Your First Form', 'everest-forms' ); ?></h3>
							<p><?php esc_html_e( 'Let\'s get started with the first contact forms for your site.', 'everest-forms' ); ?></p>
						</div>
					</a>
					<a href="https://docs.wpeverest.com/docs/everest-forms/" class="everest-forms-welcome-container__action-card" target="blank">
						<figure class="everest-forms-welcome-container__action-card-img">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><style>.cls-1{fill:none;}.cls-1,.cls-2{stroke:#3D8FC9;stroke-linecap:round;stroke-miterlimit:10;stroke-width:2px;}.cls-2{fill:#E1F0FA;}</style></defs><rect x="13" y="5" width="26" height="38" rx="2" ry="2" class="cls-1"/><path class="cls-1" d="M22,19a3,3,0,0,0-3,3V40a3,3,0,0,1-3,3h9V22A3,3,0,0,0,22,19Z"/><path class="cls-2" d="M49,19H22a3,3,0,0,1,3,3V57a2,2,0,0,0,2,2H49a2,2,0,0,0,2-2V21A2,2,0,0,0,49,19Z"/><line class="cls-1" x1="30" y1="27" x2="46" y2="27"/><line class="cls-1" x1="18" y1="13" x2="34" y2="13"/><line class="cls-1" x1="30" y1="33" x2="46" y2="33"/><line class="cls-1" x1="30" y1="39" x2="46" y2="39"/><line class="cls-1" x1="30" y1="45" x2="40" y2="45"/><line class="cls-1" x1="44" y1="45" x2="46" y2="45"/><line class="cls-1" x1="30" y1="51" x2="40" y2="51"/><line class="cls-1" x1="44" y1="51" x2="46" y2="51"/></svg>
						</figure>
						<div class="everest-forms-welcome-container__action-card-content">
							<h3><?php esc_html_e( 'Read The Full Guide', 'everest-forms' ); ?></h3>
							<p><?php esc_html_e( 'Read our step by step guide on how to create your first form.', 'everest-forms' ); ?></p>
						</div>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}

EVF_Admin_Welcome::init();
