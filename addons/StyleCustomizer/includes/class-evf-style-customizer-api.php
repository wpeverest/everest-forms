<?php
/**
 * EverestForms Style Customizer
 *
 * @package EverestForms_Style_Customizer\API
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\ValueConverter;
/**
 * Style Customizer API.
 */
class EVF_Style_Customizer_API {

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Form data.
	 *
	 * @var arary Array of form data.
	 */
	public $form_data;

	/**
	 * Settings defaults.
	 *
	 * @var array Array of settings defaults.
	 */
	public $defaults = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$raw_referer   = wp_parse_args( wp_parse_url( wp_get_raw_referer(), PHP_URL_QUERY ) );
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification

		if ( wp_get_raw_referer() ) {
			$this->form_id = isset( $raw_referer['form_id'] ) ? absint( $raw_referer['form_id'] ) : $this->form_id;
		}

		// Form data.
		$this->form_data = EVF()->form->get( $this->form_id, array( 'content_only' => true ) );

		// Load customizer elements for EVF forms only.
		if ( isset( $raw_referer['evf-style-customizer'] ) || isset( $_GET['evf-style-customizer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Change publish button text to save.
			add_filter( 'gettext', array( $this, 'change_publish_button' ), 10, 2 );

			// Register customize panel, sections and controls.
			add_action( 'customize_register', array( $this, 'customize_register' ), 11 );

			// Remove unrelated panel, sections, components, etc.
			add_filter( 'customize_section_active', array( $this, 'section_filter' ), 10, 2 );
			add_filter( 'customize_panel_active', array( $this, 'panel_filter' ), 10, 2 );
			add_filter( 'customize_loaded_components', array( $this, 'remove_core_components' ), 60 );

			// Customize form preview URL.
			add_action( 'customize_controls_init', array( $this, 'form_preview_init' ) );

			// Enqueue customizer scripts.
			add_action( 'customize_preview_init', array( $this, 'enqueue_customize_preview_scripts' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customize_control_scripts' ) );

			// Compile SASS to load on frontend.
			add_action( 'customize_save_after', array( $this, 'save_after' ) );
			add_filter( 'customize_save_response', array( $this, 'modify_customized_data' ), 10, 2 );
		}

		// Delete specific styles on form delete.
		add_action( 'deleted_post', array( $this, 'delete_styles' ) );

		// Create style during the form import, creation and duplication.
		add_action( 'everest_forms_save_form', array( $this, 'create_styles' ), 10, 4 );
		add_action( 'everest_forms_import_form', array( $this, 'create_styles' ), 10, 4 );
		add_action( 'everest_forms_create_form', array( $this, 'create_styles' ), 10, 4 );
	}



	/**
	 * Change publish button text to save.
	 *
	 * @param string $translation  Translated text.
	 * @param string $text         Text to translate.
	 */
	public function change_publish_button( $translation, $text ) {
		switch ( $text ) {
			case 'Publish':
				$translation = esc_html__( 'Save', 'everest-forms' );
				break;
			case 'Published':
				$translation = esc_html__( 'Saved', 'everest-forms' );
				break;
		}

		return $translation;
	}

	/**
	 * Show only our style settings in the preview.
	 *
	 * @param bool                 $active  Whether the Customizer section is active.
	 * @param WP_Customize_Section $section WP_Customize_Section instance.
	 */
	public function section_filter( $active, $section ) {
		if ( in_array( $section->id, array( 'everest_forms_templates', 'custom_css' ), true ) || in_array( $section->id, array_keys( apply_filters( 'everest_forms_style_customizer_sections', array() ) ), true ) ) {
			return $active;
		}

		return false;
	}

	/**
	 * Show only our style settings in the preview.
	 *
	 * @param bool               $active  Whether the Customizer panel is active.
	 * @param WP_Customize_Panel $panel WP_Customize_Section instance.
	 */
	public function panel_filter( $active, $panel ) {
		if ( in_array( $panel->id, array( 'everest_forms_templates', 'custom_css' ), true ) || in_array( $panel->id, array_keys( apply_filters( 'everest_forms_style_customizer_panels', array() ) ), true ) ) {
			return $active;
		}

		return false;
	}

	/**
	 * Remove any unwanted core components.
	 *
	 * @param  array $components List of core components to load.
	 * @return array (Maybe) Modified components list.
	 */
	public function remove_core_components( $components ) {
		$core_components = array( 'nav_menus', 'widgets' );

		if ( ! empty( $components ) ) {
			foreach ( $components as $component_key => $component ) {
				if ( in_array( $component, $core_components, true ) ) {
					unset( $components[ $component_key ] );
				}
			}
		}

		return $components;
	}

	/**
	 * Register customize panels, sections and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	public function customize_register( $wp_customize ) {
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-templates-section.php';

		// Remove core partials.
		$wp_customize->selective_refresh->remove_partial( 'blogname' );
		$wp_customize->selective_refresh->remove_partial( 'blogdescription' );
		$wp_customize->selective_refresh->remove_partial( 'custom_header' );

		// Register a customize section type.
		$wp_customize->register_section_type( 'EVF_Customize_Templates_Section' );

		$form_data       = EVF()->form->get( $this->form_id, array( 'content_only' => true ) );
		$template_id     = 'everest_forms_styles[' . $this->form_id . '][template]';
		$templates_list  = self::get_templates_list();
		$active_template = isset( $templates_list[ $form_data['settings']['layout_class'] ] ) ? $templates_list[ $form_data['settings']['layout_class'] ] : 'Default Template';

		$this->add_customize_setting(
			$wp_customize,
			$template_id,
			array(
				'default'           => $form_data['settings']['layout_class'],
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$this->defaults['template'] = $wp_customize->get_setting( $template_id )->default;

		/* Templates (@todo controls are loaded via ajax) */
		$wp_customize->add_section(
			new EVF_Customize_Templates_Section(
				$wp_customize,
				'everest_forms_templates',
				array(
					'title'       => $active_template,
					'description' => (
						'<p>' . esc_html__( 'Looking for a template? You can browse our templates, import and preview templates, then activate them right here.', 'everest-forms' ) . '</p>' .
						'<p>' . esc_html__( 'While previewing a new template, you can continue to tailor things like form styles and custom css, and explore template-specific options.', 'everest-forms' ) . '</p>'
					),
					'capability'  => 'manage_everest_forms',
					'priority'    => 0,
				)
			)
		);

		// Include customize control until we fetch via AJAX.
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-image-radio-control.php';

		$wp_customize->add_control(
			new EVF_Customize_Image_Radio_Control(
				$wp_customize,
				'everest_forms_styles[' . $this->form_id . '][template]',
				array(
					'label'         => esc_html__( 'Templates', 'everest-forms' ),
					'description'   => esc_html__( 'Choose your desire templates.', 'everest-forms' ),
					'section'       => 'everest_forms_templates',
					'capability'    => 'manage_everest_forms',
					'setting'       => 'everest_forms_styles[' . $this->form_id . '][template]',
					'priority'      => 0,
					'display_label' => true,
					'choices'       => $this->get_templates(),
				)
			)
		);

		$this->add_customize_panels( $wp_customize );
		$this->add_customize_sections( $wp_customize );
		$this->add_customize_controls( $wp_customize );
	}

	/**
	 * Returns EVF form style templates.
	 *
	 * @return array
	 */
	public static function get_templates() {

		$styles = get_option( 'evf_style_templates' );

		$default_styles_raw = evf_file_get_contents( 'addons/StyleCustomizer/assets/wp-json/default-templates.json' );

		if ( $default_styles_raw ) {
			$stored_styles  = json_decode( $styles, true );
			$default_styles = json_decode( $default_styles_raw, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
			} else {
				if ( ! $stored_styles || ! is_array( $stored_styles ) ) {
					update_option( 'evf_style_templates', $default_styles_raw );
				} else {
					foreach ( $default_styles as $key => $value ) {
						if ( ! isset( $stored_styles[ $key ] ) ) {
							$stored_styles[ $key ] = $value;
						}
					}

					update_option( 'evf_style_templates', json_encode( $stored_styles ) );
				}
			}
		}

		return apply_filters( 'evf_style_templates', json_decode( $styles ) );
	}



	public static function get_templates_list() {
		$templates = (array) self::get_templates();

		$templates_list = array();

		foreach ( $templates as $template_slug => $template ) {
			$templates_list[ $template_slug ] = $template->name;
		}

		return $templates_list;
	}

	/**
	 * Add a customize panels.
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	public function add_customize_panels( $wp_customize ) {
		$panels = apply_filters( 'everest_forms_style_customizer_panels', array() );

		if ( ! empty( $panels ) ) {
			foreach ( $panels as $panel_id => $panel_data ) {
				$wp_customize->add_panel(
					$panel_id,
					array(
						'title'       => isset( $panel_data['title'] ) ? $panel_data['title'] : '',
						'description' => isset( $panel_data['description'] ) ? $panel_data['description'] : '',
						'priority'    => isset( $panel_data['priority'] ) ? (int) $panel_data['priority'] : 160,
						'capability'  => isset( $panel_data['capability'] ) ? $panel_data['capability'] : 'manage_everest_forms',
					)
				);
			}
		}
	}

	/**
	 * Add a customize sections.
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	public function add_customize_sections( $wp_customize ) {
		$sections = apply_filters( 'everest_forms_style_customizer_sections', array() );
		if ( ! empty( $sections ) ) {
			foreach ( $sections as $section_id => $section_data ) {
				$section_args = array(
					'title'              => isset( $section_data['title'] ) ? $section_data['title'] : '',
					'description'        => isset( $section_data['description'] ) ? $section_data['description'] : '',
					'panel'              => isset( $section_data['panel'] ) ? $section_data['panel'] : '',
					'priority'           => isset( $section_data['priority'] ) ? (int) $section_data['priority'] : 160,
					'capability'         => isset( $section_data['capability'] ) ? $section_data['capability'] : 'manage_everest_forms',
					'description_hidden' => isset( $section_data['description_hidden'] ) ? $section_data['description_hidden'] : false,
				);

				// Add a core or custom customize sections.
				if ( isset( $section_data['type'] ) && class_exists( $section_data['type'] ) ) {
					$wp_customize->register_section_type( $section_data['type'] );
					$wp_customize->add_section( new $section_data['type']( $wp_customize, $section_id, $section_args ) );
				} else {
					$wp_customize->add_section( $section_id, $section_args );
				}
			}
		}
	}

	/**
	 * Add a customize setting.
	 *
	 * @param WP_Customize_Manager        $wp_customize WP_Customize_Manager instance.
	 * @param WP_Customize_Setting|string $setting_id   Customize Setting object, or ID.
	 * @param array                       $setting_args {
	 *  Optional. Array of properties for the new WP_Customize_Setting. Default empty array.
	 *
	 *  @type string       $type                  Type of the setting. Default 'option'.
	 *  @type string       $capability            Capability required for the setting. Default 'manage_everest_forms'
	 *  @type string|array $theme_supports        Theme features required to support the panel. Default is none.
	 *  @type string       $default               Default value for the setting. Default is empty string.
	 *  @type string       $transport             Options for rendering the live preview of changes in Customizer.
	 *                                            Using 'refresh' makes the change visible by reloading the whole preview.
	 *                                            Using 'postMessage' allows a custom JavaScript to handle live changes.
	 * @link https://developer.wordpress.org/themes/customize-api
	 *                                            Default is 'postMessage'
	 *  @type callable     $sanitize_callback     Callback to filter a Customize setting value in un-slashed form.
	 * }
	 */
	private function add_customize_setting( $wp_customize, $setting_id, $setting_args = array() ) {
		if ( ! empty( $setting_args ) ) {
			$wp_customize->add_setting(
				$setting_id,
				array(
					'type'              => isset( $setting_args['type'] ) ? $setting_args['type'] : 'option',
					'capability'        => isset( $setting_args['capability'] ) ? $setting_args['capability'] : 'manage_everest_forms',
					'theme_supports'    => isset( $setting_args['theme_supports'] ) ? $setting_args['theme_supports'] : '',
					'default'           => isset( $setting_args['default'] ) ? $setting_args['default'] : '',
					'transport'         => isset( $setting_args['transport'] ) ? $setting_args['transport'] : 'postMessage',
					'sanitize_callback' => isset( $setting_args['sanitize_callback'] ) ? $setting_args['sanitize_callback'] : '',
				)
			);
		}
	}

	/**
	 * Add a customize settings and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	public function add_customize_controls( $wp_customize ) {
		$controls = apply_filters( 'everest_forms_style_customizer_controls', array(), $this );
		// Include custom customize controls.
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-color-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-toggle-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-slider-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-select2-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-dimension-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-image-radio-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-image-checkbox-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-background-image-control.php';
		require_once dirname( __FILE__ ) . '/customize/class-evf-customize-color-palette-control.php';

		if ( ! empty( $controls ) ) {
			foreach ( $controls as $type => $controls_data ) {
				foreach ( $controls_data as $control_key => $control_data ) {

					$control_id = 'everest_forms_styles[' . $this->form_id . '][' . $type . '][' . $control_key . ']';

					// Control args.
					$control_args = array(
						'label'       => isset( $control_data['control']['label'] ) ? $control_data['control']['label'] : '',
						'description' => isset( $control_data['control']['description'] ) ? $control_data['control']['description'] : '',
						'section'     => isset( $control_data['control']['section'] ) ? $control_data['control']['section'] : '',
						'priority'    => isset( $control_data['control']['priority'] ) ? (int) $control_data['control']['priority'] : 160,
						'capability'  => isset( $control_data['control']['capability'] ) ? $control_data['control']['capability'] : 'manage_everest_forms',
						'choices'     => isset( $control_data['control']['choices'] ) ? $control_data['control']['choices'] : array(),
						'input_attrs' => isset( $control_data['control']['input_attrs'] ) ? $control_data['control']['input_attrs'] : array(),
					);

					// Add a customize settings.
					if ( ! empty( $control_data['setting'] ) ) {
						$control_args['setting'] = $control_id;

						$this->add_customize_setting( $wp_customize, $control_id, $control_data['setting'] );
						$this->defaults[ $type ][ $control_key ] = $wp_customize->get_setting( $control_id )->default;
					} elseif ( ! empty( $control_data['settings'] ) ) {
						foreach ( $control_data['settings'] as $setting_key => $setting_args ) {
							$setting_id = 'everest_forms_styles[' . $this->form_id . '][' . $type . '][' . $setting_key . ']';
							$this->add_customize_setting( $wp_customize, $setting_id, $setting_args );
							$this->defaults[ $type ][ $setting_key ] = $wp_customize->get_setting( $setting_id )->default;
						}

						// Control settings args handling.
						if ( ! empty( $control_data['control']['settings'] ) ) {
							foreach ( $control_data['control']['settings'] as $key => $setting ) {
								$control_args['settings'][ $key ] = 'everest_forms_styles[' . $this->form_id . '][' . $type . '][' . $setting . ']';
							}
						}
					}

					// Custom control args handling.
					if ( ! empty( $control_data['control']['custom_args'] ) && is_array( $control_data['control']['custom_args'] ) ) {
						foreach ( array_keys( $control_data['control']['custom_args'] ) as $custom_arg ) {
							$control_args[ $custom_arg ] = $control_data['control']['custom_args'][ $custom_arg ];
						}
					}

					// Add a core or custom customize controls.
					if ( class_exists( $control_data['control']['type'] ) ) {
						$wp_customize->register_control_type( $control_data['control']['type'] );
						$wp_customize->add_control( new $control_data['control']['type']( $wp_customize, $control_id, $control_args ) );
					} elseif ( isset( $control_data['control']['type'] ) ) {
						$control_args['type'] = $control_data['control']['type'];
						$wp_customize->add_control( $control_id, $control_args );
					}
				}
			}
		}
	}

	/**
	 * Callback for validating a background setting value.
	 *
	 * @since  1.0.0
	 *
	 * @param string               $value Repeat value.
	 * @param WP_Customize_Setting $setting Setting.
	 *
	 * @return string|WP_Error Background value or validation error.
	 */
	public function _sanitize_background_setting( $value, $setting ) {

		if ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_repeat]' === $setting->id ) {
			if ( ! in_array( $value, array( 'repeat-x', 'repeat-y', 'repeat', 'no-repeat' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background repeat.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_attachment]' === $setting->id ) {
			if ( ! in_array( $value, array( 'fixed', 'scroll' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background attachment.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_position_x]' === $setting->id ) {
			if ( ! in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background position X.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_position_y]' === $setting->id ) {
			if ( ! in_array( $value, array( 'top', 'center', 'bottom' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background position Y.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_size]' === $setting->id ) {
			if ( ! in_array( $value, array( 'auto', 'contain', 'cover' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background size.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_preset]' === $setting->id ) {
			if ( ! in_array( $value, array( 'default', 'fill', 'fit', 'repeat', 'custom' ), true ) ) {
				return new WP_Error( 'invalid_value', esc_html__( 'Invalid value for background size.', 'everest-forms' ) );
			}
		} elseif ( 'everest_forms_styles[' . $this->form_id . '][form_container][background_image]' === $setting->id ) {
			$value = empty( $value ) ? '' : esc_url_raw( $value );
		} else {
			return new WP_Error( 'unrecognized_setting', esc_html__( 'Unrecognized background setting.', 'everest-forms' ) );
		}
		return $value;
	}

	/**
	 * Preview form in customizer.
	 */
	public function form_preview_init() {
		global $wp_customize;

		if ( isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id = absint( $_GET['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification

			$wp_customize->set_preview_url(
				add_query_arg(
					array(
						'form_id'              => $form_id,
						'evf_preview'          => true,
						'evf-style-customizer' => true,
					),
					$wp_customize->get_preview_url()
				)
			);
		}
	}

	/**
	 * Enqueues the customize preview scripts.
	 */
	public function enqueue_customize_preview_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue preview scripts.
		wp_enqueue_script( 'everest-forms-customize-preview', plugins_url( "assets/js/admin/customize-preview{$suffix}.js", EVF_PLUGIN_FILE ), array( 'jquery', 'customize-preview' ), EVF_VERSION, true );
		wp_localize_script(
			'everest-forms-customize-preview',
			'_evfCustomizePreviewL10n',
			array(
				'form_id'            => $this->form_id,
				'notices'            => array(
					'required' => esc_html__( 'This field is required.', 'everest-forms' ),
					'error'    => esc_html__( 'This is a sample form error message for customize puropse only.', 'everest-forms' ),
					'success'  => esc_html__( 'This is a sample form success message for customize puropse only.', 'everest-forms' ),
				),
				'templates'          => self::get_templates_list(),
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'load_fonts_locally' => get_option( 'everest_forms_load_fonts_locally', 'no' ),
			)
		);
	}

	/**
	 * Enqueues the customize control scripts.
	 */
	public function enqueue_customize_control_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register control scripts.
		wp_register_style( 'selectWoo', EVF()->plugin_url() . '/assets/css/select2.css', array(), EVF_VERSION );
		wp_register_script( 'selectWoo', EVF()->plugin_url() . "/assets/js/selectWoo/selectWoo.full{$suffix}.js", array( 'jquery' ), '1.0.4', true );
		wp_register_style( 'jquery-confirm', evf()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );
		wp_register_script( 'jquery-confirm', evf()->plugin_url() . "/assets/js/jquery-confirm/jquery-confirm{$suffix}.js", array( 'jquery' ), '3.3.0', true );
		wp_register_script( 'evf-upgrade', evf()->plugin_url() . '/assets/js/admin/upgrade.js', array( 'jquery', 'jquery-confirm' ), EVF_VERSION, false );
		wp_enqueue_script( 'jquery-confirm' );
		wp_enqueue_style( 'jquery-confirm' );
		wp_enqueue_script( 'evf-upgrade' );
		// Enqueue controls scripts.
		wp_enqueue_style( 'everest-forms-customize-controls', plugins_url( 'assets/css/customize-controls.css', EVF_PLUGIN_FILE ), array(), EVF_VERSION );
		wp_enqueue_script( 'everest-forms-customize-controls', plugins_url( "assets/js/admin/customize-controls{$suffix}.js", EVF_PLUGIN_FILE ), array( 'jquery' ), EVF_VERSION, true );
		wp_localize_script(
			'everest-forms-customize-controls',
			'_evfCustomizeControlsL10n',
			array(
				'form_id'                        => $this->form_id,
				'panelTitle'                     => esc_html__( 'Everest Forms &ndash; Styles', 'everest-forms' ),
				'panelDescription'               => esc_html__( 'Everest Forms &ndash; Styles Customizer allows you to preview changes and customize any form elements.', 'everest-forms' ),
				'templates'                      => self::get_templates(),
				'is_pro'                         => defined( 'EFP_PLUGIN_FILE' ) ? true : false,
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'save_nonce'                     => wp_create_nonce( 'save_template' ),
				'delete_nonce'                   => wp_create_nonce( 'delete_template' ),
				'color_palette_nonce'            => wp_create_nonce( 'color_palette' ),
				'color_palette_edit_title'       => esc_html__( 'Are you sure you want to edit this color palette?', 'everest-forms' ),
				'color_palette_edit_description' => esc_html__( 'Your previous custom color palette will be lost!', 'everest-forms' ),
			)
		);

		$custom_background = get_theme_support( 'custom-background' );
		wp_localize_script(
			'everest-forms-customize-controls',
			'_wpCustomizeBackground',
			array(
				'defaults' => ! empty( $custom_background[0] ) ? $custom_background[0] : array(),
				'nonces'   => array(
					'add' => wp_create_nonce( 'background-add' ),
				),
			)
		);

		wp_localize_script(
			'evf-upgrade',
			'evf_upgrade',
			array(
				'upgrade_title'   => esc_html__( 'Upgrade to Pro Version to Gain Access', 'everest-forms' ),
				'upgrade_message' => esc_html__( 'Unfortunately, this feature is available on the Pro version. Please upgrade to unlock it.', 'everest-forms' ),
				'upgrade_button'  => esc_html__( 'Upgrade to PRO', 'everest-forms' ),
				'i18n_ok'         => esc_html__( 'OK', 'everest-forms' ),
				'upgrade_url'     => apply_filters( 'everest_forms_upgrade_url', 'https://everestforms.net/pricing/?utm_source=style-customizer&utm_medium=premium-style-customizer&utm_campaign=' . evf()->utm_campaign ),

			)
		);

	}

	public function modify_customized_data( $response, $wp_customize ) {

		$customized_data   = get_option( 'everest_forms_styles', array() );
		$matched_color_key = null;
		foreach ( $response['setting_validities'] as $key => $validity ) {
			if ( preg_match( '/everest_forms_styles\[(\d+)\]\[color_palette\]\[(color_\d+)\]/', $key, $matches ) ) {
				$matched_color_key = $matches[2];
				$form_id           = $matches[1];
				break;
			}
		}

		if ( $matched_color_key !== null && isset( $customized_data[ $form_id ]['color_palette'][ $matched_color_key ] ) ) {
			$customized_data[ $form_id ]['color_palette'] = array(
				$matched_color_key => $customized_data[ $form_id ]['color_palette'][ $matched_color_key ],
			);
			update_option( 'everest_forms_styles', $customized_data );
		}

		return $response;
	}



	/**
	 * Save the styles data.
	 */
	public function save_after() {
		if ( ! isset( $_REQUEST['customized'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$save       = false;
		$customized = json_decode( wp_unslash( $_REQUEST['customized'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitiz
		// Check if valid to compile and update css.
		foreach ( array_keys( $customized ) as $setting_id ) {
			if ( false !== strpos( $setting_id, 'everest_forms_styles[' . $this->form_id . ']' ) ) {
				$save = true;
				break;
			}
		}

		if ( $save ) {
			$upload_dir = wp_upload_dir();
			$custom_css = $this->compile_scss();
			$files      = array(
				array(
					'base'    => $upload_dir['basedir'] . '/everest_forms_styles',
					'file'    => 'index.html',
					'content' => '',
				),
				array(
					'base'    => $upload_dir['basedir'] . '/everest_forms_styles',
					'file'    => 'everest-forms-' . absint( $this->form_id ) . '.css',
					'content' => $custom_css,
				),
			);

			// Update form data.
			$this->update_form_data();

			// Create files and prevent hotlinking.
			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! is_wp_error( $file['content'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					}
				}
			}
		}
	}

	/**
	 * Compile SCSS to CSS style during form creation.
	 *
	 * @param int   $form_id      Form ID.
	 * @param array $form_data    Form data.
	 * @param array $data         Data args.
	 * @param bool  $style_needed True if style is needed.
	 */
	public function create_styles( $form_id, $form_data, $data = array(), $style_needed = true ) {
		$defaults = array();
		$controls = apply_filters( 'everest_forms_style_customizer_controls', array(), $this );

		// Return if style not needed.
		if ( ! $style_needed ) {
			return;
		}

		if ( ! empty( $controls ) ) {
			foreach ( $controls as $type => $controls_data ) {
				foreach ( $controls_data as $control_key => $control_data ) {
					// Add a customize settings.
					if ( ! empty( $control_data['setting'] ) ) {
						$defaults[ $type ][ $control_key ] = $control_data['setting']['default'];
					} elseif ( ! empty( $control_data['settings'] ) ) {
						foreach ( $control_data['settings'] as $setting_key => $setting_args ) {
							$defaults[ $type ][ $setting_key ] = $setting_args['default'];
						}
					}
				}
			}
		}

		if ( ! empty( $defaults ) ) {
			$upload_dir = wp_upload_dir();
			$custom_css = $this->compile_scss( $form_id, $defaults );
			$files      = array(
				array(
					'base'    => $upload_dir['basedir'] . '/everest_forms_styles',
					'file'    => 'index.html',
					'content' => '',
				),
				array(
					'base'    => $upload_dir['basedir'] . '/everest_forms_styles',
					'file'    => 'everest-forms-' . absint( $form_id ) . '.css',
					'content' => $custom_css,
				),
			);

			// Create files and prevent hotlinking.
			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! is_wp_error( $file['content'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					}
				}
			}
		}
	}

	/**
	 * Compile SCSS to CSS styles.
	 *
	 * @param int   $form_id  Form ID.
	 * @param array $defaults Form styles defaults.
	 *
	 * @return string|WP_Error The css data, or WP_Error object on failure.
	 */
	protected function compile_scss( $form_id = 0, $defaults = array() ) {

		$form_id  = 0 !== $form_id ? $form_id : $this->form_id;
		$defaults = ! empty( $defaults ) ? $defaults : $this->defaults;

		ob_start();
		include 'views/scss.php';
		$scss = ob_get_clean();

		try {
			$compiler = new Compiler();
			$compiler->setOutputStyle( OutputStyle::COMPRESSED );
			$parsed_form_id = ValueConverter::parseValue( $form_id );
			$compiler->addVariables( array( 'form_id' => $parsed_form_id ) );
			$compiler->addImportPath( plugin_dir_path( EVF_PLUGIN_FILE ) . 'assets/css/bourbon/' );
			$compiled_css = $compiler->compileString( trim( $scss ) )->getCss();
			return $compiled_css;
		} catch ( Exception $e ) {
			$logger = evf_get_logger();
			$logger->warning( $e->getMessage(), array( 'source' => 'scssphp' ) );
		}

		return new WP_Error( 'could-not-compile-scss', esc_html__( 'ScssPhp: Unable to compile content', 'everest-forms' ) );
	}

	/**
	 * Update form layout class data.
	 */
	public function update_form_data() {
		$styles    = get_option( 'everest_forms_styles', array() );
		$form_data = EVF()->form->get( $this->form_id, array( 'content_only' => true ) );

		if ( isset( $form_data['settings']['layout_class'], $styles[ $this->form_id ]['template'] ) ) {
			$form_data['settings']['layout_class'] = $styles[ $this->form_id ]['template'];

			// Update form data.
			EVF()->form->update( $this->form_id, $form_data );
		}
	}

	/**
	 * Remove specific form styles.
	 *
	 * When form is deleted then it also deletes its styles data and css file.
	 *
	 * @param int $postid Post ID.
	 */
	public function delete_styles( $postid ) {
		$upload_dir    = wp_upload_dir( null, false );
		$style_options = get_option( 'everest_forms_styles' );

		// Delete specific form styles data.
		if ( isset( $style_options[ $postid ] ) ) {
			unset( $style_options[ $postid ] );
			update_option( 'everest_forms_styles', $style_options );

			// Delete the custom css file.
			wp_delete_file( $upload_dir['basedir'] . '/everest_forms_styles/everest-forms-' . absint( $postid ) . '.css' );
		}
	}
}

new EVF_Style_Customizer_API();
