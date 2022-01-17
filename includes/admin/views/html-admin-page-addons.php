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
<div class="wrap evf_addons_wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Everest Forms Add-ons', 'everest-forms' ); ?></h1>

	<?php if ( apply_filters( 'everest_forms_refresh_addons', true ) ) : ?>
		<a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh Add-ons', 'everest-forms' ); ?></a>
	<?php endif; ?>

	<hr class="wp-header-end">
	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter add-ons list', 'everest-forms' ); ?></h2>

	<?php if ( $sections ) : ?>
		<div class="wp-filter">
			<ul class="filter-links">
				<?php foreach ( $sections as $section ) : ?>
					<li class="<?php echo esc_attr( $section->slug ); ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-addons&section=' . esc_attr( $section->slug ) ) ); ?>"<?php echo $current_section === $section->slug ? ' class="current" aria-current="page"' : ''; ?>><?php echo esc_html( $section->label ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<form class="search-form search-plugins hidden" method="get">
				<input type="hidden" name="page" value="evf-addons">
				<?php $page_section = ( isset( $_GET['section'] ) && '_featured' !== sanitize_text_field( wp_unslash( $_GET['section'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_all'; // phpcs:ignore WordPress.Security.NonceVerification ?>
				<input type="hidden" name="section" value="<?php echo esc_attr( $page_section ); ?>">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Search Add-ons', 'everest-forms' ); ?></span>
					<input type="search" name="s" value="" class="wp-filter-search hidden" placeholder="<?php esc_attr_e( 'Search Add-ons...', 'everest-forms' ); ?>" aria-describedby="live-search-desc" />
				</label>
				<input type="submit" id="search-submit" class="button hide-if-js" value="<?php esc_attr_e( 'Search Add-ons', 'everest-forms' ); ?>">
			</form>
		</div>

		<br class="clear">
		<p class="refresh">
			<?php
			/* translators: %s: Refresh URI */
			printf( esc_html__( 'Make your forms even more robust with our premium addons. Missing any addons? Click the %1$sRefresh Add-ons%2$s button above.', 'everest-forms' ), '<a href="' . esc_url( $refresh_url ) . '">', '</a>' );
			?>
		</p>

		<?php if ( '_featured' !== $current_section && $addons ) : ?>
			<form id="extension-filter" method="post">
				<div class="wp-list-table widefat extension-install">
					<h2 class="screen-reader-text"><?php esc_html_e( 'Add-ons list', 'everest-forms' ); ?></h2>

					<div class="the-list">
						<?php foreach ( $addons as $addon ) : ?>
							<div class="plugin-card plugin-card-<?php echo esc_attr( $addon->slug ); ?>">
								<a href="<?php echo esc_url( $addon->link ); ?>">
									<div class="plugin-card-top">
										<div class="name column-name">
											<h3 class="plugin-name">
												<?php echo esc_html( $addon->title ); ?>
												<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/' . $addon->image ); ?>" class="plugin-icon" alt="" />
											</h3>
										</div>
										<div class="desc column-description">
											<p class="plugin-desc"><?php echo esc_html( $addon->excerpt ); ?></p>
										</div>
									</div>
								</a>
								<div class="plugin-card-bottom">
									<?php if ( in_array( str_replace( '-lifetime', '', $license_plan ), $addon->plan, true ) ) : ?>
										<div class="status column-status">
											<strong><?php esc_html_e( 'Status:', 'everest-forms' ); ?></strong>
											<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
												<span class="status-label status-active"><?php esc_html_e( 'Activated', 'everest-forms' ); ?></span>
											<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
												<span class="status-label status-inactive"><?php esc_html_e( 'Inactive', 'everest-forms' ); ?></span>
											<?php else : ?>
												<span class="status-label status-install-now"><?php esc_html_e( 'Not Installed', 'everest-forms' ); ?></span>
											<?php endif; ?>
										</div>
										<div class="action-buttons">
											<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
												<?php
													/* translators: %s: Add-on title */
													$aria_label  = sprintf( esc_html__( 'Deactivate %s now', 'everest-forms' ), $addon->title );
													$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
													$url         = wp_nonce_url(
														add_query_arg(
															array(
																'page'   => 'evf-addons',
																'action' => 'deactivate',
																'plugin' => $plugin_file,
															),
															admin_url( 'admin.php' )
														),
														'deactivate-plugin_' . $plugin_file
													);
												?>
												<a class="button button-secondary deactivate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Deactivate', 'everest-forms' ); ?></a>
											<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
												<?php
													/* translators: %s: Add-on title */
													$aria_label  = sprintf( esc_html__( 'Activate %s now', 'everest-forms' ), $addon->title );
													$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
													$url         = wp_nonce_url(
														add_query_arg(
															array(
																'page'   => 'evf-addons',
																'action' => 'activate',
																'plugin' => $plugin_file,
															),
															admin_url( 'admin.php' )
														),
														'activate-plugin_' . $plugin_file
													);
												?>
												<a class="button button-primary activate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Activate', 'everest-forms' ); ?></a>
											<?php else : ?>
												<?php
												/* translators: %s: Add-on title */
												$aria_label = sprintf( esc_html__( 'Install %s now', 'everest-forms' ), $addon->title );
												?>
												<a href="#" class="button install-now" data-slug="<?php echo esc_attr( $addon->slug ); ?>" data-name="<?php echo esc_attr( $addon->name ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Install Addon', 'everest-forms' ); ?></a>
											<?php endif; ?>
										</div>
									<?php else : ?>
										<div class="action-buttons upgrade-plan">
											<a class="button upgrade-now" href="https://wpeverest.com/wordpress-plugins/everest-forms/pricing/?utm_source=addons-page&utm_medium=upgrade-button&utm_campaign=evf-upgrade-to-pro" target="_blank"><?php esc_html_e( 'Upgrade Plan', 'everest-forms' ); ?></a>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</form>
		<?php endif; ?>
	<?php else : ?>
		<p>
		<?php
		/* translators: %s: Add-ons Link */
		printf( esc_html__( 'Our catalog of Everest Forms Add-ons/Extensions can be found on WPEverest.com here: <a href="%s">Everest Forms Extensions Catalog</a>', 'everest-forms' ), 'https://wpeverest.com/wordpress-plugins/everest-forms/' );
		?>
		</p>
	<?php endif; ?>
</div>
<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
