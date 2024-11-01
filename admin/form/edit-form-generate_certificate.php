<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_generate_certificate extends USAM_Edit_Form
{	
	protected $action = 'generate';	
	
	protected function get_title_tab()
	{ 	
		return __('Генерация сертификатов', 'usam');	
	}

	protected function get_data_tab(  )
	{			
		$this->data = array('format' => 'U*********', 'type_format' => 'n', 'quantity' => 5, 'description' => '', 'action' => '', 'max_is_used' => 0, 'value' => '', 'active' => 0, 'start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s', time()+3600*24*360),  'is_percentage' => 0, 'user_id' => 0, 'amount_bonuses_author' => 0 );			
	}	

	function coupon_generate_settings( )
	{			
		?>	
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_format'><?php esc_html_e( 'Формат сертификата', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_format' size ="60" value='<?php echo $this->data['format']; ?>' name='format'/>
					<p class="description"><?php _e( 'Используйте цифры и буквы для фиксированной части кода и * для генерируемой', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type_format'><?php esc_html_e( 'Тип формата', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='type_format' id='option_type_format'>
						<option value='ln' <?php selected($this->data['type_format'],'ln'); ?> ><?php esc_html_e( 'Буквы и цифры', 'usam'); ?></option>
						<option value='l' <?php selected($this->data['type_format'],'l'); ?> ><?php esc_html_e( 'Буквы', 'usam'); ?></option>
						<option value='n' <?php selected($this->data['type_format'],'n'); ?>><?php esc_html_e( 'Цифры', 'usam'); ?></option>							
					</select>	
					<p class="description"><?php _e( 'Какие символы использовать в коде создаваемого сертификата', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_quantity'><?php esc_html_e( 'Количество', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_quantity' size ="60" required value='<?php echo $this->data['quantity']; ?>' name='quantity'/>
					<p class="description"><?php _e( 'Сколько сертификатов создать', 'usam'); ?></p>
				</div>
			</div>	
		</div>
		<?php 
	}	

	function coupon_data_settings( )
	{	
		?>	
		<div class="edit_form">		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Активировать', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span id="rule-selected">				
						<label><input type="radio" name="active" value="1" <?php checked($this->data['active'], 1) ?>><?php _e('Да', 'usam'); ?></label>	
						<label><input type="radio" name="active" value="0" <?php checked($this->data['active'], 0) ?>><?php _e('Нет', 'usam'); ?></label>
					</span>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_value'><?php esc_html_e( 'Номинал сертификата', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_value' value='<?php echo $this->data['value']; ?>' size='10' name='value'>
				</div>
			</div>
		</div>
		<?php 
	}
		
	function display_left()
	{				
		$conditions = usam_get_coupon_metadata( $this->id, 'conditions' );
		usam_add_box( 'usam_generate_settings', __('Параметры генерации','usam'), array( $this, 'coupon_generate_settings' ) );					
		usam_add_box( 'usam_coupon_data_settings', __('Параметры сертификата','usam'), array( $this, 'coupon_data_settings' ) );
		usam_add_box( 'usam_condition', __('Условия использования','usam'), array( $this, 'display_rules_work_basket' ), $conditions );		
    }
	
	function display_right()
	{			
		$this->add_box_description( $this->data['description'] );
    }
}
?>