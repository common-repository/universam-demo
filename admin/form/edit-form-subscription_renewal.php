<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal.class.php'  );
class USAM_Form_subscription_renewal extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 			
		if ( $this->id != null )
			$title = __('Изменить продление подписки','usam');
		else
			$title = __('Добавить продление подписки', 'usam');			
		return $title;
	}
	
	protected function get_url_go_back( ) 
	{
		return admin_url( "admin.php?page=orders&tab=subscriptions&table=subscriptions&form=view&form_name=subscription&id=".$this->data['subscription_id'] );		
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_subscription_renewal( $this->id );
		}
		else	
			$this->data = array( 'code' => usam_generate_bonus_card(), 'user_id' => 0, 'percent' => '0.00', 'status' => 1 );
	}	

	function display_settings()
	{		
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'] ); ?>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='bonus_status'><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $statuses = usam_get_renew_subscription_statuses( ); ?>
					<select name = "status">							
						<?php				
						foreach ( $statuses as $key => $name ) 
						{					
							?><option value='<?php echo$key; ?>' <?php selected($this->data['status'], $key); ?>><?php echo $name; ?></option><?php
						}
						?>
					</select>	
				</div>
			</div>			
		</div>		
		<?php			
	}

	function display_left()
	{			
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
}
?>