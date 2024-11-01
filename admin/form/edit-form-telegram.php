<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_telegram extends USAM_Form_Social_Network
{					
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить Telegram &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить профиль Telegram', 'usam');	
		return $title;
	}
					
	public function display_settings() 
	{							
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='access_token'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id ="access_token" value="<?php echo $this->data['access_token']; ?>" name="access_token"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_group'><?php esc_html_e( 'Имя отправителя сообщений', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="from_group" id='from_group'>							
						<option value='1' <?php selected(1, $this->data['from_group']); ?>><?php esc_html_e( 'имя сообщества', 'usam'); ?></option>		
						<option value='0' <?php selected(0, $this->data['from_group']); ?>><?php esc_html_e( 'имя менеджера', 'usam'); ?></option>		
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
		usam_add_box( 'usam_settings', __('Параметры группы','usam'), array( $this, 'display_settings' ) );
    }
}
?>