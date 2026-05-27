<?php
/**
 * Minimal MCP-over-HTTP server endpoint for Everest Forms.
 *
 * Implements the subset of the Model Context Protocol JSON-RPC 2.0 surface
 * needed for external clients (Claude Desktop, custom agents) to discover
 * and invoke our registered abilities as MCP tools:
 *
 *   - initialize
 *   - tools/list
 *   - tools/call
 *
 * Transport is plain HTTP POST against:
 *   POST /wp-json/everest-forms/v1/mcp
 *
 * Auth uses WordPress's normal REST auth (cookie, application password,
 * or any plugin-provided REST auth scheme). The endpoint refuses
 * unauthenticated callers.
 *
 * @package EverestForms\Abilities
 */

defined( 'ABSPATH' ) || exit;

/**
 * MCP HTTP server.
 */
class EVF_MCP_Server {

	/**
	 * REST permission check.
	 *
	 * Authenticated users only. Per-tool checks are enforced again inside
	 * the ability registry, so unauthorized users can still hit the route
	 * but cannot invoke privileged tools.
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'evf_mcp_unauthorized', 'Authentication required.', array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle_request( $request ) {
		// GET returns a tiny capability summary, useful for sanity checks.
		if ( 'GET' === $request->get_method() ) {
			return new WP_REST_Response(
				array(
					'name'         => 'everest-forms-mcp',
					'version'      => defined( 'EVF_VERSION' ) ? EVF_VERSION : '0.0.0',
					'protocol'     => '2024-11-05',
					'transport'    => 'http',
					'capabilities' => array( 'tools' => array( 'listChanged' => false ) ),
				),
				200
			);
		}

		$body = $request->get_json_params();
		if ( empty( $body ) || ! is_array( $body ) ) {
			return self::jsonrpc_error( null, -32700, 'Parse error: invalid JSON body.' );
		}

		// Batch support per JSON-RPC 2.0.
		if ( isset( $body[0] ) ) {
			$responses = array();
			foreach ( $body as $entry ) {
				$resp = self::dispatch( $entry );
				if ( null !== $resp ) {
					$responses[] = $resp;
				}
			}
			return new WP_REST_Response( $responses, 200 );
		}

		$resp = self::dispatch( $body );
		return new WP_REST_Response( $resp, 200 );
	}

	/**
	 * Dispatch a single JSON-RPC envelope.
	 *
	 * @param array $msg JSON-RPC message.
	 * @return array|null Response envelope, or null for notifications.
	 */
	protected static function dispatch( $msg ) {
		$id     = isset( $msg['id'] ) ? $msg['id'] : null;
		$method = isset( $msg['method'] ) ? (string) $msg['method'] : '';
		$params = isset( $msg['params'] ) && is_array( $msg['params'] ) ? $msg['params'] : array();

		// Notifications (no id) get no response.
		$is_notification = ! array_key_exists( 'id', $msg );

		switch ( $method ) {
			case 'initialize':
				$result = array(
					'protocolVersion' => '2024-11-05',
					'serverInfo'      => array(
						'name'    => 'everest-forms-mcp',
						'version' => defined( 'EVF_VERSION' ) ? EVF_VERSION : '0.0.0',
					),
					'capabilities'    => array( 'tools' => new stdClass() ),
				);
				break;

			case 'initialized':
			case 'notifications/initialized':
				return null;

			case 'tools/list':
				$result = array( 'tools' => self::tools_list() );
				break;

			case 'tools/call':
				$name = isset( $params['name'] ) ? (string) $params['name'] : '';
				$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();
				if ( '' === $name ) {
					return self::jsonrpc_error( $id, -32602, 'Missing required parameter: name.' );
				}
				$result = self::tools_call( $name, $args );
				if ( is_wp_error( $result ) ) {
					return self::jsonrpc_error( $id, -32000, $result->get_error_message(), $result->get_error_data() );
				}
				break;

			case 'ping':
				$result = new stdClass();
				break;

			default:
				if ( $is_notification ) {
					return null;
				}
				return self::jsonrpc_error( $id, -32601, sprintf( 'Method not found: %s', $method ) );
		}

		if ( $is_notification ) {
			return null;
		}
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		);
	}

	/**
	 * Build the MCP tools/list response from our ability registry.
	 *
	 * @return array
	 */
	protected static function tools_list() {
		$tools = array();
		foreach ( EVF_Abilities_Registry::instance()->all() as $id => $ability ) {
			$meta = self::ability_meta( $ability );
			$tools[] = array(
				'name'        => self::tool_name_from_id( $id ),
				'description' => $meta['description'],
				'inputSchema' => $meta['input_schema'],
			);
		}
		return $tools;
	}

	/**
	 * Execute a tool call.
	 *
	 * @param string $tool_name Tool name (MCP-side, e.g. "list-forms").
	 * @param array  $arguments Arguments.
	 * @return array|WP_Error Result envelope for MCP, or error.
	 */
	protected static function tools_call( $tool_name, $arguments ) {
		// Accept either the bare name ("list-forms") or the fully-qualified
		// ability id ("everest-forms/list-forms"). Sanitize and validate
		// against the actual registry so unknown names fail fast with a clear
		// error rather than passing through to execute() and producing a
		// less-specific "ability not found" later on.
		$bare       = (string) $tool_name;
		$bare       = preg_replace( '#^' . preg_quote( EVF_Abilities::NAMESPACE_ID, '#' ) . '/#', '', $bare );
		$bare       = preg_replace( '/[^a-z0-9_-]/', '', strtolower( $bare ) );
		$ability_id = EVF_Abilities::NAMESPACE_ID . '/' . $bare;

		$known = EVF_Abilities_Registry::instance()->all();
		if ( ! isset( $known[ $ability_id ] ) ) {
			return new WP_Error(
				'evf_unknown_tool',
				sprintf( 'Unknown tool "%s". Call tools/list to see available tools.', $bare ),
				array( 'status' => 404 )
			);
		}

		$result = EVF_Abilities_Registry::instance()->execute( $ability_id, $arguments );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// MCP expects "content" array of typed parts. We return a single
		// JSON text part so clients can both parse it and show it.
		return array(
			'content' => array(
				array(
					'type' => 'text',
					'text' => wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
				),
			),
			'isError' => false,
		);
	}

	/**
	 * Normalize ability metadata regardless of whether it came from the
	 * Abilities API (object) or our internal registry (array).
	 *
	 * @param mixed $ability Ability handle.
	 * @return array{description: string, input_schema: array}
	 */
	protected static function ability_meta( $ability ) {
		$description  = '';
		$input_schema = array( 'type' => 'object' );

		if ( is_object( $ability ) ) {
			if ( method_exists( $ability, 'get_description' ) ) {
				$description = (string) $ability->get_description();
			}
			if ( method_exists( $ability, 'get_input_schema' ) ) {
				$schema = $ability->get_input_schema();
				if ( ! empty( $schema ) ) {
					$input_schema = $schema;
				}
			}
		} elseif ( is_array( $ability ) ) {
			$description  = isset( $ability['description'] ) ? (string) $ability['description'] : '';
			$input_schema = isset( $ability['input_schema'] ) ? $ability['input_schema'] : $input_schema;
		}

		return array(
			'description'  => $description,
			'input_schema' => $input_schema,
		);
	}

	/**
	 * Convert "everest-forms/list-forms" into the MCP-side tool name "list-forms".
	 *
	 * Many MCP clients are stricter about tool names than ability ids, so we
	 * keep the namespace implicit and use the bare ability name on the wire.
	 *
	 * @param string $id Ability id.
	 * @return string
	 */
	protected static function tool_name_from_id( $id ) {
		$prefix = EVF_Abilities::NAMESPACE_ID . '/';
		if ( 0 === strpos( (string) $id, $prefix ) ) {
			return substr( $id, strlen( $prefix ) );
		}
		return (string) $id;
	}

	/**
	 * Build a JSON-RPC 2.0 error response.
	 *
	 * @param mixed       $id      Message id.
	 * @param int         $code    Error code.
	 * @param string      $message Message.
	 * @param mixed|null  $data    Optional data.
	 * @return array
	 */
	protected static function jsonrpc_error( $id, $code, $message, $data = null ) {
		$err = array(
			'code'    => (int) $code,
			'message' => (string) $message,
		);
		if ( null !== $data ) {
			$err['data'] = $data;
		}
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => $err,
		);
	}
}
