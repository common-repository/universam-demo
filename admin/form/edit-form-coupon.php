<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-coupon.php' );
class USAM_Form_coupon extends USAM_Form_Coupon_Code
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить купон № %s','usam'), $this->data['coupon_code'] );
		else
			$title = __('Добавить купон', 'usam');	
		return $title;
	}	

	function coupon_data_settings( )
	{	
		?>	
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='coupon_code'><?php esc_html_e( 'Код купона', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' size ="60" id="coupon_code" required value='<?php echo $this->data['coupon_code']; ?>' name='coupon_code'/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='coupon_type'><?php esc_html_e( 'Тип купона', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='coupon_type'>
						<option value='coupon' <?php selected($this->data['coupon_type'], 'coupon'); ?> ><?php esc_html_e( 'Купон', 'usam'); ?></option>
						<option value='referral' <?php selected($this->data['coupon_type'], 'referral'); ?>><?php esc_html_e( 'Реферальная ссылка', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_action'><?php esc_html_e( 'Выполнить действие', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="option_action" name='coupon_action'>
						<option value='b' <?php selected( $this->data['action'], 'b'); ?> ><?php esc_html_e( 'Изменить стоимость корзины', 'usam'); ?></option>
						<option value='s' <?php selected( $this->data['action'], 's'); ?>><?php esc_html_e( 'Изменить стоимость доставки', 'usam'); ?></option>							
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_value'><?php esc_html_e( 'Скидка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id="option_value" value='<?php echo $this->data['value']; ?>' size='10' name='value' style ="width:300px;"/>
					<select name='is_percentage' class="select_type_md">
						<option value='0' <?php selected($this->data['is_percentage'], 0); ?> ><?php echo esc_html( usam_get_currency_sign() ) ?></option>
						<option value='1' <?php selected($this->data['is_percentage'], 1); ?>>%</option>
					</select>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_max_is_used'><?php esc_html_e( 'Максимальное число использований', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $max_is_used = empty($this->data['max_is_used'])?'':$this->data['max_is_used']; ?>
					<input type='text' id='option_max_is_used' value='<?php echo $max_is_used; ?>' name='max_is_used'/>
				</div>
			</div>			
			<div class ="edit_form__item">
				<?php 				
				$bonus_calculation_option = stripos($this->data['amount_bonuses_author'],'%') !== false?1:0; 
				$this->data['amount_bonuses_author'] = preg_replace("/[^0-9\\.,]/", '', $this->data['amount_bonuses_author']);
				?>
				<div class ="edit_form__item_name"><label for='bonuses_author'><?php esc_html_e( 'Зачислить владельцу', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='bonuses_author' value='<?php echo $this->data['amount_bonuses_author']; ?>' name='bonuses_author' style ="width:300px;"/>
					<select name='bonus_calculation_option' class="select_type_md">
						<option value='0' <?php selected($bonus_calculation_option, 0); ?> ><?php echo esc_html( usam_get_currency_sign() ) ?></option>
						<option value='1' <?php selected($bonus_calculation_option, 1); ?>>%</option>
					</select>
				</div>
			</div>
		</div>	
		<?php 
	}
	
	function display_coupon_url( )
	{	
		?>	
		<div class="edit_form">	
			<div class ="edit_form__item">
				<span class="js-copy-clipboard"><?php echo usam_get_coupon_url($this->data['coupon_code']); ?></span>
			</div>			
		</div>	
		<?php 
	}
			
	function display_left()
	{					
		$conditions = usam_get_coupon_metadata( $this->id, 'conditions' );
		usam_add_box( 'usam_coupon_data_settings', __('Параметры купона','usam'), array( $this, 'coupon_data_settings' ) );	
		usam_add_box( 'usam_condition', __('Условия использования','usam'), array( $this, 'display_rules_work_basket' ), $conditions );		
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );
		$this->add_box_description( $this->data['description'] );				
		$title = __('Владелец','usam');
		$title_button = $this->data['user_id']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='select_user' data-screen='user' data-list='users'  class='js-modal'>$title_button</a>";	
		usam_add_box( 'usam_user', $title, array( $this, 'display_users_metabox' ) );			
		usam_add_box( 'usam_coupon_url', __('Ссылка на применения купона','usam'), array( $this, 'display_coupon_url' ) );
    }
}
?>