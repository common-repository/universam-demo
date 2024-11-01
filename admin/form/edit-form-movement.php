<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_movement extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'movement', 'from_storage' => '', 'for_storage' => '', 'name' => __( 'Новое перемещение', 'usam')];
	}
	
	protected function add_document_data(  )
	{	
		unset($this->blocks['contacts']);	
		$this->js_args['from_storage'] = usam_get_storage( $this->data['from_storage'] );	
		if( $this->js_args['from_storage'] )
		{
			$location = usam_get_location( $this->js_args['from_storage']['location_id'] );
			$this->js_args['from_storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['from_storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['from_storage']['id'], 'address');
		}
		$this->js_args['for_storage'] = usam_get_storage( $this->data['for_storage'] );	
		if( $this->js_args['for_storage'] )
		{
			$location = usam_get_location( $this->js_args['for_storage']['location_id'] );
			$this->js_args['for_storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['for_storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['for_storage']['id'], 'address');
		}
		$this->add_products_document();
	}	

	function display_document_properties()
	{						
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Ваша фирма','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.bank_account_id'>
					<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
				</select>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Со склада','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="object change_object" v-if="data.from_storage>0" @click="sidebar('storages','from_storage')">
					<div class="object_title" v-html="from_storage.title"></div>
					<div class="object_description" v-html="from_storage.city+' '+from_storage.address"></div>
				</div>				
				<a v-else @click="sidebar('storages','from_storage')"><?php esc_html_e( 'Выбрать склад', 'usam'); ?></a>				
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'На склад','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="object change_object" v-if="data.for_storage>0" @click="sidebar('storages','for_storage')">
					<div class="object_title" v-html="for_storage.title"></div>
					<div class="object_description" v-html="for_storage.city+' '+for_storage.address"></div>
				</div>				
				<a v-else @click="sidebar('storages','for_storage')"><?php esc_html_e( 'Выбрать склад', 'usam'); ?></a>				
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