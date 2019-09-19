<?php
/**
 * EverestForms EVF_AJAX. AJAX Event Handlers.
 *
 * @class   EVF_AJAX
 * @package EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_AJAX class.
 */
class EVF_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_evf_ajax' ), 0 );
		self::add_ajax_events();
	}

	/**
	 * Set EVF AJAX constant and headers.
	 */
	public static function define_ajax() {
		if ( ! empty( $_GET['ev-ajax'] ) ) {
			evf_maybe_define_constant( 'DOING_AJAX', true );
			evf_maybe_define_constant( 'EVF_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for EVF Ajax Requests.
	 *
	 * @since 1.0.0
	 */
	private static function evf_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		evf_nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for EVF Ajax request and fire action.
	 */
	public static function do_evf_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['evf-ajax'] ) ) {
			$wp_query->set( 'evf-ajax', sanitize_text_field( $_GET['evf-ajax'] ) );
		}

		$action = $wp_query->get( 'evf-ajax' );

		if ( $action ) {
			self::evf_ajax_headers();
			$action = sanitize_text_field( $action );
			do_action( 'evf_ajax_' . $action );
			wp_die();
		}
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'save_form'              => false,
			'create_form'            => false,
			'get_next_id'            => false,
			'install_extension'      => false,
			'integration_connect'    => false,
			'new_email_add'          => false,
			'integration_disconnect' => false,
			'deactivation_notice'    => false,
			'rated'                  => false,
			'review_dismiss'         => false,
			'enabled_form'           => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_everest_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_everest_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// EVF AJAX can be used for frontend ajax requests.
				add_action( 'evf_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function get_next_id() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_get_next_id', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( $form_id < 1 ) {
			wp_send_json_error(
				array(
					'error' => __( 'Invalid form', 'everest-forms' ),
				)
			);
		}
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error();
		}
		$field_key      = EVF()->form->field_unique_key( $form_id );
		$field_id_array = explode( '-', $field_key );
		$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
		wp_send_json_success(
			array(
				'field_id'  => $new_field_id,
				'field_key' => $field_key,
			)
		);
	}

	/**
	 * AJAX create new form.
	 */
	public static function create_form() {
		ob_start();

		check_ajax_referer( 'everest_forms_create_form', 'security' );

		if ( ! current_user_can( 'edit_everest_forms' ) ) {
			wp_die( -1 );
		}

		$title    = isset( $_POST['title'] ) ? $_POST['title'] : __( 'Blank Form', 'everest-forms' );
		$template = isset( $_POST['template'] ) ? $_POST['template'] : 'blank';

		$form_id = EVF()->form->create( $title, $template );

		if ( $form_id ) {
			$data = array(
				'id'       => $form_id,
				'redirect' => add_query_arg(
					array(
						'tab'     => 'fields',
						'form_id' => $form_id,
					),
					admin_url( 'admin.php?page=evf-builder' )
				),
			);

			wp_send_json_success( $data );
		}

		wp_send_json_error(
			array(
				'error' => __( 'Something went wrong, please try again later', 'everest-forms' ),
			)
		);
	}

	/**
	 * AJAX Form save.
	 */
	public static function save_form() {
		check_ajax_referer( 'everest_forms_save_form', 'security' );

		// Check for permissions.
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			die( esc_html__( 'You do not have permission.', 'everest-forms' ) );
		}

		// Check for form data.
		if ( empty( $_POST['form_data'] ) ) {
			die( esc_html__( 'No data provided', 'everest-forms' ) );
		}

		$form_post = json_decode( stripslashes( $_POST['form_data'] ) );

		$data = array();

		if ( ! is_null( $form_post ) && $form_post ) {
			foreach ( $form_post as $post_input_data ) {
				// For input names that are arrays (e.g. `menu-item-db-id[3][4][5]`),
				// derive the array path keys via regex and set the value in $_POST.
				preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

				$array_bits = array( $matches[1] );

				if ( isset( $matches[3] ) ) {
					$array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
				}

				$new_post_data = array();

				// Build the new array value from leaf to trunk.
				for ( $i = count( $array_bits ) - 1; $i >= 0; $i -- ) {
					if ( $i === count( $array_bits ) - 1 ) {
						$new_post_data[ $array_bits[ $i ] ] = wp_slash( $post_input_data->value );
					} else {
						$new_post_data = array(
							$array_bits[ $i ] => $new_post_data,
						);
					}
				}

				$data = array_replace_recursive( $data, $new_post_data );
			}
		}

		// Check for empty meta key.
		$empty_meta_data = array();
		if ( ! empty( $data['form_fields'] ) ) {
			foreach ( $data['form_fields'] as $field ) {
				// Register string for translation.
				if ( isset( $field['label'] ) ) {
					evf_string_translation( $data['id'], $field['id'], $field['label'] );
				}

				if ( empty( $field['meta-key'] ) && ! in_array( $field['type'], array( 'html', 'title', 'captcha' ), true ) ) {
					$empty_meta_data[] = $field['label'];
				}
			}

			if ( ! empty( $empty_meta_data ) ) {
				wp_send_json_error(
					array(
						'errorTitle'   => __( 'Meta Key missing', 'everest-forms' ),
						'errorMessage' => sprintf( __( 'Please add Meta key for fields: %s', 'everest-forms' ), '<strong>' . implode( ', ', $empty_meta_data ) . '</strong>' ),
					)
				);
			}
		}

		// Fix for sorting field ordering.
		if ( isset( $data['structure'], $data['form_fields'] ) ) {
			$structure           = evf_flatten_array( $data['structure'] );
			$data['form_fields'] = array_merge( array_intersect_key( array_flip( $structure ), $data['form_fields'] ), $data['form_fields'] );
		}

		$form_id = EVF()->form->update( $data['id'], $data );

		do_action( 'everest_forms_save_form', $form_id, $data );

		if ( ! $form_id ) {
			wp_send_json_error(
				array(
					'errorTitle'   => esc_html__( 'Form not found', 'everest-forms' ),
					'errorMessage' => esc_html__( 'An error occurred while saving the form.', 'everest-forms' ),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'form_name'    => esc_html( $data['settings']['form_title'] ),
					'redirect_url' => admin_url( 'admin.php?page=evf-builder' ),
				)
			);
		}
	}

	/**
	 * Ajax handler for installing a extension.
	 *
	 * @since 1.2.0
	 *
	 * @see Plugin_Upgrader
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function install_extension() {
		check_ajax_referer( 'updates' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error(
				array(
					'slug'         => '',
					'errorCode'    => 'no_plugin_specified',
					'errorMessage' => __( 'No plugin specified.', 'everest-forms' ),
				)
			);
		}

		$status = array(
			'install' => 'plugin',
			'slug'    => sanitize_key( wp_unslash( $_POST['slug'] ) ),
			'name'    => wp_unslash( $_POST['name'] ),
		);

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'everest-forms' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$key = get_option( 'everest-forms-pro_license_key' );
		$api = json_decode(
			EVF_Updater_Key_API::version(
				array(
					'license'   => $key,
					'item_name' => $status['name'],
				)
			)
		);

		if ( is_wp_error( $api ) ) {
			$status['errorMessage'] = $api->get_error_message();
			wp_send_json_error( $status );
		}

		$status['pluginName'] = $api->name;

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'everest-forms' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$install_status = install_plugin_install_status( $api );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			$status['activateUrl'] = add_query_arg(
				array(
					'action'   => 'activate',
					'plugin'   => $install_status['file'],
					'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $install_status['file'] ),
				),
				admin_url( 'admin.php?page=evf-addons' )
			);
		}

		wp_send_json_success( $status );
	}

	/**
	 * AJAX Integration connect.
	 */
	public static function integration_connect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Checking permission.
		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'everest-forms' ),
				)
			);
		}

		do_action( 'everest_forms_integration_account_connect_' . $_POST['source'], $_POST );
	}

	/**
	 * AJAX Email Add.
	 */
	public static function new_email_add() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		// $connection = self::output_email_connection( '', array( 'connection_name' => $_POST['name'] ), $_POST['id'] );
		$connection_id = 'connection_' . uniqid();

		wp_send_json_success(
			array(
				// 'html' => $connection[ 'html' ],
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * AJAX Integration disconnect.
	 */
	public static function integration_disconnect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Checking permission.
		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'everest-forms' ),
				)
			);
		}

		$connected_accounts = get_option( 'everest_forms_integrations', false );

		if ( ! empty( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] ) ) {
			unset( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] );
			update_option( 'everest_forms_integrations', $connected_accounts );
			wp_send_json_success();
		} else {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Connection missing', 'everest-forms' ),
				)
			);
		}
	}

	/**
	 * AJAX plugin deactivation notice.
	 */
	public static function deactivation_notice() {
		global $status, $page, $s;

		check_ajax_referer( 'deactivation-notice', 'security' );

		$deactivate_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'        => 'deactivate',
					'plugin'        => EVF_PLUGIN_BASENAME,
					'plugin_status' => $status,
					'paged'         => $page,
					's'             => $s,
				),
				admin_url( 'plugins.php' )
			),
			'deactivate-plugin_' . EVF_PLUGIN_BASENAME
		);

		/* translators: %1$s - deactivation reason page; %2$d - deactivation url. */
		$deactivation_notice = sprintf( __( 'Before we deactivate Everest Forms, would you care to <a href="%1$s" target="_blank">let us know why</a> so we can improve it for you? <a href="%2$s">No, deactivate now</a>.', 'everest-forms' ), 'https://wpeverest.com/deactivation/everest-forms/', $deactivate_url );

		wp_send_json(
			array(
				'fragments' => apply_filters(
					'everest_forms_deactivation_notice_fragments',
					array(
						'deactivation_notice' => '<tr class="plugin-update-tr active updated" data-slug="everest-forms" data-plugin="everest-forms/everest-forms.php"><td colspan ="3" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt"><p>' . $deactivation_notice . '</p></div></td></tr>',
					)
				),
			)
		);
	}

	/**
	 * Triggered when clicking the rating footer.
	 */
	public static function rated() {
		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		update_option( 'everest_forms_admin_footer_text_rated', 1 );
		wp_die();
	}

	/**
	 * Triggered when clicking the review notice button.
	 */
	public static function review_dismiss() {
		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		$review              = get_option( 'everest_forms_review', array() );
		$review['time']      = current_time( 'timestamp' );
		$review['dismissed'] = true;
		update_option( 'everest_forms_review', $review );
		wp_die();
	}

	/**
	 * Triggered when clicking the form toggle.
	 */
	public static function enabled_form() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_enabled_form', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;

		$form_data = EVF()->form->get( absint( $form_id ), array( 'content_only' => true ) );

		$form_data['form_enabled'] = $enabled;

		EVF()->form->update( $form_id, $form_data );
	}
}

EVF_AJAX::init();
