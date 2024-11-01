<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_check extends USAM_Edit_Form_Document
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Чек №%s от %s на сумму %s','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->currency_display($this->data['totalprice']) );
		else
			$title = __('Добавить счет', 'usam');	
		return $title;
	}	
	
	protected function data_default()
	{
		return ['type' => 'check', 'store_id' => 0, 'shift_id' => '', 'info_check' => 0, 'payment_type' => 'cash', 'name' => __('Чек','usam')];
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
			<div class ="edit_form__item_name"><?php _e( 'Магазин','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="object change_object" v-if="data.store_id>0" @click="sidebar('storages')">
					<div class="object_title" v-html="storage.title"></div>
					<div class="object_description" v-html="storage.city+' '+storage.address"></div>
				</div>				
				<a v-else @click="sidebar('storages')"><?php esc_html_e( 'Выбрать магазин', 'usam'); ?></a>				
			</div>
		</div>		
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Номер смены','usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<input type='text' v-model='data.shift_id'>
			</div>
		</label>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Тип чека', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<select v-model='data.info_check'>
					<option value='1'><?php esc_html_e( 'Информационный чек', 'usam'); ?></option>
					<option value='0'><?php esc_html_e( 'Фискальный чек', 'usam'); ?></option>
				</select>
			</div>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Способ оплаты', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<?php $payment_types = usam_get_payment_types(); ?>
				<select v-model = "data.payment_type">
					<?php												
					foreach ( $payment_types as $key => $title ) 
					{
						?><option value='<?php echo $key; ?>'><?php echo $title; ?></option><?php
					}
					?>
				</select>
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