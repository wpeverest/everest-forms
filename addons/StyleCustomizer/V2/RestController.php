<?php
/**
 * Style Customizer v2 — REST controller.
 *
 * Read/save a form's v2 style record. Registers into the existing `everest-forms/v1`
 * namespace. Every route is gated by an admin capability; untrusted input is run through
 * {@see Sanitizer} before it ever touches the option.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * `everest-forms/v1/styles/{id}` controller.
 */
final class RestController {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'styles';

	/**
	 * Hook the controller into EVF's REST registry. Called from {@see Engine::boot()}.
	 */
	public static function register() {
		add_filter( 'everest_forms_rest_api_get_rest_namespaces', array( __CLASS__, 'add_namespace' ) );
	}

	/**
	 * Append this controller to the v1 namespace so `EVF_REST_API` instantiates + registers it.
	 *
	 * @param array $namespaces namespace => class-names.
	 * @return array
	 */
	public static function add_namespace( $namespaces ) {
		$namespaces['everest-forms/v1'][] = __CLASS__;
		return $namespaces;
	}

	/**
	 * Register the routes (called by `EVF_REST_API::register_rest_routes()`).
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Form ID.', 'everest-forms' ),
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Store the builder's current (unsaved) structure for the live preview (see PreviewDraft).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/preview-draft',
			array(
				'args' => array(
					'id' => array(
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_preview_draft' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// AI styling — turn a plain-text prompt into style tokens (see ai_style()).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/ai',
			array(
				'args' => array(
					'id' => array(
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'ai_style' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Delete a user template (create/update retired — see EVF style-customizer-v2 UX rework).
		register_rest_route(
			$this->namespace,
			'/style-templates/(?P<tid>[A-Za-z0-9\-]+)',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_template' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Delete a reusable custom colour palette (Pro) (create/update retired — see EVF
		// style-customizer-v2 UX rework).
		register_rest_route(
			$this->namespace,
			'/style-palettes/(?P<pid>[A-Za-z0-9\-]+)',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_palette' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

	}

	/**
	 * DELETE /style-templates/{tid} — remove a user template.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_template( $request ) {
		$deleted = Templates::delete_user_template( (string) $request['tid'] );
		return rest_ensure_response( array( 'deleted' => (bool) $deleted ) );
	}

	/**
	 * DELETE /style-palettes/{pid} — remove a reusable custom colour palette (Pro).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_palette( $request ) {
		if ( ! Engine::pro_active() ) {
			return self::pro_only_palette_error();
		}
		$deleted = Palettes::delete( (string) $request['pid'] );
		return rest_ensure_response(
			array(
				'deleted'  => (bool) $deleted,
				'palettes' => Palettes::all_custom(),
			)
		);
	}

	/**
	 * The shared 403 for a custom-palette write attempted without Pro.
	 *
	 * @return \WP_Error
	 */
	protected static function pro_only_palette_error() {
		return new \WP_Error(
			'evf_style_pro_only',
			__( 'Custom color palettes are a Pro feature.', 'everest-forms' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * POST /styles/{id}/ai — turn a plain-text prompt into style tokens via the AI gateway.
	 * Returns the AI's intent sanitized into a partial v2 record; nothing is persisted here.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function ai_style( $request ) {
		if ( ! class_exists( 'EVF_AI_API' ) || ! class_exists( 'EVF_AI_Registration' ) ) {
			return new \WP_Error(
				'evf_ai_unavailable',
				__( 'AI features are unavailable on this site.', 'everest-forms' ),
				array( 'status' => 501 )
			);
		}

		if ( \EVF_AI_Registration::is_local_site() ) {
			return new \WP_Error(
				'evf_ai_not_registered',
				__( 'AI features are not available on local or staging sites.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		$prompt        = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );
		$refine_prompt = sanitize_textarea_field( (string) $request->get_param( 'refine_prompt' ) );
		$current       = $request->get_param( 'current_record' );
		$current       = is_array( $current ) ? $current : array();

		// Conversation memory: without these, a refine call only sees the ORIGINAL prompt plus
		// the current token dump, with no signal of which key(s) the gateway itself touched last
		// turn — that ambiguity is what causes a "increase it to 200px" follow-up to land on the
		// wrong (often form-wide) property instead of continuing the specific edit it just made.
		$history            = self::sanitize_history( $request->get_param( 'history' ) );
		$last_changed_keys  = self::sanitize_token_keys( $request->get_param( 'last_changed_keys' ) );

		if ( '' === $prompt ) {
			return new \WP_Error(
				'evf_style_bad_request',
				__( 'Please describe the look you want.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		if ( ! \EVF_AI_Registration::is_registered() ) {
			\EVF_AI_Registration::register();
		}
		if ( ! \EVF_AI_Registration::is_registered() ) {
			return new \WP_Error(
				'evf_ai_not_registered',
				__( 'This site could not be registered with the AI service. Please try again.', 'everest-forms' ),
				array( 'status' => 502 )
			);
		}

		$form_id      = absint( $request['id'] );
		$field_labels = self::get_field_labels( $form_id );

		$ai_style = \EVF_AI_API::style_form(
			$prompt,
			$current,
			$refine_prompt,
			$history,
			$last_changed_keys,
			array(
				'field_labels'    => $field_labels,
				'available_fonts' => function_exists( 'evfsc_get_google_font_families' ) ? evfsc_get_google_font_families() : array(),
			)
		);

		if ( is_wp_error( $ai_style ) ) {
			$code = $ai_style->get_error_code();
			if ( in_array( $code, array( 'rate_limit', 'daily_limit_reached' ), true ) ) {
				$status = 429;
			} elseif ( 'off_topic_prompt' === $code ) {
				$status = 400; // The prompt itself was rejected — not a gateway failure.
			} else {
				$status = 502;
			}
			$upstream_data = $ai_style->get_error_data();
			// "tier" (only present on a "daily_limit_reached" 429) lets the panel show an
			// upgrade CTA for a Free user but not for a Pro user who hit their own, higher cap.
			$tier = is_array( $upstream_data ) && isset( $upstream_data['tier'] ) ? $upstream_data['tier'] : '';
			// Usage snapshot (remaining is 0 on a daily_limit_reached) so the panel's
			// "X requests left today" indicator stays in sync even on the blocked turn.
			return new \WP_Error(
				$code,
				$ai_style->get_error_message(),
				array( 'status' => $status, 'tier' => $tier, 'usage' => \EVF_AI_API::get_last_usage() )
			);
		}

		$requested_tokens  = isset( $ai_style['tokens'] ) && is_array( $ai_style['tokens'] ) ? $ai_style['tokens'] : array();
		$requested_palette = isset( $ai_style['palette'] ) ? (string) $ai_style['palette'] : '';

		$clean = Sanitizer::sanitize_record(
			array(
				'tokens'  => $requested_tokens,
				'palette' => $requested_palette,
			),
			true
		);
		$clean_palette = isset( $clean['palette'] ) ? $clean['palette'] : '';

		$pro_notice = self::pro_locked_notice( $requested_tokens, $clean['tokens'], $requested_palette, $clean_palette );
		$cap_notice = self::unsupported_capability_notice( $clean['tokens'], trim( $prompt . ' ' . $refine_prompt ), $field_labels );

		// Did ANYTHING the AI asked for this turn actually survive sanitization? If not, the
		// gateway's own summary ("I've applied…") is flatly wrong and must not be shown at all —
		// appending an honest correction after a false "applied" claim just reads as
		// self-contradictory ("Applied a 20px font size. Pro required for: Font size — not
		// applied."). Replace the summary outright instead of appending to it.
		$nothing_applied = empty( $clean['tokens'] ) && '' === $clean_palette;
		$blocked         = $pro_notice && $nothing_applied;

		$summary = isset( $ai_style['summary'] ) ? sanitize_text_field( (string) $ai_style['summary'] ) : '';
		if ( $blocked ) {
			$summary = $pro_notice;
		} elseif ( $pro_notice ) {
			$summary = trim( $summary . ' ' . $pro_notice );
		}
		if ( $cap_notice ) {
			$summary = trim( $summary . ' ' . $cap_notice );
		}

		return rest_ensure_response(
			array(
				'tokens'     => $clean['tokens'],
				'palette'    => $clean_palette,
				'summary'    => $summary,
				// Only when the WHOLE request was Pro-blocked — a partial success still gets the
				// normal "applied" treatment with an inline caveat, not the full warning treatment.
				'notice'     => $blocked,
				// Unlike "notice" above, the upgrade link isn't limited to a fully-blocked turn —
				// ANY Pro-only token/palette the AI couldn't apply should offer a way to upgrade,
				// even when other tokens from the same turn did apply.
				'notice_url' => $pro_notice ? \EVF_AI_Ajax::get_notice_upgrade_url() : '',
				// Daily-usage snapshot { remaining, limit, used } so the panel can show
				// "X requests left today" — see EVF_AI_API::get_last_usage().
				'usage'      => \EVF_AI_API::get_last_usage(),
			)
		);
	}

	/**
	 * Sanitize the client's conversation history for the AI gateway — a plain, capped list of
	 * {role, text} turns so a refine call carries the actual dialogue, not just the very first
	 * prompt ever typed in this chat session.
	 *
	 * @param mixed $raw Raw `history` param.
	 * @return array List of { role: 'user'|'assistant', text: string }, oldest first, capped at 20.
	 */
	protected static function sanitize_history( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( array_slice( $raw, -20 ) as $turn ) {
			if ( ! is_array( $turn ) || empty( $turn['text'] ) ) {
				continue;
			}
			$out[] = array(
				'role' => 'user' === ( $turn['role'] ?? '' ) ? 'user' : 'assistant',
				'text' => sanitize_textarea_field( (string) $turn['text'] ),
			);
		}
		return $out;
	}

	/**
	 * Sanitize a list of token keys the client claims the AI's PREVIOUS turn changed — filtered
	 * against the real schema so only genuine token keys ever reach the gateway or any local logic.
	 *
	 * @param mixed $raw Raw `last_changed_keys` param.
	 * @return array List of valid schema keys.
	 */
	protected static function sanitize_token_keys( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $key ) {
			if ( is_string( $key ) && Schema::get( $key ) ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/**
	 * Current form's field labels (skipping structural fields with no user-facing label) —
	 * used to spot when a prompt names one specific field for a change the engine can only
	 * apply form-wide (see {@see unsupported_capability_notice()}).
	 *
	 * @param int $form_id Form post ID.
	 * @return array List of field labels.
	 */
	protected static function get_field_labels( $form_id ) {
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return array();
		}
		$data   = evf_decode( $post->post_content );
		$labels = array();
		foreach ( ( $data['form_fields'] ?? array() ) as $field ) {
			if ( ! empty( $field['label'] ) ) {
				$labels[] = (string) $field['label'];
			}
		}
		return $labels;
	}

	/**
	 * Build an honest addendum for AI-requested changes that survived sanitization but can't
	 * actually be fulfilled the way the AI's summary implies:
	 *  - a background image: the AI has no access to this site's media library, so any URL it
	 *    invents is fake — never let it apply silently (EVF-2706-style "attempted the impossible").
	 *  - a font family that isn't a real, currently-available Google Font — the schema's static
	 *    options only contain the "Theme default" placeholder (real families are data-driven), so
	 *    an invented name would otherwise sail through sanitization and silently no-op in the browser.
	 *  - field spacing/padding named one specific field in the prompt — the engine only has a
	 *    form-wide spacing token, so the change (still applied, it's the best available) needs an
	 *    honest caveat instead of quietly affecting every field.
	 *
	 * @param array  $clean_tokens Sanitized tokens for this turn — mutated by reference to strip
	 *                             anything this guard rejects outright (image, bad font).
	 * @param string $prompt_text  The user's combined prompt + refine text, for the field-name check.
	 *                             Pass '' when unavailable.
	 * @param array  $field_labels Current form's field labels, for the field-name check. Pass an
	 *                             empty array when unavailable.
	 * @return string Empty string when nothing was flagged.
	 *
	 * Public: also called by {@see EVF_AI_Form_Builder::maybe_apply_ai_style()} so the AI form
	 * builder surfaces the same honest notice as the Style Customizer's own AI launcher.
	 */
	public static function unsupported_capability_notice( array &$clean_tokens, $prompt_text, array $field_labels ) {
		$labels = array();

		if ( ! empty( $clean_tokens['wrap.bgImage']['desktop'] ) ) {
			unset( $clean_tokens['wrap.bgImage'] );
			$labels[] = __( 'a background image (choose one from the Style panel\'s Background section instead)', 'everest-forms' );
		}

		if ( ! empty( $clean_tokens['fonts.family']['desktop'] ) ) {
			$family    = (string) $clean_tokens['fonts.family']['desktop'];
			$available = function_exists( 'evfsc_get_google_font_families' ) ? evfsc_get_google_font_families() : array();
			if ( ! in_array( $family, $available, true ) ) {
				unset( $clean_tokens['fonts.family'] );
				$labels[] = sprintf(
					/* translators: %s: font family name the AI could not find. */
					__( '"%s" (not a recognised font — pick one from the Style panel instead)', 'everest-forms' ),
					$family
				);
			}
		}

		$field_wide_only = array_intersect( array_keys( $clean_tokens ), array( 'field.margin', 'input.pad' ) );
		if ( $field_wide_only && $prompt_text ) {
			foreach ( $field_labels as $label ) {
				if ( $label && false !== stripos( $prompt_text, $label ) ) {
					$labels[] = sprintf(
						/* translators: %s: the specific field the user named. */
						__( 'spacing for just "%s" (field spacing currently applies to every field at once)', 'everest-forms' ),
						$label
					);
					break;
				}
			}
		}

		if ( ! $labels ) {
			return '';
		}

		return sprintf(
			/* translators: %s: comma-separated list of requested changes the AI could not apply as asked. */
			__( "Couldn't apply %s.", 'everest-forms' ),
			wp_sprintf( '%l', $labels )
		);
	}

	/**
	 * Build an honest addendum for when the AI's raw response asked for tokens/palette that
	 * {@see Sanitizer::sanitize_record()} then had to drop or replace on a free site — either
	 * because a token/palette is Pro-only, or because a "free" (palette-driven) token was given
	 * a raw custom colour instead of coming from one of the 2 free palettes (see EVF-2708).
	 * Without this, the AI's own summary claims a change that never actually applied.
	 *
	 * Public: also called by {@see EVF_AI_Form_Builder::maybe_apply_ai_style()} for the
	 * create-time/refine "style" block embedded in a form-generation response, so a free
	 * site gets the same honest notice there as it does from the Style Customizer's own
	 * AI launcher — not just here.
	 *
	 * @param array  $requested_tokens  Raw tokens the AI asked to set.
	 * @param array  $clean_tokens      Tokens that survived sanitization.
	 * @param string $requested_palette Raw palette id the AI asked for.
	 * @param string $clean_palette     Palette id that survived sanitization.
	 * @return string Empty string when nothing was Pro-blocked.
	 */
	public static function pro_locked_notice( array $requested_tokens, array $clean_tokens, $requested_palette, $clean_palette ) {
		if ( Engine::pro_active() ) {
			return '';
		}

		$labels               = array();
		$custom_color_blocked = false;

		foreach ( $requested_tokens as $key => $requested_value ) {
			$token = Schema::get( $key );
			if ( ! $token ) {
				continue;
			}
			if ( 'pro' === $token['tier'] ) {
				if ( ! array_key_exists( $key, $clean_tokens ) ) {
					$labels[] = $token['label'];
				}
				continue;
			}
			if ( 'free' === $token['tier'] ) {
				// Free tier: on a non-Pro site this key's clean value is always DERIVED from the
				// chosen palette (see Sanitizer::sanitize_record()), never trusted raw — so if it's
				// missing, or doesn't match what was asked for, a custom colour got replaced with
				// the palette's own colour instead (see EVF-2708).
				$requested = is_array( $requested_value ) ? ( $requested_value['desktop'] ?? null ) : $requested_value;
				$clean_bag = $clean_tokens[ $key ] ?? null;
				$clean_val = is_array( $clean_bag ) ? ( $clean_bag['desktop'] ?? null ) : $clean_bag;
				if ( ! is_string( $requested ) || ! is_string( $clean_val ) || strtolower( $requested ) !== strtolower( $clean_val ) ) {
					$custom_color_blocked = true;
				}
			}
		}

		$pro_palette_blocked = false;
		if ( $requested_palette && $requested_palette !== $clean_palette ) {
			foreach ( Schema::palettes() as $palette ) {
				if ( $palette['id'] === $requested_palette && ! empty( $palette['is_pro'] ) ) {
					$labels[]             = $palette['name'];
					$pro_palette_blocked  = true;
					break;
				}
			}
		}

		$messages = array();
		if ( $labels ) {
			$messages[] = sprintf(
				/* translators: %s: comma-separated list of Pro-only style features the AI could not apply. */
				__( 'Pro required for: %s — not applied.', 'everest-forms' ),
				wp_sprintf( '%l', array_unique( $labels ) )
			);
		}
		// Only add this when the palette itself wasn't already the (more specific) reason above.
		if ( $custom_color_blocked && ! $pro_palette_blocked ) {
			$messages[] = __( 'Custom colors are a Pro feature — Free is limited to the 2 built-in palettes.', 'everest-forms' );
		}

		return implode( ' ', $messages );
	}

	/**
	 * POST /styles/{id}/preview-draft — cache the builder's current serialized structure so the
	 * live preview iframe can render unsaved Fields/Settings-tab edits. Nothing is written to the
	 * form (see {@see PreviewDraft}). Also returns the section/token visibility recomputed
	 * against that same unsaved state (e.g. a field-type-gated group, or a Pro addon's
	 * `evf_style_v2_sections` condition like Multi-Part's "Enable Multi-Part form" toggle) so the
	 * panel can show/hide sections live, without requiring a save first.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function save_preview_draft( $request ) {
		$form_id      = absint( $request['id'] );
		$form_data    = $request->get_param( 'form_data' );
		$session      = (string) $request->get_param( 'session' );
		$style_tokens = $request->get_param( 'style_tokens' );

		if ( ! is_string( $form_data ) || '' === $form_data ) {
			PreviewDraft::clear( $form_id, $session );
			return rest_ensure_response( array( 'stored' => false ) );
		}

		$stored   = PreviewDraft::store( $form_id, $form_data, $session, is_array( $style_tokens ) ? $style_tokens : null );
		$response = array( 'stored' => (bool) $stored );

		if ( is_array( PreviewDraft::current( $form_id ) ) ) {
			$response['sections'] = apply_filters( 'evf_style_v2_sections', Schema::sections(), $form_id );
			$response['schema']   = self::visible_tokens( Schema::tokens(), $form_id );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Only users who can manage forms may read/write styles.
	 *
	 * @return bool
	 */
	public function permissions_check() {
		return current_user_can( 'manage_everest_forms' );
	}

	/**
	 * Detect which named palette a legacy record's `color_palette` selection matches. Returns
	 * an empty string if it matches none (a custom palette).
	 *
	 * @param array $legacy Legacy record.
	 * @return string Palette id, or ''.
	 */
	protected static function detect_palette( $legacy ) {
		if ( empty( $legacy['color_palette'] ) || ! is_array( $legacy['color_palette'] ) ) {
			return '';
		}
		$colors = reset( $legacy['color_palette'] );
		if ( ! is_array( $colors ) ) {
			return '';
		}
		$norm = static function ( $v ) {
			return strtolower( trim( (string) $v ) );
		};

		$builtins = array();
		$customs  = array();
		foreach ( Schema::palettes() as $palette ) {
			if ( empty( $palette['is_custom'] ) ) {
				$builtins[] = $palette;
			} else {
				$customs[] = $palette;
			}
		}
		foreach ( array_merge( $builtins, $customs ) as $palette ) {
			$match = true;
			foreach ( $palette['colors'] as $slot => $value ) {
				if ( ! isset( $colors[ $slot ] ) || $norm( $colors[ $slot ] ) !== $norm( $value ) ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				return $palette['id'];
			}
		}
		return '';
	}

	/**
	 * Read the per-form "Apply Theme Style" flag. Reuses the same meta the v1 preview toggle
	 * and the front-end shortcode wrapper use.
	 *
	 * @param int $form_id Form id.
	 * @return bool
	 */
	protected static function get_apply_theme_style( $form_id ) {
		return 'default' !== get_post_meta( $form_id, 'everest_forms_enable_theme_style', true );
	}

	/**
	 * Build the full styles payload for a form — the saved record plus the schema the panel
	 * renders from. Static so it can be shared between `get_item()` and
	 * {@see BuilderPanel::enqueue()}, which localizes it directly into the builder page.
	 *
	 * @param int $form_id Form id.
	 * @return array
	 */
	public static function build_payload( $form_id ) {
		$form_id = absint( $form_id );
		$all     = get_option( 'everest_forms_styles', array() );
		$stored  = isset( $all[ $form_id ] ) && is_array( $all[ $form_id ] ) ? $all[ $form_id ] : array();

		if ( Engine::is_v2_record( $stored ) ) {
			$record = $stored;
		} elseif ( ! empty( $stored ) ) {
			// A legacy (v1) record: migrate on read; only persisted when the user saves.
			$record            = Migrator::migrate_record( $stored );
			$record['palette'] = self::detect_palette( $stored );

			// v1 never had a per-form Custom CSS field of its own — the only "Custom CSS" a user
			// could edit while styling THIS form lived in WP core's site-wide Additional CSS
			// section, surfaced by the legacy per-form Customizer screen (see
			// class-evf-style-customizer-api.php, EVF-2737). Seed it here so a form whose
			// rendering already depended on that shared CSS doesn't visually change — or look
			// like its CSS was erased (EVF-2732) — the moment it's first migrated; from here on
			// each form owns an independent, editable copy, and that legacy screen no longer
			// exposes the global section for anyone to leak further.
			if ( empty( $record['custom_css'] ) && function_exists( 'wp_get_custom_css' ) ) {
				$global_css = wp_get_custom_css();
				if ( '' !== trim( (string) $global_css ) ) {
					$record['custom_css'] = $global_css;
				}
			}
		} else {
			$record = array(
				'schema_version' => Schema::version(),
				'tokens'         => array(),
			);
		}

		/**
		 * Filter the record right before it's sent to the panel — regardless of which of the 3
		 * branches above produced it. Lets an addon whose own settings live OUTSIDE
		 * `everest_forms_styles` (e.g. Multi-Part's per-form pagination settings, stored in the
		 * form's own post_content) backfill its tokens the first time this form's record is read,
		 * without depending on {@see Migrator::migrate_record()}'s one-time, legacy-only hook.
		 *
		 * @param array $record  The record about to be returned to the panel.
		 * @param int   $form_id Form id.
		 */
		$record = apply_filters( 'evf_style_v2_record', $record, $form_id );

		return array(
			'form_id'        => $form_id,
			'schema_version' => Schema::version(),
			'schema'         => self::visible_tokens( Schema::tokens(), $form_id ),
			/**
			 * Filter the sections shown in the panel for THIS form — e.g. Multi-Part hides its
			 * "Pagination" section unless the form actually has multi-step enabled, the same way
			 * {@see self::visible_tokens()} hides the "Upload area" group without a File/Image
			 * Upload field. Display-only: {@see Schema::tokens()} itself (what Sanitizer/Compiler
			 * use) is untouched, so hiding a section never drops already-saved customization.
			 *
			 * @param array $sections Section definitions keyed by section id.
			 * @param int   $form_id  Form id.
			 */
			'sections'       => apply_filters( 'evf_style_v2_sections', Schema::sections(), $form_id ),
			'palettes'       => Schema::palettes(),
			'palette_map'    => Schema::palette_map(),
			'templates'      => Templates::all(),
			'user_templates' => Templates::user_templates(),
			'breakpoints'    => Compiler::breakpoints(),
			'google_fonts'   => function_exists( 'evfsc_get_google_font_families' ) ? evfsc_get_google_font_families() : array(),
			'apply_theme_style' => self::get_apply_theme_style( $form_id ),
			// Display-only; the security boundary is enforced server-side on save.
			'pro_active'     => Engine::pro_active(),
			'record'         => $record,
			// Drives the panel's migration banner; informational only, not reversible.
			'migration'      => array(
				'just_migrated' => ! empty( $stored ) && ! Engine::is_v2_record( $stored ),
			),
		);
	}

	/**
	 * Drop token GROUPS that only make sense for a field type this form doesn't have — currently
	 * just "Upload area" (the file.* tokens) without a File/Image Upload field. Panel-display
	 * concern only: the full {@see Schema::tokens()} list (what Sanitizer/Compiler actually use)
	 * is untouched, so a form that later loses its upload field doesn't lose saved customization.
	 *
	 * @param array $tokens  Full token list.
	 * @param int   $form_id Form id.
	 * @return array
	 */
	protected static function visible_tokens( $tokens, $form_id ) {
		$types = self::form_field_types( $form_id );
		if ( isset( $types['file-upload'] ) || isset( $types['image-upload'] ) ) {
			return $tokens;
		}
		return array_values(
			array_filter(
				$tokens,
				static function ( $token ) {
					return 'Upload area' !== $token['group'];
				}
			)
		);
	}

	/**
	 * The set of field TYPES present on a form (e.g. `'file-upload' => true`), for gating
	 * field-type-dependent style groups/sections. Memoized per request — {@see build_payload()}
	 * and any addon hooking `evf_style_v2_sections` may both want this for the same form.
	 *
	 * @param int $form_id Form id.
	 * @return array
	 */
	protected static function form_field_types( $form_id ) {
		$form_id = absint( $form_id );

		// A draft just POSTed in this same request (see save_preview_draft()) reflects unsaved
		// Fields-tab edits — prefer it over the last-saved row so a field added/removed since the
		// last save is immediately reflected, not just after the form is saved.
		$draft = PreviewDraft::current( $form_id );
		if ( is_array( $draft ) ) {
			return self::extract_field_types( isset( $draft['form_fields'] ) ? $draft['form_fields'] : array() );
		}

		static $cache = array();
		if ( isset( $cache[ $form_id ] ) ) {
			return $cache[ $form_id ];
		}
		$types = array();
		if ( $form_id ) {
			$form = evf()->form->get( $form_id, array( 'content_only' => true, 'cap' => array() ) );
			if ( isset( $form['form_fields'] ) && is_array( $form['form_fields'] ) ) {
				$types = self::extract_field_types( $form['form_fields'] );
			}
		}
		$cache[ $form_id ] = $types;
		return $types;
	}

	/**
	 * @param array $form_fields Form fields, keyed by field id.
	 * @return array Field types present, as `[ type => true ]`.
	 */
	protected static function extract_field_types( $form_fields ) {
		$types = array();
		if ( is_array( $form_fields ) ) {
			foreach ( $form_fields as $field ) {
				if ( isset( $field['type'] ) ) {
					$types[ $field['type'] ] = true;
				}
			}
		}
		return $types;
	}

	/**
	 * GET — thin wrapper around {@see self::build_payload()}.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {
		return rest_ensure_response( self::build_payload( absint( $request['id'] ) ) );
	}

	/**
	 * POST — sanitize and persist the record. Optimistic-concurrency: if the caller sends a
	 * stale `base_updated_at`, reject with 409 so it can reload rather than clobber.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_item( $request ) {
		$form_id = absint( $request['id'] );

		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new \WP_Error(
				'evf_style_no_form',
				__( 'Form not found.', 'everest-forms' ),
				array( 'status' => 404 )
			);
		}

		$incoming = $request->get_param( 'record' );
		if ( ! is_array( $incoming ) ) {
			return new \WP_Error(
				'evf_style_bad_request',
				__( 'Missing or invalid "record".', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		$all          = get_option( 'everest_forms_styles', array() );
		$base_updated = $request->get_param( 'base_updated_at' );

		if ( isset( $all[ $form_id ]['_updated_at'] ) && null !== $base_updated
			&& (int) $all[ $form_id ]['_updated_at'] !== (int) $base_updated ) {
			return new \WP_Error(
				'evf_style_conflict',
				__( 'These styles changed elsewhere — reload before saving.', 'everest-forms' ),
				array( 'status' => 409 )
			);
		}

		self::maybe_backup_legacy_record( $form_id, $all );

		$clean      = Sanitizer::sanitize_record( $incoming );
		$old_record = isset( $all[ $form_id ] ) && is_array( $all[ $form_id ] ) ? $all[ $form_id ] : array();
		$clean      = Sanitizer::preserve_stale_pro_tokens( $clean, $old_record );

		// Re-read right before writing to avoid clobbering a concurrent save to a different form.
		$all             = get_option( 'everest_forms_styles', array() );
		$all[ $form_id ] = $clean;
		update_option( 'everest_forms_styles', $all, false ); // autoload=no.

		$apply_theme = $request->get_param( 'apply_theme_style' );
		if ( null !== $apply_theme ) {
			update_post_meta(
				$form_id,
				'everest_forms_enable_theme_style',
				rest_sanitize_boolean( $apply_theme ) ? 'theme' : 'default'
			);
		}

		return rest_ensure_response(
			array(
				'saved'             => true,
				'record'            => $clean,
				'_updated_at'       => $clean['_updated_at'],
				'apply_theme_style' => self::get_apply_theme_style( $form_id ),
			)
		);
	}

	/**
	 * Snapshot a form's pre-v2 record the first time it's about to be overwritten by a v2 save.
	 *
	 * @param int   $form_id Form id.
	 * @param array $all     The full `everest_forms_styles` option (pre-overwrite).
	 */
	protected static function maybe_backup_legacy_record( $form_id, $all ) {
		if ( empty( $all[ $form_id ] ) || Engine::is_v2_record( $all[ $form_id ] ) ) {
			return;
		}
		$backups = get_option( 'everest_forms_styles_legacy_backup', array() );
		if ( isset( $backups[ $form_id ] ) ) {
			return;
		}
		$backups[ $form_id ] = $all[ $form_id ];
		update_option( 'everest_forms_styles_legacy_backup', $backups, false ); // autoload=no.
	}

}
