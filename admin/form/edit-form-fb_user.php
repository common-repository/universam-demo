<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_fb_user extends USAM_Form_Social_Network
{		
	protected function get_title_tab()
	{ 										
		if ( $this->id != null )
		{
			$title = sprintf( __('Изменить анкету &laquo;%s&raquo;','usam'), $this->data['name'] );
		}
		else
			$title = __('Добавить анкету', 'usam');	
		return $title;
	}
	
    public function display_settings( )
	{  				
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='profile_id'><?php esc_html_e( 'ID анкеты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><input type="text" id="profile_id" name="code" value="<?php echo $this->data['code']; ?>" size="60" /></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='access_token'><?php esc_html_e( 'Access Token', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><input type="text" id='access_token' name="access_token" value="<?php echo $this->data['access_token']; ?>" size="60" /></div>
			</div>			
		</div>
      <?php
	}    
}
?>