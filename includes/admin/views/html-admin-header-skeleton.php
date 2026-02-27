<?php
/**
 * Admin View: Header Skeleton
 *
 * Renders a pixel-perfect PHP skeleton of the React header.
 * Prevents layout shift and flash-of-unstyled-content on page load.
 *
 * Included from html-admin-settings.php inside #evf-react-header-root.
 *
 * @package EverestForms\Admin\Views
 * @since   3.4.3
 */

defined( 'ABSPATH' ) || exit;


$evf_current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

$evf_active_nav = 'settings';
if ( 'everest-forms' === $evf_current_page ) {
	$evf_active_nav = 'forms';
} elseif ( 'evf-entries' === $evf_current_page ) {
	$evf_active_nav = 'entries';
} elseif ( 'evf-addons' === $evf_current_page ) {
	$evf_active_nav = 'addons';
} elseif ( 'evf-analytics' === $evf_current_page ) {
	$evf_active_nav = 'analytics';
}

$evf_is_pro  = defined( 'EFP_VERSION' ) || defined( 'EVF_PRO_VERSION' );
$evf_version = defined( 'EVF_VERSION' ) ? EVF_VERSION : '';

$evf_left_nav = array(
	'forms'    => array(
		'label' => __( 'All Forms', 'everest-forms' ),
		'url'   => admin_url( 'admin.php?page=everest-forms' ),
	),
	'entries'  => array(
		'label' => __( 'Entries', 'everest-forms' ),
		'url'   => admin_url( 'admin.php?page=evf-entries' ),
	),
	'settings' => array(
		'label' => __( 'Settings', 'everest-forms' ),
		'url'   => admin_url( 'admin.php?page=evf-settings' ),
	),
	'addons'   => array(
		'label' => __( 'Addons', 'everest-forms' ),
		'url'   => admin_url( 'admin.php?page=evf-addons' ),
	),
);
?>

<div id="evf-react-header-root">
	<div class="evf-skeleton" id="evf-header-skeleton" role="banner">

		<div class="evf-skeleton__left">
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=everest-forms' ) ); ?>"
				class="evf-skeleton__logo"
				aria-label="<?php esc_attr_e( 'Everest Forms', 'everest-forms' ); ?>"
			>
				<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<rect width="40" height="40" rx="8" fill="#7C3AED" fill-opacity="0.08"/>
					<path d="M20 8L32 30H8L20 8Z" fill="#7C3AED" fill-opacity="0.15"/>
					<path d="M20 11L30 29H10L20 11Z" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linejoin="round"/>
					<line x1="14.5" y1="23.5" x2="25.5" y2="23.5" stroke="#7C3AED" stroke-width="2" stroke-linecap="round"/>
					<line x1="16.5" y1="27" x2="23.5" y2="27" stroke="#7C3AED" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</a>

			<ul class="evf-skeleton__nav" role="navigation" aria-label="<?php esc_attr_e( 'Main navigation', 'everest-forms' ); ?>">
				<?php foreach ( $evf_left_nav as $evf_key => $evf_item ) : ?>
					<li class="evf-skeleton__nav-item<?php echo ( $evf_active_nav === $evf_key ) ? ' evf-skeleton__nav-item--active' : ''; ?>">
						<a
							href="<?php echo esc_url( $evf_item['url'] ); ?>"
							class="evf-skeleton__nav-link"
							<?php echo ( $evf_active_nav === $evf_key ) ? 'aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( $evf_item['label'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="evf-skeleton__right">

			<!-- Right nav: Help -->
			<nav class="evf-skeleton__right-nav" aria-label="<?php esc_attr_e( 'Secondary navigation', 'everest-forms' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=general' ) ); ?>" class="evf-skeleton__right-link">
					<?php esc_html_e( 'Help', 'everest-forms' ); ?>
				</a>
			</nav>

			<!-- Divider -->
			<div class="evf-skeleton__divider" role="separator" aria-hidden="true"></div>

			<?php if ( ! $evf_is_pro ) : ?>
				<!-- Upgrade to Pro — free users only, mirrors React's !isPro block -->
				<a
					href="https://everestforms.net/free-vs-pro/?utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=header-upgrade-btn&utm_content=Upgrade+to+Pro"
					target="_blank"
					rel="noopener noreferrer"
					class="evf-skeleton__upgrade"
				>
					<?php esc_html_e( 'Upgrade To Pro', 'everest-forms' ); ?>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
						<polyline points="15 3 21 3 21 9"/>
						<line x1="10" y1="14" x2="21" y2="3"/>
					</svg>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $evf_version ) ) : ?>
				<span class="evf-skeleton__version" aria-label="<?php echo esc_attr( sprintf( __( 'Version %s', 'everest-forms' ), $evf_version ) ); ?>">
					v<?php echo esc_html( $evf_version ); ?>
				</span>
			<?php endif; ?>

			<div class="evf-skeleton__bell" aria-hidden="true">
				<div class="evf-skeleton__bell-inner"></div>
			</div>

		</div>
	</div>
</div>

<script>
/* global document */
( function () {
	'use strict';

	document.body.classList.add( 'evf-js-loaded' );

	window.evfHeaderReady = function () {
		var skeleton = document.getElementById( 'evf-header-skeleton' );
		if ( ! skeleton ) {
			return;
		}

		skeleton.classList.add( 'evf-skeleton--hidden' );

		setTimeout( function () {
			if ( skeleton.parentNode ) {
				skeleton.parentNode.removeChild( skeleton );
			}

			var styles = document.getElementById( 'evf-header-skeleton-styles' );
			if ( styles && styles.parentNode ) {
				styles.parentNode.removeChild( styles );
			}
		}, 200 );
	};
}() );
</script>
