<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );
class USAM_Yandex_Webmaster extends USAM_Yandex
{
	protected $version = '4';
	protected $site_id = '';	
	protected $url_api = 'https://api.webmaster.yandex.net';
	
	public function __construct()
	{
		$default = ['webmaster' => ['site_id' => '', 'user_id' => ''] ];
		$this->option = get_option( 'usam_yandex', $default );	
		$this->site_id = empty($this->option['webmaster']['site_id'])?'':$this->option['webmaster']['site_id'];
		
		parent::__construct( );
	}
	
	public function ready( )
	{
		if ( $this->site_id )
			return true;
		return false;
	}
	
	public function get_user()
	{			
		if ( empty($this->option['webmaster']['user_id']) )
		{
			$result = $this->send_request();	
			if ( !empty($result['user_id']) )
			{
				$this->option['webmaster']['user_id'] = $result['user_id'];
				update_option('usam_yandex', $this->option);
			}
			else
				return 0;
		}
		return $this->option['webmaster']['user_id'];
	}
	
	public function get_sites( )
	{			
		if ( !$this->ready() ) 
			return [];
		$result = $this->send_request( "hosts" );		
		$hosts = [];	
		if ( !empty($result['hosts']) )
			$hosts = $result['hosts'];
		return $hosts;
	}		
	
	//Позволяет получить информацию о текущем состоянии индексирования сайта.
	public function get_info_site( )
	{				
		if ( !$this->ready() ) 
			return [];
		return $this->send_request( "hosts/".$this->site_id );	
	}	
	
	//Получение статистики сайта
	public function get_statistics_site( )
	{				
		if ( !$this->ready() )
			return [];
		return $this->send_request( "hosts/{$this->site_id}/summary" );	
	}		

	// Получение истории индексирования сайта
	/*
	SEARCHABLE	    Страницы в поиске.
	DOWNLOADED	    Загруженные страницы.
	DOWNLOADED_2XX	Страницы, загруженные с кодом из группы 2xx.
	DOWNLOADED_3XX	Страницы, загруженные с кодом из группы 3xx.
	DOWNLOADED_4XX	Страницы, загруженные с кодом из группы 4xx.
	DOWNLOADED_5XX	Страницы, загруженные с кодом из группы 5xx.
	FAILED_TO_DOWNLOAD	Не удалось загрузить.
	EXCLUDED	     Исключенные страницы.
	EXCLUDED_DISALLOWED_BY_USER	Исключенные по желанию владельца ресурса (4xx-коды, запрет в robots.txt).
	EXCLUDED_SITE_ERROR	Исключенные из-за ошибки на стороне сайта.
	EXCLUDED_NOT_SUPPORTED	Исключенные из-за отсутствия поддержки на стороне роботов Яндекса.
	*/
	public function get_indexing( $date_from, $date_to, $indicators = array('SEARCHABLE'))
	{				
		if ( !$this->ready() )
			return [];
		$date_from = date( "c", strtotime($date_to) );
		$date_to   = date( "c", strtotime($date_to) );		
		$result = $this->send_request("hosts/{$this->site_id}/indexing-history", ['date_from' => $date_from, 'date_to' => $date_to, 'indexing_indicator' => $indicators]);	
		return $result;
	}	


	public function get_indexing_samples( $offset = 0, $limit = 10 )
	{					
		if ( !$this->ready() )
			return [];
		return $this->send_request("hosts/{$this->site_id}/indexing/samples", ['offset' => $offset, 'limit' => $limit]);	
	}		
	
	//Получение информации о популярных запросах
	/*
	* @param $orderBy string ordering: TOTAL_CLICKS|TOTAL_SHOWS
    * @param $indicators array('TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION')
	*/
	public function get_popular( $order_by = 'TOTAL_SHOWS', $indicators = array() )
	{				
		if ( !$this->ready() )
			return [];
		
		$order_by = $order_by == 'TOTAL_SHOWS'?'TOTAL_SHOWS':'TOTAL_CLICKS';
	
		$result = $this->send_request( "hosts/{$this->site_id}/search-queries/popular", ['order_by' => $order_by, 'query_indicator' => $indicators]);			
		if ( !empty($result['queries']) )
			return $result['queries'];		
		
		return [];
	}	
		
	//Позволяет получить примеры внешних ссылок на страницы сайта.	
	public function get_external( $offset = 0, $limit = 10 )
	{				
		if ( !$this->ready() )
			return [];
		return $this->send_request( "hosts/{$this->site_id}/links/external/samples", ['offset' => $offset, 'limit' => $limit]);
	}	
	
	//Добавление оригинального текста.	
	public function add_original_text( $content )
	{				
		if ( !$this->ready() )
			return [];
		$content = substr($content, 0, 4);	
		return $this->send_request( "hosts/{$this->site_id}/original-texts", ['content' => $content]);	
	}	
	
	protected function get_url( $resource = '' )
	{ 	
		if ( $resource )
		{		
			$user_id = $this->get_user();
			$resource = "{$user_id}/{$resource}";
		}
		return "{$this->url_api}/v{$this->version}/user/{$resource}";
	}
		
	protected function send_request( $resource = '', $params = [] )
	{		
		$headers = $this->get_headers();		
		$url_api = $this->get_url( $resource );	
		if ( $params )
			$url_api .= '?' . $this->dataToString($params);	
		$data = wp_remote_get( $url_api, ['sslverify' => true, 'headers' => $headers]);				 
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}		
		$resp = json_decode($data['body'], true); 
		if( isset($resp['error_code'] ) )
		{			
			if( $resp['error_code'] == 'INVALID_USER_ID' )
			{
				$url_api = $this->get_url();	
				$data = wp_remote_get( $url_api, ['sslverify' => true, 'headers' => $headers]);
				$result = json_decode($data['body'], true);
				if( !empty($result['user_id']) )
				{
					$this->option['webmaster']['user_id'] = $result['user_id'];
					update_option('usam_yandex', $this->option);
				}
			}
			$this->set_error( $resp );
			return false;
		}		
		return $resp;		
	}
}
?>