<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-rule_coupon.php' );
class USAM_Form_rule_coupon extends USAM_Form_Creating_Rule_Coupon
{				
	function coupon_data_settings( )
	{			
		if ( $this->id != null )
			$coupon = usam_get_coupon( $this->data['coupon_id'] );
		else	
			$coupon = array( 'amount_bonuses_author' => '', 'max_is_used' => 0, 'end_date' => '', 'start_date' => '' );	
		$currency = usam_get_currency_sign();
		?>		
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_format'><?php esc_html_e( 'Формат купона', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' size ="60" id='option_format' value='<?php echo $this->data['format']; ?>' name='format'/>
					<p class="description"><?php _e( 'Используйте цифры и буквы для фиксированной части кода купона и * для генерируемой', 'usam'); ?></p>
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
				<div class ="edit_form__item_name"><label for='rule_trigger'><?php esc_html_e( 'Триггер', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='rule_type'>
						<option value='referral' <?php selected($this->data['rule_type'],'referral'); ?>><?php esc_html_e( 'Создать реферальную ссылку при регистрации', 'usam'); ?></option>
						<option value='send_newsletter' <?php selected($this->data['rule_type'],'send_newsletter'); ?>><?php esc_html_e( 'Генерация купона в рассылке', 'usam'); ?></option>						
						<?php foreach ( usam_get_site_triggers() as $key => $name ){ ?>
							<option value='<?php echo $key; ?>' <?php selected($this->data['rule_type'],$key); ?>><?php echo $name; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал действия купона', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $coupon['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $coupon['end_date'] ); ?>
				</div>
			</div>
			<?php	
			if ( $this->data['rule_type'] != 'order_close' )
			{
				?>				
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_discount'><?php esc_html_e( 'Скидка по купону', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' value='<?php echo $this->data['discount']; ?>' id='option_discount' size='3' required name='discount' style ="width:300px;"/>
						<select name='discount_type' class="select_type_md">
							<option value='0' <?php selected($this->data['discount_type'],0); ?>><?php echo esc_html( $currency ) ?></option>
							<option value='1' <?php selected($this->data['discount_type'],1); ?>>%</option>					
						</select>
					</div>
				</div>	
				<div class ="edit_form__item">
					<?php	
					$bonus_calculation_option = stripos($coupon['amount_bonuses_author'],'%') !== false?1:0; 
					$coupon['amount_bonuses_author'] = preg_replace("/[^0-9\\.,]/", '', $coupon['amount_bonuses_author']);
					?>
					<div class ="edit_form__item_name"><label for='bonuses_author'><?php esc_html_e( 'Зачислить владельцу', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' id='bonuses_author' value='<?php echo $coupon['amount_bonuses_author']; ?>' name='bonuses_author' style ="width:300px;"/>
						<select name='bonus_calculation_option' class="select_type_md">
							<option value='0' <?php selected($bonus_calculation_option, 0); ?> ><?php echo esc_html( usam_get_currency_sign() ) ?></option>
							<option value='1' <?php selected($bonus_calculation_option, 1); ?>>%</option>
						</select>
					</div>
				</div>				
				<?php			
			}
			else
			{
				$this->data = array_merge(['percentage_of_use' => '', 'totalprice' => '', 'day' => ''], $this->data );
				?>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_discount'><?php esc_html_e( 'Скидка', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' value='<?php echo $this->data['discount']; ?>' id='option_discount' size='3' required name='discount' style ="width:300px;"/>
						<select name='discount_type' class="select_type_md">
							<option value='0' <?php selected($this->data['discount_type'],0); ?>><?php echo esc_html( $currency ) ?></option>
							<option value='1' <?php selected($this->data['discount_type'],1); ?>>%</option>
							<option value='2' <?php selected($this->data['discount_type'],2); ?>><?php esc_html_e( '% от заказа как фиксированная скидка', 'usam'); ?></option>
							<option value='3' <?php selected($this->data['discount_type'],3); ?>><?php esc_html_e( 'Бесплатная доставка', 'usam'); ?></option>
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_percentage_of_use'><?php esc_html_e( 'Использование', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' size='3' id='option_percentage_of_use' value="<?php echo esc_attr($this->data['percentage_of_use']); ?>" name='percentage_of_use'>
						<p class='description'><?php esc_html_e( 'Укажите процент от стоимости заказа, который активировал правило. Рассчитанный процент в рублях будет добавлен к условиям использования купона. Больше суммы, полученной при расчете, нельзя использовать созданный купон.', 'usam') ?></p>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_totalprice'><?php esc_html_e( 'Сумма заказа', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' size='10' id="option_totalprice" value="<?php echo $this->data['totalprice']; ?>" name='totalprice'/>
						<p class='description'><?php esc_html_e( 'Укажите сумму заказа, больше которой создавать купон.', 'usam') ?></p>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_day'><?php esc_html_e( 'Дней действует купон', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='text' class='pickdate' id='option_day' size='4' value="<?php echo $this->data['day']; ?>" name='day'>
					</div>
				</div>				
				<?php	
			}
			if ( $this->data['rule_type'] != 'referral' )
			{
			?>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_max_is_used'><?php esc_html_e( 'Максимальное число использований', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_max_is_used' value='<?php echo $coupon['max_is_used']; ?>' name='max_is_used'/>
				</div>
			</div>
			<?php } ?>
		</div>
		<?php
    }	
	
	function display_left()
	{					
		$this->titlediv( $this->data['title'] );		
		$conditions = usam_get_coupon_metadata( $this->id, 'conditions' );
		usam_add_box( 'usam_coupon_data_settings', __('Параметры купона','usam'), array( $this, 'coupon_data_settings' ) );	
		usam_add_box( 'usam_condition', __('Условия использования','usam'), array( $this, 'display_rules_work_basket' ), $conditions );	
		usam_add_box( 'usam_conditions_creating_coupon', __('Условия создания купона','usam'), array( $this, 'display_conditions_creating_coupon' ) );		
		usam_add_box( 'usam_options', __('Сообщение клиентам о купоне','usam'), array( $this, 'display_message' ) );			
    }
}
?>