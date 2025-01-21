<?php
/**
 * EverestForms Style Customizer setup
 *
 * @package EverestForms_Style_Customizer
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main EverestForms Style Customizer Class.
 *
 * @class EverestForms_Style_Customizer
 */
if ( ! class_exists( 'EverestForms_Style_Customizer' ) ) {

	class EverestForms_Style_Customizer {

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 */
		private function __construct() {

				$this->configs();
				$this->includes();

			// Hooks.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'everest_forms_shortcode_scripts', array( $this, 'enqueue_shortcode_scripts' ) );
			add_action( 'everest_forms_builder_content_fields', array( $this, 'output_form_designer' ) );
			if ( defined( 'EFP_PLUGIN_FILE' ) ) {
				add_action( 'everest_form_elemntor_style', array( $this, 'evf_elementor' ), 10, 1 );
			}
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}


		/**
		 * Configs.
		 */
		private function configs() {
			require_once __DIR__ . '/configs/evf-style-customizer-form-wrapper-configs.php';
			require_once __DIR__ . '/configs/evf-style-customizer-color-palette.php';
			require_once __DIR__ . '/configs/evf-style-customizer-submission-message-configs.php';
		}

		/**
		 * Includes.
		 */
		private function includes() {
			require_once __DIR__ . '/functions.php';
			require_once __DIR__ . '/class-evf-style-customizer-api.php';
			require_once __DIR__ . '/class-evf-style-customizer-ajax.php';
		}

		/**
		 * Get the customizer url.
		 */
		private function get_customizer_url() {
			$form_id        = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification
			$customizer_url = esc_url_raw(
				add_query_arg(
					array(
						'evf-style-customizer' => true,
						'form_id'              => $form_id,
						'return'               => rawurlencode(
							add_query_arg(
								array(
									'page'    => 'evf-builder',
									'tab'     => 'fields',
									'form_id' => $form_id,
								),
								admin_url( 'admin.php' )
							)
						),
					),
					admin_url( 'customize.php' )
				)
			);

			return $customizer_url;
		}


		/**
		 * Enqueue scripts.
		 */
		public function admin_enqueue_scripts() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Register admin scripts.
			wp_register_style( 'everest-forms-customize-admin', plugins_url( 'assets/css/customize-admin.css', EVF_PLUGIN_FILE ), array(), EVF_VERSION );

			// Add RTL support for admin styles.
			wp_style_add_data( 'everest-forms-customize-admin', 'rtl', 'replace' );

			// Admin styles for EVF pages only.
			if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
				wp_enqueue_style( 'everest-forms-customize-admin' );
			}
		}

		/**
		 * Enqueue shortcode scripts.
		 *
		 * @param array $atts Shortcode Attributes.
		 */
		public function enqueue_shortcode_scripts( $atts ) {
			$form_id       = absint( $atts['id'] );
			$upload_dir    = wp_upload_dir( null, false );
			$style_options = get_option( 'everest_forms_styles' );

			// Enqueue shortcode styles.
			if ( file_exists( trailingslashit( $upload_dir['basedir'] ) . 'everest_forms_styles/everest-forms-' . $form_id . '.css' ) ) {
				wp_enqueue_style( 'everest-forms-style-' . $form_id, esc_url_raw( set_url_scheme( $upload_dir['baseurl'] . '/everest_forms_styles/everest-forms-' . $form_id . '.css' ) ), array(), filemtime( trailingslashit( $upload_dir['basedir'] ) . 'everest_forms_styles/everest-forms-' . $form_id . '.css' ), 'all' );
			}

			if (
				isset( $style_options[ $form_id ]['font']['show_theme_font'], $style_options[ $form_id ]['font']['font_family'] ) && isset( $style_options[ $form_id ]['font']['show_theme_font'] )
				&& 0 === absint( $style_options[ $form_id ]['font']['show_theme_font'] ) &&
				'' !== $style_options[ $form_id ]['font']['font_family']
			) {
				$font_family = $style_options[ $form_id ]['font']['font_family'];
				evfsc_enqueue_fonts( $font_family );
			}

		}

		/**
		 * Output form designer.
		 */
		public function output_form_designer() {
			?>
		<a href="<?php echo esc_url( $this->get_customizer_url() ); ?>" class="everest-forms-designer-icon tips" title="<?php esc_attr_e( 'Form Designer', 'everest-forms' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.85 9.08l-4.07 4.06a.57.57 0 0 0-.06.81l.06.05 1.37 1.37.1.1c.09-.09.16-.18.25-.25a.78.78 0 0 1 1.06 0q1.55 1.53 3.07 3.07a.79.79 0 0 1 0 1.11q-.58.59-1.17 1.17a.8.8 0 0 1-1.13 0l-3-3-.1-.12a.8.8 0 0 1 .09-1l.19-.19-.1-.1-1.76-1.76a1.14 1.14 0 0 1 0-1.61l4.07-4a.63.63 0 0 0 0-.89c-.48-.44-.94-.93-1.41-1.4a.57.57 0 0 0-.81 0l-.33.31.07.08.54.54a.71.71 0 0 1 0 1l-6.58 6.5a.71.71 0 0 1-.81.2.78.78 0 0 1-.27-.13l-2-2a.71.71 0 0 1 0-1l6.51-6.51a.72.72 0 0 1 1-.09l.09.09.49.49.15.02.7-.71a1.14 1.14 0 0 1 1.61 0l2.17 2.17a1.14 1.14 0 0 1 0 1.61z"/></svg>
		</a>
			<?php
		}


		/**
		 * Register controls for Pro.
		 *
		 * @param class $widget gives the widget class name.
		 *
		 * @since 1.1.2
		 */
		public function evf_elementor( $widget ) {

			$widget->start_controls_section(
				'everest_form_container',
				array(
					'label' => esc_html__( 'Form Wrapper', 'everest-forms' ),
					// 'tab'   => \Elementor\::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'form_cont_padding',
				array(
					'label'      => esc_html__( 'Form Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms ' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_responsive_control(
				'form_cont_margin',
				array(
					'label'      => esc_html__( ' Form Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms ' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->start_controls_tabs( 'tabs_form_container' );
			$widget->start_controls_tab(
				'form_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);
			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'form_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms ',
				)
			);
			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'form_border',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms ',
				)
			);

			$widget->add_responsive_control(
				'form_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms ' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'form_shadow',
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'form_hover',
				array(
					'label' => esc_html__( 'Hover', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'form_bg_hover',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms:hover',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'form_border_hover',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms:hover',
				)
			);

			$widget->add_responsive_control(
				'form_border_radius_hover',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .everest-forms:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'form_shadow_hover',
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms:hover',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();
			$widget->start_controls_section(
				'everest_form_label',
				array(
					'label' => esc_html__( 'Field Label', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'label_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .evf-field-label .evf-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_responsive_control(
				'label_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .evf-field-label .evf-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'label_typography',
					'selector' => '{{WRAPPER}} .everest-forms .evf-field-label .evf-label',
				)
			);

			$widget->add_control(
				'label_color',
				array(
					'label'     => esc_html__( 'Label', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .evf-field-label .evf-label' => 'color: {{VALUE}}',
						'separator' => 'after',
					),
				)
			);

			$widget->add_control(
				'inline_help_label_color',
				array(
					'label'     => esc_html__( 'Inline/Description Text', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .form-row .everest-forms-field-label-inline,{{WRAPPER}} .everest-forms .form-row .evf-field-description' => 'color: {{VALUE}}',
						'separator' => 'after',
					),
				)
			);

			$widget->add_control(
				'req_symbol_color',
				array(
					'label'     => esc_html__( 'Required Symbol', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row label .required' => 'color: {{VALUE}} !important',
					),
				)
			);

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_style_input',
				array(
					'label' => esc_html__( 'Input Fields', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'input_typography',
					'selector' => '{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select',
				)
			);

			$widget->add_control(
				'input_placeholder_color',
				array(
					'label'     => esc_html__( 'Placeholder Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input::-webkit-input-placeholder,
					{{WRAPPER}} .everest-forms  email::-webkit-input-placeholder,
					{{WRAPPER}} .everest-forms  number::-webkit-input-placeholder,
					{{WRAPPER}} .everest-forms  select::-webkit-input-placeholder,
					{{WRAPPER}} .everest-forms  url::-webkit-input-placeholder' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_responsive_control(
				'input_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_responsive_control(
				'input_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="password"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->start_controls_tabs( 'tabs_input_field_style' );
			$widget->start_controls_tab(
				'tab_input_field_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'input_field_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'input_field_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_input_field_focus',
				array(
					'label' => esc_html__( 'Focus', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'input_field_focus_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input[type="text"]:focus,
				{{WRAPPER}} .everest-forms input[type="email"]:focus,
				{{WRAPPER}} .everest-forms input[type="number"]:focus,
				{{WRAPPER}} .everest-forms input[type="url"]:focus,
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select:focus' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'input_field_focus_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms input[type="text"]:focus,
				{{WRAPPER}} .everest-forms input[type="email"]:focus,
				{{WRAPPER}} .everest-forms input[type="number"]:focus,
				{{WRAPPER}} .everest-forms input[type="url"]:focus,
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select:focus',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'input_border_options',
				array(
					'label'     => esc_html__( 'Border Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->add_control(
				'box_border',
				array(
					'label'     => esc_html__( 'Box Border', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SWITCHER,
					'label_on'  => esc_html__( 'Show', 'everest-forms' ),
					'label_off' => esc_html__( 'Hide', 'everest-forms' ),
					'default'   => 'no',
				)
			);

			$widget->add_control(
				'border_style',
				array(
					'label'     => esc_html__( 'Border Style', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => 'solid',
					'options'   => array(
						'solid'  => esc_html__( 'Solid', 'everest-forms' ),
						'dotted' => esc_html__( 'Dotted', 'everest-forms' ),
						'dashed' => esc_html__( 'Dashed', 'everest-forms' ),
						'groove' => esc_html__( 'Groove', 'everest-forms' ),
					),
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'border-style: {{VALUE}};',
					),
					'condition' => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'box_border_width',
				array(
					'label'      => esc_html__( 'Border Width', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'default'    => array(
						'top'    => 1,
						'right'  => 1,
						'bottom' => 1,
						'left'   => 1,
					),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->start_controls_tabs( 'tabs_border_style' );

			$widget->start_controls_tab(
				'tab_border_normal',
				array(
					'label'     => esc_html__( 'Normal', 'everest-forms' ),
					'condition' => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'box_border_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '#252525',
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_border_hover',
				array(
					'label'     => esc_html__( 'Focus', 'everest-forms' ),
					'condition' => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'box_border_hover_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .everest-forms input[type="text"]:focus,
				{{WRAPPER}} .everest-forms input[type="email"]:focus,
				{{WRAPPER}} .everest-forms input[type="number"]:focus,
				{{WRAPPER}} .everest-forms input[type="url"]:focus,
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select:focus' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'border_hover_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms input[type="text"]:focus,
				{{WRAPPER}} .everest-forms input[type="email"]:focus,
				{{WRAPPER}} .everest-forms input[type="number"]:focus,
				{{WRAPPER}} .everest-forms input[type="url"]:focus,
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'shadow_options',
				array(
					'label'     => esc_html__( 'Box Shadow Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->start_controls_tabs( 'tabs_shadow_style' );

			$widget->start_controls_tab(
				'tab_shadow_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'box_shadow',
					'selector' => '{{WRAPPER}} .everest-forms input[type="text"],
				{{WRAPPER}} .everest-forms input[type="email"],
				{{WRAPPER}} .everest-forms input[type="number"],
				{{WRAPPER}} .everest-forms input[type="url"],
				{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row select',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_shadow_hover',
				array(
					'label' => esc_html__( 'Focus', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'box_active_shadow',
					'selector' => '{{WRAPPER}} .everest-forms input[type="text"]:focus,
				{{WRAPPER}} .everest-forms input[type="email"]:focus,
				{{WRAPPER}} .everest-forms input[type="number"]:focus,
				{{WRAPPER}} .everest-forms input[type="url"]:focus,
				{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row select:focus',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_style_textarea',
				array(
					'label' => esc_html__( 'Textarea Fields', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'textarea_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_responsive_control(
				'textarea_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'textarea_typography',
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea',
				)
			);

			$widget->add_control(
				'textarea_placeholder_color',
				array(
					'label'     => esc_html__( 'Placeholder Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms textarea::-webkit-input-placeholder' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->start_controls_tabs( 'tabs_textarea_field_style' );

			$widget->start_controls_tab(
				'tab_textarea_field_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'textarea_field_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .everest-forms .evf-field-container .evf-frontend-row textarea' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'textarea_field_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_textarea_field_focus',
				array(
					'label' => esc_html__( 'Focus', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'textarea_field_focus_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row textarea:focus' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'textarea_field_focus_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea:focus',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'textarea_border_options',
				array(
					'label'     => esc_html__( 'Border Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->add_control(
				'ta_box_border',
				array(
					'label'     => esc_html__( 'Box Border', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SWITCHER,
					'label_on'  => esc_html__( 'Show', 'everest-forms' ),
					'label_off' => esc_html__( 'Hide', 'everest-forms' ),
					'default'   => 'no',
				)
			);

			$widget->add_control(
				'ta_border_style',
				array(
					'label'     => esc_html__( 'Border Style', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => 'solid',
					'options'   => array(
						'solid'  => esc_html__( 'Solid', 'everest-forms' ),
						'dotted' => esc_html__( 'Dotted', 'everest-forms' ),
						'dashed' => esc_html__( 'Dashed', 'everest-forms' ),
						'groove' => esc_html__( 'Groove', 'everest-forms' ),
					),
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row textarea' => 'border-style: {{VALUE}};',
					),
					'condition' => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'ta_box_border_width',
				array(
					'label'      => esc_html__( 'Border Width', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'default'    => array(
						'top'    => 1,
						'right'  => 1,
						'bottom' => 1,
						'left'   => 1,
					),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->start_controls_tabs( 'tabs_ta_border_style' );

			$widget->start_controls_tab(
				'tab_ta_border_normal',
				array(
					'label'     => esc_html__( 'Normal', 'everest-forms' ),
					'condition' => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'ta_box_border_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'ta_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_ta_border_hover',
				array(
					'label'     => esc_html__( 'Focus', 'everest-forms' ),
					'condition' => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'ta_box_border_hover_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea:focus' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'ta_border_hover_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'ta_box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'ta_shadow_options',
				array(
					'label'     => esc_html__( 'Box Shadow Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->start_controls_tabs( 'tabs_ta_shadow_style' );

			$widget->start_controls_tab(
				'tab_ta_shadow_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'ta_box_shadow',
					'selector' => '{{WRAPPER}}  .everest-forms .evf-field-container .evf-frontend-row textarea',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_ta_shadow_hover',
				array(
					'label' => esc_html__( 'Focus', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'ta_box_active_shadow',
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field-container .evf-frontend-row textarea:focus',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_checked_styling',
				array(
					'label' => esc_html__( 'Radio/Checkbox Field', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->start_controls_tabs( 'tabs_checkbox_field_style' );
			$widget->start_controls_tab(
				'tab_unchecked_field_bg',
				array(
					'label' => esc_html__( 'Check Box', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'checkbox_text_typography',
					'selector' => '{{WRAPPER}} .everest-forms .evf-field-checkbox label.everest-forms-field-label-inline',
				)
			);

			$widget->add_control(
				'checked_field_text_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .evf-field-checkbox label.everest-forms-field-label-inline' => 'color: {{VALUE}};',
					),
					'separator' => 'after',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_radio_field',
				array(
					'label' => esc_html__( 'Radio Button', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'radio_text_typography',
					'selector' => '{{WRAPPER}} .everest-forms .evf-field-radio label.everest-forms-field-label-inline',
				)
			);

			$widget->add_control(
				'radio_field_text_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .evf-field-radio label.everest-forms-field-label-inline' => 'color: {{VALUE}};',
					),
					'separator' => 'after',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_button_styling',
				array(
					'label' => esc_html__( 'Button Styles', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'button_max_width',
				array(
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'label'       => esc_html__( 'Width', 'everest-forms' ),
					'size_units'  => array( 'px', '%' ),
					'range'       => array(
						'px' => array(
							'min'  => 100,
							'max'  => 2000,
							'step' => 5,
						),
						'%'  => array(
							'min'  => 10,
							'max'  => 100,
							'step' => 1,
						),
					),
					'render_type' => 'ui',
					'selectors'   => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms s input[type=submit]' => 'width: {{SIZE}}{{UNIT}}',
					),
					'separator'   => 'after',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'button_typography',
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms input[type=submit]',
				)
			);

			$widget->add_responsive_control(
				'button_inner_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button,{{WRAPPER}} .everest-forms button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'before',
				)
			);

			$widget->add_responsive_control(
				'button_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->start_controls_tabs( 'tabs_button_style' );

			$widget->start_controls_tab(
				'tab_button_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'button_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button,{{WRAPPER}} .everest-forms button[type=submit],{{WRAPPER}} .everest-forms input[type=submit]' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'button_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_button_hover',
				array(
					'label' => esc_html__( 'Hover', 'everest-forms' ),
				)
			);

			$widget->add_control(
				'button_hover_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button:hover,{{WRAPPER}} .everest-forms button[type=submit]:hover,{{WRAPPER}} .everest-forms input[type=submit]:hover' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'button_hover_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms-part-button:hover,{{WRAPPER}} .everest-forms button[type=submit]:hover,{{WRAPPER}} .everest-forms .everest-forms input[type=submit]:hover',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'button_border_options',
				array(
					'label'     => esc_html__( 'Border Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->add_control(
				'button_box_border',
				array(
					'label'     => esc_html__( 'Box Border', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SWITCHER,
					'label_on'  => esc_html__( 'Show', 'everest-forms' ),
					'label_off' => esc_html__( 'Hide', 'everest-forms' ),
					'default'   => 'no',
				)
			);

			$widget->add_control(
				'button_border_style',
				array(
					'label'     => esc_html__( 'Border Style', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => 'solid',
					'options'   => array(
						'solid'  => esc_html__( 'Solid', 'everest-forms' ),
						'dotted' => esc_html__( 'Dotted', 'everest-forms' ),
						'dashed' => esc_html__( 'Dashed', 'everest-forms' ),
						'groove' => esc_html__( 'Groove', 'everest-forms' ),
					),
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'border-style: {{VALUE}};',
					),
					'condition' => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'button_box_border_width',
				array(
					'label'      => esc_html__( 'Border Width', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'default'    => array(
						'top'    => 1,
						'right'  => 1,
						'bottom' => 1,
						'left'   => 1,
					),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->start_controls_tabs( 'tabs_button_border_style' );

			$widget->start_controls_tab(
				'tab_button_border_normal',
				array(
					'label'     => esc_html__( 'Normal', 'everest-forms' ),
					'condition' => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'button_box_border_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'button_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_button_border_hover',
				array(
					'label'     => esc_html__( 'Hover', 'everest-forms' ),
					'condition' => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->add_control(
				'button_box_border_hover_color',
				array(
					'label'     => esc_html__( 'Border Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button:hover,{{WRAPPER}} .everest-forms  button[type=submit]:hover,{{WRAPPER}} .everest-forms  input[type=submit]:hover' => 'border-color: {{VALUE}};',
					),
					'condition' => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->add_responsive_control(
				'button_border_hover_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .everest-forms-part-button:hover,{{WRAPPER}} .everest-forms button[type=submit]:hover,{{WRAPPER}} .everest-forms  input[type=submit]:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					),
					'condition'  => array(
						'button_box_border' => 'yes',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->add_control(
				'button_shadow_options',
				array(
					'label'     => esc_html__( 'Box Shadow Options', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->start_controls_tabs( 'tabs_button_shadow_style' );

			$widget->start_controls_tab(
				'tab_button_shadow_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'button_shadow',
					'selector' => '{{WRAPPER}} .everest-forms  .everest-forms-part-button,{{WRAPPER}} .everest-forms  button[type=submit],{{WRAPPER}} .everest-forms  input[type=submit]',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_button_shadow_hover',
				array(
					'label' => esc_html__( 'Hover', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'button_hover_shadow',
					'selector' => '{{WRAPPER}} .everest-forms  .everest-forms-part-button:hover,{{WRAPPER}} .everest-forms  button[type=submit]:hover,{{WRAPPER}} .everest-forms  input[type=submit]:hover',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_oute_r_styling',
				array(
					'label' => esc_html__( 'Outer Field', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'oute_r_inner_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_responsive_control(
				'oute_r_inner_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->start_controls_tabs( 'tabs_oute_r' );

			$widget->start_controls_tab(
				'oute_r_normal',
				array(
					'label' => esc_html__( 'Normal', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'oute_r_field_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'oute_r__border',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field',
				)
			);

			$widget->add_responsive_control(
				'oute_r_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'oute_r_shadow',
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field',
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'oute_r_hover',
				array(
					'label' => esc_html__( 'Hover', 'everest-forms' ),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'oute_r_field_bg_hover',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field:hover',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'oute_r__border_hover',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms  .evf-field:hover',
				)
			);

			$widget->add_responsive_control(
				'oute_r_border_radius_hover',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .evf-field:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'oute_r_shadow_hover',
					'selector' => '{{WRAPPER}} .everest-forms .evf-field:hover',
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_response_message',
				array(
					'label' => esc_html__( 'Form Message', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->start_controls_tabs( 'tabs_response_style' );
			$widget->start_controls_tab(
				'tab_response_success',
				array(
					'label' => esc_html__( 'Success', 'everest-forms' ),
				)
			);

			$widget->add_responsive_control(
				'response_success_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-notice--success' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->add_responsive_control(
				'response_success_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms .everest-forms-notice--success,{{WRAPPER}} .everest-forms .everest-forms .everest-forms-notice::before' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'response_success_typography',
					'selector' => '{{WRAPPER}} .everest-forms  .everest-forms-notice--success',
				)
			);

			$widget->add_control(
				'response_success_color',
				array(
					'label'     => esc_html__( 'Text Color', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-notice--success' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				array(
					'name'     => 'response_success_bg',
					'types'    => array( 'classic', 'gradient' ),
					'selector' => '{{WRAPPER}} .everest-forms .everest-forms-notice--success',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'response_success_border',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms  .everest-forms-notice--success',
				)
			);

			$widget->add_responsive_control(
				'response_success_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  .everest-forms-notice--success' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->start_controls_tab(
				'tab_response_validation',
				array(
					'label' => esc_html__( 'Validation/Error', 'everest-forms' ),
				)
			);

			$widget->add_responsive_control(
				'response_validation_padding',
				array(
					'label'      => esc_html__( 'Padding', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  label.evf-error' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),

				)
			);

			$widget->add_responsive_control(
				'response_validation_margin',
				array(
					'label'      => esc_html__( 'Margin', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  label.evf-error' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'separator'  => 'after',
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'     => 'response_validation_typography',
					'selector' => '{{WRAPPER}} .everest-forms  label.evf-error',
				)
			);

			$widget->add_control(
				'response_validation_color',
				array(
					'label'     => esc_html__( 'Text Color/Field Border', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  label.evf-error' => 'color: {{VALUE}};',
						'{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row .evf-frontend-grid .evf-field.everest-forms-invalid .select2-container,
					{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row .evf-frontend-grid .evf-field.everest-forms-invalid input.input-text,
					{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row .evf-frontend-grid .evf-field.everest-forms-invalid select,
					{{WRAPPER}} .everest-forms .evf-field-container .evf-frontend-row .evf-frontend-grid .evf-field.everest-forms-invalid textarea' => 'border-color: {{VALUE}};',
					),
				)
			);

			$widget->add_control(
				'response_validation_bg',
				array(
					'label'     => esc_html__( 'Background', 'everest-forms' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .everest-forms  label.evf-error' => 'background: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'     => 'response_validation_border',
					'label'    => esc_html__( 'Border', 'everest-forms' ),
					'selector' => '{{WRAPPER}} .everest-forms  label.evf-error',
				)
			);

			$widget->add_responsive_control(
				'response_validation_border_radius',
				array(
					'label'      => esc_html__( 'Border Radius', 'everest-forms' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						'{{WRAPPER}} .everest-forms  label.evf-error' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$widget->end_controls_tab();

			$widget->end_controls_tabs();

			$widget->end_controls_section();

			$widget->start_controls_section(
				'section_extra_option_styling',
				array(
					'label' => esc_html__( 'Extra Option', 'everest-forms' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$widget->add_responsive_control(
				'content_max_width',
				array(
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'label'       => esc_html__( 'Maximum Width', 'everest-forms' ),
					'size_units'  => array( 'px', '%' ),
					'range'       => array(
						'px' => array(
							'min'  => 250,
							'max'  => 2000,
							'step' => 5,
						),
						'%'  => array(
							'min'  => 10,
							'max'  => 100,
							'step' => 1,
						),
					),
					'render_type' => 'ui',
					'selectors'   => array(
						'{{WRAPPER}} .everest-forms ' => 'max-width: {{SIZE}}{{UNIT}}',
					),
				)
			);

			$widget->end_controls_section();
		}

	}
}
