<?php
/**
 * Likert field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Likert Class.
 */
class EVF_Field_Likert extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Likert', 'everest-forms' );
		$this->type   = 'likert';
		$this->icon   = 'evf-icon evf-icon-likert';
		$this->order  = 20;
		$this->group  = 'survey';
		$this->is_pro = true;
		$this->plan   = 'plus professional agency themegrill-agency';
		$this->addon  = 'everest-forms-survey-polls-quiz';
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => 'lDKH45N4fPU',
		);

		parent::__construct();
	}
}
