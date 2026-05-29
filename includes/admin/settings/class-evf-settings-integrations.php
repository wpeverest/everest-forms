<?php
/**
 * EverestForms Integration Settings
 *
 * @package EverestForms\Admin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Integrations', false ) ) {
	return new EVF_Settings_Integrations();
}

/**
 * EVF_Settings_Integrations.
 */
class EVF_Settings_Integrations extends EVF_Settings_Page {

	/**
	 * Minimum Pro version that supports the new category UI.
	 * Bump when a new Pro release changes the rendering contract.
	 *
	 * @since 1.x.0
	 * @var   string
	 */
	const PRO_NEW_UI_VERSION = '1.9.13';

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

	/**
	 * Returns true when Everest Forms Pro is active.
	 *
	 * @return bool
	 */
	protected function is_pro_active() {
		return defined( 'EFP_VERSION' );
	}

	/**
	 * Returns true when the active Pro version supports the new category UI.
	 * Falls back to true when Pro is not installed (upsell-only mode).
	 *
	 * @return bool
	 */
	protected function pro_supports_new_ui() {
		if ( ! $this->is_pro_active() ) {
			return true;
		}
		return version_compare( EFP_VERSION, self::PRO_NEW_UI_VERSION, '>=' );
	}

	/**
	 * Output the settings page.
	 */
	public function output() {
		global $current_section;

		$GLOBALS['hide_save_button'] = true;
		$integrations                = evf()->integrations->get_integrations();

		if ( ! $this->pro_supports_new_ui() ) {
			if ( '' === $current_section ) {
				$this->output_integrations_legacy( $integrations );
			} elseif ( isset( $integrations[ $current_section ] ) ) {
				$integrations[ $current_section ]->output_integration();
			}
			return;
		}

		// Legacy deep-link: ?section=<integration_id> (no cat- prefix).
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
	 * Suppress the global Save button.
	 */
	public function save_button() {}

	/**
	 * Handle save for the active category.
	 */
	public function save() {
		global $current_section;

		if ( empty( $current_section ) || ! str_starts_with( $current_section, 'cat-' ) ) {
			return;
		}

		if (
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'everest-forms-settings' )
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

	// -------------------------------------------------------------------------
	// Legacy flat-list UI  (Pro < PRO_NEW_UI_VERSION)
	// -------------------------------------------------------------------------

	/**
	 * Renders the original flat integration list.
	 * Identical to the pre-category implementation — nothing changed.
	 *
	 * @param array $integrations
	 */
	protected function output_integrations_legacy( $integrations ) {
		?>
		<div class="everest-forms-options-header">
			<div class="everest-forms-options-header--top">
				<span class="evf-forms-options-header-header--top-icon">
					<?php echo evf_file_get_contents( '/assets/images/settings-icons/integration.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<h3><?php esc_html_e( 'Integrations', 'everest-forms' ); ?></h3>
			</div>
		</div>
		<div class="everest-forms-integrations-connection">
			<?php foreach ( $integrations as $integration ) : ?>
				<div class="everest-forms-integrations"
					data-action="<?php echo esc_attr( isset( $integration->upgrade ) ? $integration->upgrade : '' ); ?>"
					data-links="<?php echo esc_attr( isset( $integration->vedio_id ) ? $integration->vedio_id : '' ); ?>">
					<div class="integration-header-info">
						<div class="integration-status">
							<span class="toggle-switch-outer <?php echo esc_attr( $integration->account_status ); ?>"></span>
						</div>
						<div class="integration-detail">
							<figure class="logo">
								<img src="<?php echo esc_url( $integration->icon ); ?>"
									alt="<?php echo esc_attr( $integration->method_title ); ?>">
							</figure>
							<div class="integration-info">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ) ); ?>">
									<h3><?php echo esc_html( $integration->method_title ); ?></h3>
								</a>
								<p><?php echo esc_html( $integration->method_description ); ?></p>
							</div>
						</div>
					</div>
					<div class="integartion-action">
						<a class="integration-setup"
							href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ) ); ?>">
							<span class="evf-icon evf-icon-setting-cog"></span>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// New category sidebar UI  (Pro >= PRO_NEW_UI_VERSION, or no Pro)
	// -------------------------------------------------------------------------

	/**
	 * @return array<string, string>
	 */
	protected function get_legacy_category_map() {
		return apply_filters( 'everest_forms_integration_categories', array() );
	}

	/**
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
				$category = $integration->category;
			} elseif ( isset( $legacy_map[ $id ] ) ) {
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
	 * @param  string $category
	 * @return string
	 */
	protected function category_to_slug( $category ) {
		return 'cat-' . sanitize_title( $category );
	}

