<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_vk_group extends USAM_Form_Social_Network
{		
	private $vk_api;	
	protected function get_title_tab()
	{ 	
		$this->vk_api = get_option('usam_vk_api', array('client_id' => ''));						
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
		$group_access_token = usam_get_social_network_profile_metadata( $this->id, 'group_access_token' );
		$confirmation = usam_get_social_network_profile_metadata( $this->id, 'confirmation' );
		$secret_key = usam_get_social_network_profile_metadata( $this->id, 'secret_key' );
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
					<input type="text" id="vk_access_token" name="access_token" value="<?php echo $this->data['access_token']; ?>" size="60" />
					<?php $url = 'http://oauth.vk.com/authorize?client_id='.$this->vk_api['client_id'].'&scope=wall,photos,ads,offline,friends,notifications,market&display=page&response_type=token&redirect_uri=http://api.vk.com/blank.html'; ?>
					<p><?php printf( __('Чтобы получить Access Token перейдите по <a href="%s" target="_blank" rel="noopener">ссылке</a>, подтвердите уровень доступа и скопируйте access_token с открывшейся страницы','usam'),$url); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='group_access_token'><?php esc_html_e( 'Ключ доступа к API', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="group_access_token" name="metas[group_access_token]" value="<?php echo $group_access_token; ?>" size="60" />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='vk_confirmation'><?php esc_html_e( 'Подтверждение уведомлений', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="vk_confirmation" name="metas[confirmation]" value="<?php echo $confirmation; ?>" size="60" />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='secret_key'><?php esc_html_e( 'Секретный ключ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="secret_key" name="metas[secret_key]" value="<?php echo $secret_key; ?>" size="60" />
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
	
	public function display_events( )
	{  		
		$message_group_join = usam_get_social_network_profile_metadata( $this->id, 'message_group_join' );
		$message_group_unsure = usam_get_social_network_profile_metadata( $this->id, 'message_group_unsure' );
		$message_group_accepted = usam_get_social_network_profile_metadata( $this->id, 'message_group_accepted' );
		$message_group_approved = usam_get_social_network_profile_metadata( $this->id, 'message_group_approved' );
		$message_group_request = usam_get_social_network_profile_metadata( $this->id, 'message_group_request' );
		$message_group_leave = usam_get_social_network_profile_metadata( $this->id, 'message_group_leave' );
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_join"><?php esc_html_e( 'Сообщение, когда пользователь вступил в группу или мероприятие', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_join" name="messages[message_group_join]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_join); ?></textarea>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_unsure"><?php esc_html_e( 'Сообщение, когда пользователь выбрал вариант «Возможно, пойду» в мероприятий', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_unsure" name="messages[message_group_unsure]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_unsure); ?></textarea>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_accepted"><?php esc_html_e( 'Сообщение, когда пользователь принял приглашение в группу или на мероприятие', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_accepted" name="messages[message_group_accepted]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_accepted); ?></textarea>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_join_approved"><?php esc_html_e( 'Сообщение, когда заявка на вступление в группу/мероприятие была одобрена руководителем сообщества', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_join_approved" name="messages[message_group_approved]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_approved); ?></textarea>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_join_request"><?php esc_html_e( 'Сообщение, когда пользователь подал заявку на вступление в сообщество', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_join_request" name="messages[message_group_request]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_request); ?></textarea>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="vk_group_leave"><?php esc_html_e( 'Сообщение, когда участник удалился', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="vk_group_leave" name="messages[message_group_leave]" style="width:100%; height:200px;"><?php echo htmlspecialchars($message_group_leave); ?></textarea>
				</div>
			</div>				
		</div>
      <?php
	}   
	
	function display_left()
	{				
		if ( !empty($this->vk_api['client_id']) )
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
			usam_add_box( 'usam_events', __('Обработка событий','usam'), array( $this, 'display_events' ) );
		}		
		else
		{
			_e('Не настроено API','usam');
		}
    }
}
?>