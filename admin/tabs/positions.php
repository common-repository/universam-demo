<?php
require_once( USAM_FILE_PATH .'/includes/seo/sites_query.class.php' );				
require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );		
class USAM_Tab_positions extends USAM_Tab
{	
	protected $views = ['table'];
	public function __construct()
	{			
		USAM_Admin_Assets::set_graph( );			
		if ( current_user_can('view_seo_setting') )			
			$this->views[] = 'settings';		
	}	

	function get_title_tab() 
	{			
		if ( $this->view == 'settings' )
		{			
			if ( $this->table == 'search_engine_location' )		
				$title = __('Местоположение для поисковых систем', 'usam');		
			elseif ( $this->table == 'search_engine_region' )		
				$title = __('Регионы поисковых систем', 'usam');		
			else
			{				
				$title = __('Настройки SEO', 'usam');		
				if ( !empty( $_REQUEST['section']) )
				{
					if ( $_REQUEST['section'] == 'yandex' )
						$title = __('Сервисы Яндекса', 'usam');	
					elseif ( $_REQUEST['section'] == 'google' )
						$title = __('Сервисы Google', 'usam');	
					if ( $_REQUEST['section'] == 'bing' )
						$title = __('Сервисы Bing', 'usam');
				}
			}
		}
		else
			$title = __('Анализ результатов', 'usam');		
		return $title;
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'positions' )		
			return [['form' => 'edit', 'form_name' => 'position', 'title' => __('Добавить ключевые слова', 'usam')]];
		elseif ( $this->table == 'search_engine_location' )		
			return [['form' => 'edit', 'form_name' => 'search_engine_location', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'search_engine_region' )	
			return [['form' => 'edit', 'form_name' => 'search_engine_region', 'title' => __('Добавить', 'usam')]];
		return [];
	}	
	
	public function get_tab_sections() 
	{ 	
		if ( $this->view == 'settings' )
		{  
			$tables = $this->get_settings_tabs();
			array_unshift($tables, ['title' => __('Назад','usam')]);	
		}	
		else
			$tables = ['positions' => ['title' => __('Позиция сайта','usam'), 'type' => 'table'], 'links' => ['title' => __('Ссылки сайта','usam'), 'type' => 'table'],  'view' => ['title' => __('Ваши конкуренты','usam'), 'type' => 'table']];	
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		return ['application' => ['title' => __('Глобальная настройка','usam'), 'type' => 'section'], 'yandex' => ['title' => __('Яндекс','usam'), 'type' => 'section'], 'google' => ['title' => 'Google', 'type' => 'section'], 'bing' => ['title' => 'Bing', 'type' => 'section'], 'search_engine_location' => ['title' => __('Местоположения','usam'), 'type' => 'table'], 'search_engine_region' => ['title' => __('Регионы','usam'), 'type' => 'table']];	
	}	
	
	public function display_options() 
	{			
		$options = [
			['type' => 'radio', 'title' => __('Включить проверку позиции сайта', 'usam'), 'option' => 'check_position_site'],		
			['type' => 'input', 'title' => __('Запросов к Яндекс XML за раз', 'usam'), 'option' => 'yandex_query_limit', 'description' => __('Максимальное количество запросов за раз.', 'usam'), 'attribute' => array( 'maxlength' => "3", 'size' => "3"), 'default' => 10],				
			['type' => 'input', 'title' => __('Запросов к Google за раз', 'usam'), 'option' => 'google_query_limit', 'description' => __('Максимальное количество запросов за раз.', 'usam'), 'attribute' => array( 'maxlength' => "3", 'size' => "3"), 'default' => 10],	
			['type' => 'radio', 'title' => __('Редирект со страниц вложений на прикрепленную запись', 'usam'), 'option' => 'attachment_redirect']				
		];
		$this->display_table_row_option( $options );
	}
		
	public function display_section_application() 
	{
		usam_add_box( 'usam_currency_options', __('Настройка определения позиции сайта', 'usam'), [$this, 'display_options']);
	}
	
