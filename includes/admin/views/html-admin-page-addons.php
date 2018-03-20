<?php
/**
 * Admin View: Page - Addons
 *
 * @var string $view
 * @var object $addons
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap everest-forms evf_addons_wrap">
	<h1 class="wp-heading-inline"><?php _e( 'Everest Forms Add-ons', 'everest-forms' ); ?></h1>

	<?php if ( apply_filters( 'everest_forms_refresh_addons', true ) ) : ?>
		<a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh Add-ons', 'everest-forms' ); ?></a>
	<?php endif; ?>

	<hr class="wp-header-end">
	<h2 class="screen-reader-text"><?php _e( 'Filter Everest Forms Extensions', 'everest-forms' ); ?></h1>

	<?php if ( $sections ) : ?>
		<ul class="subsubsub">
			<?php foreach ( $sections as $section ) : ?>
				<li class="<?php echo esc_attr( $section->slug ); ?>">
					<a href="<?php echo admin_url( 'admin.php?page=evf-addons&section=' . esc_attr( $section->slug ) ); ?>"<?php echo $current_section === $section->slug ? ' class="current" aria-current="page"' : ''; ?>><?php echo esc_html( $section->label ); ?></a>
					<?php echo ( end( $section_keys ) !== $section->slug ) ? ' |' : ''; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( ! empty( $_GET['search'] ) ) : ?>
			<h2 class="search-form-title" ><?php printf( __( 'Showing search results for: %s', 'everest-forms' ), '<strong>' . esc_html( $_GET['search'] ) . '</strong>' ); ?></h2>
		<?php endif; ?>

		<form class="search-form hidden" method="GET">
			<button type="submit">
				<span class="dashicons dashicons-search"></span>
			</button>
			<input type="hidden" name="page" value="evf-addons">
			<?php $page_section = ( isset( $_GET['section'] ) && '_featured' !== $_GET['section'] ) ? $_GET['section'] : '_all'; ?>
			<input type="hidden" name="section" value="<?php echo esc_attr( $page_section ); ?>">
			<input type="text" name="search" value="<?php echo esc_attr( isset( $_GET['search'] ) ? $_GET['search'] : '' ); ?>" placeholder="<?php _e( 'Enter a search term and press enter', 'everest-forms' ); ?>">
		</form>
		<?php if ( '_featured' !== $current_section && $addons ) : ?>
			<ul class="products">
			<?php foreach ( $addons as $addon ) : ?>
				<li class="product">

				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php else : ?>
		<p><?php printf( __( 'Our catalog of Everest Forms Add-ons/Extensions can be found on WPEverest.com here: <a href="%s">Everest Forms Extensions Catalog</a>', 'everest-forms' ), 'https://wpeverest.com/wordpress-plugins/everest-forms/' ); ?></p>
	<?php endif; ?>
</div>
