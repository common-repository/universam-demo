<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_sms extends USAM_Edit_Form
{	
	public function toolbar_buttons( ) 
	{
		?>
		<div class="action_buttons__button"><?php submit_button( __('Отправить сообщение','usam'), 'primary button', 'send', false, array( 'id' => 'submit-send' ) ); ?></div>
		<div class="action_buttons__button"><?php submit_button( __('Сохранить','usam'), 'button', 'save', false, array( 'id' => 'submit-save' ) ); ?></div>
		<?php
	}
	
	protected function get_data_tab(  )
	{
		if ( $this->id )
		{
			$_email = new USAM_SMS( $this->id );					
			$this->data = $_email->get_data();
		}
		else
			$this->data = array( 'phone' => '', 'message' => '' );				
	}
	
	public function display_form( ) 
	{	
		$phone = $this->data['phone'] ? $this->data['phone'] : '';
		?>			
		<div class = "mailing">
			<table class="table_email_form">				
				<tr>
					<td class="name"><a href="" id="open_select_email" class="button button-secondary js-modal" data-modal='select_phone' data-screen='phone_book' data-list='employees' ><?php _e('Кому', 'usam') ?></a></td>
					<td><input type="text" name="phone" id ="to_email_adress" class="js_email_adress" value="<?php echo $phone; ?>"/></td>
				</tr>					
				<tr>
					<td colspan="2">
						<textarea rows="10" autocomplete="off" cols="40" name="message"><?php echo htmlspecialchars($this->data['message']); ?></textarea>
					</td>						
				</tr>
			</table>				
		</div>
		<?php			
	}		
}
?>