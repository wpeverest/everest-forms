<?php
/**
 * Admin View: Template Page Skeleton
 *
 * PHP-rendered shimmer skeleton for the Add New Form / template selection page.
 * Shown immediately on page load while templates.min.js downloads and parses,
 * preventing a white-screen flash. React replaces #evf-templates content on mount.
 *
 * @package EverestForms\Admin\Views
 */

defined( 'ABSPATH' ) || exit;
?>

<style id="evf-template-skeleton-styles">
	.evf-ts{padding:32px;box-sizing:border-box}
	.evf-ts-box{background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:600px 100%;animation:evf-ts-sh 1.6s ease-in-out infinite;border-radius:4px}
	@keyframes evf-ts-sh{0%{background-position:-600px 0}100%{background-position:600px 0}}
	.evf-ts-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden}
	.evf-ts-row{display:flex;align-items:center}
	.evf-ts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
</style>

<div class="evf-ts">

	<!-- Page title bar -->
	<div class="evf-ts-row" style="justify-content:space-between;padding:20px 32px;border-bottom:1px solid #e2e8f0;margin:-32px -32px 32px">
		<div class="evf-ts-box" style="width:140px;height:20px"></div>
		<div class="evf-ts-box" style="width:16px;height:16px;border-radius:50%"></div>
	</div>

	<!-- CTA cards -->
	<div class="evf-ts-row" style="gap:32px;margin-bottom:32px">
		<div class="evf-ts-card" style="flex:1;padding:32px">
			<div class="evf-ts-row" style="justify-content:space-between;margin-bottom:16px">
				<div class="evf-ts-box" style="width:48px;height:48px;border-radius:12px"></div>
				<div class="evf-ts-box" style="width:44px;height:22px;border-radius:6px"></div>
			</div>
			<div class="evf-ts-box" style="width:58%;height:17px;margin-bottom:8px"></div>
			<div class="evf-ts-box" style="width:90%;height:12px;margin-bottom:4px"></div>
			<div class="evf-ts-box" style="width:72%;height:12px;margin-bottom:24px"></div>
			<div class="evf-ts-box" style="width:86px;height:13px"></div>
		</div>
		<div class="evf-ts-card" style="flex:1;padding:32px">
			<div class="evf-ts-box" style="width:48px;height:48px;border-radius:12px;margin-bottom:20px"></div>
			<div class="evf-ts-box" style="width:60%;height:17px;margin-bottom:8px"></div>
			<div class="evf-ts-box" style="width:90%;height:12px;margin-bottom:4px"></div>
			<div class="evf-ts-box" style="width:78%;height:12px;margin-bottom:24px"></div>
			<div class="evf-ts-box" style="width:68px;height:13px"></div>
		</div>
	</div>

	<!-- Template section card -->
	<div class="evf-ts-card">

		<!-- Top bar: search | heading + filter tabs -->
		<div class="evf-ts-row" style="border-bottom:1px solid #e2e8f0">
			<div style="width:256px;min-width:256px;padding:20px;border-right:1px solid #e2e8f0">
				<div class="evf-ts-box" style="height:36px;border-radius:8px"></div>
			</div>
			<div class="evf-ts-row" style="flex:1;justify-content:space-between;padding:20px 28px">
				<div class="evf-ts-box" style="width:190px;height:19px"></div>
				<div class="evf-ts-box" style="width:150px;height:34px;border-radius:8px"></div>
			</div>
		</div>

		<!-- Sidebar + grid -->
		<div class="evf-ts-row" style="align-items:flex-start">

			<!-- Sidebar: categories + CTA card -->
			<div style="width:256px;min-width:256px;padding:20px;padding-top:12px;border-right:1px solid #e2e8f0">
				<div class="evf-ts-box" style="width:72px;height:10px;margin-bottom:12px;border-radius:3px"></div>
				<?php foreach ( array( 78, 62, 88, 55, 72, 60, 70, 52, 65, 58 ) as $w ) : ?>
				<div class="evf-ts-row" style="justify-content:space-between;padding:12px;margin-bottom:2px">
					<div class="evf-ts-box" style="width:<?php echo esc_attr( $w ); ?>%;height:13px;border-radius:3px"></div>
					<div class="evf-ts-box" style="width:20px;height:13px;border-radius:3px"></div>
				</div>
				<?php endforeach; ?>
				<div class="evf-ts-card" style="margin-top:20px;padding:16px;border-radius:12px">
					<div class="evf-ts-box" style="width:68%;height:13px;margin-bottom:6px"></div>
					<div class="evf-ts-box" style="width:90%;height:11px;margin-bottom:4px"></div>
					<div class="evf-ts-box" style="width:80%;height:11px;margin-bottom:12px"></div>
					<div class="evf-ts-box" style="height:36px;border-radius:8px"></div>
				</div>
			</div>

			<!-- Template grid: 2 cols -->
			<div style="flex:1;padding:28px;padding-top:12px">
				<div class="evf-ts-grid">
					<?php for ( $i = 0; $i < 4; $i++ ) : ?>
					<div class="evf-ts-card">
						<div style="background:linear-gradient(129deg,#F3F2F8 2.83%,#F7F5F9 110.96%);padding:20px 20px 0;min-height:140px">
							<div class="evf-ts-box" style="height:120px;border-radius:8px 8px 0 0;opacity:.6"></div>
						</div>
						<div style="padding:20px">
							<div class="evf-ts-box" style="width:62%;height:13px;margin-bottom:6px"></div>
							<div class="evf-ts-box" style="width:90%;height:11px;margin-bottom:4px"></div>
							<div class="evf-ts-box" style="width:76%;height:11px"></div>
						</div>
					</div>
					<?php endfor; ?>
				</div>
			</div>

		</div>
	</div>
</div>

<script>
/* Remove the PHP skeleton styles once React has replaced the content */
( function () {
	var observer = new MutationObserver( function () {
		var styles = document.getElementById( 'evf-template-skeleton-styles' );
		if ( styles && ! document.querySelector( '.evf-ts' ) ) {
			styles.parentNode.removeChild( styles );
			observer.disconnect();
		}
	} );
	var root = document.getElementById( 'evf-templates' );
	if ( root ) observer.observe( root, { childList: true } );
}() );
</script>
