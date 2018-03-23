<?php
/**
 * Admin View: Page - Addons
 *
 * @var string $view
 * @var object $addons
 *
 * @package EverestForms
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap everest-forms evf_addons_wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Everest Forms Add-ons', 'everest-forms' ); ?></h1>

	<?php if ( apply_filters( 'everest_forms_refresh_addons', true ) ) : ?>
		<a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh Add-ons', 'everest-forms' ); ?></a>
	<?php endif; ?>

	<hr class="wp-header-end">
	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter add-ons list', 'everest-forms' ); ?></h1>

	<?php if ( $sections ) : ?>
		<ul class="subsubsub">
			<?php foreach ( $sections as $section ) : ?>
				<li class="<?php echo esc_attr( $section->slug ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-addons&section=' . esc_attr( $section->slug ) ) ); ?>"<?php echo $current_section === $section->slug ? ' class="current" aria-current="page"' : ''; ?>><?php echo esc_html( $section->label ); ?></a>
					<?php echo ( end( $section_keys ) !== $section->slug ) ? ' |' : ''; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( ! empty( $_GET['search'] ) ) : ?>
			<h2 class="search-form-title" ><?php printf( esc_html__( 'Showing search results for: %s', 'everest-forms' ), '<strong>' . esc_html( $_GET['search'] ) . '</strong>' ); ?></h2>
		<?php endif; ?>

		<form class="search-form" method="GET">
			<button type="submit">
				<span class="dashicons dashicons-search"></span>
			</button>
			<input type="hidden" name="page" value="evf-addons">
			<?php $page_section = ( isset( $_GET['section'] ) && '_featured' !== $_GET['section'] ) ? $_GET['section'] : '_all'; ?>
			<input type="hidden" name="section" value="<?php echo esc_attr( $page_section ); ?>">
			<input type="text" name="search" value="<?php echo esc_attr( isset( $_GET['search'] ) ? $_GET['search'] : '' ); ?>" placeholder="<?php _e( 'Enter a search term and press enter', 'everest-forms' ); ?>">
		</form>
		<?php if ( '_featured' !== $current_section && $addons ) : ?>
			<div class="wp-list-table widefat plugin-install">
				<h2 class="screen-reader-text"><?php esc_html_e( 'Add-ons list', 'everest-forms' ); ?></h1>

				<div class="the-list">
					<?php foreach ( $addons as $addon ) : ?>
						<div class="plugin-card plugin-card-<?php echo esc_attr( $addon->slug ); ?>">
							<div class="plugin-card-top">
								<div class="name column-name">
									<h3 class="plugin-name">
										<?php echo esc_html( $addon->title ); ?>
										<img src="<?php echo esc_url( $addon->image ); ?>" class="plugin-icon" alt="" />
									</h3>
								</div>
								<div class="desc column-description">
									<p class="plugin-desc"><?php echo esc_html( $addon->excerpt ); ?></p>
								</div>
							</div>
							<div class="plugin-card-bottom">
								<?php if ( $license_plan ) : ?>
									<div class="status column-status">
										<strong><?php esc_html_e( 'Status:', 'everest-forms' ); ?></strong>
										<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
											<span class="status-label status-active"><?php esc_html_e( 'Active', 'everest-forms' ); ?></span>
										<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ): ?>
											<span class="status-label status-inactive"><?php esc_html_e( 'Inactive', 'everest-forms' ); ?></span>
										<?php else: ?>
											<span class="status-label install-now"><?php esc_html_e( 'Not Installed', 'everest-forms' ); ?></span>
										<?php endif; ?>
									</div>
									<div class="action-buttons">
										<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
											<?php
												$plugin_file = $addon->slug . '/' . $addon->slug . '.php';
												$url = wp_nonce_url( add_query_arg( array(
													'page'   => 'evf-addons',
													'action' => 'deactivate',
													'plugin' => $plugin_file,
												), admin_url( 'admin.php' ) ), 'deactivate-plugin_' . $plugin_file );
											?>
											<a class="button button-secondary deactivate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( sprintf( __( 'Deactivate %s now', 'everest-forms' ), $addon->title ) ); ?>"><?php esc_html_e( 'Deactivate', 'everest-forms' ); ?></a>
										<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ): ?>
											<?php
												$plugin_file = $addon->slug . '/' . $addon->slug . '.php';
												$url = wp_nonce_url( add_query_arg( array(
													'page'   => 'evf-addons',
													'action' => 'activate',
													'plugin' => $plugin_file,
												), admin_url( 'admin.php' ) ), 'activate-plugin_' . $plugin_file );
											?>
											<a class="button button-primary activate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( sprintf( __( 'Activate %s now', 'everest-forms' ), $addon->title ) ); ?>"><?php esc_html_e( 'Activate', 'everest-forms' ); ?></a>
										<?php else: ?>
											<button class="button button-secondary install-now"><?php esc_html_e( 'Install Addon', 'everest-forms'); ?></button>
										<?php endif; ?>
									</div>
								<?php else: ?>
									<div class="action-buttons upgrade-plan">
										<a class="button button-hero upgrade-now" href="https://wpeverest.com/upgrade-plan"><?php esc_html_e( 'Upgrade Plan', 'everest-forms' ); ?></a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p><?php printf( __( 'Our catalog of Everest Forms Add-ons/Extensions can be found on WPEverest.com here: <a href="%s">Everest Forms Extensions Catalog</a>', 'everest-forms' ), 'https://wpeverest.com/wordpress-plugins/everest-forms/' ); ?></p>
	<?php endif; ?>
</div>
