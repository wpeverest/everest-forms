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

			echo '<a href="#" class="evf-panel-tab evf-' . esc_attr( $container_name ) . '-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $slug ) . esc_attr( $class ) . '" data-section="' . esc_attr( $slug ) . '">';
			if ( ! empty( $icon ) ) {
				echo '<figure class="logo"><img src="' . esc_url( $icon ) . '"></figure>';
			}
			echo esc_html( $name );
			echo '<i class="dashicons dashicons-arrow-right-alt2 everest-forms-toggle-arrow"></i>';
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
