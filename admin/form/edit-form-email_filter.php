<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/mailings/email_filter.class.php' );
class USAM_Form_email_filter extends USAM_Edit_Form
{		
	public function get_data_tab() 
	{		
		if ( $this->id != null )
			$this->data = usam_get_email_filter( $this->id );	
		else
			$this->data = ['mailbox_id' => '', 'if' => '', 'condition' => '', 'value' => '', 'action' => ''];	
	}
		
	function display_filter()
	{			
		$user_id = get_current_user_id();
		$mailboxes = usam_get_mailboxes( array( 'fields' => array( 'id','name','email'), 'user_id' => $user_id ) );
		?>		
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_mailbox_id'><?php esc_html_e( 'Для ящика', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="mailbox_id" id="option_mailbox_id">			
						<option value='0' <?php selected(0, $this->data['mailbox_id'] ); ?>><?php _e('все мои ящики','usam'); ?></option>
						<?php
						foreach ( $mailboxes as $mailbox )
						{							
							?><option value='<?php echo $mailbox->id ?>' <?php selected($mailbox->id, $this->data['mailbox_id'] ); ?>><?php echo "$mailbox->name ($mailbox->email)"; ?></option><?php
						}		
						?>						
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_if'><?php esc_html_e( 'Если', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id='option_if' name="if">
						<?php							
						$if = ['sender' => __('Адрес отправителя','usam'), 'recipient' => __('Адрес получателя','usam'), 'copy' => __('Копия','usam'), 'subject' => __('Тема','usam'), 'size' => __('Размер письма','usam')];
						foreach( $if as $key => $value )
						{								
							echo "<option ".selected( $this->data['if'], $key, false)." value='$key'>$value</option>";
						}
						?>					
					</select>	
					<select id ="option_condition" name="condition">
						<?php							
						$conditions = usam_get_conditions( 'string' );
						foreach( $conditions as $key => $value )
						{								
							echo "<option ".selected( $this->data['condition'], $key, false)." value='$key'>$value</option>";
						}
						?>					
					</select>
					<input type="text" id ="option_value" name="value" value="<?php echo $this->data['value']; ?>">								
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_action'><?php esc_html_e( 'Действие', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id='option_action' name="option_action">
						<?php							
						$actions = ['read' => __('Пометить письмо прочитанным','usam'), 'important' => __('Пометить письмо важным','usam'), 'delete' => __('Удалить на всегда','usam')];
						foreach( $actions as $key => $value )
						{								
							echo "<option ".selected( $this->data['action'], $key, false)." value='$key'>$value</option>";
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
		usam_add_box( 'usam_filter_setting', __('Настройка фильтра','usam'), array( $this, 'display_filter') );			
    }	
}
?>