<?php
/**
 * EverestForms Integration Settings
 *
 * @package EverestForms\Admin
 * @version 1.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Integrations', false ) ) {
	return new EVF_Settings_Integrations();
}

/**
 * EVF_Settings_Integrations.
 *
 * Pure renderer — owns no data. Every string, URL, icon, category and feature
 * list is read directly from the integration object supplied by EVF_Integrations.
 *
 * Category resolution priority (most-specific wins):
 *   1. $integration->category  — set on upsell placeholders in EVF_Integrations.
 *   2. everest_forms_integration_categories filter — used by Pro addons via
 *      register_category() hook (backward-compatible).
 *   3. "Other" — final fallback.
 */
class EVF_Settings_Integrations extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'integration';
		$this->label = esc_html__( 'Integration', 'everest-forms' );

		if ( isset( evf()->integrations ) && evf()->integrations->get_integrations() ) {
			parent::__construct();
		}
	}

	// =========================================================================
	// Grouping & navigation helpers
	// =========================================================================

	/**
	 * Returns the filter-based ID → category map.
	 *
	 * This exists solely for backward compatibility with Pro addon classes that
	 * register their category via:
	 *   add_filter( 'everest_forms_integration_categories', [ $this, 'register_category' ] );
	 *
	 * New upsell placeholders carry a `category` property instead and never
	 * touch this map.
	 *
	 * @since  x.x.x
	 * @return array<string, string>
	 */
	protected function get_legacy_category_map() {
		return apply_filters( 'everest_forms_integration_categories', array() );
	}

	/**
	 * Preferred render order for sidebar category labels.
	 *
	 * @since  x.x.x
	 * @return string[]
	 */
	protected function get_category_order() {
		return apply_filters(
			'everest_forms_integration_category_order',
			array(
				esc_html__( 'CRM', 'everest-forms' ),
				esc_html__( 'Email Marketing', 'everest-forms' ),
				esc_html__( 'Cloud Storage', 'everest-forms' ),
				esc_html__( 'SMS Notifications', 'everest-forms' ),
				esc_html__( 'Google Sheets', 'everest-forms' ),
				esc_html__( 'Google Calendar', 'everest-forms' ),
				esc_html__( 'Geolocation', 'everest-forms' ),
				esc_html__( 'OpenAI', 'everest-forms' ),
				esc_html__( 'Other', 'everest-forms' ),
			)
		);
	}

	/**
	 * Groups integrations by category, respecting the preferred display order.
	 *
	 * Category resolution per integration (first match wins):
	 *   1. $integration->category        — upsell placeholders (EVF_Integrations).
	 *   2. Legacy filter map by ID       — Pro addon register_category() hooks.
	 *   3. esc_html__( 'Other', ... )    — safe fallback.
	 *
	 * @since  x.x.x
	 * @param  array $integrations Keyed by integration ID.
	 * @return array<string, object[]>
	 */
	protected function group_integrations_by_category( array $integrations ) {
		$legacy_map = $this->get_legacy_category_map();
		$other      = esc_html__( 'Other', 'everest-forms' );
		$grouped    = array();

		foreach ( $integrations as $id => $integration ) {
			if ( 'clean-talk' === $id ) {
				continue;
			}

			if ( ! empty( $integration->category ) ) {
				// New-style: category declared on the object itself.
				$category = $integration->category;
			} elseif ( isset( $legacy_map[ $id ] ) ) {
				// Old-style: Pro addon registered via everest_forms_integration_categories filter.
				$category = $legacy_map[ $id ];
			} else {
				$category = $other;
			}

			$grouped[ $category ][] = $integration;
		}

		$sorted = array();
		foreach ( $this->get_category_order() as $cat ) {
			if ( isset( $grouped[ $cat ] ) ) {
				$sorted[ $cat ] = $grouped[ $cat ];
				unset( $grouped[ $cat ] );
			}
		}

		return array_merge( $sorted, $grouped );
	}

	/**
	 * Converts a category label to a URL-safe section slug.
	 *
	 * @since  x.x.x
	 * @param  string $category
	 * @return string
	 */
	protected function category_to_slug( $category ) {
		return 'cat-' . sanitize_title( $category );
	}

	/**
	 * Returns the currently active category slug.
	 *
	 * @since  x.x.x
	 * @param  array $grouped
	 * @return string
	 */
	protected function get_active_category_slug( array $grouped ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		if ( ! empty( $section ) && ! str_starts_with( $section, 'cat-' ) ) {
			return $section;
		}

		if ( ! empty( $section ) ) {
			foreach ( array_keys( $grouped ) as $category ) {
				if ( $this->category_to_slug( $category ) === $section ) {
					return $section;
				}
			}
		}

		$first = array_key_first( $grouped );
		return $first ? $this->category_to_slug( $first ) : '';
	}

	// =========================================================================
	// Upsell rendering
	// =========================================================================

	/**
	 * Returns true when an integration is a free-tier upsell placeholder.
	 *
	 * @since  x.x.x
	 * @param  object $integration
	 * @return bool
	 */
	protected function is_upsell_integration( $integration ) {
		return isset( $integration->upgrade ) && 'upgrade' === $integration->upgrade;
	}

	/**
	 * Renders the Pro upsell card.
	 *
	 * Pure template — reads every value off the integration object.
	 * Visual styling lives entirely in evf-upsell-integration.scss.
	 *
	 * @since  x.x.x
	 * @param  object $integration
	 */
	protected function render_upsell_card( $integration ) {
		$title       = $integration->method_title ?? '';
		$description = $integration->method_description ?? '';
		$icon        = $integration->icon ?? '';
		$video_id    = $integration->vedio_id ?? ''; // typo in source data — kept as-is
		$upgrade_url = $integration->upgrade_url ?? 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/';
		$docs_url    = $integration->docs_url ?? 'https://docs.wpeverest.com/everest-forms/docs/';
		$features    = $integration->features ?? array();
		?>
		<div class="evf-upsell-integration-card">
			<div class="evf-upsell-card-header">
				<?php if ( $icon ) : ?>
					<span class="evf-upsell-icon">
						<img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					</span>
				<?php endif; ?>

				<div class="evf-upsell-card-heading">
					<h3><?php echo esc_html( $title ); ?></h3>
				</div>

				<span class="evf-upsell-lock-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2"
						stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
					</svg>
				</span>
			</div>

			<?php if ( ! empty( $features ) ) : ?>
				<hr class="evf-upsell-divider">
				<?php $this->render_upsell_features( $features ); ?>
			<?php endif; ?>

			<div class="evf-upsell-actions">
				<a href="<?php echo esc_url( $upgrade_url ); ?>"
					class="evf-upsell-btn evf-upsell-btn-primary"
					target="_blank" rel="noopener noreferrer">
					<?php
					printf(
						/* translators: %s: integration name */
						esc_html__( 'Unlock %s — Upgrade to Pro', 'everest-forms' ),
						esc_html( $title )
					);
					?>
					<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2"
						stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<line x1="7" y1="17" x2="17" y2="7"/>
						<polyline points="7 7 17 7 17 17"/>
					</svg>
				</a>

				<?php if ( $video_id ) : ?>
					<a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>"
						class="evf-upsell-btn  evf-upsell-upgrade-trigger"
						data-name="<?php echo esc_attr( $title ); ?>"
						data-links="<?php echo esc_attr( $video_id ); ?>"
						data-upgrade-url="<?php echo esc_url( $upgrade_url ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
							fill="currentColor" aria-hidden="true">
							<path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
								10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8
								8-8 8 3.59 8 8-3.59 8-8 8z"/>
						</svg>
						<?php esc_html_e( 'Watch Demo', 'everest-forms' ); ?>
					</a>
				<?php endif; ?>

				<a href="<?php echo esc_url( $docs_url ); ?>"
					class="evf-upsell-btn evf-upsell-btn-ghost"
					target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View Documentation', 'everest-forms' ); ?>
				</a>
			</div>

		</div>
		<?php
	}

	/**
	 * Renders the feature checklist inside the upsell card.
	 *
	 * Accepts the features array straight off the integration object.
	 *
	 * @since  x.x.x
	 * @param  string[] $features
	 */
	protected function render_upsell_features( array $features ) {
		$check_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
			fill="none" stroke="currentColor" stroke-width="2.5"
			stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<polyline points="20 6 9 17 4 12"/>
		</svg>';

		echo '<ul class="evf-upsell-features">';
		foreach ( $features as $feature ) {
			echo '<li>' . $check_icon . esc_html( $feature ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
	}

	// =========================================================================
	// Pro integration rendering
	// =========================================================================

	/**
	 * Ensures a Pro integration's API client is ready before rendering.
	 *
	 * @since  x.x.x
	 * @param  object $integration
	 */
	protected function ensure_client_initialised( $integration ) {
		if (
			property_exists( $integration, 'client' )
			&& empty( $integration->client )
			&& method_exists( $integration, 'get_client' )
		) {
			try {
				$integration->client = $integration->get_client();
			} catch ( \Throwable $e ) {
				// Silently ignore — the integration form handles a missing client.
			}
		}
	}

	/**
	 * Renders the connection form for a real (Pro) integration, or the upsell
	 * card for a free-tier placeholder.
	 *
	 * @since  x.x.x
	 * @param  object $integration
	 */
	protected function render_integration_form( $integration ) {
		if ( $this->is_upsell_integration( $integration ) ) {
			$this->render_upsell_card( $integration );
			return;
		}

		$use_connection_form = false;

		if ( method_exists( $integration, 'output_connection_form' ) ) {
			try {
				$use_connection_form = ( new ReflectionMethod( $integration, 'output_connection_form' ) )->isPublic();
			} catch ( \Throwable $e ) {
				$use_connection_form = false;
			}
		}

		try {
			if ( $use_connection_form ) {
				$this->ensure_client_initialised( $integration );
				$integration->output_connection_form();
			} else {
				$integration->output_integration();
			}
		} catch ( \Throwable $e ) {
			try {
				$integration->output_integration();
			} catch ( \Throwable $e2 ) {
				echo '<p>' . esc_html__( 'This integration could not be loaded. Please update the addon.', 'everest-forms' ) . '</p>';
			}
		}
	}



	/**
	 * Output the settings page.
	 */
	public function output() {
		global $current_section;

		$GLOBALS['hide_save_button'] = true;

		$integrations = evf()->integrations->get_integrations();

		// Legacy: single-integration section without a 'cat-' prefix.
		if (
			! empty( $current_section )
			&& ! str_starts_with( $current_section, 'cat-' )
			&& isset( $integrations[ $current_section ] )
		) {
			$integrations[ $current_section ]->output_integration();
			return;
		}

		$this->output_integrations( $integrations );

		$GLOBALS['hide_save_button'] = true;
	}

	/**
	 * Suppresses the global Save button — each integration renders its own.
	 */
	public function save_button() {}

	/**
	 * Delegates save to whichever Pro integration submitted the form.
	 *
	 * @since x.x.x
	 */
	public function save() {
		global $current_section;

		if ( empty( $current_section ) || ! str_starts_with( $current_section, 'cat-' ) ) {
			return;
		}

		if (
			! isset( $_POST['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'everest-forms-settings' )
		) {
			return;
		}

		$grouped         = $this->group_integrations_by_category( evf()->integrations->get_integrations() );
		$active_category = null;

		foreach ( array_keys( $grouped ) as $category ) {
			if ( $this->category_to_slug( $category ) === $current_section ) {
				$active_category = $category;
				break;
			}
		}

		if ( null === $active_category || empty( $grouped[ $active_category ] ) ) {
			return;
		}

		$submitted_id = isset( $_POST['_evf_integration_id'] )
			? sanitize_text_field( wp_unslash( $_POST['_evf_integration_id'] ) )
			: '';

		foreach ( $grouped[ $active_category ] as $integration ) {
			if ( $this->is_upsell_integration( $integration ) ) {
				continue;
			}
			if ( $submitted_id && $integration->id !== $submitted_id ) {
				continue;
			}
			if ( method_exists( $integration, 'save' ) ) {
				$integration->save();
			}
		}
	}

	/**
	 * Renders the sidebar category navigation.
	 *
	 * @since x.x.x
	 */
	public function output_sections() {
		$grouped     = $this->group_integrations_by_category( evf()->integrations->get_integrations() );
		$active_slug = $this->get_active_category_slug( $grouped );

		if ( empty( $grouped ) ) {
			return;
		}

		echo '<ul class="evf-subsections">';

		foreach ( array_keys( $grouped ) as $category ) {
			$slug = $this->category_to_slug( $category );
			$url  = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => $slug,
				),
				admin_url( 'admin.php' )
			);

			printf(
				'<li><a href="%s" class="%s">%s</a></li>',
				esc_url( $url ),
				$active_slug === $slug ? 'current' : '',
				esc_html( $category )
			);
		}

		echo '</ul>';
	}

	/**
	 * Renders all integrations belonging to the active category.
	 *
	 * @since  x.x.x
	 * @param  array $integrations
	 */
	protected function output_integrations( array $integrations ) {
		$grouped         = $this->group_integrations_by_category( $integrations );
		$active_slug     = $this->get_active_category_slug( $grouped );
		$active_category = array_key_first( $grouped );

		foreach ( array_keys( $grouped ) as $category ) {
			if ( $this->category_to_slug( $category ) === $active_slug ) {
				$active_category = $category;
				break;
			}
		}

		$items = $grouped[ $active_category ] ?? array();
		?>
		<div class="everest-forms-options-header">
			<div class="everest-forms-options-header--top">
				<span class="evf-forms-options-header-header--top-icon">
					<?php echo evf_file_get_contents( '/assets/images/settings-icons/integration.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<h3><?php echo esc_html( $active_category ); ?></h3>
			</div>
		</div>

		<?php
		foreach ( $items as $integration ) :
			$GLOBALS['hide_save_button'] = true;

			if ( $this->is_upsell_integration( $integration ) ) :
				$this->render_upsell_card( $integration );
				continue;
			endif;

			$form_action = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => $active_slug,
				),
				admin_url( 'admin.php' )
			);
			?>

			<?php if ( ! empty( $integration->use_post_form ) ) : ?>

				<form method="post" action="<?php echo esc_url( $form_action ); ?>">
					<input type="hidden" name="_evf_integration_id" value="<?php echo esc_attr( $integration->id ); ?>">

					<?php $this->render_integration_form( $integration ); ?>

					<?php
					if ( empty( $GLOBALS['hide_save_button'] ) ) :
						$save_label = apply_filters( 'everest_forms_setting_save_label', esc_attr__( 'Save Changes', 'everest-forms' ) );
						wp_nonce_field( 'everest-forms-settings' );
						?>
						<p class="submit">
							<button name="save" type="submit"
								class="everest-forms-btn everest-forms-btn-primary everest-forms-save-button"
								value="<?php echo esc_attr( $save_label ); ?>">
								<?php echo esc_html( $save_label ); ?>
							</button>
						</p>
					<?php endif; ?>
				</form>

			<?php else : ?>

				<div class="everest-forms-card">
					<div class="everest-forms-accordion-wrapper">
						<div class="everest-forms-accordion-item">
							<div class="everest-forms-accordion-header">
								<div class="everest-forms-accordion-status">
									<span class="toggle-switch-outer <?php echo esc_attr( $integration->account_status ); ?>"></span>
								</div>
								<span class="everest-forms-accordion-icon">
									<img src="<?php echo esc_url( $integration->icon ); ?>"
										alt="<?php echo esc_attr( $integration->method_title ); ?>">
								</span>
								<h3 class="everest-forms-accordion-title">
									<?php echo esc_html( $integration->method_title ); ?>
								</h3>
								<span class="everest-forms-accordion-toggle">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
										<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor"
											stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</span>
							</div>
							<div class="everest-forms-accordion-content">
								<div class="everest-forms-accordion-content-inner">
									<?php $this->render_integration_form( $integration ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>

			<?php endif; ?>

			<?php
		endforeach;

		$GLOBALS['hide_save_button'] = true;
	}
}

return new EVF_Settings_Integrations();
