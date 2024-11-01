<?php	
require_once( USAM_FILE_PATH . '/admin/form/view-form-invoice.php' );
class USAM_Form_invoice_offer extends USAM_Form_invoice
{		
	protected function data_default()
	{
		return ['type' => 'invoice_offer', 'closedate' => date("Y-m-d H:i:s", strtotime('+5 days')), 'contract' => 0, 'conditions' => ''];
	}
	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Счет-оферта (Лицензионный договор) №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );
	}
}
?>