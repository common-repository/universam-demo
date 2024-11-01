<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_partner_order extends USAM_Edit_Form_Document
{	
	protected function data_default()
	{
		return ['type' => 'partner_order', 'contract' => 0];
	}
	
	protected function add_document_data(  )
	{
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );	
		$this->add_products_document();
	}	
		
	function display_document_properties()
	{				
		$this->display_document_counterparties(); 
		$this->display_document_contract();
    }
	
	function display_document_footer()
	{
		$this->register_modules_products();
		?>
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-document.php' ); ?>
			</template>
		</usam-box>
		<?php
		
	}
}
?>