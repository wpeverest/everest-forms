<?php

/**
 * Functionality related to the admin TinyMCE editor.
 *
 * @class    EVF_Add_Form
 * @version  1.0.0
 * @package  EverestForms/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EVF_Add_Form', false ) ) :

	class EVF_Add_Form {

		/**
		 * Primary class constructor.
		 */

		public function __construct() {

			add_action( 'media_buttons', array( $this, 'media_button' ), 15 );			
		}

		/**
		 * Allow easy shortcode insertion via a custom media button.
		 *
		 * @since 1.0.0
		 *
		 * @param string $editor_id
		 */
		function media_button( $editor_id ) {

			if ( ! apply_filters( 'evf_display_media_button', is_admin(), $editor_id ) ) {
				return;
			}

			// Setup the icon - cevfrently using a dashicon
			
			$icon = '<span class="dashicons dashicons-list-view" style="line-height:25px; font-size:16px"></span>';
			$login_icon = '<span class="dashicons dashicons-migrate" style="line-height:25px; font-size:16px"></span>';

			printf( '<a href="#" class="button evf-insert-form-button" data-editor="%s" title="%s">%s %s</a>',
				esc_attr( $editor_id ),
				esc_attr__( 'Add Everest Form', 'everest-forms' ),
				$icon,
				__( 'Add Form', 'everest-forms' )
			);

			add_action( 'admin_footer', array( $this, 'shortcode_modal' ) );
		}

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
										
										if ( !empty( $forms ) ) {
											printf( '<p><label for="evf-modal-select-form">%s</label></p>', __( 'Select a form below to insert', 'everest-forms' ) );
											echo '<select id="evf-modal-select-form">';
											foreach ( $forms as $form => $form_value) {
												printf( '<option value="%d">%s</option>', $form, esc_html( $form_value ) );
											}
											echo '</select>';
											
										} else {
											echo '<p>';
												__(printf( 'Whoops, you haven\'t created a form yet.'),'everest-forms');
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

endif;

return new EVF_Add_Form();