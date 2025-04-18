<?php
/**
 * CleanTalk.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\CleanTalk
 */

namespace EverestForms\Addons\CleanTalk;

use EverestForms\Addons\CleanTalk\Builder\Builder;
use EverestForms\Addons\CleanTalk\Settings\Settings;
use EverestForms\Traits\Singleton;

/**
 * CleanTalk.
 *
 * @since 3.0.5
 */
class CleanTalk {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup the CleanTalk.
	 *
	 * @since 3.0.5
	 */
	public function setup() {
		if ( ! is_admin() ) {
			return;
		}
		new Builder();
		new Settings();
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

		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-clean-talk' );
		}
	}
}