	/**
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

	/**
	 * @param  object[] $integrations
	 * @return bool
	 */
	protected function category_is_all_upsell( array $integrations ) {
		foreach ( $integrations as $integration ) {
			if ( ! $this->is_upsell_integration( $integration ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param  object $integration
	 * @return bool
	 */
	protected function is_upsell_integration( $integration ) {
		return isset( $integration->upgrade ) && 'upgrade' === $integration->upgrade;
	}

	/**
	 * Renders the sidebar category navigation.
	 */
	public function output_sections() {
		if ( ! $this->pro_supports_new_ui() ) {
			return;
		}

		$grouped     = $this->group_integrations_by_category( evf()->integrations->get_integrations() );
		$active_slug = $this->get_active_category_slug( $grouped );

		if ( empty( $grouped ) ) {
			return;
		}

		$lock_icon = '<svg class="evf-sidebar-upsell-icon" width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-left:5px;flex-shrink:0;" aria-hidden="true">
			<path d="M0 2C0 0.895431 0.895431 0 2 0H18C19.1046 0 20 0.895431 20 2V18C20 19.1046 19.1046 20 18 20H2C0.895431 20 0 19.1046 0 18V2Z" fill="#FF8C39"/>
			<path d="M10 4.1666L13.5 13.4999H6.5L10 4.1666Z" fill="#EFEFEF"/>
			<path d="M14.9994 15.833H4.99939V14.167H14.9994V15.833ZM15.0004 13.5H5.00037L4.16638 6.5L10.0004 11.3125L15.8334 6.5L15.0004 13.5Z" fill="white"/>
		</svg>';

		echo '<ul class="evf-subsections">';
		foreach ( $grouped as $category => $items ) {
			$slug      = $this->category_to_slug( $category );
			$show_lock = ! $this->is_pro_active() && $this->category_is_all_upsell( $items );
			$url       = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => $slug,
				),
				admin_url( 'admin.php' )
			);

			printf(
				'<li><a href="%s" class="%s">%s%s</a></li>',
				esc_url( $url ),
				esc_attr( $active_slug === $slug ? 'current' : '' ),
				esc_html( $category ),
				$show_lock ? $lock_icon : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
		echo '</ul>';
	}

	/**
	 * Initialises the API client on a Pro integration if not already set.
	 *
	 * @param object $integration
	 */
	protected function ensure_client_initialised( $integration ) {
		if (
			property_exists( $integration, 'client' ) &&
			empty( $integration->client ) &&
			method_exists( $integration, 'get_client' )
		) {
			try {
				$integration->client = $integration->get_client();
			} catch ( \Throwable $e ) {
				// Integration form handles a missing client gracefully.
			}
		}
	}

	/**
	 * Renders the inner connection form for a Pro integration.
	 * Only called in new-UI path where output_connection_form() exists.
	 *
	 * @param object $integration
	 */
	protected function render_integration_form( $integration ) {
		if ( $this->is_upsell_integration( $integration ) ) {
			$this->render_upsell_card( $integration );
			return;
		}

		$this->ensure_client_initialised( $integration );

		try {
			$integration->output_connection_form();
		} catch ( \Throwable $e ) {
			echo '<p>' . esc_html__( 'This integration could not be loaded. Please update the addon.', 'everest-forms' ) . '</p>';
		}
	}

	/**
	 * Renders the upsell card for a free-tier placeholder integration.
	 *
	 * @param object $integration
	 */
	protected function render_upsell_card( $integration ) {
		$title       = $integration->method_title ?? '';
		$icon        = $integration->icon ?? '';
		$video_id    = $integration->vedio_id ?? '';
		$upgrade_url = $integration->upgrade_url ?? 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/';
		$docs_url    = $integration->docs_url ?? 'https://docs.everestforms.net/docs/';
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
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-left:5px;flex-shrink:0;" aria-hidden="true">
						<path d="M0 2C0 0.895431 0.895431 0 2 0H18C19.1046 0 20 0.895431 20 2V18C20 19.1046 19.1046 20 18 20H2C0.895431 20 0 19.1046 0 18V2Z" fill="#FF8C39"></path>
						<path d="M10 4.1666L13.5 13.4999H6.5L10 4.1666Z" fill="#EFEFEF"></path>
						<path d="M14.9994 15.833H4.99939V14.167H14.9994V15.833ZM15.0004 13.5H5.00037L4.16638 6.5L10.0004 11.3125L15.8334 6.5L15.0004 13.5Z" fill="white"></path>
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
					<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<line x1="7" y1="17" x2="17" y2="7"/>
						<polyline points="7 7 17 7 17 17"/>
					</svg>
				</a>

				<?php if ( $video_id ) : ?>
					<a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>"
						class="evf-upsell-btn evf-upsell-upgrade-trigger"
						data-name="<?php echo esc_attr( $title ); ?>"
						data-links="<?php echo esc_attr( $video_id ); ?>"
						data-upgrade-url="<?php echo esc_url( $upgrade_url ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
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
	 * @param string[] $features
	 */
	protected function render_upsell_features( array $features ) {
		$check_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';

		echo '<ul class="evf-upsell-features">';
		foreach ( $features as $feature ) {
			echo '<li>' . $check_icon . esc_html( $feature ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
	}

	/**
	 * Renders all integrations for the active category.
	 *
	 * @param array $integrations
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
										<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
