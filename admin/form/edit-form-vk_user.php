<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_vk_user extends USAM_Form_Social_Network
{	
	private $vk_api;		
	protected function get_title_tab()
	{ 	
		$this->vk_api = get_option('usam_vk_api', array('client_id' => ''));					
		if ( $this->id != null )
		{
		//	add_action('admin_footer', array(&$this, 'admin_footer'));
			$title = sprintf( __('Изменить анкету &laquo;%s&raquo;','usam'), $this->data['name'] );
		}
		else
			$title = __('Добавить анкету', 'usam');	
		return $title;
	}
		
	function admin_footer( ) 
	{			
		$params = ['client_id' => $this->vk_api['client_id'], 'redirect_uri' => admin_url('admin.php?unprotected_query=vk_token'), 'response_type' => 'code', 'state' => $this->id, 'v' => '5.80', 'scope' => 'wall,photos,ads,offline,friends,notifications,market'];	
		$query = http_build_query($params);  	
		$url = 'https://oauth.vk.com/authorize/?'.$query;
		
		$html = "<div class='modal-body modal-scroll'>
			<iframe src='$url' style='width:100%;'></iframe>
		</div>";
		echo usam_get_modal_window( __('Получить токен','usam'), 'open_window_get_token', $html );						
	}
	
    public function display_settings( )
	{  				
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='profile_id'><?php esc_html_e( 'ID анкеты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="profile_id" name="code" value="<?php echo $this->data['code']; ?>" size="60" />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='access_token'>Access Token:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='access_token' name="access_token" value="<?php echo $this->data['access_token']; ?>" size="60" />
					<?php $url = 'http://oauth.vk.com/authorize?client_id='.$this->vk_api['client_id'].'&scope=wall,photos,ads,offline,friends,notifications,market&display=page&response_type=token&redirect_uri=http://api.vk.com/blank.html'; ?>
					<p><?php _e('Чтобы получить Access Token','usam'); ?></p>
					<ol>
						<li><?php printf( __('перейдите по <a href="%s" target="_blank">ссылке</a>','usam'),$url); ?>,</li>
						<li><?php _e('подтвердите уровень доступа','usam'); ?>,</li>
						<li><?php _e('скопируйте access_token с открывшейся страницы','usam'); ?>.</li>
					</ol>
				</div>
			</div>
			<?php 
			if ( false )
			{
				?>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"></div>
					<div class ="edit_form__item_option">
						<button id="get_token" data-toggle="modal" data-target="#open_window_get_token" type='button' class='button-primary button'><?php _e( 'Получить токин', 'usam'); ?></button>
					</div>
				</div>
				<?php 
			}
			?>	
		</div>
      <?php
	}    
}
?>