<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_receipt extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'receipt', 'store_id' => 0, 'for_storage' => ''];
	}
	
	protected function add_document_data(  )
	{	
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );		
		$this->js_args['storage'] = usam_get_storage( $this->data['store_id'] );	
		if( $this->js_args['storage'] )
		{
			$location = usam_get_location( $this->js_args['storage']['location_id'] );
			$this->js_args['storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['storage']['id'], 'address');
		}
		$this->add_products_document();
	}	
}
?>