<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_accumulative extends USAM_Edit_Form
{			
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило накопительных скидок &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить правило накопительных скидок', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		$default = ['name' => '', 'active' => 0, 'method' => 'price', 'end' => 0, 'sort' => 100, 'layers' => [], 'type_prices' => [], 'period' => 'u','period_from' => 1, 'period_from_type' => 'y','start_date' => '', 'end_date' => '','start_calculation_date' => '', 'end_calculation_date' => ''];
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_accumulative_discount');		
		$this->data = usam_format_data( $default, $this->data );		
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i" );	
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i" );
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	function display_left()
	{					
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<div class='edit_form'>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Период действия', 'usam'); ?>:</div>
					<div class ="edit_form__item_option date_intervals">
						<datetime-picker v-model="data.start_date"></datetime-picker> - <datetime-picker v-model="data.end_date"></datetime-picker>
						<input type="hidden" name="start_date" v-model="data.start_date"/>
						<input type="hidden" name="end_date" v-model="data.end_date"/>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_method'><?php esc_html_e( 'Тип скидки', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select id='option_method' name='method' v-model="data.method">
							<option value='price'><?php _e('Скидка к покупке','usam') ?></option>
							<option value='bonus'><?php _e('Начислить бонусы','usam') ?></option>						
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Период для расчета скидок', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select id="option_period" name='period' v-model="data.period">
							<option value='u'><?php _e('За все время','usam') ?></option>
							<option value='d'><?php _e('За период','usam') ?></option>		
							<option value='p'><?php _e('За последние','usam') ?></option>							
						</select>
					</div>
				</div>
				<div class ="edit_form__item" v-if="data.period=='d'">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Интервал расчета', 'usam'); ?>:</div>
					<div class ="edit_form__item_option date_intervals">
						<datetime-picker v-model="data.start_calculation_date"></datetime-picker> - <datetime-picker v-model="data.end_calculation_date"></datetime-picker>
						<input type="hidden" name="start_calculation_date" v-model="data.start_calculation_date"/>
						<input type="hidden" name="end_calculation_date" v-model="data.end_calculation_date"/>
					</div>
				</div>
				<div class ="edit_form__item" v-if="data.period=='p'">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Выбрать оплаченные заказы за последний(е)', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" name="period_from" class="interval" v-model="data.period_from" size="4">
						<select name='period_from_type' v-model="data.period_from_type" class="interval">
							<option value='d'><?php _e('День','usam') ?></option>
							<option value='m'><?php _e('Месяц','usam') ?></option>	
							<option value='y'><?php _e('Год','usam') ?></option>						
						</select>
					</div>
				</div>
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Сортировка', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" v-model="data.sort" name="sort" autocomplete="off">
					</div>
				</label>
			</div>	
		</div>	
		<usam-box :id="'usam_table_rate'" :handle="false" :title="'<?php _e( 'Таблица скидок', 'usam'); ?>'">
			<template v-slot:body>
				<level-table :lists='data.layers'>				
					<template v-slot:thead="slotProps">
						<th class="column_sum"><?php _e( 'Сумма', 'usam'); ?></th>	
						<th class="column_discount"><?php _e( 'Скидка', 'usam'); ?></th>	
						<th class="column_actions"></th>		
					</template>			
					<template v-slot:tbody="slotProps">
						<td class="column_sum" draggable="true">
							<input :ref="'value'+slotProps.k" type="text" name="sum[]" v-model="slotProps.row.sum" size="4">	
						</td>						
						<td class="column_discount">	
							<input type="text" name="discounts[]" v-model="slotProps.row.discount" size="4">
						</td>						
						<td class="column_actions">											
							<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
							<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>										
						</td>
					</template>
				</level-table>
			</template>
		</usam-box>	
		<?php
    }	
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
		usam_add_box( 'usam_prices', __('Типы цен','usam'), array( $this, 'selecting_type_prices' ) );				
    }
}
?>