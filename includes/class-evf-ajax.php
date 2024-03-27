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
		// @codingStandardsIgnoreStart
		if ( ! empty( $_GET['evf-ajax'] ) ) {
			evf_maybe_define_constant( 'DOING_AJAX', true );
			evf_maybe_define_constant( 'EVF_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
				}
			$GLOBALS['wpdb']->hide_errors();
			}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Send headers for EVF Ajax Requests.
	 *
	 * @since 1.0.0
	 */
	private static function evf_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			evf_nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "evf_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Check for EVF Ajax request and fire action.
	 */
	public static function do_evf_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['evf-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$wp_query->set( 'evf-ajax', sanitize_text_field( wp_unslash( $_GET['evf-ajax'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
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
			'save_form'                => false,
			'create_form'              => false,
			'get_next_id'              => false,
			'install_extension'        => false,
			'integration_connect'      => false,
			'new_email_add'            => false,
			'integration_disconnect'   => false,
			'rated'                    => false,
			'review_dismiss'           => false,
			'survey_dismiss'           => false,
			'allow_usage_dismiss'      => false,
			'php_notice_dismiss'       => false,
			'enabled_form'             => false,
			'import_form_action'       => false,
			'template_licence_check'   => false,
			'template_activate_addon'  => false,
			'ajax_form_submission'     => true,
			'send_test_email'          => false,
			'locate_form_action'       => false,
			'slot_booking'             => true,
			'active_addons'            => false,
			'get_local_font_url'       => false,
			'form_migrator_forms_list' => false,
			'form_migrator'            => false,
			'fm_dismiss_notice'        => false,
			'form_entry_migrator'      => false,
			'embed_form'               => false,
			'goto_edit_page'           => false,
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

	/**
	 * Ajax handler to get next form ID.
	 */
	public static function get_next_id() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_get_next_id', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( $form_id < 1 ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Invalid form', 'everest-forms' ),
				)
			);
		}

		// Check permisssions.
		if ( ! current_user_can( 'everest_forms_edit_form', $form_id ) ) {
			wp_send_json_error();
		}

		if ( isset( $_POST['fields'] ) ) {
			$fields_data = array();
			for ( $i = 0; $i < $_POST['fields']; $i++ ) {
				$field_key      = evf()->form->field_unique_key( $form_id );
				$field_id_array = explode( '-', $field_key );
				$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
				$fields_data [] = array(
					'field_id'  => $new_field_id,
					'field_key' => $field_key,
				);
			}
			wp_send_json_success(
				$fields_data
			);
		} else {
			$field_key      = evf()->form->field_unique_key( $form_id );
			$field_id_array = explode( '-', $field_key );
			$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
			wp_send_json_success(
				array(
					'field_id'  => $new_field_id,
					'field_key' => $field_key,
				)
			);
		}
	}

	/**
	 * AJAX create new form.
	 */
	public static function create_form() {
		ob_start();

		check_ajax_referer( 'everest_forms_create_form', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_create_forms' ) ) {
			wp_die( -1 );
		}

		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : esc_html__( 'Blank Form', 'everest-forms' );
		$template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'blank';

		$form_id = evf()->form->create( $title, $template );

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
				'error' => esc_html__( 'Something went wrong, please try again later', 'everest-forms' ),
			)
		);
	}

	/**
	 * AJAX Form save.
	 */
	public static function save_form() {
		check_ajax_referer( 'everest_forms_save_form', 'security' );

		$logger = evf_get_logger();

		// Check permissions.
		$logger->info(
			__( 'Checking permissions.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			$logger->critical(
				__( 'You do not have permission.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
			die( esc_html__( 'You do not have permission.', 'everest-forms' ) );
		}

		// Check for form data.
		$logger->info(
			__( 'Checking for form data.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		if ( empty( $_POST['form_data'] ) ) {
			$logger->critical(
				__( 'No data provided.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
			die( esc_html__( 'No data provided', 'everest-forms' ) );
		}

		$form_post = evf_sanitize_builder( json_decode( wp_unslash( $_POST['form_data'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		$data = array();

		if ( ! is_null( $form_post ) && $form_post ) {
			foreach ( $form_post as $post_index => $post_input_data ) {
				// For input names that are arrays (e.g. `menu-item-db-id[3][4][5]`),
				// derive the array path keys via regex and set the value in $_POST.
				preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

				$array_bits = array( $matches[1] );

				if ( isset( $matches[3] ) ) {
					$array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
				}

				$new_post_data = array();

				// Build the new array value from leaf to trunk.
				for ( $i = count( $array_bits ) - 1; $i >= 0; $i-- ) {
					if ( count( $array_bits ) - 1 === $i ) {
						if ( '' === $array_bits[ $i ] ) {
							$new_post_data [ $post_index ] = wp_slash( $post_input_data->value );
						} else {
							$new_post_data[ $array_bits[ $i ] ] = wp_slash( $post_input_data->value );
						}
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
		$logger->info(
			__( 'Check for empty meta key.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		$empty_meta_data = array();
		if ( ! empty( $data['form_fields'] ) ) {
			foreach ( $data['form_fields'] as $field_key => $field ) {
				if ( ! empty( $field['label'] ) ) {
					// Only allow specific html in label.
					$data['form_fields'][ $field_key ]['label'] = wp_kses(
						$field['label'],
						array(
							'a'      => array(
								'href'  => array(),
								'class' => array(),
							),
							'span'   => array(
								'class' => array(),
							),
							'em'     => array(),
							'small'  => array(),
							'strong' => array(),
						)
					);

					// Register string for translation.
					evf_string_translation( $data['id'], $field['id'], $field['label'] );
				}

				if ( empty( $field['meta-key'] ) && ! in_array( $field['type'], array( 'html', 'title', 'captcha', 'divider', 'reset' ), true ) ) {
					$empty_meta_data[] = $field['label'];
				}
			}

			if ( ! empty( $empty_meta_data ) ) {
				$logger->error(
					__( 'Meta Key missing.', 'everest-forms' ),
					array( 'source' => 'form-save' )
				);
				wp_send_json_error(
					array(
						'errorTitle'   => esc_html__( 'Meta Key missing', 'everest-forms' ),
						/* translators: %s: empty meta data */
						'errorMessage' => sprintf( esc_html__( 'Please add Meta key for fields: %s', 'everest-forms' ), '<strong>' . implode( ', ', $empty_meta_data ) . '</strong>' ),
					)
				);
			}
		}

		// Fix for sorting field ordering.
		$logger->info(
			__( 'Fix for sorting field ordering.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		if ( isset( $data['structure'], $data['form_fields'] ) ) {
			$structure           = evf_flatten_array( $data['structure'] );
			$data['form_fields'] = array_merge( array_intersect_key( array_flip( $structure ), $data['form_fields'] ), $data['form_fields'] );
		}

		$form_id     = evf()->form->update( $data['id'], $data );
		$form_styles = get_option( 'everest_forms_styles', array() );
		$logger->info(
			__( 'Saving form.', 'everest-forms' ),
			array( 'source' => 'form-save' )
		);
		do_action( 'everest_forms_save_form', $form_id, $data, array(), ! empty( $form_styles[ $form_id ] ) );

		if ( ! $form_id ) {
			$logger->error(
				__( 'An error occurred while saving the form.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
			wp_send_json_error(
				array(
					'errorTitle'   => esc_html__( 'Form not found', 'everest-forms' ),
					'errorMessage' => esc_html__( 'An error occurred while saving the form.', 'everest-forms' ),
				)
			);
		} else {
			$logger->info(
				__( 'Form Saved successfully.', 'everest-forms' ),
				array( 'source' => 'form-save' )
			);
			wp_send_json_success(
				apply_filters(
					'everest_forms_save_form_data',
					array(
						'form_name'    => esc_html( $data['settings']['form_title'] ),
						'redirect_url' => admin_url( 'admin.php?page=evf-builder' ),
					),
					$form_id,
					$data
				)
			);
		}
	}

	/**
	 * Ajax handler for form submission.
	 */
	public static function ajax_form_submission() {
		check_ajax_referer( 'everest_forms_ajax_form_submission', 'security' );

		if ( ! empty( $_POST['everest_forms']['id'] ) ) {
			$process = evf()->task->ajax_form_submission( evf_sanitize_entry( wp_unslash( $_POST['everest_forms'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $process['response'] ) && 'success' === $process['response'] ) {
				wp_send_json_success( $process );
			}

			wp_send_json_error( $process );
		}
	}

	/**
	 * Ajax handler for template required addon activation.
	 */
	public static function template_activate_addon() {
		check_ajax_referer( 'everest_forms_template_licence_check', 'security' );

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error(
				array(
					'errorCode'    => 'no_addon_specified',
					'errorMessage' => esc_html__( 'No Addon specified.', 'everest-forms' ),
				)
			);
		}

		$activate = activate_plugin( sanitize_text_field( wp_unslash( $_POST['addon'] ) ) . '/' . sanitize_text_field( wp_unslash( $_POST['addon'] ) ) . '.php' );

		if ( is_wp_error( $activate ) ) {
			wp_send_json_error(
				array(
					'errorCode'    => 'addon_not_active',
					'errorMessage' => esc_html__( 'Addon can not be activate. Please try again.', 'everest-forms' ),
				)
			);
		} else {
			wp_send_json_success( 'Addon sucessfully activated.' );
		}
	}

	/**
	 * Ajax handler for licence check.
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function template_licence_check() {
		check_ajax_referer( 'everest_forms_template_licence_check', 'security' );

		if ( empty( $_POST['plan'] ) ) {
			wp_send_json_error(
				array(
					'plan'         => '',
					'errorCode'    => 'no_plan_specified',
					'errorMessage' => esc_html__( 'No Plan specified.', 'everest-forms' ),
				)
			);
		}

		$addons        = array();
		$template_data = EVF_Admin_Form_Templates::get_template_data();
		$template_data = is_array( $template_data ) ? $template_data : array();
		if ( ! empty( $template_data ) ) {
			foreach ( $template_data as $template ) {
				if ( isset( $_POST['slug'] ) && $template->slug === $_POST['slug'] && in_array( trim( $_POST['plan'] ), $template->plan, true ) ) {
					$addons = $template->addons;
				}
			}
		}

		$output  = '<div class="everest-forms-recommend-addons">';
		$output .= '<p class="desc plugins-info">' . esc_html__( 'This form template requires the following addons.', 'everest-forms' ) . '</p>';
		$output .= '<table class="plugins-list-table widefat striped">';
		$output .= '<thead><tr><th scope="col" class="manage-column required-plugins" colspan="2">Required Addons</th></tr></thead><tbody id="the-list">';
		$output .= '</div>';

		$activated = true;
		foreach ( $addons as $slug => $addon ) {
			if ( is_plugin_active( $slug . '/' . $slug . '.php' ) ) {
				$class        = 'active';
				$parent_class = '';
			} elseif ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php' ) ) {
				$class        = 'activate-now';
				$parent_class = 'inactive';
				$activated    = false;
			} else {
				$class        = 'install-now';
				$parent_class = 'inactive';
				$activated    = false;
			}
			$output .= '<tr class="plugin-card-' . $slug . ' plugin ' . $parent_class . '" data-slug="' . $slug . '" data-plugin="' . $slug . '/' . $slug . '.php" data-name="' . $addon . '">';
			$output .= '<td class="plugin-name">' . $addon . '</td>';
			$output .= '<td class="plugin-status"><span class="' . esc_attr( $class ) . '"></span></td>';
			$output .= '</tr>';
		}
		$output .= '</tbody></table></div>';

		wp_send_json_success(
			array(
				'html'     => $output,
				'activate' => $activated,
			)
		);
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
					'errorMessage' => esc_html__( 'No plugin specified.', 'everest-forms' ),
				)
			);
		}

		$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
		$plugin = plugin_basename( sanitize_text_field( wp_unslash( $_POST['slug'] . '/' . $_POST['slug'] . '.php' ) ) );
		$status = array(
			'install' => 'plugin',
			'slug'    => sanitize_key( wp_unslash( $_POST['slug'] ) ),
		);

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'everest-forms' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
			$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$status['plugin']     = $plugin;
			$status['pluginName'] = $plugin_data['Name'];

			if ( current_user_can( 'activate_plugin', $plugin ) && is_plugin_inactive( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$status['errorCode']    = $result->get_error_code();
					$status['errorMessage'] = $result->get_error_message();
					wp_send_json_error( $status );
				}

				wp_send_json_success( $status );
			}
		}

		$api = json_decode(
			EVF_Updater_Key_API::version(
				array(
					'license'   => get_option( 'everest-forms-pro_license_key' ),
					'item_name' => ! empty( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
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
			$status['errorMessage'] = esc_html__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'everest-forms' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$install_status = install_plugin_install_status( $api );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			if ( isset( $_POST['page'] ) && 'everest-forms_page_evf-builder' === $_POST['page'] ) {
				activate_plugin( $install_status['file'] );
			} else {
				$status['activateUrl'] =
				esc_url_raw(
					add_query_arg(
						array(
							'action'   => 'activate',
							'plugin'   => $install_status['file'],
							'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $install_status['file'] ),
						),
						admin_url( 'admin.php?page=evf-addons' )
					)
				);
			}
		}

		wp_send_json_success( $status );
	}

	/**
	 * AJAX Integration connect.
	 */
	public static function integration_connect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'everest-forms' ),
				)
			);
		}

		do_action( 'everest_forms_integration_account_connect_' . ( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' ), $_POST );
	}

	/**
	 * AJAX Email Add.
	 */
	public static function new_email_add() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		$connection_id = 'connection_' . uniqid();
		$preview_url   = add_query_arg(
			array(
				'evf_email_preview' => $connection_id,
				'form_id'           => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			),
			home_url()
		);
		wp_send_json_success(
			array(
				'connection_id' => $connection_id,
				'preview_url'   => $preview_url,
			)
		);
	}

	/**
	 * AJAX Email Duplicate.
	 */
	public static function email_duplicate() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		$connection_id = 'connection_' . uniqid();
		$preview_url   = '';
		$preview_url   = add_query_arg(
			array(
				'evf_email_preview' => $connection_id,
				'form_id'           => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
				'preview_url'       => $preview_url,
			),
			home_url()
		);

		wp_send_json_success(
			array(
				'connection_id'      => $connection_id,
				'prev_connection_id' => isset( $_POST['prev_connection_id'] ) ? sanitize_text_field( wp_unslash( $_POST['prev_connection_id'] ) ) : '',
				'preview_url'        => $preview_url,
			)
		);
	}


	/**
	 * AJAX Integration disconnect.
	 */
	public static function integration_disconnect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'everest-forms' ),
				)
			);
		}

		do_action( 'everest_forms_integration_account_disconnect_' . ( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' ), $_POST );

		$connected_accounts = get_option( 'everest_forms_integrations', false );

		if ( ! empty( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] ) ) {
			unset( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] );
			update_option( 'everest_forms_integrations', $connected_accounts );
			wp_send_json_success( array( 'remove' => true ) );
		} else {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Connection missing', 'everest-forms' ),
				)
			);
		}
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
		$review['time']      = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$review['dismissed'] = true;
		update_option( 'everest_forms_review', $review );
		wp_die();
	}

	/**
	 * Triggered when clicking the survey notice button.
	 */
	public static function survey_dismiss() {

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		$survey              = get_option( 'everest_forms_survey', array() );
		$survey['dismissed'] = true;
		update_option( 'everest_forms_survey', $survey );
		wp_die();
	}

	/**
	 * Triggered when clicking the allow usage notice allow or deny buttons.
	 */
	public static function allow_usage_dismiss() {
		check_ajax_referer( 'allow_usage_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		$allow_usage_tracking = isset( $_POST['allow_usage_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['allow_usage_tracking'] ) ) : false;

		update_option( 'everest_forms_allow_usage_notice_shown', true );

		if ( 'true' === $allow_usage_tracking ) {
			update_option( 'everest_forms_allow_usage_tracking', 'yes' );
		}

		wp_die();
	}

	/**
	 * Triggered when clicking the PHP deprecation notice.
	 */
	public static function php_notice_dismiss() {
		check_ajax_referer( 'php_notice_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		$current_date = gmdate( 'Y-m-d' );
		$prompt_count = get_option( 'everest_forms_php_deprecated_notice_prompt_count', 0 );

		update_option( 'everest_forms_php_deprecated_notice_last_prompt_date', $current_date );
		update_option( 'everest_forms_php_deprecated_notice_prompt_count', ++$prompt_count );
		wp_die();
	}

	/**
	 * Triggered when clicking the form toggle.
	 */
	public static function enabled_form() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_enabled_form', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;

		if ( ! current_user_can( 'everest_forms_edit_form', $form_id ) ) {
			wp_die( -1 );
		}

		$form_data = evf()->form->get( absint( $form_id ), array( 'content_only' => true ) );

		$form_data['form_enabled'] = $enabled;

		evf()->form->update( $form_id, $form_data );
	}

	/**
	 * Import Form ajax.
	 */
	public static function import_form_action() {
		try {
			check_ajax_referer( 'process-import-ajax-nonce', 'security' );
			EVF_Admin_Import_Export::import_forms();
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Send test email.
	 */
	public static function send_test_email() {
		try {
			check_ajax_referer( 'process-ajax-nonce', 'security' );
			$from  = esc_attr( get_bloginfo( 'name', 'display' ) );
			$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );

			/* translators: %s: from address */
			$subject = 'Everest Form: ' . sprintf( esc_html__( 'Test email from %s', 'everest-forms' ), $from );
			$header  = "Reply-To: {{from}} \r\n";
			$header .= 'Content-Type: text/html; charset=UTF-8';
			$message = sprintf(
				'%s <br /> %s <br /> %s <br /> %s <br /> %s',
				__( 'Congratulations,', 'everest-forms' ),
				__( 'Your test email has been received successfully.', 'everest-forms' ),
				__( 'We thank you for trying out Everest Forms and joining our mission to make sure you get your emails delivered.', 'everest-forms' ),
				__( 'Regards,', 'everest-forms' ),
				__( 'Everest Forms Team', 'everest-forms' )
			);
			$status  = wp_mail( $email, $subject, $message, $header );
			if ( $status ) {
				wp_send_json_success( array( 'message' => __( 'Test email was sent successfully! Please check your inbox to make sure it is delivered.', 'everest-forms' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Test email was unsuccessful! Something went wrong.', 'everest-forms' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Locate form.
	 */
	public static function locate_form_action() {
		global $wpdb;
		try {
			check_ajax_referer( 'process-locate-ajax-nonce', 'security' );
			$id                     = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$everest_form_shortcode = '%[everest_form id="' . $id . '"%';
			$form_id_shortcode      = '%{"formId":"' . $id . '"%';
			$pages                  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}posts WHERE post_content LIKE %s OR post_content LIKE %s", $everest_form_shortcode, $form_id_shortcode ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$page_list              = array();
			foreach ( $pages as $page ) {
				if ( 'page' === $page->post_type || 'post' === $page->post_type ) {
					$page_title               = $page->post_title;
					$page_guid                = get_permalink( $page->ID );
					$page_list[ $page_title ] = $page_guid;
				}
			}
			wp_send_json_success( $page_list );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
	/**
	 * Slot booking.
	 */
	public static function slot_booking() {
		try {
			check_ajax_referer( 'everest_forms_slot_booking_nonce', 'security' );
			$datetime_value  = isset( $_POST['data-time-value'] ) ? sanitize_text_field( wp_unslash( $_POST['data-time-value'] ) ) : '';
			$datetime_format = isset( $_POST['data-time-format'] ) ? sanitize_text_field( wp_unslash( $_POST['data-time-format'] ) ) : '';
			$date_format     = isset( $_POST['data-format'] ) ? sanitize_text_field( wp_unslash( $_POST['data-format'] ) ) : '';
			$mode            = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
			$form_id         = isset( $_POST['form-id'] ) ? sanitize_text_field( wp_unslash( $_POST['form-id'] ) ) : '';
			$time_interval   = isset( $_POST['time-interval'] ) ? sanitize_text_field( wp_unslash( $_POST['time-interval'] ) ) : '';
			$datetime_arr    = parse_datetime_values( $datetime_value, $datetime_format, $date_format, $mode, $time_interval );

			if ( empty( $datetime_arr ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please select at least one date time.', 'everest-forms' ),
					)
				);
			}
			$booked_slot = maybe_unserialize( get_option( 'evf_booked_slot', '' ) );
			$is_booked   = false;
			if ( ! empty( $booked_slot ) && array_key_exists( $form_id, $booked_slot ) ) {
				foreach ( $datetime_arr as $arr ) {

					foreach ( $booked_slot[ $form_id ] as $key => $slot ) {
						if ( $arr[0] >= $slot[0] && $arr[1] <= $slot[1] ) {
							$is_booked = true;
							break;
						} elseif ( $arr[0] >= $slot[0] && $arr[0] < $slot[1] && $arr[1] >= $slot[1] ) {
							$is_booked = true;
							break;
						}
					}
				}
			}
			if ( $is_booked ) {
				wp_send_json_success(
					array(
						'message' => __( 'This slot is already booked. Please choose other slot', 'everest-forms' ),
					)
				);
			}
			wp_send_json_error(
				array(
					'message' => __( 'This slot is not booked.', 'everest-forms' ),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => __( 'Something went wrong.', 'everest-forms' ),
				)
			);
		}
	}

	/**
	 * Activate addons from builder.
	 */
	public static function active_addons() {
		try {
			check_ajax_referer( 'evf_active_nonce', 'security' );
			$plugin   = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
			$activate = activate_plugin( $plugin );
			if ( is_wp_error( $activate ) ) {
				$activation_error = $activate->get_error_message();
				wp_send_json_error(
					array(
						'message' => $activation_error,
					)
				);
			} else {
				wp_send_json_success(
					array(
						'message' => __( 'Activated successfully', 'everest-forms' ),
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Download the provided font and return the url for font file.
	 *
	 * @since 2.0.8
	 */
	public static function get_local_font_url() {
		$font_url = isset( $_POST['font_url'] ) ? sanitize_text_field( wp_unslash( $_POST['font_url'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification

		$allowed_urls = array(
			'https://fonts.googleapis.com',
		);

		if ( ! in_array( $font_url, $allowed_urls ) ) {
			return;
		}

		if ( str_contains( $font_url, 'https://fonts.googleapis.com' ) ) {
			$font_url = evf_maybe_get_local_font_url( $font_url );
		}

		return wp_send_json_success( $font_url );
	}

	/**
	 * Forms list for form migrator.
	 *
	 * @since 2.0.8
	 */
	public static function form_migrator_forms_list() {
		try {
			check_ajax_referer( 'evf_form_migrator_forms_list_nonce', 'security' );

			$form_slug = isset( $_POST['form_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['form_slug'] ) ) : '';
			if ( '' === $form_slug ) {
				wp_send_json_error(
					array(
						'message' => __( 'Missing form slug !', 'everest-forms' ),
					)
				);
			}

			// Creating the form instance and getting the form list.
			$class_name = 'EVF_Fm_' . ucfirst( trim( str_replace( '-', '', $form_slug ) ) );

			if ( ! class_exists( $class_name ) ) {
				$except_message = sprintf( '<b><i>%s</i></b> %s', $class_name, esc_html__( 'does not exist.', 'everest-forms' ) );
				throw new Exception( $except_message );
			}

			$form_instance = new $class_name();
			$forms_list    = $form_instance->get_forms();

			if ( empty( $forms_list ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html( 'No forms are currently available in the list !!!', 'everest-forms' ),
					)
				);
			}
			$row               = 0;
			$form_per_page     = 5;
			$ceil              = ceil( count( $forms_list ) / $form_per_page );
			$forms_list_table  = '<div class="evf-fm-forms-table-wrapper">';
			$forms_list_table .= '<h4>' . sprintf( '%s %s', esc_html__( 'Import', 'everest-forms' ), $form_instance->name ) . '</h4>';
			$forms_list_table .= '<table class="evf-fm-forms-table" data-form-slug="' . esc_attr( $form_slug ) . '">';
			$forms_list_table .= '<tr class="evf-th-title"><th><input id="evf-fm-select-all" type="checkbox" name="fm_select_all_form" /></th><th>' . esc_html__( 'Form	Name', 'everest-forms' ) . '</th><th>' . esc_html__( 'Imported', 'everest-forms' ) . '</th><th>' . esc_html__( 'Action' ) . '</th></tr>';
			$hidden            = '';
			$imported          = get_option( 'evf_fm_' . $form_slug . '_imported_form_list', array() );
			foreach ( $forms_list as $form_id => $form_name ) {
				++$row;
				if ( in_array( $form_id, $imported ) ) {
					$is_imported   = true;
					$imported_text = esc_html__( 'Yes', 'everest-forms' );
				} else {
					$is_imported   = false;
					$imported_text = esc_html__( 'No', 'everest-forms' );
				}
				$forms_list_table .= '<tr id="evf-fm-row-' . esc_attr( $row ) . '" class="evf-fm-row ' . esc_attr( $hidden ) . '"><td><input class="evf-fm-select-single" type="checkbox" name="fm_select_single_form_' . esc_attr( $form_id ) . '" data-form-id="' . esc_attr( $form_id ) . '" /></td><td>' . esc_html__( $form_name, 'everest-forms' ) . '</td><td><p class="evf-fm-imported" data-form-id="' . esc_attr( $form_id ) . '">' . esc_attr( $imported_text ) . '<p></td>';
				$forms_list_table .= '<td>';
				$forms_list_table .= '<div class="evf-fm-import-actions"><button class="evf-fm-import-single" data-form-id="' . esc_attr( $form_id ) . '">' . esc_html( 'Import Form' ) . '</button>';
				if ( 'contact-form-7' !== $form_slug ) {
					$disabled          = $is_imported ? '' : 'disabled';
					$forms_list_table .= '<button class="evf-fm-import-entry" data-form-id="' . esc_attr( $form_id ) . '"' . esc_attr( $disabled ) . '>' . esc_html( 'Import Entry' ) . '</button>';
				}
				$forms_list_table .= '</div></td></tr>';
				if ( $row === $form_per_page ) {
					$hidden = 'evf-fm-hide-row';
				}
			}
			$forms_list_table .= '</table>';
			$forms_list_table .= '<div class="evf-fm-import-selected-wrapper"><button class="evf-fm-import-selected-btn">' . esc_html( 'Import Selected Forms' ) . '</button>';
			$forms_list_table .= '<div data-total-page="' . esc_attr( count( $forms_list ) ) . '" data-fm-ceil="' . esc_attr( $ceil ) . '"  data-form-per-page="' . esc_attr( $form_per_page ) . '" class="evf-fm-pagination">';

			for ( $page = 1; $page <= $ceil; $page++ ) {
				$active = '';
				if ( 1 === $page ) {
					$active = 'evf-fm-btn-active';
				}
				$forms_list_table .= '<button class="evf-fm-page ' . esc_attr( $active ) . '" data-page="' . esc_attr( $page ) . '">' . esc_attr( $page ) . '</button>';
			}
			$forms_list_table .= '</div></div>';
			$forms_list_table .= '</div>';
			wp_send_json_success(
				array(
					'message'          => esc_html( 'All Forms List', 'everest-forms' ),
					'forms_list_table' => $forms_list_table,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Form migrator.
	 *
	 * @since 2.0.8
	 */
	public static function form_migrator() {
		try {
			check_ajax_referer( 'evf_form_migrator_nonce', 'security' );
			$form_slug = isset( $_POST['form_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['form_slug'] ) ) : '';
			$form_ids  = isset( $_POST['form_ids'] ) ? wp_unslash( $_POST['form_ids'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( '' === $form_ids ) {
				wp_send_json_error(
					array(
						'message' => __( 'Missing Form ID !!!', 'everest-forms' ),
					)
				);
			}

			$class_name = 'EVF_Fm_' . ucfirst( trim( str_replace( '-', '', $form_slug ) ) );

			if ( ! class_exists( $class_name ) ) {
				$except_message = sprintf( '<b><i>%s</i></b> %s', $class_name, esc_html__( 'does not exist.' ) );
				throw new Exception( $except_message );
			}
			// Create the instance of class.
			$form_instance = new $class_name();
			$forms_data    = $form_instance->get_fm_mapped_form_data( $form_ids );

			if ( 1 === count( $forms_data ) ) {
				wp_send_json_success(
					array(
						'message'   => sprintf( '%s <a href="%s" target="_blank">%s</a>', __( 'Imported Successfully.', 'everest-forms' ), esc_url( $forms_data[ $form_ids[0] ]['edit'] ), __( 'View Form', 'everest-forms' ) ),
						'form_data' => $forms_data,
					)
				);
			} else {
				wp_send_json_success(
					array(
						'message'   => __( 'Imported Successfully', 'everest-forms' ),
						'form_data' => $forms_data,
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
	/**
	 * Dismiss Form migrator notice.
	 *
	 * @since 2.0.8
	 */
	public static function fm_dismiss_notice() {
		try {
			check_ajax_referer( 'evf_fm_dismiss_notice_nonce', 'security' );

			$option_id = isset( $_POST['option_id'] ) ? sanitize_text_field( $_POST['option_id'] ) : '';
			update_option( $option_id, true );

			wp_send_json_success(
				array(
					'message' => __( 'Updated !', 'everest-forms' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
	/**
	 * Form entry migrator.
	 *
	 * @since 2.0.8
	 */
	public static function form_entry_migrator() {
		try {
			check_ajax_referer( 'evf_form_entry_migrator_nonce', 'security' );
			if ( ! wpforms()->is_pro() ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Entries not available in WPForms Lite.', 'everest-forms' ),
					)
				);
			}
			$form_id   = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
			$form_slug = isset( $_POST['form_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['form_slug'] ) ) : '';

			if ( empty( $form_id ) || empty( $form_slug ) ) {

				wp_send_json_error(
					array(
						'message' => __( 'Invalid Request !!', 'everest-forms' ),
					)
				);
			}

			$migrated_form_list = get_option( 'evf_fm_' . $form_slug . '_imported_form_list', array() );
			$evf_form_id        = array_search( $form_id, $migrated_form_list );

			if ( ! $evf_form_id ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Please migrate the form before importing the entry', 'everest-forms' ),
					)
				);
			}
			// Creating the form instance and getting the form list.
			$class_name = 'EVF_Fm_' . ucfirst( trim( str_replace( '-', '', $form_slug ) ) );

			if ( ! class_exists( $class_name ) ) {
				$except_message = sprintf( '<b><i>%s</i></b> %s', $class_name, esc_html__( 'does not exist.' ) );
				throw new Exception( $except_message );
			}
			// Create the instance of class.
			$form_instance = new $class_name();
			$evf_entries   = $form_instance->migrate_entry( $evf_form_id, $form_id );

			$success   = array();
			$unsuccess = array();
			foreach ( $evf_entries as $key => $entry ) {
				if ( ! $entry ) {
					$unsuccess[] = $key;
					continue;
				}
				$success[] = $key;
			}
			if ( count( $unsuccess ) === 0 ) {
				$response = array(
					'message'   => esc_html__( 'All entries are migrated successfully!!', 'everest-forms' ),
					'success'   => $success,
					'unsuccess' => $unsuccess,
				);
			} elseif ( count( $unsuccess ) > 0 && count( $success ) === 0 ) {
				$response = array(
					'message'   => esc_html__( 'Entry migration failed!!', 'everest-forms' ),
					'success'   => $success,
					'unsuccess' => $unsuccess,
				);
			} elseif ( count( $unsuccess ) > 0 && count( $success ) > 0 ) {
				$response = array(
					'message'   => esc_html__( 'Only some entries are migrated successfully!!', 'everest-forms' ),
					'success'   => $success,
					'unsuccess' => $unsuccess,
				);
			}
			wp_send_json_success(
				$response
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
	/**
	 * Everest Forms - Embed Form.
	 *
	 * @since 2.0.8
	 */
	public static function embed_form() {
		check_ajax_referer( 'everest_forms_embed_form', 'security' );
		$args  = array(
			'post_status' => 'publish',
			'post_type'   => 'page',
		);
		$pages = get_pages( $args );

		wp_send_json_success( $pages );
	}

	/**
	 * Get page edit link
	 *
	 * @since 2.0.8
	 */
	public static function goto_edit_page() {
		check_ajax_referer( 'everest_forms_goto_edit_page', 'security' );

		$page_id = empty( $_POST['page_id'] ) ? 0 : sanitize_text_field( absint( $_POST['page_id'] ) );

		if ( empty( $page_id ) ) {
			$url  = add_query_arg( 'post_type', 'page', admin_url( 'post-new.php' ) );
			$meta = array(
				'embed_page'       => 0,
				'embed_page_title' => ! empty( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '',
			);
		} else {
			$url  = get_edit_post_link( $page_id, '' );
			$meta = array(
				'embed_page' => $page_id,
			);
		}
		$page_url        = add_query_arg(
			array(
				'form' => 'everest-forms',
			),
			esc_url_raw( $url )
		);
		$meta['form_id'] = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		EVF_Admin_Embed_Wizard::set_meta( $meta );

		wp_send_json_success( $page_url );
	}
}

EVF_AJAX::init();