	public function display_section_yandex() 
	{
		usam_add_box( 'usam_yandex_passport', __('Паспорт в Яндексе', 'usam'), array( $this, 'yandex_passport_meta_box' ) );			
		usam_add_box( 'usam_yandex_metrika', __('Метрика', 'usam'), array( $this, 'yandex_metrika_meta_box' ) );
		usam_add_box( 'usam_yandex_webmaster', __('Вебмастер', 'usam'), array( $this, 'yandex_webmaster_meta_box' ) );		
		usam_add_box( 'usam_yandex_postoffice', __('Почтовый офис', 'usam'), array( $this, 'yandex_postoffice_meta_box' ) );
		usam_add_box( 'usam_yandex_xml', __('Яндекс XML', 'usam'), array( $this, 'yandex_xml_meta_box' ) );
		usam_add_box( 'usam_yandex_xml', __('Яндекс Коннект', 'usam'), array( $this, 'yandex_pdd_meta_box' ) );
		usam_add_box( 'usam_yandex_developer', __('Кабинет разработчика', 'usam'), array( $this, 'yandex_developer_meta_box' ) );
	}
	
	public function display_section_google() 
	{
		usam_add_box( 'usam_google_webmasters', 'WebMasters', array( $this, 'google_webmasters_meta_box' ) );
		usam_add_box( 'usam_google_analytics', 'Analytics', array( $this, 'google_analytics_meta_box' ) );
		usam_add_box( 'usam_google_api', ' Google API', array( $this, 'google_api_meta_box' ) );
		usam_add_box( 'usam_google_cloud_platform', 'Cloud Platform', array( $this, 'google_cloud_platform_meta_box' ) );
		usam_add_box( 'usam_google_postmaster', 'Postmaster', array( $this, 'google_postmaster_meta_box' ) );
	}
	
	public function display_section_bing() 
	{	
		usam_add_box( 'usam_bing', __('Bing', 'usam'), [$this, 'bing_meta_box']);
	}	
	
	public function yandex_passport_meta_box() 
	{
		$url = get_rest_url(null,'/usam/v1/oauth/yandex');
		?>
		<script>					//&scope=metrika:read metrika:write webmaster:hostinfo webmaster:verify webmaster:turbopages pdd:registrar-auth
			function makeAuth( )
			{
				var client_id = jQuery("#yandex-client_id").val();
				var w = window.open("https://oauth.yandex.ru/authorize?response_type=code&force_confirm=1&client_id="+client_id+"&redirect_uri=<?php echo $url; ?>", '','height=500,width=500');
				//	w.close();
			}
		</script>
		<div class = "edit_form">								
			<?php	
			$options = [
				['key' => 'client_id', 'type' => 'input', 'title' => __('Идентификатор приложения', 'usam'), 'option' => 'yandex', 'description' => sprintf( __('Идентификатор приложения. Доступен в %s', 'usam'), '<a target="_blank" href="https://oauth.yandex.ru/" rel="noopener">'.__('свойствах приложения', 'usam').'</a>')],			
				['key' => 'client_secret', 'type' => 'password', 'title' => 'Client secret', 'option' => 'yandex'],
				['type' => 'text', 'title' => 'Callback URL', 'html' => '<span class="js-copy-clipboard">'.get_rest_url(null,'/usam/v1/oauth/yandex').'</span>'],					
			];
			$this->row_option( $options );	
			?>
			<div id ="usam_row-yandex-client_id" class ="edit_form__item">
				<div class ="edit_form__item_name"></div>
				<div class ="edit_form__item_option">
					<input type="button" onclick="makeAuth()" value="<?php esc_html_e( 'Получить токен', 'usam'); ?>">
				</div>
			</div>	
		</div>		
		<?php
	}
	
