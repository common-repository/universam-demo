<?php		
require_once( USAM_FILE_PATH .'/includes/mailings/signature_query.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_email extends USAM_Edit_Form
{		
	public function toolbar_buttons( ) 
	{
		?>
		<div class="action_buttons__button"><?php submit_button( __('Отправить сообщение','usam'), 'primary button', 'send', false, array( 'id' => 'submit-send' ) ); ?></div>
		<div class="action_buttons__button"><?php submit_button( __('Сохранить','usam'), 'button', 'save', false, array( 'id' => 'submit-save' ) ); ?></div>
		<?php
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_title_tab()
	{ 				
		if ( $this->id != null )
			$title = sprintf( __('Изменить письмо от %s','usam'), usam_local_date( $this->data['date_insert'], "d.m.Y" ) );
		else
			$title = __('Добавить письмо', 'usam');	
		return $title;
	}	
		
	protected function get_data_tab(  )
	{	
		if ( $this->id )
			$this->data = usam_get_email( $this->id );
		else
			$this->data = ['from_name' => '', 'from_email' => '', 'title' => '', 'body' => '', 'folder' => 'drafts', 'to_name' => '','to_email' => '', 'copy_email' => '', 'date_insert' => '', 'type' => 'sent_letter'];	
	}	
	
	public function display_form( ) 
	{	
		global $mailbox_id;
			
		$user_id = get_current_user_id();
		$mailboxes = usam_get_mailboxes(['fields' => ['id','name','email'], 'user_id' => $user_id]);
				
		$reply_to_email = usam_get_email_metadata( $this->id, 'reply_to_email' );
		$copy_email = usam_get_email_metadata( $this->id, 'copy_email' );
		if ( $reply_to_email )	
		{
			$reply_to_name = usam_get_email_metadata( $this->id, 'reply_to_name' );
			$to = $reply_to_name? $reply_to_name." <{$reply_to_email}>":$reply_to_email;	
		}
		else
			$to = !empty($this->data['to_name'])? $this->data['to_name']." <".$this->data['to_email'].">":$this->data['to_email'];			
		?>
		<div class = "mailing">
			<table class="table_email_form">
				<tr>
					<td class="name"><?php _e('От кого', 'usam') ?>:</td>
					<td>
						<select name="from_mailbox_id" id="from_mailbox_id">';				
							<?php
							foreach ( $mailboxes as $mailbox )
							{									
								?><option value='<?php echo $mailbox->id ?>' <?php selected($mailbox->id, $mailbox_id); ?>><?php echo "$mailbox->name ($mailbox->email)"; ?></option><?php
							}		
							?>						
						</select>
					</td>
				</tr>
				<tr>
					<td class="name"><a href="" id="open_select_email" class="button button-secondary js-modal" data-modal='select_email' data-screen='address_book' data-list='employees' ><?php _e('Кому', 'usam') ?></a></td>
					<td><input type="text" name="to" id ="to_email_adress" class="js_email_adress" value="<?php echo htmlspecialchars($to); ?>"/></td>
				</tr>
				<tr>
					<td class="name"><a href="" id="open_select_email" class="button button-secondary js-modal" data-modal='select_email' data-screen='address_book' data-list='employees' ><?php _e('Копия', 'usam') ?></a></td>
					<td><input type="text" name="copy_to" id ="copy_to_email_adress" class="js_email_adress" value="<?php echo htmlspecialchars($copy_email); ?>"/></td>
				</tr>
				<tr>
					<td class="name"><?php _e('Тема', 'usam') ?>:</td>
					<td><input type="text" name="title" value="<?php echo htmlspecialchars($this->data['title']); ?>"/></td>
				</tr>
				<tr>
					<td class="name"><?php _e('Шаблоны', 'usam') ?>:</td>					
					<?php			
					if ( $this->data['body'] == '' || strripos($this->data['body'], 'usam_newsletter') === false) 
						$template = 'default';
					else
						$template = '';
					?>
					<td>						
						<select name="template" id="template">									
							<option value='default' <?php selected('default', $template); ?>><?php _e('Шаблон по умолчанию', 'usam') ?></option>			
							<option value=''<?php selected('', $template); ?>><?php _e('Без шаблона', 'usam') ?></option>															
						</select>						
					</td>
				</tr>
				<tr>
					<td class="name"><?php _e('Подписи', 'usam') ?>:</td>	
					<td>
						<?php 
						$user_id = get_current_user_id(); 
						$signatures = usam_get_signatures( array( 'mailbox_id' => array(0, $mailbox_id), 'manager_id' => $user_id, 'orderby' => 'mailbox_id', 'order' => 'DESC' ) );						
						?>
						<select id="signature">																	
							<option value=''><?php _e('Без подписи', 'usam') ?></option>
							<?php			
							$sig = '';
							foreach ( $signatures as $signature )
							{							
								if ( $this->data['body'] == '' && $signature->mailbox_id == $mailbox_id )
								{
									$selected = 'selected="selected"';
									$sig = $signature->signature;
								}
								else
									$selected = '';
								?><option value='<?php echo $signature->id ?>' <?php echo $selected; ?>><?php echo $signature->name; ?></option><?php
							}		
							?>															
						</select>
					</td>
				</tr>
				<tr>
					<td colspan='2'><?php echo $this->display_attachments(); ?></td>
				</tr>				
				<tr>
					<td colspan="2">
					<?php 				
					$this->data['body'] = ($sig!='' ?'<br><br><br><div class="js-signature">'.$sig.'</div>':'<br><div class="js-signature"></div>').$this->data['body'];
					
					add_editor_style( USAM_URL . '/admin/assets/css/email-editor-style.css' );						
					wp_editor( $this->data['body'], 'email_editor', [
						'textarea_name' => 'message',
						'media_buttons'=>false,
						'textarea_rows' => 50,	
						'wpautop' => 0,							
						'tinymce' => [							
							'theme_advanced_buttons3' => 'invoicefields,checkoutformfields',
							]
						]
					 ); 
					?>
					</td>						
				</tr>
			</table>				
		</div>
		<?php			
	}		
}
?>