<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_partner_order extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf( __('Заказ от партнера №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );
	}	
	
	protected function data_default()
	{
		return ['type' => 'partner_order', 'contract' => 0];
	}
	
	protected function add_document_data(  )
	{
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );	
		$this->add_products_document();
	}	
}
?>