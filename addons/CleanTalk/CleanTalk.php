<?php
/**
 * CleanTalk.
 *
 * @since 3.2.2
 * @package EverestForms\Addons\CleanTalk
 */

namespace EverestForms\Addons\CleanTalk;

use EverestForms\Addons\CleanTalk\Builder\Builder;
use EverestForms\Addons\CleanTalk\Settings\Settings;
use EverestForms\Traits\Singleton;

/**
 * CleanTalk.
 *
 * @since 3.2.2
 */
class CleanTalk {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * @since 3.2.2
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup the CleanTalk.
	 *
	 * @since 3.2.2
	 */
	public function setup() {
		if ( ! is_admin() ) {
			return;
		}
		new Builder();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'everest-forms-clean-talk', plugins_url( "addons/CleanTalk/assets/js/admin/admin{$suffix}.js", EVF_PLUGIN_FILE ), array( 'jquery' ), EVF_VERSION, true );
		wp_register_style( 'everest-forms-clean-talk-backward', plugins_url( 'addons/CleanTalk/assets/css/admin/backward.css', EVF_PLUGIN_FILE ), array(), EVF_VERSION );
		wp_register_style( 'everest-forms-clean-talk-style', plugins_url( 'addons/CleanTalk/assets/css/admin/admin.css', EVF_PLUGIN_FILE ), array(), EVF_VERSION );

		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			wp_enqueue_style( 'everest-forms-clean-talk-backward' );
		}

		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-clean-talk' );
			wp_enqueue_style( 'everest-forms-clean-talk-style' );
			wp_localize_script(
				'everest-forms-clean-talk',
				'everest_forms_clean_talk',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'everest_forms_clean_talk_nonce' ),
					'output'  => $this->evf_get_popup_output(),
				)
			);
		}
	}

	/**
	 *  Get the output for the CleanTalk popup.
	 *  @since 3.2.3
	 */
	public function evf_get_popup_output(){
		$output = '';

		$output .= '<form action="" class="everest-forms-clean-talk-form-container">';
		$output .= '<div class="clean-talk-form-group">';
		$output .= '<div class="everest-forms-clean-talk-error-message-container" style="display: none"></div>';
		$output .= '<div class="everest-forms-clean-talk-access-key-title">' . __( 'CleanTalk Access Key', 'everest-forms' ) . '</div>';
		$output .= '<input type="password" class="everest-forms-clean-talk-access-key" placeholder="Enter access key" required />';
		$output .= '<p style="margin-top: 8px; font-size: 14px; text-align: left; margin-bottom: 24px; font">';
		$output .= __( 'Enter your CleanTalk REST API key from your ', 'everest-forms' );
		$output .= '<a href="https://cleantalk.org/my" target="_blank">' . __( "account dashboard here", 'everest-forms' ) .'</a>.';
		$output .= '</p>';

		$output .= '<div class="everest-forms-clean-talk-note">';
		$output .= '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: text-bottom; margin-right: 5px;">';
		$output .= '<path fill-rule="evenodd" clip-rule="evenodd" d="M8 1.45455C4.38505 1.45455 1.45455 4.38505 1.45455 8C1.45455 11.615 4.38505 14.5455 8 14.5455C11.615 14.5455 14.5455 11.615 14.5455 8C14.5455 4.38505 11.615 1.45455 8 1.45455ZM0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8ZM8 7.27273C8.40166 7.27273 8.72727 7.59834 8.72727 8V10.9091C8.72727 11.3108 8.40166 11.6364 8 11.6364C7.59834 11.6364 7.27273 11.3108 7.27273 10.9091V8C7.27273 7.59834 7.59834 7.27273 8 7.27273ZM8 4.36364C7.59834 4.36364 7.27273 4.68925 7.27273 5.09091C7.27273 5.49257 7.59834 5.81818 8 5.81818H8.00727C8.40894 5.81818 8.73455 5.49257 8.73455 5.09091C8.73455 4.68925 8.40894 4.36364 8.00727 4.36364H8Z" fill="#4584FF"/>';
		$output .= '</svg>';
		$output .= '<p><strong>Note : </strong>' . __( "This will update the CleanTalk Access Key globally. You can check here on ", 'everest-forms' );
		$output .= '<a href="' . esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration&section=clean-talk') ) . '" target="__blank">' . __( "Settings &gt; Integration &gt; CleanTalk", 'everest-forms' ) .'</a>.</p>';
		$output .= '</div>';

		$output .= '</div>';
		$output .= '</form>';

		return $output;
	}
}
