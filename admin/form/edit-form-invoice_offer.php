<?php			
require_once( USAM_FILE_PATH . '/admin/form/edit-form-invoice.php' );
class USAM_Form_invoice_offer extends USAM_Form_invoice
{		
	protected function data_default()
	{
		return ['type' => 'invoice_offer', 'closedate' => date("Y-m-d H:i:s", strtotime('+5 days')), 'contract' => 0, 'conditions' => ''];
	}
}
?>