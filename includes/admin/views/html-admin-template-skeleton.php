<?php
/**
 * Admin View: Template Page Skeleton
 *
 * PHP-rendered shimmer skeleton shown while templates.min.js downloads.
 * Mirrors the exact React output of App.tsx + Main.tsx pixel-perfectly.
 * React replaces #evf-templates children on mount.
 *
 * @package EverestForms\Admin\Views
 */

defined( 'ABSPATH' ) || exit;
?>

<style id="evf-template-skeleton-styles">
	.evf-ts-box{background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:600px 100%;animation:evf-ts-sh 1.6s ease-in-out infinite;border-radius:4px}
	@keyframes evf-ts-sh{0%{background-position:-600px 0}100%{background-position:600px 0}}
</style>

<!--
	Mirrors:
	App.tsx   → Box margin="20px" bg="white" border borderRadius="16px" overflow="hidden"
	            └─ Flex px="8" py="5" borderBottom (title bar)
	            └─ Main p="32px"
	               ├─ SimpleGrid cols-2 gap="32px" mb="32px"  (CTA cards)
	               └─ Box borderRadius="16px" border overflow  (template section)
-->
<div style="background:#fff;margin:20px;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">

	<!-- Title bar: App.tsx Flex px="8"(32px) py="5"(20px) borderBottom -->
	<div style="display:flex;align-items:center;justify-content:space-between;padding:20px 32px;border-bottom:1px solid #e2e8f0">
		<div class="evf-ts-box" style="width:140px;height:20px"></div>
		<div class="evf-ts-box" style="width:16px;height:16px;border-radius:50%"></div>
	</div>

	<!-- Main content: p="32px" -->
	<div style="padding:32px">

		<!-- CTA cards: SimpleGrid cols-2 spacing="32px" mb="32px" -->
		<div style="display:flex;gap:32px;margin-bottom:32px">

			<!-- AI card: p="32px" borderRadius="16px" border overflow -->
			<div style="flex:1;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;overflow:hidden;position:relative">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
					<div class="evf-ts-box" style="width:48px;height:48px;border-radius:12px"></div>
					<div class="evf-ts-box" style="width:44px;height:22px;border-radius:6px"></div>
				</div>
				<div class="evf-ts-box" style="width:58%;height:18px;margin-bottom:8px"></div>
				<div class="evf-ts-box" style="width:90%;height:12px;margin-bottom:4px"></div>
				<div class="evf-ts-box" style="width:72%;height:12px;margin-bottom:24px"></div>
				<div class="evf-ts-box" style="width:86px;height:13px"></div>
			</div>

			<!-- Scratch card: p="32px" borderRadius="16px" border -->
			<div style="flex:1;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px">
				<div class="evf-ts-box" style="width:48px;height:48px;border-radius:12px;margin-bottom:20px"></div>
				<div class="evf-ts-box" style="width:60%;height:18px;margin-bottom:8px"></div>
				<div class="evf-ts-box" style="width:90%;height:12px;margin-bottom:4px"></div>
				<div class="evf-ts-box" style="width:78%;height:12px;margin-bottom:24px"></div>
				<div class="evf-ts-box" style="width:68px;height:13px"></div>
			</div>
		</div>

		<!-- Template section: bg="white" borderRadius="16px" border overflow -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">

			<!-- Top bar: search (w=256px) | heading + filter tabs -->
			<div style="display:flex;align-items:center;border-bottom:1px solid #e2e8f0">
				<div style="width:256px;min-width:256px;padding:20px;border-right:1px solid #e2e8f0;box-sizing:border-box">
					<div class="evf-ts-box" style="height:36px;border-radius:8px"></div>
				</div>
				<div style="flex:1;display:flex;align-items:center;justify-content:space-between;padding:20px 28px">
					<div class="evf-ts-box" style="width:190px;height:19px"></div>
					<div class="evf-ts-box" style="width:150px;height:34px;border-radius:8px"></div>
				</div>
			</div>

			<!-- Sidebar + grid -->
			<div style="display:flex;align-items:flex-start">

				<!-- Sidebar: w=256px p="20px" pt="12px" -->
				<div style="width:256px;min-width:256px;padding:20px;padding-top:12px;border-right:1px solid #e2e8f0;box-sizing:border-box">

					<!-- CATEGORIES label -->
					<div class="evf-ts-box" style="width:72px;height:10px;margin-bottom:10px;border-radius:3px"></div>

					<!-- Category rows: py="12px" px="12px" matching sidebar item padding -->
					<?php foreach ( array( 78, 62, 88, 55, 72, 60, 70, 52, 65, 58 ) as $w ) : ?>
					<div style="display:flex;justify-content:space-between;align-items:center;padding:12px;margin-bottom:2px">
						<div class="evf-ts-box" style="width:<?php echo esc_attr( $w ); ?>%;height:13px;border-radius:3px"></div>
						<div class="evf-ts-box" style="width:20px;height:13px;border-radius:3px"></div>
					</div>
					<?php endforeach; ?>

					<!-- "Can't find a template?" card: mt="20px" borderRadius="12px" border p="16px" -->
					<div style="margin-top:20px;border:1px solid #e2e8f0;border-radius:12px;padding:16px;background:linear-gradient(to bottom right,rgba(117,69,187,.06),rgba(117,69,187,.02))">
						<div class="evf-ts-box" style="width:68%;height:13px;margin-bottom:6px"></div>
						<div class="evf-ts-box" style="width:90%;height:11px;margin-bottom:4px"></div>
						<div class="evf-ts-box" style="width:80%;height:11px;margin-bottom:12px"></div>
						<div class="evf-ts-box" style="height:36px;border-radius:8px"></div>
					</div>
				</div>

				<!-- Template grid: p="28px" pt="12px" 2-col gap="16px" -->
				<div style="flex:1;padding:28px;padding-top:12px;min-width:0">
					<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">

						<?php for ( $i = 0; $i < 6; $i++ ) : ?>
						<!-- Template card: borderRadius="12px" border overflow -->
						<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;display:flex;flex-direction:column">
							<!-- Image area: gradient bg + white inner wrapper -->
							<div style="background:linear-gradient(129deg,#F3F2F8 2.83%,#F7F5F9 110.96%);padding:20px 20px 0;border-bottom:1px solid #e2e8f0;min-height:160px">
								<div class="evf-ts-box" style="height:140px;border-radius:8px 8px 0 0;opacity:.6"></div>
							</div>
							<!-- Info: p="20px" -->
							<div style="padding:20px;flex:1">
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

	</div><!-- /Main content -->
</div><!-- /Outer card -->

<script>
/* Remove skeleton <style> once React has replaced the content */
( function () {
	var observer = new MutationObserver( function () {
		if ( ! document.querySelector( '.evf-ts-box' ) ) {
			var s = document.getElementById( 'evf-template-skeleton-styles' );
			if ( s ) s.parentNode.removeChild( s );
			observer.disconnect();
		}
	} );
	var root = document.getElementById( 'evf-templates' );
	if ( root ) observer.observe( root, { childList: true, subtree: true } );
}() );
</script>
