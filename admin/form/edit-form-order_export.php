<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/export-form.php' );
class USAM_Form_order_export extends USAM_Form_Export
{	
	protected $rule_type = 'order_export';
	protected function get_columns_sort() 
	{
		return ['date' => __('По дате','usam'), 'id' => __('По номеру','usam'), 'status' => __('По статусу','usam')];
	}	
	
	public function display_filter() 
	{		
		?>
		 <div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_status'><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select-list @change="data.status=$event.id" :multiple='1' :lists="statuses" :selected="data.status"></select-list>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Группы', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select-list @change="data.groups=$event.id" :multiple='1' :lists="groups" :selected="data.groups"></select-list>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Источник', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.source=$event.id" :multiple='1' :lists='<?php echo json_encode( usam_get_order_source() ); ?>' :selected="data.source"></select-list>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_id'><?php esc_html_e( 'Номер', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.from_id" name="from_id" class="interval"> - 
					<input type="text" v-model="data.to_id" name="to_id" class="interval">	
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Дата создания', 'usam'); ?>:</div>
				<div class="edit_form__item_option edit_form__item_group">
					<v-date-picker v-model="data.from_dateinsert" :input-debounce="800">
						<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents" class="date_picker"/></template>
					</v-date-picker>
					<span> - </span>
					<v-date-picker v-model="data.to_dateinsert" :input-debounce="800">
						<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents" class="date_picker"/></template>
					</v-date-picker>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_ordersum'><?php esc_html_e( 'Сумма', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.from_ordersum" name="from_ordersum" class="interval"> - 
					<input type="text" v-model="data.to_ordersum" name="to_ordersum" class="interval">	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_productcount'><?php esc_html_e( 'Количество товаров', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.from_productcount" name="from_productcount"  class="interval"> - 
					<input type="text" v-model="data.to_productcount" name="to_productcount" class="interval">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_location'><?php esc_html_e( 'Местоположение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php 
					$location_id = usam_get_exchange_rule_metadata( $this->id, 'location' );	
					$location = usam_get_full_locations_name( $location_id );
					?>
					<autocomplete :selected="'<?php echo $location; ?>'" @change="data.location=$event.id" :request="'locations'"></autocomplete>
					<input type="hidden" v-model="data.location" name="location">						
				</div>
			</div>		
		</div>	
	   <?php   
	}	
}
?>