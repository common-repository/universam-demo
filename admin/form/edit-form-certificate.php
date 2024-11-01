<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-coupon.php' );
class USAM_Form_certificate extends USAM_Form_Coupon_Code
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить сертификат № %s','usam'), $this->data['coupon_code'] );
		else
			$title = __('Добавить сертификат', 'usam');	
		return $title;
	}
	
	function certificates_settings( )
	{	
		?>	
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='coupon_code'><?php esc_html_e( 'Код сертификата', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id="coupon_code" size ="60" required value='<?php echo $this->data['coupon_code']; ?>' name='coupon_code'/>
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
					<input type='text' id="option_value" value='<?php echo $this->data['value']; ?>' required size='10' name='value'>
				</div>
			</div>
		</div>
		<?php 
	}	
		
	function display_left()
	{						
		usam_add_box( 'usam_settings', __('Параметры','usam'), array( $this, 'certificates_settings' ) );		
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
		$title = __('Владелец','usam');
		$title_button = $this->data['customer']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='select_user' data-screen='user' data-list='users'  class='js-modal'>$title_button</a>";	
		usam_add_box( 'usam_user', $title, array( $this, 'display_users_metabox' ) );			
    }
}
?>