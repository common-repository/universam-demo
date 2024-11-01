<?php 
class USAM_Checking_Site_Position
{
	private $setting;	
	private $host;	
	private $errors = array();	
	private $competitor_count = 10;
	private $site_id = 0;
	private $competitor = false;		
	
	private function get_format( )
	{
		$format = ['id' => '%d', 'keyword_id' => '%d', 'location_id' => '%d', 'search_engine' => '%s', 'number' => '%d', 'date_insert' => '%s'];		
		return $format;
	}
	
	private function set_error( $error )
	{ 	
		$this->errors[] = sprintf( __('Проверка позиции сайта. Ошибка: %s'), $error );
	}
	
	function get_error_message(  )
	{
		return $this->errors;
	}
	
	protected function set_log_file( )
	{
		usam_log_file( $this->errors );
	}
	
	private function get_format_data( $data )
	{
		$format = $this->get_format();		
		
		$update_format = array();
		foreach ($data as $key => $value )
		{	
			if ( isset($format[$key]) )
			{
				$update_format[] = $format[$key];
			}
		}		
		return $update_format;
	}
		
	private function insert_competitor( $domain, $data )
	{
		if ( $this->competitor == false )
			return false; 
		
		$site = new USAM_Site( $domain, 'domain' );
		$site_data = $site->get_data();			
		if ( empty($site_data) )
		{
			$new_site = array( 'domain' => $domain, 'type' => 'C' );
			$site = new USAM_Site( $new_site );			
			$site->save();	
			$data['site_id'] = $site->get('id');
		}
		else
			$data['site_id'] = $site_data['id'];
		$this->insert_statistic( $data );	
	}
		
	private function insert_statistic( $data )
	{				
		global $wpdb;
		
		if ( empty($data['number']) )
			return false;
		
		$data['date_insert'] = date( "Y-m-d" );			
		$sql = "INSERT INTO `".USAM_TABLE_STATISTICS_KEYWORDS."` (`date_insert`,`keyword_id`,`location_id`,`search_engine`,`number`,`url`,`site_id`) VALUES ('%s','%d','%d','%s','%d','%s','%d') ON DUPLICATE KEY UPDATE `number`='%d'";	
		$result = $wpdb->query( $wpdb->prepare( $sql, $data['date_insert'], $data['keyword_id'], $data['location_id'], $data['search_engine'], $data['number'], $data['url'], $data['site_id'], $data['number'] ));	
		return $result;
	}	
	
	public function save_position_site( $site_id = 0, $competitor = true )
	{				
		global $wpdb;
		
		$this->site_id = absint($site_id);
		$this->competitor = (bool)$competitor;
		
		require_once( USAM_FILE_PATH . '/includes/simple_html_dom.php' );	
		require_once( USAM_FILE_PATH . '/includes/seo/site.class.php' );
		require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );			
		$this->host = mb_strtolower( parse_url( get_site_url(), PHP_URL_HOST) );	
		$limit = 30;	
			
		$keywords = usam_get_keywords( array('check' => 1, 'number' => $limit ) );			
				
		$date_insert = date( "Y-m-d" );	
		$statistic_keywords = $wpdb->get_results("SELECT * FROM `".USAM_TABLE_STATISTICS_KEYWORDS."` WHERE `date_insert`='{$date_insert}' AND `site_id`='{$this->site_id}'");		
		
		$current_stat = array();
		foreach( $statistic_keywords as $data )
		{
			$current_stat[$data->search_engine][$data->location_id][$data->keyword_id] = $data->number;
		}				
		$locations = usam_get_search_engine_regions();
		$y = $g = 0;
		
		$google_limit = get_option('usam_google_query_limit', 10);
		$yandex_limit = get_option('usam_yandex_query_limit', 10);
		
		$start = false;
		$limit_total = false;
		foreach( $locations as $location )
		{							
			foreach($keywords as $keyword)
			{	
				if ( !empty($current_stat[$location->search_engine][$location->location_id][$keyword->id]) )
					continue;	
				
				$start = true;
				if ( $location->search_engine == 'y' && $y < $yandex_limit )
				{								
					if ( $this->get_yandex_position( $keyword, $location ) )	
						$y++;	
					else
						$y = $yandex_limit;
						
				}
				elseif ( $location->search_engine == 'g' && $g < $google_limit )		
				{					
					if ( $this->get_google_position( $keyword, $location ) )
						$g++;
					else
						$g = $google_limit;
				}
				else
					$limit_total = true;
				
				if ( $y == $yandex_limit && $g == $google_limit )
				{
					$limit_total = true;
					break 2;				
				}
			}
		}			
		$this->set_log_file();		
		if ( $start == false )
			return true;
		
