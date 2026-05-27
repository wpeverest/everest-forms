<?php
/**
 * Field-type schemas + addon hint registry.
 *
 * The Abilities API needs to validate field definitions before persisting them
 * to a form. This class is the source of truth for:
 *
 *   1. Which keys a field type accepts (label, required, choices, etc.)
 *   2. Per-type defaults applied when the caller omits something.
 *   3. Which Everest Forms addon ships a given field type, so we can produce
 *      "you need to install/activate addon X" errors when the LLM asks for a
 *      type that isn't registered on this site.
 *
 * The runtime check for whether a type is *currently usable* still goes
 * through `evf()->form_fields->form_fields` — this class only adds metadata
 * and friendly errors on top of that.
 *
 * @package EverestForms\Abilities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field schemas.
 */
class EVF_Field_Schemas {

	/**
	 * Common keys every field accepts. Type-specific keys layer on top.
	 *
	 * @var string[]
	 */
	const COMMON_KEYS = array(
		'label',
		'description',
		'required',
		'placeholder',
		'default_value',
		'css',
		'label_hide',
		'meta-key',
	);

	/**
	 * Type -> addon slug map. Used to emit a friendly error when a requested
	 * field type isn't registered (e.g. "needs the Survey, Polls and Quiz
	 * addon"). Missing entries default to "Everest Forms PRO".
	 *
	 * @return array<string, array{addon: string, plugin: string}>
	 */
	public static function addon_hints() {
		return array(
			// Survey, Polls & Quiz.
			'likert'                 => array( 'addon' => 'Survey, Polls and Quiz', 'plugin' => 'everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php' ),
			'scale-rating'           => array( 'addon' => 'Survey, Polls and Quiz', 'plugin' => 'everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php' ),
			'yes-no'                 => array( 'addon' => 'Survey, Polls and Quiz', 'plugin' => 'everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php' ),

			// PRO field types.
			'address'                => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'color'                  => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'country'                => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'credit-card'            => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'file-upload'            => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'image-upload'           => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'lookup'                 => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'password'               => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'phone'                  => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'privacy-policy'         => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'range-slider'           => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'rating'                 => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'signature'              => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'wysiwyg'                => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),

			// Payments (PRO).
			'payment-single'         => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'payment-checkbox'       => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'payment-radio'          => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'payment-quantity'       => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'payment-coupon'         => array( 'addon' => 'Coupons (PRO)',    'plugin' => 'everest-forms-coupons/everest-forms-coupons.php' ),
			'payment-subtotal'       => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),
			'payment-total'          => array( 'addon' => 'Everest Forms PRO', 'plugin' => 'everest-forms-pro/everest-forms-pro.php' ),

			// Captcha.
			'captcha'                => array( 'addon' => 'Captcha', 'plugin' => 'everest-forms-captcha/everest-forms-captcha.php' ),
			'recaptcha'              => array( 'addon' => 'Captcha', 'plugin' => 'everest-forms-captcha/everest-forms-captcha.php' ),
			'hcaptcha'               => array( 'addon' => 'Captcha', 'plugin' => 'everest-forms-captcha/everest-forms-captcha.php' ),
			'turnstile'              => array( 'addon' => 'Captcha', 'plugin' => 'everest-forms-captcha/everest-forms-captcha.php' ),