	public function yandex_developer_meta_box() 
	{		
		$options = [['key' => 'api', 'type' => 'input', 'title' => __('Счетчик', 'usam'), 'group' => 'developer', 'option' => 'yandex', 'description' => sprintf( __('Ваш API-ключ в %s', 'usam'), '<a target="_blank" href="https://developer.tech.yandex.ru/" rel="noopener">'.__('кабинете разработчика.', 'usam').'</a>').' '.sprintf( __('Ознакомьтесь с %s Яндекс Карты перед использованием.', 'usam'), '<a target="_blank" href="https://yandex.ru/legal/maps_termsofuse/" rel="noopener">'.__('условиями использования', 'usam').'</a>')]];
		$this->display_table_row_option( $options );
	}
	
	public function yandex_metrika_meta_box() 
	{		
		$options = [ 			
			['type' => 'checkbox', 'title' => __('Включить', 'usam'), 'option' => 'yandex_metrika_active'],				
		];	 		
		$_counters = [];					
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/metrika_management.class.php' );
		$metrika = new USAM_Yandex_Metrika_Management();
		$counters = $metrika->get_counters();	
		$metrika->set_log_file();				
		
		if ( !empty($counters) && is_array($counters) ) 									
		{
			foreach ($counters as $counter) 
				$_counters[$counter['id']] = '<a href="https://metrika.yandex.ru/dashboard?id='.$counter['id'].'" target="_blank" rel="noopener">'.$counter['name'].'</a>&nbsp;&nbsp;'.__( 'Разрешение', 'usam').': '.$counter['permission'];
		}			
		if ( $_counters )
			$options[] = ['key' => 'counter_id', 'type' => 'select', 'title' => __('Счетчик', 'usam'), 'group' => 'metrika', 'option' => 'yandex', 'options' => $_counters];
		else
			$options[] = ['key' => 'counter_id', 'type' => 'input', 'title' => __('Счетчик', 'usam'), 'group' => 'metrika', 'option' => 'yandex'];				
			
		$options[] = ['key' => 'webvisor', 'type' => 'checkbox', 'title' => __('Вебвизор, карта скроллинга, аналитика форм', 'usam'), 'group' => 'metrika', 'option' => 'yandex'];
		$options[] = ['key' => 'ecommerce', 'type' => 'checkbox', 'title' => __('Электронная коммерция', 'usam'), 'group' => 'metrika', 'option' => 'yandex'];
	//	$options[] = ['key' => 'location', 'type' => 'select', 'title' => __('Расположение счетчика', 'usam'), 'group' => 'metrika', 'option' => 'yandex', 'default' => 'footer', 'options' => ['header' => __("Вверху страницы", "usam"), 'footer' => __("Снизу страницы", "usam")]];
		$this->display_table_row_option( $options );		
	}
	
	public function yandex_webmaster_meta_box() 
	{
		$options = [];
		$site_ids = [];	
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/webmaster.class.php' );	
		$webmaster = new USAM_Yandex_Webmaster();		
		$sites = $webmaster->get_sites();		
		foreach ($sites as $site) 
		{
			$site_ids[$site['host_id']] = $site['unicode_host_url'];					
		} 
		if ( $site_ids )
			$options[] = ['key' => 'site_id', 'type' => 'select', 'title' => __('Ваш сайт', 'usam'), 'group' => 'webmaster', 'option' => 'yandex', 'options' => $site_ids];
		else
			$options[] = ['key' => 'site_id', 'type' => 'input', 'title' => __('Ваш сайт', 'usam'), 'group' => 'webmaster', 'option' => 'yandex'];				
			
		$options[] = ['key' => 'verify', 'type' => 'input', 'title' => __('Подтвердить права на сайт', 'usam'), 'group' => 'webmaster', 'option' => 'yandex'];	
		$this->display_table_row_option( $options );
	}
	
