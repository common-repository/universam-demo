<?php
class USAM_Tab_instagram extends USAM_Tab
{
	private $instagram;
	protected $views = ['table', 'settings'];
	public function __construct()
	{			
		$this->instagram = get_option('usam_instagram_api', array('client_id' => '', 'client_secret' => '', 'token' => '' ));	
	}
	
	public function get_title_tab()
	{			
		if ( $this->view == 'settings' )
			return __('Настройка публикации', 'usam');	
		else
			return __('Записи в Instagram', 'usam');	
	}
	
	public function load_tab() 
	{ 			
		if ( $this->view == 'settings' )
			add_action('admin_footer', array(&$this, 'admin_footer'));			
	}	
	
	function admin_footer( ) 
	{		
		$params = array( 'client_id' => $this->instagram['client_id'], 'redirect_uri' => admin_url('admin.php?unprotected_query=instagram_token'), 'response_type' => 'code' );
		
		$query = http_build_query($params);  	
		$url = 'https://api.instagram.com/oauth/authorize/?'.$query;	
		
		$html = "<div class='modal__buttons'>
			<iframe src='$url' style='width:100%;'></iframe>
		</div>";
		echo usam_get_modal_window( __('Получить токин','usam'), 'open_window_get_token', $html );						
	}
	
	public function add_meta_options_help_center_tabs( ) 
	{
		$url = admin_url('admin.php?unprotected_query=instagram_token');
		$url_arr = explode(".", basename($url));
		$domain = $url_arr[count($url_arr)-2] . "." . $url_arr[count($url_arr)-1];
		
		$tabs[] = new USAM_Help_Center_Item('basic-help', __('Настройки API Instagram', 'usam'), __('Настройки API Instagram', 'usam'),
			array(
				'content' => '<p>' .sprintf(__('Чтобы получить доступ к API Instagram, вам нужно <b><a %s>создать приложение</a></b>', 'usam'), 'href="https://www.instagram.com/developer/clients/manage" target="_blank" rel="noopener"').':</p>		
		<p>В настройках приложения необходимо установить параметры в разделе <strong>Open API</strong>:</p>
		<ol>
			<li><strong>Базовый домен:</strong> '.$domain.'</li>
			<li><strong>Адрес сайта:</strong> '.$url.'</li>			
		</ol>',
			)
		);
		return $tabs;
	}
	
	public function display_settings_view( ) 
	{			
		usam_add_box( 'usam_application', __('Приложение', 'usam'), array( $this, 'application_meta_box' ) );	
	}
	
	public function application_meta_box()
	{		
		$options = [
			['key' => 'client_id', 'type' => 'input', 'title' => __('ID приложения', 'usam'), 'option' => 'instagram'],	
			['key' => 'client_secret', 'type' => 'input', 'title' => 'Client Secret', 'option' => 'instagram'],	
		]; 		  
		if ( !empty($this->instagram['client_id']) && empty($this->instagram['token']))
			$options[] = ['html' => '<button id="get_token" data-toggle="modal" data-target="#open_window_get_token" type="button" class="button-primary button">'.__( 'Получить токин', 'usam').'</button>', 'type' => 'text', 'title' => '', 'option' => 'instagram'];
		if ( !empty($this->instagram['token']))
			$options[] = ['html' => '<button id="get_token" data-toggle="modal" data-target="#open_window_get_token" type="button" class="button-primary button">'.__( 'Получить заново токин', 'usam').'</button>', 'type' => 'text', 'title' => '', 'option' => 'instagram'];
		$this->display_table_row_option( $options );
	}	
}