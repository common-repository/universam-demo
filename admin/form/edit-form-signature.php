<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_signature extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 			
		if ( $this->id != null )
		{
			$title = __('Изменить шаблон','usam');
		}
		else
			$title = __('Добавить шаблон', 'usam');	
		return $title;
	}
	
	public function get_data_tab() 
	{		
		if ( $this->id != null )
		{							
			$this->data = usam_get_signature( $this->id );				
		}
		else
		{
			$this->data = array( 'mailbox_id' => '', 'signature' => '', 'name' => '' );		
		}		
	}
		
	function display_signature_setting()
	{			
		$user_id = get_current_user_id();	
		$mailboxes = usam_get_mailboxes( array( 'fields' => array( 'id','name','email'), 'user_id' => $user_id ) );
		?>		
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_mailbox_id'><?php esc_html_e( 'Для ящика', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="mailbox_id" id="option_mailbox_id">';				
						<option value='0' <?php selected(0, $this->data['mailbox_id'] ); ?>><?php _e('все мои ящики','usam'); ?></option>
						<?php
						foreach ( $mailboxes as $mailbox )
						{							
							?>
							<option value='<?php echo $mailbox->id ?>' <?php selected($mailbox->id, $this->data['mailbox_id'] ); ?>><?php echo "$mailbox->name ($mailbox->email)"; ?></option>
							<?php
						}		
						?>						
					</select>
				</div>
			</div>
		</div>	
		<?php
	}
	
	protected function signature(  ) 
	{	              
		wp_editor(stripslashes(str_replace('\\&quot;','',$this->data['signature'])),'signature',array(
			'textarea_name' => 'signature',
			'media_buttons' => false,
			'textarea_rows' => 10,
			//'teeny' => true,
			'tinymce' => array( 'theme_advanced_buttons3' => false, 'remove_linebreaks' => false )
			)	
		);
	}
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_signature_setting', __('Настройка','usam'), array( $this, 'display_signature_setting') );	
		$this->signature();
    }	
}
?>