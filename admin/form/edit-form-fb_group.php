<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_fb_group extends USAM_Form_Social_Network
{		
	protected function get_title_tab()
	{ 						
		if ( $this->id != null )
		{
			$title = sprintf( __('Изменить группу &laquo;%s&raquo;','usam'), $this->data['name'] );
		}
		else
			$title = __('Добавить группу', 'usam');	
		return $title;
	}
	
    public function display_settings( )
	{  					
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='group_page_id'><?php esc_html_e( 'ID вашей группы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input value = "<?php echo $this->data['code'] ?>" type='text' name='code' id = "group_page_id"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='vk_access_token'><?php esc_html_e( 'Access Token', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="vk_access_token" name="access_token" value="<?php echo $this->data['access_token']; ?>" size="60">					
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_group'><?php esc_html_e( 'Имя отправителя сообщений', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="from_group" id='from_group'>							
						<option value='1' <?php selected(1, $this->data['from_group']); ?>><?php esc_html_e( 'имя группы', 'usam'); ?></option>		
						<option value='0' <?php selected(0, $this->data['from_group']); ?>><?php esc_html_e( 'имя пользователя', 'usam'); ?></option>		
					</select>
				</div>
			</div>		
		</div>
      <?php
	}   
	
	function display_left()
	{		
		if ( $this->data['name'] )
		{
			?> 
			<div class="profile">
				<img class="profile__image" src="<?php echo $this->data['photo']; ?>">
				<div class="profile__title"><?php echo $this->data['name']; ?></div>
			</div>			
			<?php
		}
		usam_add_box( 'usam_settings', __('Параметры доступа','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_publish_settings', __('Параметры публикаций','usam'), array( $this, 'social_network_publish_settings' ) );	
    }
}
?>