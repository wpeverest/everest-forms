<?php
/**
 * Captcha field
 *
 * @package EverestForms\Fields
 * @since   1.6.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Captcha Class.
 */
class EVF_Field_Captcha extends EVF_Form_Fields {

	/**
	 * Math equation and operators.
	 *
	 * @var array
	 */
	public $math;

	/**
	 * Captcha questions to ask for.
	 *
	 * @var array
	 */
	public $questions;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Math Captcha', 'everest-forms' );
		$this->type     = 'captcha';
		$this->icon     = 'evf-icon evf-icon-captcha';
		$this->order    = 255;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->addon    = 'everest-forms-captcha';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'obScswjZ24Q',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'captcha',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'label_hide',
					'css',
				),
			),
		);

		// Allow customizing math captcha.
		$this->math = apply_filters(
			'everest_forms_math_captcha',
			array(
				'min' => 1,
				'max' => 15,
				'cal' => array( '+', '*' ),
			)
		);

		// Allow for additional questions or customizing captcha questions.
		$this->questions = apply_filters(
			'everest_forms_default_captcha_questions',
			array(
				1 => array(
					'question' => esc_html__( 'What is 2+3?', 'everest-forms' ),
					'answer'   => esc_html__( '5', 'everest-forms' ),
				),
			)
		);

		parent::__construct();
	}

	/**
	 * Captcha Format and questions.
	 *
	 * @param array $field Field Data.
	 */
	public function captcha( $field ) {
		$format    = ! empty( $field['format'] ) ? esc_attr( $field['format'] ) : 'math';
		$questions = ! empty( $field['questions'] ) ? $field['questions'] : $this->questions;

		// Field is always required.
		$this->field_element(
			'text',
			$field,
			array(
				'type'  => 'hidden',
				'slug'  => 'required',
				'value' => '1',
			)
		);

		// Format.
		$format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'format',
				'value'   => esc_html__( 'Format', 'everest-forms' ),
				'tooltip' => sprintf( esc_html__( 'Choose a captcha format to be displayed on frontend.', 'everest-forms' ) ),
			),
			false
		);
		$format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'format',
				'value'   => $format,
				'options' => array(
					'math'     => esc_html__( 'Math', 'everest-forms' ),
					'question' => esc_html__( 'Question and Answer', 'everest-forms' ),
				),
			),
			false
		);

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'format',
				'content' => $format_label . $format_select,
			)
		);

		// Questions.
		$questions_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'questions',
				'value'   => esc_html__( 'Questions and Answers', 'everest-forms' ),
				'tooltip' => sprintf( esc_html__( 'Add multiple questions below to ask the user. We will select questions randomly.', 'everest-forms' ) ),
			),
			false
		);
		$questions_field = sprintf(
			'<ul data-next-id="%s" class="evf-questions-list" data-field-id="%s" data-field-type="%s">',
			max( array_keys( $questions ) ) + 1,
			esc_attr( $field['id'] ),
			esc_attr( $this->type )
		);
		foreach ( $questions as $key => $value ) {
			$questions_field .= '<li data-key="' . absint( $key ) . '">';
			$questions_field .= sprintf( '<div class="question-wrap"><input type="text" name="form_fields[%s][questions][%s][question]" value="%s" class="question" placeholder="%s"><a class="add" href="#"><i class="dashicons dashicons-plus"></i></a></div>', $field['id'], $key, esc_attr( $value['question'] ), esc_html__( 'Question', 'everest-forms' ) );
			$questions_field .= sprintf( '<div class="answer-wrap"><input type="text" name="form_fields[%s][questions][%s][answer]" value="%s" class="answer" placeholder="%s"><a class="remove" href="#"><i class="dashicons dashicons-minus"></i></a></div>', $field['id'], $key, esc_attr( $value['answer'] ), esc_html__( 'Answer', 'everest-forms' ) );
			$questions_field .= '</li>';
		}
		$questions_field .= '</ul>';

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'questions',
				'content' => $questions_label . $questions_field,
				'class'   => 'math' === $format ? 'everest-forms-hidden' : '',
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$format      = ! empty( $field['format'] ) ? $field['format'] : 'math';
		$number1     = wp_rand( $this->math['min'], $this->math['max'] );
		$number2     = wp_rand( $this->math['min'], $this->math['max'] );
		$cal         = $this->math['cal'][ wp_rand( 0, count( $this->math['cal'] ) - 1 ) ];
		$questions   = ! empty( $field['questions'] ) ? $field['questions'] : $this->questions;
		$question    = current( $questions );

		// Label.
		$this->field_preview_option( 'label', $field );
		?>
		<div class="format-selected format-selected-<?php echo esc_attr( $format ); ?>">
			<span class="everest-forms-equation">
				<?php printf( '%s %s %s = ', $number1, $cal, $number2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<p class="everest-forms-question"><?php echo esc_html( $question['question'] ); ?></p>
			<input type="text" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="widefat" disabled>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
