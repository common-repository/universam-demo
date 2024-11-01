<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_act extends USAM_View_Form_Document
{		
	protected function add_document_data(  )
	{	
		$this->add_products_document();
	}	
	
	protected function get_edit()
	{  
		if ( $this->data['status'] == 'approved' )
			return false;
		else
			return true;
	}
}
?>