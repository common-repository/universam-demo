<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );

class USAM_Form_receipt extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'receipt', 'store_id' => 0];
	}
	
	protected function add_document_data(  )
	{	
		unset($this->blocks['contacts']);
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

	function display_document_properties()
	{						
		$this->display_document_counterparties();	
		$this->display_document_contract();
		?>			
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'На склад','usam'); ?>:</div>
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
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-movement.php' ); ?>
			</template>
		</usam-box>
		<?php
		
	}
}
?>