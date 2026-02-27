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

	/**
	 * Returns the map of integration ID to category label.
	 *
	 * @since  x.x.x
	 * @return array [ integration_id => category_label ]
	 */
	protected function get_category_map() {
		$map = array(
			'google-sheets' => esc_html__( 'Google Sheets', 'everest-forms' ),
			'dropbox'       => esc_html__( 'Cloud Storage', 'everest-forms' ),
			'google-drive'  => esc_html__( 'Cloud Storage', 'everest-forms' ),
		);

		return apply_filters( 'everest_forms_integration_categories', $map );
	}

	/**
	 * Returns the preferred display order for category labels in the sidebar.
	 *
	 * @since  x.x.x
	 * @return array Ordered list of category label strings.
	 */
	protected function get_category_order() {
		$order = array(
			esc_html__( 'CRM', 'everest-forms' ),
			esc_html__( 'Email Marketing', 'everest-forms' ),
			esc_html__( 'Cloud Storage', 'everest-forms' ),
			esc_html__( 'SMS Notifications', 'everest-forms' ),
			esc_html__( 'Google Sheets', 'everest-forms' ),
			esc_html__( 'Google Calendar', 'everest-forms' ),
			esc_html__( 'Geolocation', 'everest-forms' ),
			esc_html__( 'OpenAI', 'everest-forms' ),
			esc_html__( 'Other', 'everest-forms' ),
		);

		return apply_filters( 'everest_forms_integration_category_order', $order );
	}

	/**
	 * Groups integrations by category respecting the preferred display order.
	 *
	 * @since  x.x.x
	 * @param  array $integrations All loaded integration objects keyed by ID.
	 * @return array [ category_label => [ integration_object, ... ] ]
	 */
	protected function group_integrations_by_category( $integrations ) {
		$category_map = $this->get_category_map();
		$grouped      = array();

		foreach ( $integrations as $id => $integration ) {
			if ( 'clean-talk' === $id ) {
				continue;
			}
			$category               = isset( $category_map[ $id ] )
				? $category_map[ $id ]
				: esc_html__( 'Other', 'everest-forms' );
			$grouped[ $category ][] = $integration;
		}

		$sorted = array();

		foreach ( $this->get_category_order() as $cat ) {
			if ( isset( $grouped[ $cat ] ) ) {
				$sorted[ $cat ] = $grouped[ $cat ];
				unset( $grouped[ $cat ] );
			}
		}

		foreach ( $grouped as $cat => $items ) {
			$sorted[ $cat ] = $items;
		}

		return $sorted;
	}

	/**
	 * Converts a category label into a URL-safe section slug.
	 *
	 * @since  x.x.x
	 * @param  string $category Category label.
	 * @return string
	 */
	protected function category_to_slug( $category ) {
		return 'cat-' . sanitize_title( $category );
	}

	/**
	 * Returns the currently active category slug.
	 *
	 * @since  x.x.x
	 * @param  array $grouped Grouped integrations keyed by category label.
	 * @return string
	 */
	protected function get_active_category_slug( $grouped ) {
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

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
	 * Ensures an integration's client property is initialised before rendering.
	 *
	 * @since x.x.x
	 * @param object $integration Integration instance.
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
				// Silently ignore.
			}
		}
	}

	/**
	 * Safely renders an integration's inline form inside the accordion body.
	 *
	 * @since x.x.x
	 * @param object $integration Integration instance.
	 */
	protected function render_integration_form( $integration ) {
		$use_connection_form = false;

		if ( method_exists( $integration, 'output_connection_form' ) ) {
			try {
				$reflection          = new ReflectionMethod( $integration, 'output_connection_form' );
				$use_connection_form = $reflection->isPublic();
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
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$GLOBALS['hide_save_button'] = true;

		$integrations = evf()->integrations->get_integrations();

		if ( ! empty( $current_section )
			&& ! str_starts_with( $current_section, 'cat-' )
			&& isset( $integrations[ $current_section ] )
		) {
			$integrations[ $current_section ]->output_integration();
			return;
		}

		$this->output_integrations( $integrations );

		// Re-enforce after output in case any integration set it to false.
		$GLOBALS['hide_save_button'] = true;
	}

	/**
	 * Suppress the global page-level Save button entirely.
	 * Each integration renders its own Save button if needed.
	 */
	public function save_button() {
		// Do nothing.
	}

	/**
	 * Handle saving for integrations.
	 *
	 * @since x.x.x
	 */
	public function save() {
		global $current_section;

		if ( empty( $current_section ) || ! str_starts_with( $current_section, 'cat-' ) ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'everest-forms-settings' ) ) {
			return;
		}

		$integrations = evf()->integrations->get_integrations();
		$grouped      = $this->group_integrations_by_category( $integrations );

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
			if ( $submitted_id && $integration->id !== $submitted_id ) {
				continue;
			}
			if ( method_exists( $integration, 'save' ) ) {
				$integration->save();
			}
		}
	}

	/**
	 * Output the sidebar subsection navigation as category items.
	 *
	 * @since x.x.x
	 */
	public function output_sections() {
		$integrations = evf()->integrations->get_integrations();
		$grouped      = $this->group_integrations_by_category( $integrations );
		$active_slug  = $this->get_active_category_slug( $grouped );

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

			echo '<li><a href="' . esc_url( $url ) . '" class="' . ( $active_slug === $slug ? 'current' : '' ) . '">'
				. esc_html( $category )
				. '</a></li>';
		}

		echo '</ul>';
	}

	/**
	 * Renders integrations in the active category.
	 *
	 * @since x.x.x
	 * @param array $integrations All loaded integration objects.
	 */
	protected function output_integrations( $integrations ) {
		$grouped         = $this->group_integrations_by_category( $integrations );
		$active_slug     = $this->get_active_category_slug( $grouped );
		$active_category = null;

		foreach ( array_keys( $grouped ) as $category ) {
			if ( $this->category_to_slug( $category ) === $active_slug ) {
				$active_category = $category;
				break;
			}
		}

		if ( null === $active_category ) {
			$active_category = array_key_first( $grouped );
		}

		$items = isset( $grouped[ $active_category ] ) ? $grouped[ $active_category ] : array();
		?>
	<div class="everest-forms-options-header">
		<div class="everest-forms-options-header--top">
            <span class="evf-forms-options-header-header--top-icon"><?php echo evf_file_get_contents( '/assets/images/settings-icons/integration.svg' ); // phpcs:ignore ?></span>
			<h3><?php echo esc_html( $active_category ); ?></h3>
		</div>
	</div>

		<?php foreach ( $items as $integration ) : ?>
			<?php
			$GLOBALS['hide_save_button'] = true;

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
				<?php
				$this->render_integration_form( $integration );

				if ( empty( $GLOBALS['hide_save_button'] ) ) :
						$everest_forms_setting_save_label = apply_filters( 'everest_forms_setting_save_label', esc_attr__( 'Save Changes', 'everest-forms' ) );
					wp_nonce_field( 'everest-forms-settings' );
					?>
					<p class="submit">
						<button
							name="save"
							class="everest-forms-btn everest-forms-btn-primary everest-forms-save-button"
							type="submit"
							value="<?php echo esc_attr( $everest_forms_setting_save_label ); ?>"
								>
						<?php echo esc_html( $everest_forms_setting_save_label ); ?>
						</button>
					</p>
				<?php endif; ?>
			</form>

		<?php else : ?>
			<?php error_log( print_r( $integration, true ) ); ?>
			<div class="everest-forms-card">
			<div class="everest-forms-accordion-wrapper">
				<div class="everest-forms-accordion-item ">
					<div class="everest-forms-accordion-header">
						<div class="everest-forms-accordion-status">
							<span class="toggle-switch-outer <?php echo esc_attr( $integration->account_status ); ?>"></span>
						</div>
						<span class="everest-forms-accordion-icon">
							<img src="<?php echo esc_url( $integration->icon ); ?>" alt="<?php echo esc_attr( $integration->method_title ); ?>">
						</span>
						<h3 class="everest-forms-accordion-title">
							<?php echo esc_html( $integration->method_title ); ?>
						</h3>
						<span class="everest-forms-accordion-toggle">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
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
		?>
		<?php
	}
}

return new EVF_Settings_Integrations();