		return false;
	}	
	
	public function get_yandex_position( $keyword, $location )
	{	
		if ( $this->check_ability_request( 'usam_yandex_suspicious_traffic' ) == false ) 	
			return false;
		
		$yandex = get_option('usam_yandex');	
		if ( empty($yandex['xml']) )
			return false;
		
		$yandex_authorization = $yandex['xml'];				
		$pages = 1;	
		$n = 0;	
		
		$query_esc = htmlspecialchars( $keyword->keyword ); // текст поискового запроса
		$group_page = 100; //количество групп на одной странице
				
		$page  = 1;
		$url = '';
		$number = false;
		$is_error = false;				
		while ( $page <= $pages )
		{								
			$params = [
				'user' => $yandex_authorization['username'], // логин
				'key' => $yandex_authorization['password'], // ключ Яндекс.XML
				'filter' => 'none',
				'groupby' => 'attr=d.mode=deep.groups-on-page='.$group_page, // attr=<служебный атрибут>.mode=<тип группировки>.groups-on-page=<количество групп на одной странице>.docs-in-group=<количество документов в каждой группе>
				'query' => urlencode($query_esc),
				'lr' => $location->code
			];
			$request_link = "https://yandex.ru/search/xml" . '?' . urldecode(http_build_query($params). '<<host="'.$this->host.'"');
			$response = @file_get_contents($request_link);	
			sleep(1);
			if ( $response ) 
			{ 						
				$xmldoc = new SimpleXMLElement( $response );
				if ( !empty($xmldoc->response->error) ) 
				{
					$is_error = true;						
					if( strpos($response, 'Лимит запросов исчерпан') !== false ) 
					{
						$min = (int)date('i');							
						$time = time() + 60*(60-$min)+1;
						set_transient( 'usam_yandex_suspicious_traffic', $time, HOUR_IN_SECONDS );
						$this->set_error( __( "Яндекс. Лимит запросов исчерпан. Увеличьте количество запросов или подождите...", "usam") );
					}					
					else
					{
						$error = $xmldoc->response->error->__toString();	
						$this->set_error( $error );
					}				
					return false;
				}    
				$pos = 1;
			//	$domains = $xmldoc->xpath('/yandexsearch/response/results/grouping/group/doc/domain');	
				$links = $xmldoc->xpath('/yandexsearch/response/results/grouping/group/doc/url');							
				foreach ( $links as $link ) 
				{						
					$link = (string)$link[0];
					$domain = mb_strtolower(parse_url($link, PHP_URL_HOST));	
					$domain = str_replace("www.","",$domain);					
					$n++;					
					if ( $this->host == $domain )
					{							
						$url = $link;
						$number = $pos + ( $page - 1 ) * $group_page;	
					}          
					elseif ( $this->competitor_count >= $n )
					{
						$k = $pos + ( $page - 1 ) * $group_page;	
						$this->insert_competitor( $domain, array('keyword_id' => $keyword->id, 'search_engine' => $location->search_engine, 'location_id' => $location->location_id, 'number' => $k, 'url' => $link) );
					}
					if ( $this->competitor_count < $n && $number )
						break 2;
					$pos++;
				}							
			} 
			else 
			{
				$is_error = true;		
				$this->set_error( __( "Яндекс. Внутренняя ошибка сервера", "usam") );								
				break;
			}	
			$page++;					
		}		
		if ( $is_error == false && $number == false )
			$number = 100;
		
		if ( $number )		
		{
			$this->insert_statistic( array('keyword_id' => $keyword->id, 'search_engine' => $location->search_engine, 'location_id' => $location->location_id, 'number' => $number, 'url' => $url, 'site_id' => $this->site_id ) );	
		}
		return $number;	
	}	
	
	// https://www.searchengines.ru/regionalnost-v-google-get-parametr-uule.html
	private function get_google_secret_key( $number )
	{
		$secretkey = array( 4 => 'E', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z', 26 => 'a', 27 => 'b', 28 => 'c', 29 => 'd', 30 => 'e', 31 => 'f', 32 => 'g', 33 => 'h', 34 => 'i', 35 => 'j', 36 => 'k', 37 => 'l', 38 => 'm', 39 => 'n', 40 => 'o', 41 => 'p', 42 => 'q', 43 => 'r', 44 => 's', 45 => 't', 46 => 'u', 47 => 'v', 48 => 'w', 49 => 'x', 50 => 'y', 51 => 'z', 52 => '0', 53 => '1', 54 => '2', 55 => '3', 56 => '4', 57 => '5', 58 => '6', 59 => '7', 60 => '8', 61 => '9', 62 => '--', 63 => '-', 64 => 'A', 65 => 'B', 66 => 'C', 67 => 'D', 68 => 'E', 69 => 'F', 70 => 'G', 71 => 'H', 72 => 'I', 73 => 'J', 76 => 'M', 83 => 'T', 89 => 'L' );
		
		return isset($secretkey[$number])?$secretkey[$number]:'';		
	}
	
	function check_ability_request( $key )
	{
		if ( $time = get_transient( $key ) ) 
		{
			if ( $time < time() ) 						
				delete_transient( $key );
			else
				return false;
		}			
		return true;
	}
		
	function get_google_position( $keyword, $location )
	{			
		if ( $this->check_ability_request( 'usam_google_suspicious_traffic' ) == false ) 	
			return false;
	
		$location_id = 0;
		$page = 0;		
		$number = false;	
		$url = '';
		$is_error = false;
		
		$uule = "w+CAIQICI".$this->get_google_secret_key(mb_strlen($location->name)).base64_encode( $location->name ); 	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.google.ru/search?q='.urlencode($keyword->keyword)."&uule=$uule&num=100&hl=ru&start=$page&ie=UTF-8");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION,CURL_SSLVERSION_DEFAULT);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$webcontent = curl_exec ($ch);
		$error = curl_error($ch); 
		curl_close ($ch);	
		if( $webcontent )
		{	
			$html = usam_str_get_html( $webcontent );
			if ( !empty($html) )
			{
				$links = $html->find( '#search a' );	
				foreach ( $links as $i => $a ) 				
				{ 
					$link = $a->getAttribute( 'href' );							
					$domain = parse_url($link, PHP_URL_HOST);	
					$domain = str_replace("www.","",$domain);
					if( $domain == $this->host )			
					{							
						$number = $n+1;
						$url = $link;
					}
					elseif ( $this->competitor_count > $n )
					{
						$k = $n+1;
						$this->insert_competitor( $domain, ['keyword_id' => $keyword->id, 'search_engine' => $location->search_engine, 'location_id' => $location->location_id, 'number' => $k, 'url' => $link]);
					}
					if ( $this->competitor_count < $n && $number )
						break;					
				}
			}	
			else
			{ 
				$is_error = true;
				$this->set_error( __( "Google зарегистрировал подозрительный трафик, исходящий из вашей сети.", "usam") );
				$time = time() + 3600;
				set_transient( 'usam_google_suspicious_traffic', $time, HOUR_IN_SECONDS );
			}				
		}	
		else
		{			
			$is_error = true;
			$this->set_error( __( "Google. Внутренняя ошибка сервера", "usam") );
		}	
		sleep(5);
		if ( $is_error == false && $number == false )
			$number = 100;

		if ( $number )
		{			
			$this->insert_statistic(['keyword_id' => $keyword->id, 'search_engine' => $location->search_engine, 'location_id' => $location->location_id, 'number' => $number, 'url' => $url, 'site_id' => $this->site_id]);
		} 		
		return $number;	
	}	
}

function usam_query_position_site()
{		
	$class = new USAM_Checking_Site_Position();
	$result = $class->save_position_site( );			
	if ( $result == false )		
	{
		if ( !get_transient( 'usam_start_query_position_site' ) ) 
		{
			$check_time = mktime(date_i18n('H'), 59, 59, date('m'), date_i18n('d'), date('Y'))+ 21600 - time(); // отложит на 6 утра следующего дня
			set_transient( 'usam_start_query_position_site', true, $check_time ); //DAY_IN_SECONDS
		}
	}
}
?>