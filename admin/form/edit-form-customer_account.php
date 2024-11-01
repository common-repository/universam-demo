<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
class USAM_Form_customer_account extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf(__('Изменить клиентский счет &#8220;%s&#8221;','usam'), $this->id);
		else
			$title = __('Добавить клиентский счет', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_customer_account( $this->id );
		}
		else	
			$this->data = array( 'user_id' => 0, 'status' => 'active' ); 
	}	

	function display_settings()
	{		
		?>
		<div class="edit_form">						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='customer_account_status'><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $statuses = usam_get_statuses_customer_account( ); ?>
					<select id="customer_account_status" name = "status">							
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
	
	function display_right()
	{				
		$title = __('Клиент','usam');
		$title_button = $this->data['user_id']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='select_user' data-screen='user' data-list='users'  class='js-modal'>$title_button</a>";	
		usam_add_box( 'usam_user', $title, array( $this, 'display_user_metabox' ) );			
    }
}
?>