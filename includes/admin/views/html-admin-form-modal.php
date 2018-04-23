<?php
/**
 * Admin View: Page - Form Modal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>
<script type="text/template" id="tmpl-evf-add-new-form">
	<div class="evf-backbone-modal evf-reservation-schedule">
		<div class="evf-backbone-modal-content">
			<section class="evf-backbone-modal-main" role="main">
				<header class="evf-backbone-modal-header">
					<h1><?php _e( 'Create new form', 'everest-forms' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span
									class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'everest-forms' ); ?></span>
					</button>
				</header>
				<article>
					<form action="" method="post">

						<label for="evf-modal-form-name"><?php echo __( 'Form Title', 'everest-forms' ) ?></label>
						<input type="text" name="evf-modal-form-name" id="evf-modal-form-name"/>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok"
						        class="button button-primary button-large"><?php _e( 'Create', 'everest-forms' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="evf-backbone-modal-backdrop modal-close"></div>
</script>