			// Repeater.
			'repeater'               => array( 'addon' => 'Repeater Fields', 'plugin' => 'everest-forms-repeater-fields/everest-forms-repeater-fields.php' ),
		);
	}

	/**
	 * Per-type extra accepted keys + per-type defaults.
	 *
	 * Schema shape: [ type => [ 'extra_keys' => [...], 'defaults' => [...], 'requires_choices' => bool ] ].
	 *
	 * @return array
	 */
	public static function type_schemas() {
		return array(
			'text'        => array(),
			'textarea'    => array( 'extra_keys' => array( 'limit_count', 'limit_mode' ) ),
			'email'       => array( 'extra_keys' => array( 'confirmation' ) ),
			'url'         => array(),
			'number'      => array( 'extra_keys' => array( 'min_value', 'max_value', 'step' ) ),
			'phone'       => array( 'extra_keys' => array( 'format' ) ),
			'first-name'  => array( 'extra_keys' => array( 'sublabels' ) ),
			'last-name'   => array( 'extra_keys' => array( 'sublabels' ) ),
			'select'      => array( 'extra_keys' => array( 'choices', 'multiple_choices' ), 'requires_choices' => true ),
			'radio'       => array( 'extra_keys' => array( 'choices' ), 'requires_choices' => true ),
			'checkbox'    => array( 'extra_keys' => array( 'choices' ), 'requires_choices' => true ),
			'date-time'   => array( 'extra_keys' => array( 'datetime_format', 'date_format', 'time_format' ) ),
			'hidden'      => array(),
			'html'        => array( 'extra_keys' => array( 'code' ) ),
			'divider'     => array(),
			'title'       => array( 'extra_keys' => array( 'subtitle' ) ),
			'address'     => array( 'extra_keys' => array( 'address_scheme' ) ),
			'country'     => array( 'extra_keys' => array( 'choices' ) ),
			'file-upload' => array( 'extra_keys' => array( 'extensions', 'max_size', 'multiple_upload' ) ),
			'image-upload'=> array( 'extra_keys' => array( 'extensions', 'max_size', 'multiple_upload' ) ),
			'rating'      => array( 'extra_keys' => array( 'rating_scale', 'rating_icon' ) ),
			'range-slider'=> array( 'extra_keys' => array( 'min_value', 'max_value', 'step' ) ),
			'signature'   => array(),
			'likert'      => array( 'extra_keys' => array( 'rows', 'columns', 'single_row' ), 'requires_choices' => false ),
			'scale-rating'=> array( 'extra_keys' => array( 'rating_scale' ) ),
			'yes-no'      => array(),
			'password'    => array( 'extra_keys' => array( 'confirmation' ) ),
			'wysiwyg'     => array(),
		);
	}

	/**
	 * Convenience: full schema for one type, merging common keys + type extras.
	 *
	 * @param string $type Field type id.
	 * @return array|null Null when the type isn't known here (still may be runtime-registered).
	 */
	public static function for_type( $type ) {
		$type    = (string) $type;
		$schemas = self::type_schemas();
		if ( ! isset( $schemas[ $type ] ) ) {
			return null;
		}
		$entry      = $schemas[ $type ];
		$extra_keys = isset( $entry['extra_keys'] ) ? (array) $entry['extra_keys'] : array();
		return array(
			'type'             => $type,
			'accepted_keys'    => array_values( array_unique( array_merge( self::COMMON_KEYS, $extra_keys ) ) ),
			'requires_choices' => ! empty( $entry['requires_choices'] ),
			'addon'            => self::addon_for( $type ),
		);
	}

	/**
	 * Addon hint for a type, or null if it ships with core.
	 *
	 * @param string $type Field type id.
	 * @return array|null
	 */
	public static function addon_for( $type ) {
		$hints = self::addon_hints();
		return isset( $hints[ $type ] ) ? $hints[ $type ] : null;
	}

	/**
	 * Set of field type ids currently registered at runtime, derived from the
	 * EVF Fields registry. This is the authoritative "is this type usable now"
	 * check; addon_hints() is only metadata for nicer errors.
	 *
	 * @return array<string, true>
	 */
	public static function registered_types() {
		$out      = array();
		$registry = function_exists( 'evf' ) ? evf()->form_fields : null;
		$groups   = ( is_object( $registry ) && isset( $registry->form_fields ) ) ? (array) $registry->form_fields : array();
		foreach ( $groups as $fields ) {
			foreach ( (array) $fields as $field ) {
				if ( is_object( $field ) && isset( $field->type ) ) {
					$out[ $field->type ] = true;
				}
			}
		}
		return $out;
	}
}
