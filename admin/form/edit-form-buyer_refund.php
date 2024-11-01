<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_buyer_refund extends USAM_Edit_Form_Document
{	
	protected function data_default()
	{
		return ['type' => 'buyer_refund', 'store_id' => 0];
	}
	
	protected function form_class( ) 
	{
		return 'edit_form_products_document';
	}
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['storage'] = usam_get_storage( $this->data['store_id'] );	
		if( $this->js_args['storage'] )
		{
			$location = usam_get_location( $this->js_args['storage']['location_id'] );
			$this->js_args['storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['storage']['id'], 'address');
		}
		$this->add_products_document();
	}	

	function display_document_properties()
	{					
		$this->display_document_counterparties(); ?>				
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Склад','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="object change_object" v-if="data.store_id>0" @click="sidebar('storages')">
					<div class="object_title" v-html="storage.title"></div>
					<div class="object_description" v-html="storage.city+' '+storage.address"></div>
				</div>				
				<a v-else @click="sidebar('storages')"><?php esc_html_e( 'Выбрать склад', 'usam'); ?></a>				
			</div>
		</div>	
		<?php
		add_action('usam_after_form',function() {
			require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-storages.php' );
		});
		usam_vue_module('list-table');
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