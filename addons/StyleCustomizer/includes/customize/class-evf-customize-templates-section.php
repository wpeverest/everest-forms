<?php
/**
 * Customize API: EVF_Customize_Templates_Section class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Templates Section class.
 *
 * A UI container for form templates controls, which are displayed within sections.
 *
 * @see WP_Customize_Section
 */
class EVF_Customize_Templates_Section extends WP_Customize_Section {

	/**
	 * Section Type.
	 *
	 * @var string
	 */
	public $type = 'evf-templates';

	/**
	 * An Underscore (JS) template for rendering this panel's container.
	 *
	 * The templates panel renders a custom section heading with the current template and a switch template button.
	 *
	 * @see WP_Customize_Panel::print_template()
	 *
	 * @since 4.9.0
	 */
	protected function render_template() {
		?>
		<li id="accordion-section-{{ data.id }}" class="accordion-section control-section-evf-templates">
			<h3 class="accordion-section-title">
				<span class="customize-action"><?php esc_html_e( 'Active template', 'everest-forms' ); ?></span> <span class="customize-template-name">{{ data.title }}</span>

				<?php if ( current_user_can( 'manage_everest_forms' ) ) : ?>
					<button type="button" class="button change-template" aria-label="<?php esc_attr_e( 'Change template', 'everest-forms' ); ?>"><?php echo esc_html_x( 'Change', 'template', 'everest-forms' ); ?></button>
				<?php endif; ?>
			</h3>
			<ul class="accordion-section-content">
				<li class="customize-section-description-container section-meta <# if ( data.description ) { #>customize-info<# } #>">
					<div class="customize-section-title">
						<button class="customize-section-back" tabindex="-1">
							<span class="screen-reader-text"><?php echo esc_html_e( 'Back', 'everest-forms' ); ?></span>
						</button>
						<h3>
							<span class="customize-action">
								<?php esc_html_e( 'You are browsing', 'everest-forms' ); ?>
							</span>
							<?php esc_html_e( 'Templates', 'everest-forms' ); ?>
						</h3>
						<# if ( data.description ) { #>
							<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false"><span class="screen-reader-text"><?php esc_html_e( 'Help', 'everest-forms' ); ?></span></button>
							<div class="description customize-section-description">
								{{{ data.description }}}
							</div>
						<# } #>

						<div class="customize-control-notifications-container"></div>
					</div>
				</li>
			</ul>
		</li>
		<?php
	}
}
