<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_template.class.php' );
class USAM_Form_chat_bot_template extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить шаблон','usam');
		else
			$title = __('Добавить шаблон', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_сhat_bot_template( $this->id );
		}
		else	
			$this->data = array( 'id' => 0,  'name' => '', 'active' => 1, 'channel' => 'all' );
	}	
	
	function display_settings()
	{		
		$channels = array( 'chat' => __('Чат сайта','usam'), 'vk' => __('Контакт','usam'), 'telegram' => 'Telegram' );
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='channel_chat_bot_template'><?php _e( 'Канал','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id = "channel_chat_bot_template" name = "channel">
						<option value='all' <?php selected( 'all', $this->data['channel'] ); ?>><?php _e( 'Все каналы','usam'); ?></option>
						<?php				
						foreach ( $channels as $key => $name ) 
						{					
							?><option value='<?php echo $key; ?>' <?php selected( $this->data['channel'], $key ); ?>><?php echo $name; ?></option><?php
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
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );					
    }
}
?>