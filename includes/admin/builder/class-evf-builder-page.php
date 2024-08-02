<?php
/**
 * EverestForms Builder Page/Tab
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EVF_Admin_Form_Panel', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/abstracts/legacy/class-evf-admin-form-panel.php';
}

if ( ! class_exists( 'EVF_Builder_Page', false ) ) :

	/**
	 * EVF_Builder_Page Class.
	 */
	abstract class EVF_Builder_Page extends EVF_Admin_Form_Panel {

		/**
		 * Form object.
		 *
		 * @var object
		 */
		public $form;

		/**
		 * Builder page id.
		 *
		 * @var string
		 */
		protected $id = '';

		/**
		 * Builder page label.
		 *
		 * @var string
		 */
		protected $label = '';

		/**
		 * Is sidebar available?
		 *
		 * @var boolean
		 */
		protected $sidebar = false;

		/**
		 * Array of form data.
		 *
		 * @var array
		 */
		public $form_data = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			$form_id         = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$this->form      = evf()->form->get( $form_id );
			$this->form_data = is_object( $this->form ) ? evf_decode( $this->form->post_content ) : array();

			// Init hooks.
			$this->init_hooks();

			// Hooks.
			add_filter( 'everest_forms_builder_tabs_array', array( $this, 'add_builder_page' ), 20 );
			add_action( 'everest_forms_builder_sidebar_' . $this->id, array( $this, 'output_sidebar' ) );
			add_action( 'everest_forms_builder_content_' . $this->id, array( $this, 'output_content' ) );
		}

		/**
		 * Get builder page ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get builder page label.
		 *
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * Get builder page sidebar.
		 *
		 * @return string
		 */
		public function get_sidebar() {
			return $this->sidebar;
		}

		/**
		 * Get builder page form data.
		 *
		 * @return string
		 */
		public function get_form_data() {
			return $this->form_data;
		}

		/**
		 * Add this page to builder.
		 *
		 * @param  array $pages Builder pages.
		 * @return mixed
		 */
		public function add_builder_page( $pages ) {
			$pages[ $this->id ] = array(
				'label'   => $this->label,
				'sidebar' => $this->sidebar,
			);

			return $pages;
		}

		/**
		 * Add sidebar tab sections.
		 *
		 * @param string $name Name of the section.
		 * @param string $slug Slug of the section.
		 * @param string $icon Icon of the section.
		 * @param string $container_name Name of that container.
		 */
		public function add_sidebar_tab( $name, $slug, $icon = '', $container_name = 'setting' ) {
			$class  = '';
			$class .= 'default' === $slug ? ' default' : '';
			$class .= ! empty( $icon ) ? ' icon' : '';
			if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
				$pro_addons = array(
					'webhook'            => array(
						'id'   => '0DQPfQgrWM8',
						'name' => esc_html__( 'WebHook', 'everest-forms' ),
					),
					'form_restriction'   => array(
						'id'   => 'Q1-dja7m3Sc',
						'name' => esc_html__( 'Form Restriction', 'everest-forms' ),
					),
					'multi_part'         => array(
						'id'   => 'qVpDzAx-_4A',
						'name' => esc_html__( 'Multi Part', 'everest-forms' ),
					),
					'pdf_submission'     => array(
						'id'   => '37CtaxJzYis',
						'name' => esc_html__( 'PDF Submission', 'everest-forms' ),
					),
					'post_submission'    => array(
						'id'   => 'gTtewu4DpCo',
						'name' => esc_html__( 'Post Submission', 'everest-forms' ),
					),
					'save_and_continue'  => array(
						'id'   => '4xxEi0rSB20',
						'name' => esc_html__( 'Save and Continue', 'everest-forms' ),
					),
					'survey_polls_quiz'  => array(
						'id'   => 'i1vR-YmaBOg',
						'name' => esc_html__( 'Survey,Polls,Quiz', 'everest-forms' ),
					),
					'user_registration'  => array(
						'id'   => 'MEyuznG2Tok',
						'name' => esc_html__( 'User Registration', 'everest-forms' ),
					),
					'conversation_forms' => array(
						'id'   => 'XO38b8Lp19s',
						'name' => esc_html__( 'Conversation Forms', 'everest-forms' ),
					),
					'sms_notifications'  => array(
						'id'   => 'tz4UKBX9WxM',
						'name' => esc_html__( 'SMS Notifications', 'everest-forms' ),
					),
					'telegram'           => array(
						'id'   => '',
						'name' => esc_html__( 'Telegram', 'everest-forms' ),
					),
				);

			} else {
				$pro_addons = array();
			}

			$is_pro_addon  = array_key_exists( $slug, $pro_addons );
			$upgrade_class = $is_pro_addon ? 'upgrade-addons-settings' : '';
			$pro_icon      = plugins_url( 'assets/images/icons/evf-pro-icon.png', EVF_PLUGIN_FILE );
			$icon_url      = $is_pro_addon ? $pro_icon : '';
			$evf_video     = $is_pro_addon ? isset( $pro_addons[ $slug ]['id'] ) ? $pro_addons[ $slug ]['id'] : '' : '';

			echo '<a href="#" class="evf-panel-tab evf-' . esc_attr( $container_name ) . '-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $slug ) . esc_attr( $class ) . ' ' . esc_attr( $upgrade_class ) . '" data-section="' . esc_attr( $slug ) . '" " data-links="' . esc_attr( $evf_video ) . '" >';
			if ( ! empty( $icon ) ) {
				echo '<figure class="logo"><img src="' . esc_url( $icon ) . '"></figure>';
			}
			echo esc_html( $name );
			if ( ! empty( $icon_url ) ) {
				echo '<i class="dashicons" style="background-image: url(' . esc_url( $icon_url ) . '); background-size: cover; display: inline-block;  color: #ccd1d6;"></i>';
			} else {
				echo '<i class="dashicons dashicons-arrow-right-alt2 everest-forms-toggle-arrow"></i>';
			}
			echo '</a>';
		}

		/**
		 * Hook in tabs.
		 */
		public function init_hooks() {}

		/**
		 * Outputs the builder sidebar.
		 */
		public function output_sidebar() {}

		/**
		 * Outputs the builder content.
		 */
		public function output_content() {}
	}

endif;
