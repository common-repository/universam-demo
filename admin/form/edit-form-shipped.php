<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/basket/calculate_delivery_service.class.php' );
class USAM_Form_shipped extends USAM_Edit_Form
{		
	protected $vue = true;	
	protected $form_edit = true;
		
	protected function get_title_tab()
	{ 
		return '<span v-if="data.id">'.sprintf('%s № %s %s', usam_get_document_name('shipped'), '<span class="number">{{data.number}}</span>', '<span class="subtitle_date">'.__('от','usam').' {{localDate(data.date_insert,"'.get_option('date_format', 'Y/m/j').'")}}</span>' ).'</span><span v-else>'.sprintf('Добавить %s', mb_strtolower(usam_get_document_name('shipped')) ).'</span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}

	protected function get_data_tab(  )
	{					
		$this->register_modules_products();
		$default = ['id' => 0, 'number' => '', 'order_id' => 0, 'include_in_cost' => '', 'date_insert' => date('Y-m-d H:i:s'), 'courier' => 0, 'name' => '', 'method' => '', 'storage_pickup' => 0, 'storage' => 0,  'price' => 0, 'date_delivery' => '', 'readiness_date' => '', 'delivery_problem' => '', 'note' => '', 'external_document' => '', 'external_document_date' => '', 'date_exchange' => '', 'exchange' => 0, 'status' => '', 'track_id' => '', 'tax_id' => 0, 'seller_id' => 0, 'totalprice' => 0, 'type_price' => usam_get_manager_type_price(), 'storage_pickup_data' => [], 'storage_data' => []];
		if ( $this->id )
		{
			$this->data = usam_get_shipped_document( $this->id );		
			$metas = usam_get_shipped_document_metadata( $this->id );			
			foreach($metas as $metadata )
				if ( !isset($this->data[$metadata->meta_key]) )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
			if ( $this->viewing_not_allowed() )
			{
				$this->data = [];
				return;
			}
		}
		$this->data = usam_format_data( $default, $this->data );	
		if( !empty($this->data['date_delivery']) )
			$this->data['date_delivery'] = get_date_from_gmt( $this->data['date_delivery'], "Y-m-d H:i:s" );		
		if( !empty($this->data['external_document_date']) )
			$this->data['external_document_date'] = get_date_from_gmt( $this->data['external_document_date'], "Y-m-d H:i:s" );
		if( !empty($this->data['readiness_date']) )
			$this->data['readiness_date'] = get_date_from_gmt( $this->data['readiness_date'], "Y-m-d H:i:s" );
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i:s" );		
		if ( current_user_can('edit_shipped')  )
			$this->change = $this->data['status'] == 'shipped' || $this->data['status'] == 'canceled' ? false : true;
		else
			$this->change = false;	
		
		add_action('usam_after_form',function() {
			?>
			<modal-panel ref="modalstorages">
				<template v-slot:title><?php _e('Выбор склада', 'usam'); ?></template>
				<template v-slot:body="modalProps">
					<list-table v-if="modalProps.show" :load="modalProps.show" query="storages" :args="{add_fields:['phone','schedule','city','address'], issuing:1, owner:sidebardata.owner}">
						<template v-slot:thead>
							<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
							<th></th>	
						</template>
						<template v-slot:tbody="slotProps">
							<tr v-for="(item, k) in slotProps.items" @click="selectStorage(item); sidebar('storages')">
								<td class="column_title">
									<div class="object">
										<div class="object_title" v-html="item.title"></div>
										<div class="object_description" v-html="item.city+' '+item.address"></div>
									</div>	
								</td>
								<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
							</tr>
						</template>
					</list-table>
				</template>
			</modal-panel>
		<?php 
		});
		usam_vue_module('list-table');
	}	
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'shipped', 'view' );
	}
	
	protected function toolbar_buttons( ) 
	{
		?>		
		<div class="action_buttons__button" v-if="data.id>0"><a href="<?php echo add_query_arg(['form' => 'view']); ?>" class="button"><?php _e('Посмотреть','usam'); ?></a></div>
		<?php
		if ( current_user_can( 'edit_shipped' ) )
		{
			?><button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button><?php	
		}
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_shipped', 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}
				
	function display_left()
	{								
		?>
		<usam-box :id="'usam_products'" :title="'<?php _e( 'Состав отгрузки', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-shipped.php' ); ?>
			</template>		
		</usam-box>
		<usam-box :id="'usam_main_data_document'" :title="'<?php _e( 'Основная информация', 'usam'); ?>'">
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-main.php' ); ?>
			</template>
		</usam-box>
		<usam-box :id="'usam_courier'" :title="'<?php _e( 'Информация для курьера', 'usam'); ?>'" v-if="data.storage_pickup==0">
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-courier.php' ); ?>
			</template>
		</usam-box>		
		<?php	
	//	usam_add_box( 'usam_marking_codes', __('Маскировочные коды товаров отгрузки', 'usam'), array( $this, 'display_marking_codes' ) ); //$this->list_table('marking_codes_form');	
	}	
	
	function display_right()
	{			
		?>				
		<usam-box :id="'usam_display_attached'" :title="'<?php _e( 'Прикреплен', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Номер заказа', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.order_id">
						</div>													
					</div>
				</div>	
			</template>
		</usam-box>
		<usam-box :id="'usam_external_document'" :title="'<?php _e( 'Внешний документ', 'usam'); ?>'">
			<template v-slot:body>
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-external-document.php' ); ?>
			</template>
		</usam-box>
		<?php
	}
}
?>