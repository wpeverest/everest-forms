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
					// Server-level guidance the client prepends to the model's
					// context. This is the cross-cutting "knowledge base" for
					// behaviors that no single tool description can carry —
					// e.g. "create forms inactive by default", enum value
					// mappings, exact field-type names. See self::instructions().
					'instructions'    => self::instructions(),
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
			$meta        = self::ability_meta( $ability );
			$bare        = self::tool_name_from_id( $id );
			$destructive = EVF_Abilities::is_destructive( $bare );

			// MCP "annotations" hint to the client (Claude Desktop, etc.) how
			// a tool behaves so the UI can render an appropriate confirmation
			// prompt before invoking it. Without these, Claude Desktop treats
			// every tool as safe and skips the Allow/Deny UI — which is what
			// we want for read-only tools, but is dangerous for create/delete
			// abilities.
			//
			// Spec: https://modelcontextprotocol.io/specification/server/tools#tool-annotations
			$annotations = array(
				'title'           => isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucwords( str_replace( '-', ' ', $bare ) ),
				'readOnlyHint'    => ! $destructive,
				'destructiveHint' => $destructive,
				// `idempotent` is true when calling the tool twice with the
				// same args has no further effect. Read-only tools are
				// idempotent by definition; for destructive ones we lean
				// conservative and say no (better the client warns extra
				// than warns too little).
				'idempotentHint'  => ! $destructive,
				// `openWorldHint` = true means the tool reaches outside the
				// host system (e.g., the public internet). Most EVF
				// abilities act only on the local DB — false. The
				// exception is activate-addon, which can pull resources
				// during plugin activation, but it stays on the local site
				// so still false.
				'openWorldHint'   => false,
			);

			$tools[] = array(
				'name'        => $bare,
				'description' => $meta['description'],
				'inputSchema' => $meta['input_schema'],
				'annotations' => $annotations,
			);
		}
		return $tools;
	}

	/**
	 * Server-level instructions returned on `initialize`.
	 *
	 * The MCP client prepends this to the model's context for the whole
	 * session. It's the home for cross-cutting rules that don't belong in any
	 * single tool's description: safe defaults, value mappings the model can't
	 * guess, and exact identifiers. Keep it tight — it costs tokens on every
	 * conversation (though clients cache it).
	 *
	 * Filterable so PRO/addons can append their own guidance.
	 *
	 * @return string
	 */
	public static function instructions() {
		$lines = array(
			'You are operating Everest Forms (a WordPress form plugin) through these tools. Read these rules before calling any tool.',
			'',
			'## Identifiers — never guess',
			'- Forms and entries are referenced by numeric ids. There is NO lookup-by-name. If the user names a form ("my contact form"), call list-forms first to find its id; do not invent one.',
			'- To act on entries, get their ids from list-entries first. To inspect a form\'s fields/layout before editing, call get-form.',
			'',
			'## Form activation (IMPORTANT — strict default)',
			'- Forms created with create-form are INACTIVE by default and must stay that way unless the user explicitly asks to publish, activate, or make the form live.',
			'- Do NOT pass status:"publish" on create-form unless the user clearly requested it. When unsure, omit status (it defaults to inactive) and tell the user they can publish when ready.',
			'- "Inactive" means the Active toggle is off (form_enabled=0) and post_status is "inactive"; the form will not accept submissions until published.',
			'- Use update-form-status to move an existing form between publish / draft / trash.',
			'',
			'## Field types — use these EXACT ids (guessing causes errors)',
			'- Core text: "text" (not "single-line"/"input"), "textarea" (not "paragraph"), "email", "url", "number", "phone".',
			'- Names: "first-name" + "last-name" as two separate fields (there is no single "name" type).',
			'- Choice types: "select" (not "dropdown"), "radio", "checkbox", "country" — these REQUIRE a non-empty `choices` array or validation fails.',
			'- Structure/static: "date-time", "address", "file-upload", "image-upload", "hidden", "html", "divider", "title", "signature", "wysiwyg".',
			'- Survey (needs Survey, Polls and Quiz addon): "likert", "scale-rating", "yes-no", "rating".',
			'- Payment (needs PRO / Coupons): "payment-single", "payment-radio", "payment-checkbox", "payment-quantity", "payment-coupon", "payment-subtotal", "payment-total".',
			'- If unsure a type exists or is usable on this site, call list-field-types or describe-field-type (check its usable_now flag) first.',
			'',
			'## Choices format (select / radio / checkbox / country)',
			'- Pass `choices` as an array. Each item may be a bare string ("Red") or an object {label, value?, default?}.',
			'- Example: choices: [ {"label":"Basic","value":"basic","default":true}, "Pro", "Enterprise" ].',
			'',
			'## Layout',
			'- Prefer ONE create-form call with a complete `layout` over create-empty-then-patch. Cheaper and atomic.',
			'- `layout` is an array of rows: [ { "row": [ field, field ] } ]. Single-field rows span full width.',
			'- Side-by-side fields: put them in the same row with grid:1 / grid:2 (up to grid:3).',
			'- Multi-step forms: set multi_part:{ enabled:true, parts:[{name:"Step 1"}, ...] } and tag each layout row with part:N. Needs the Multi-Part addon.',
			'- Conversational (one question at a time): conversational:{ enabled:true, slug, title, description }. Needs the Conversational Forms addon.',
			'- Multi-part and conversational are MUTUALLY EXCLUSIVE on the same form — never set both.',
			'',
			'## Addon-required errors — how to react',
			'- action:"activate" -> addon is installed but inactive. activate-addon is TWO-STEP: first call WITHOUT confirm returns confirmation_required and does nothing. You MUST ask the user to confirm, then call activate-addon again with confirm:true, then retry the original call. Never set confirm:true unless the user explicitly agreed in this conversation.',
			'- action:"install_or_upgrade" -> addon is NOT installed. Do NOT call activate-addon (it will fail). Tell the user to install/purchase it from their Everest Forms account or upgrade their plan.',
			'- If the user declines either, omit the offending field/setting and proceed with the rest.',
			'- You can pre-check availability with list-addons (look at fully_operational) before building.',
			'',
			'## Setting value mappings (do NOT invent values)',
			'- Save and Continue must be active to use; enable via settings.enable_save_and_continue = "1".',
			'- Link expiration: settings.save_and_continue_time accepts only "week" | "two_weeks" | "month". Map natural language: ~7 days->"week", ~14 days->"two_weeks", ~30 days/1 month->"month".',
			'- Entry status values: "publish", "approved", "denied", "pending", "spam", "trash".',
			'- Form status values (update-form-status): "publish", "draft", "trash".',
			'',
			'## Entries',
			'- create-entry / update-entry-fields accept field references by either meta-key (e.g. "email_3") OR the field human label (e.g. "Email"); the server resolves either.',
			'- Pass fire_hooks:false on create-entry to skip email notifications/integrations (use for bulk/test imports).',
			'- For deleting many entries, use bulk-delete-entries with an array of ids rather than many delete-entry calls.',
			'- delete-entry / bulk-delete-entries default to permanent; pass permanent:false to move to trash instead.',
			'',
			'## Editing existing forms',
			'- update-form deep-merges `settings` (it does not replace them) and appends `fields`/`layout`.',
			'- To change one existing field without resending everything, use form_fields_patch:{ "<field_id>": { key:value } }. Get the field id from get-form first.',
			'',
			'## Safety & UX',
			'- create / update / delete / activate operations modify the site; the client will prompt the user to approve each one.',
			'- Use dry_run:true on create-form / update-form to preview the resulting fields + structure without saving.',
			'- After a successful change, briefly summarize what changed and share the returned edit_url so the user can review it.',
		);

		$instructions = implode( "\n", $lines );

		/**
		 * Filter the MCP server instructions sent to AI clients on initialize.
		 *
		 * @param string $instructions The default instructions block.
		 */
		return (string) apply_filters( 'everest_forms_mcp_instructions', $instructions );
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
		$label        = '';

		if ( is_object( $ability ) ) {
			if ( method_exists( $ability, 'get_description' ) ) {
				$description = (string) $ability->get_description();
			}
			if ( method_exists( $ability, 'get_label' ) ) {
				$label = (string) $ability->get_label();
			}
			if ( method_exists( $ability, 'get_input_schema' ) ) {
				$schema = $ability->get_input_schema();
				if ( ! empty( $schema ) ) {
					$input_schema = $schema;
				}
			}
		} elseif ( is_array( $ability ) ) {
			$description  = isset( $ability['description'] ) ? (string) $ability['description'] : '';
			$label        = isset( $ability['label'] ) ? (string) $ability['label'] : '';
			$input_schema = isset( $ability['input_schema'] ) ? $ability['input_schema'] : $input_schema;
		}

		return array(
			'label'        => $label,
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
