<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_invoice extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'invoice', 'closedate' => date("Y-m-d H:i:s", strtotime('+5 days')), 'contract' => 0, 'conditions' => ''];
	}
	
	protected function form_class( ) 
	{
		return 'edit_form_products_document';
	}
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );		
		$this->add_products_document();
	}	

	function display_document_properties()
	{						
		$this->display_document_counterparties(); 
		$this->display_document_contract(); ?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Срок','usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<datetime-picker v-model="data.closedate"/>
			</div>
		</div>
		<?php
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
		<usam-box :id="'usam_document_conditions'" :title="'<?php _e( 'Условия и комментарии', 'usam'); ?>'">
			<template v-slot:body>
				<textarea class="width100" rows='3' cols='40' maxlength='255' v-model="data.conditions"></textarea>
			</template>
		</usam-box>
		<?php
		
	}
}
?>