	public function yandex_postoffice_meta_box() 
	{
		$site_ids = [];		
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/postoffice.class.php' );
		$postoffice = new USAM_Yandex_Postoffice();
		$sites = $postoffice->get_reg_list();
		foreach ($sites as $site) 
		{
			$site_ids[$site['domain']] = $site['domain'];					
		} 
		if ( $site_ids )
			$options[] = ['key' => 'postoffice', 'type' => 'select', 'title' => __('Домен', 'usam'), 'option' => 'yandex', 'options' => $site_ids];
		else
			$options[] = ['key' => 'postoffice', 'type' => 'input', 'title' => __('Домен', 'usam'), 'option' => 'yandex'];		
		$this->display_table_row_option( $options ); 		
	}
	
	public function yandex_xml_meta_box() 
	{		
		$options = array( 						
			['key' => 'username', 'type' => 'password', 'title' => __('Логин', 'usam'), 'group' => 'xml', 'option' => 'yandex'],					
			['key' => 'password', 'type' => 'password', 'title' => __('Ключ', 'usam'), 'group' => 'xml', 'option' => 'yandex'],	
		);	 
		$this->display_table_row_option( $options ); 
	}	
	
	
	public function yandex_pdd_meta_box() 
	{
		$options = array( 										
			['key' => 'password', 'type' => 'password', 'title' => __('ПДД-токен', 'usam'), 'group' => 'pdd', 'option' => 'yandex'],	
		);	 
		$this->display_table_row_option( $options );
	}	
		
	function google_webmasters_meta_box()
	{
		$options = array( 						
			array( 'key' => 'verify', 'type' => 'password', 'title' => __('Подтвердить права на сайт', 'usam'), 'option' => 'google', 'attribute' => array( 'maxlength' => "50", 'size' => "50") ), );
		$this->display_table_row_option( $options );		
	}
		
	public function google_api_meta_box() 
	{	
		$options = array( 						
			array( 'key' => 'client_id', 'type' => 'password', 'title' => __('Идентификатор клиента', 'usam'), 'option' => 'google' ),					
			array( 'key' => 'client_secret', 'type' => 'password', 'title' => __('Секретный код клиента', 'usam'), 'option' => 'google' ),	
		);	 
		$this->display_table_row_option( $options ); 		
	}
	
	function google_analytics_meta_box()
	{
		$options = array( 			
			['type' => 'checkbox', 'title' => __('Включить', 'usam'), 'option' => 'google_analytics_active'],					
			['key' => 'analytics_id', 'type' => 'input', 'title' => __('ID отслеживания', 'usam'), 'option' => 'google', 'attribute' => array( 'maxlength' => "50", 'size' => "50")],
			['key' => 'analytics_ecommerce', 'type' => 'checkbox', 'title' => __('Электронная коммерция', 'usam'), 'option' => 'google'],
		);	 
		$this->display_table_row_option( $options ); 		
	}
	
	function google_cloud_platform_meta_box()
	{
		$options = array( 
			array('key' => 'cloud-platform-api-key', 'type' => 'password', 'title' => __('Ключ API', 'usam'), 'option' => 'google', 'attribute' => array('maxlength' => "50", 'size' => "50")),		
		);	   		
		$this->display_table_row_option( $options );				
	}
	
	function google_postmaster_meta_box()
	{
		printf( __('Этот инструмент используется для отслеживания эффективности почтовых отправлений в домен gmail.com. Чтобы начать использовать Postmaster Вам понадобится актуальный аккаунт в приложении Google или почтовый ящик на gmail.com. Перейдите на %s, чтобы начать использовать .', 'usam'), '<a href="http://www.postmaster.google.com" target="_blank" rel="noopener">Postmaster</a>' );	
	}
	
	function bing_meta_box()
	{	
		$options = array( 						
			array( 'key' => 'verify', 'type' => 'input', 'title' => __('Подтвердить права на сайт', 'usam'), 'option' => 'bing', 'attribute' => array( 'maxlength' => "50", 'size' => "50") ),	
		 );	  
		 $this->display_table_row_option( $options );
	}
}