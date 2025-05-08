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
		wp_enqueue_style( 'everest-forms-clean-talk-style' );

		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-clean-talk' );
			wp_localize_script(
				'everest-forms-clean-talk',
				'everest_forms_clean_talk',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'everest_forms_clean_talk_nonce' ),
				)
			);
		}
	}
}
