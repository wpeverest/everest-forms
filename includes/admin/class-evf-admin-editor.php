<?php
/**
 * Functionality related to the admin TinyMCE editor.
 *
 * @package EverestForms/Admin
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EVF_Admin_Editor', false ) ) {
	return new EVF_Admin_Editor();
}

/**
 * EVF_Admin_Editor Class.
 */
class EVF_Admin_Editor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'media_buttons', array( $this, 'media_button' ), 15 );
	}

	/**
	 * Allow easy shortcode insertion via a custom media button.
	 *
	 * @param string $editor_id Unique editor identifier, e.g. 'content'.
	 */
	function media_button( $editor_id ) {
		if ( ! apply_filters( 'everest_forms_show_media_button', is_admin(), $editor_id ) ) {
			return;
		}

		// Setup the svg icon.
		$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path fill="#82878c" d="M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z"/></g></svg>';
		printf(
			'<a href="#" class="button evf-insert-form-button" data-editor="%s" title="%s"><span class="wp-media-buttons-icon">%s</span> %s</a>',
			esc_attr( $editor_id ),
			esc_attr__( 'Add Everest Form', 'everest-forms' ),
			$svg_icon,
			__( 'Add Form', 'everest-forms' )
		);

		// If we have made it, then load the JS.
		wp_enqueue_script( 'everest-forms-editor' );

		add_action( 'admin_footer', array( $this, 'shortcode_modal' ) );
	}

	/**
	 * Modal window for inserting the form shortcode into TinyMCE.
	 */
	function shortcode_modal() {
       	?>
   		<div id="evf-modal-backdrop" style="display: none"></div>
		<div id="evf-modal-wrap" style="display: none">
			<form id="evf-modal" tabindex="-1">
				<div id="evf-modal-title">
					<?php _e( 'Insert Form', 'everest-forms' ); ?>
					<button type="button" id="evf-modal-close"><span class="screen-reader-text"><?php _e( 'Close', 'everest-forms' ); ?></span></button>
				</div>
				<div id="evf-modal-inner">
					<div id="evf-modal-options">
						<?php
							$forms = evf_get_all_forms();

							if ( ! empty( $forms ) ) {
								printf( '<p><label for="evf-modal-select-form">%s</label></p>', __( 'Select a form below to insert', 'everest-forms' ) );
								echo '<select id="evf-modal-select-form">';
								foreach ( $forms as $form_id => $form_value ) {
									printf( '<option value="%d">%s</option>', $form_id, esc_html( $form_value ) );
								}
								echo '</select>';
							} else {
								echo '<p>';
								printf(
									wp_kses(
										/* translators: %s - Everest Builder page. */
										__( 'Whoops, you haven\'t created a form yet. Want to <a href="%s">give it a go</a>?', 'everest-forms' ),
										array(
											'a' => array(
												'href' => array(),
											),
										)
									),
									admin_url( 'admin.php?page=evf-builder' )
								);
								echo '</p>';
							}
						?>
					</div>
				</div>
				<div class="submitbox">
					<div id="evf-modal-cancel">
						<a class="submitdelete deletion" href="#"><?php _e( 'Cancel', 'everest-forms' ); ?></a>
					</div>
					<?php if ( ! empty( $forms ) ) : ?>
						<div id="evf-modal-update">
							<button class="button button-primary" id="evf-modal-submit"><?php _e( 'Add Form', 'everest-forms' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			</form>
		</div>
       	<?php
	}
}

return new EVF_Admin_Editor();
