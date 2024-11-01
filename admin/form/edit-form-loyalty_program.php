<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_loyalty_program extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf(__('Изменить программу &laquo;%s&raquo;','usam'), '{{data.name}}' ).'</span><span v-else>'.__('Добавить программу','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{
		$default = ['name' => '', 'description' => '', 'start_date' => '', 'end_date' => '', 'what' => 'bonus_card', 'active' => 1, 'rule_type' => 'registration', 'total_purchased' => 0, 'value' => ''];			
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_bonuses_rules');
		$this->data = usam_format_data( $default, $this->data );		
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i" );	
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i" );
	}
		
	function display_left()
	{				
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Введите название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<div class ="form_description"><textarea name='description' v-model="data.description" placeholder="<?php _e('Описание программы', 'usam') ?>"></textarea></div>
		</div>		
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Сумма начисления', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Куда начислять', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select  name="what" v-model="data.what">						
								<option value='bonus_card' <?php selected($this->data['what'],'bonus_card'); ?>><?php esc_html_e('На бонусную карту', 'usam'); ?></option>
								<option value='accounts' <?php selected($this->data['what'],'accounts'); ?>><?php esc_html_e('На персональный счет', 'usam'); ?></option>
							</select>	
						</div>
					</div>			
					<div class ="edit_form__item" v-show="data.rule_type!='order_close'">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' v-model="data.value" name='value' size='10' maxlength="10"/>
						</div>
					</div>	
				</div>	
			</template>
		</usam-box>
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Условия начисления', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Интервал', 'usam'); ?>:</div>
						<div class ="edit_form__item_option date_intervals">
							<datetime-picker v-model="data.start_date"></datetime-picker> - <datetime-picker v-model="data.end_date"></datetime-picker>
							<input type="hidden" name="start_date" v-model="data.start_date"/>
							<input type="hidden" name="end_date" v-model="data.end_date"/>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Когда начислять', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select name="rule_type" v-model="data.rule_type">
								<?php foreach ( usam_get_site_triggers() as $key => $name ){ ?>
									<option value='<?php echo $key; ?>'><?php echo $name; ?></option>		
								<?php } ?>					
							</select>	
						</div>
					</div>			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Уже куплено на сумму', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' id="option_value" v-model="data.total_purchased" name='total_purchased' size='10' maxlength="10"/>
						</div>
					</div>			
				</div>	
			</template>
		</usam-box>
		<?php	
	}
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
		
    }
}
?>