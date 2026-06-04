<?php
/**
 * EVF AI Builder UI — injects the AI button and modal into the form builder.
 * Loaded separately from class-evf-ai-integration.php.
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Builder_UI {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'everest_forms_builder_tabs', [ $this, 'render_modal' ] );
	}

	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'everest-forms_page_evf-builder' !== $screen->id ) {
			return;
		}

		wp_enqueue_style(
			'evf-ai-builder',
			EVF()->plugin_url() . '/assets/css/admin/evf-ai-builder.css',
			[],
			EVF_VERSION
		);

		wp_enqueue_script(
			'evf-ai-builder',
			EVF()->plugin_url() . '/assets/js/admin/evf-ai-builder.js',
			[ 'jquery' ],
			EVF_VERSION,
			true
		);

		wp_localize_script( 'evf-ai-builder', 'evfAI', [
			'nonce'        => wp_create_nonce( 'evf_ai_nonce' ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'isRegistered' => EVF_AI_Registration::is_registered(),
			'tier'         => EVF_AI_Registration::get_tier(),
			'i18n'         => [
				'placeholder'   => __( 'e.g. Create a contact form with name, email, phone and message…', 'everest-forms' ),
				'generating'    => __( 'Generating your form…', 'everest-forms' ),
				'success'       => __( 'Form created! Opening editor…', 'everest-forms' ),
				'error_generic' => __( 'Something went wrong. Please try again.', 'everest-forms' ),
				'limit_reached' => __( 'You\'ve used all free AI requests for today. Upgrade to EVF Pro for unlimited access.', 'everest-forms' ),
				'not_available' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
				'pro_badge'     => __( 'Pro', 'everest-forms' ),
				'free_badge'    => __( 'Free', 'everest-forms' ),
			],
		] );
	}

	public function render_modal() {
		?>
		<!-- EVF AI Modal — injected by class-evf-ai-builder-ui.php -->
		<div id="evf-ai-overlay" style="display:none;" aria-hidden="true">
			<div id="evf-ai-modal" role="dialog" aria-labelledby="evf-ai-modal-title" aria-modal="true">

				<div class="evf-ai-modal-header">
					<h2 id="evf-ai-modal-title">
						<span class="evf-ai-sparkle">✨</span>
						<?php esc_html_e( 'Generate Form with AI', 'everest-forms' ); ?>
					</h2>
					<button class="evf-ai-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'everest-forms' ); ?>">&#x2715;</button>
				</div>

				<div class="evf-ai-modal-body">
					<label for="evf-ai-prompt" class="screen-reader-text">
						<?php esc_html_e( 'Describe your form', 'everest-forms' ); ?>
					</label>
					<textarea
						id="evf-ai-prompt"
						rows="4"
						maxlength="1000"
					></textarea>
					<div class="evf-ai-char-count"><span id="evf-ai-chars">0</span> / 1000</div>
					<div class="evf-ai-usage" id="evf-ai-usage" style="display:none;"></div>
					<div class="evf-ai-error" id="evf-ai-error" style="display:none;" role="alert"></div>
				</div>

				<div class="evf-ai-modal-footer">
					<div class="evf-ai-tier-badge" id="evf-ai-tier-badge"></div>
					<button id="evf-ai-generate" type="button" class="button button-primary">
						✨ <?php esc_html_e( 'Generate Form', 'everest-forms' ); ?>
					</button>
				</div>

			</div>
		</div>
		<?php
	}
}
