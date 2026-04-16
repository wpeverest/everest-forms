<?php
/**
 * Net Promoter Score field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Net_Promoter_Score Class.
 */
class EVF_Field_Net_Promoter_Score extends EVF_Form_Fields {


	public function __construct() {
		$this->name   = esc_html__( 'Net Promoter Score', 'everest-forms' );
		$this->type   = 'net-promoter-score';
		$this->icon   = 'evf-icon evf-icon-net-promoter-score';
		$this->order  = 250;
		$this->group  = 'survey';
		$this->is_pro = true;

		parent::__construct();
	}

}
