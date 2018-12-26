<?php
/**
 * Score Rating field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Score_Rating Class.
 */
class EVF_Field_Score_Rating extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Score Rating', 'everest-forms' );
		$this->type   = 'score-rating';
		$this->icon   = 'evf-icon';
		$this->order  = 220;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
