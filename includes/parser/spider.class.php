<?php 
require_once( USAM_FILE_PATH . '/includes/parser/product-update-parser.class.php' );
require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );
require_once( USAM_FILE_PATH . '/includes/parser/parser.function.php' );
abstract class USAM_Spider
{
	protected static $id = 0;
	protected static $parsing_site = [];	
	protected static $link_rules = [];
	protected static $link_option = 0;	
	protected $products_added = 0;	
	protected $products_update = 0;	
	protected static $start_time;	
	protected static $max_time;		
	protected $link_processing = [];		
	protected $site_urls = [];
					
	public function get( $id ) 
	{			
		global $wpdb;
		require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );		
		self::$id = $id;	
		self::$parsing_site = usam_get_parsing_site( self::$id );		
		if ( empty(self::$parsing_site) )
			return false;	

		$metas = usam_get_parsing_site_metadata( self::$id );
		foreach($metas as $metadata )
		{
			if ( $metadata->meta_key == 'existence_check' || $metadata->meta_key == 'bypass_speed' || $metadata->meta_key == 'type_import' )
				self::$parsing_site[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);				
		}				
		$site_urls = $wpdb->get_results("SELECT url, status FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".self::$id." AND status=0 ORDER BY date_insert ASC LIMIT 1000");
		if ( $site_urls )
		{
			$this->site_urls = $site_urls;
			$this->products_added = usam_get_parsing_site_metadata( self::$id, 'products_added' );
			$this->products_update = usam_get_parsing_site_metadata( self::$id, 'products_update' );			
		}		
		else
		{			
			$status = $wpdb->get_var("SELECT status FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".self::$id." AND status=1");
			if ( $status )
				return false;	
			else
			{				
				$urls = usam_get_parsing_site_metadata( self::$id, 'urls' );
				foreach( $urls as $key => &$url_data )
				{
					if ( !$key )						
					{
						$this->add_url( $url_data['url'] );
						$url_data['status'] = 1;
					}
					else
						$url_data['status'] = 0;
				}
				usam_update_parsing_site_metadata( self::$id, 'urls', $urls );
			}
		}	
		self::$link_option = absint(usam_get_parsing_site_metadata( self::$id, 'link_option' ));
		$link_rules = usam_get_parsing_site_metadata( self::$id, 'excluded' );
		self::$link_rules = empty($link_rules)?[]:explode(chr(10),$link_rules);
		foreach (self::$link_rules as $key => $excluded_url) 
			self::$link_rules[$key] = trim($excluded_url);
		return true;
	}
		
	public function start()
	{			
		global $wpdb;
		self::$start_time = time();
		
		ignore_user_abort(true);		
		set_time_limit(1800);
		if ( function_exists( 'ini_get' ) )
			self::$max_time = ini_get('max_execution_time') - 20;				
		if ( self::$max_time <= 0 )
			self::$max_time = 200;		
		if ( self::$parsing_site['scheme'] == 'https' )
		{
			add_filter( 'https_ssl_verify', '__return_true' );
			add_filter( 'https_local_ssl_verify', '__return_true' );
		}
		else
			add_filter( 'https_ssl_verify', '__return_false' );		
	
		$anonymous_function = function($parsed_args, $url) { $parsed_args['reject_unsafe_urls'] = false; return $parsed_args; };	
		add_filter( 'http_request_args', $anonymous_function, 10, 2 ); //Отключить проверку ссылки при загрузке картинок
		
		add_filter( 'block_local_requests', '__return_false' );				
		$anonymous_function = function($is, $host, $url) { return true; };	
		add_filter( 'http_request_host_is_external', $anonymous_function, 10, 3 );
		
		$anonymous_function = function($r, $url) { 
			$r['user-agent'] = usam_get_user_agent();
			return $r;
		};	
		add_filter( 'http_request_args', $anonymous_function, 10, 2 );		
			
		if ( usam_is_license_type('FREE') )	
			return true;			

	//	register_shutdown_function(array(get_called_class(), 'shutdown')); 				
		
		usam_start_measure_performance();
		
		$urls = usam_get_parsing_site_metadata( self::$id, 'urls' );	
		foreach( $urls as $url_data )
		{
			if( $url_data['status'] == 1 )
			{
				$this->link_processing = $url_data;
				break;
			}
		}
		if ( empty($this->site_urls) || empty($this->link_processing) )
			return true; // Завершить			
		
		foreach( $this->site_urls as $k => $d ) 
		{ 				
			if( empty($d->status) )
			{			
				if( $this->start_parsing( $d->url ) )
					unset($this->site_urls[$k]);
				else
					break;
			}
			if( self::$max_time < time() - self::$start_time )		
				break;
		}			
		$completed = (int)$wpdb->get_var("SELECT COUNT(*) FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".self::$id." AND status=0")==0;
		if ( $completed )
			$completed = $this->complete_link_processing();
		if ( $completed )
		{ 
			usam_update_parsing_site( self::$id, ['end_date' => date("Y-m-d H:i:s")] );
			$parser = new USAM_Parser( self::$parsing_site );
			$cookie = $parser->delete_cookie();
		}
		$this->calculate_result();		
		return $completed;
	}
	
	public function start_parsing( $url )
	{	
		static $errors = 0;
				
		$parser = new USAM_Parser( self::$parsing_site );
		$data = $parser->get_website_data( $url );			
		$links = $parser->get_links();
		$parser->clear();
		unset($parser);
		$this->update_url( $url );		
		if( $data )		
		{		
			if ( self::$parsing_site['view_product'] == 'list' )
			{
				foreach ($data as $k => $value)
				{								
					if ( $this->check_data( $value ) )							
						$data[$k]['url'] = $url;
					else
						unset($data[$k]);
				}
			}
			elseif( $this->check_data( $data ) )
				$data['url'] = $url;
			else
				$data = []; 		
			if( empty($data) || $this->parsing( $data, $url ) )
				return $this->check_links( $links );	
		}
		elseif ( $links )
			return $this->check_links( $links );
		else
		{
			$errors++;
			if ( $errors > 3 )
				return true;
			else
				sleep(1);		
		}
		return false;
	}
	
	public function check_links( $urls )
	{		
		$result = true;	
		if ( $urls )
		{		
			foreach ( $urls as $i => $url ) 				
			{ 				
				$host = parse_url($url, PHP_URL_HOST);
				if ( !$host )
				{
					$url = self::$parsing_site['scheme'].'://'.self::$parsing_site['domain'].$url;
					$urls[$i] = $url;
					$host = self::$parsing_site['domain'];
				}	
				if ( strlen($url) > 255 || $host != self::$parsing_site['domain'] || !$this->check_link( $url ) ) 
					unset($urls[$i]);	
			}
			$this->calculate_result();
			foreach ( $urls as $i => $url ) 				
			{ 		
				$bypass_speed = self::$parsing_site['bypass_speed']?self::$parsing_site['bypass_speed']*1000000:1000000;
				$bypass_speed = $bypass_speed < 10000 ? 10000:$bypass_speed; 
				unset($urls[$i]);
				usleep($bypass_speed);							
			}
		}
		return $result;
	}
		
	protected function parsing( $data, $url )
	{		
		if ( self::$parsing_site['view_product'] == 'list' )
		{
			foreach ($data as $value)		
				$this->insert_product( $value );	
		}
		else
			$this->insert_product( $data );
		return true;
	}

	protected function check_link_processing_rules( $url )
	{	
		foreach (self::$link_rules as $excluded_url) 
		{					
			if ( substr($excluded_url, -1)  == '*' )
			{				
				if (substr($excluded_url,0,1 ) == '*' )
				{							
					$excluded_url = str_replace('*', '', $excluded_url);
					if ( stripos($url, $excluded_url) !== false )
						return true;
				}
				else
				{					
					$excluded_url = str_replace('*', '', $excluded_url);		
					if (strpos($url, $excluded_url) === 0)
						return true;
				}		
			}
			elseif (substr($excluded_url,0,1 ) == '*' )
			{						
				$excluded_url = str_replace('*', '', $excluded_url);
				if ( stripos($url, $excluded_url) !== false )
					return true;
			}
			elseif( $excluded_url == $url ) 
				return true; //Соответствует правилам
		}
		return false;
	}
	
	protected function check_link( $url )
	{			
		if( !empty(self::$link_rules) )
		{
			$result = $this->check_link_processing_rules( $url );	
			if ( $result && !self::$link_option || !$result && self::$link_option )		// 0 исключить, 1 включить
				return false;	
		}				
		$urls = explode( '.', $url );
		$end = end($urls);
		if ( in_array( $end, ['jpg','jpeg','png','webp']) )
			return false;		
	
		foreach( $this->site_urls as $data )
		{			
			if( $data->url == $url )
				return false;
		}	
		global $wpdb;
		$result = $wpdb->get_row("SELECT url, status FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".self::$id." AND url='$url'");
		if ( empty($result) )
			$this->add_url( $url );
		elseif ( $result->status == 1 )
		{
			$this->site_urls[] = $result;
			return false;							
		}
		return true;
	}
	
	protected function check_data( $data )
	{				
		if ( !$data['product_identification'] )
			return false;		
		$result = true;
		if ( !empty($this->link_processing['conditions']) )
		{
			foreach ($this->link_processing['conditions'] as $condition) 
			{	
				if ( $condition['value'] !== '' )
				{					
					$result = false;					
					if( isset($data[$condition['tag']]) )
					{
						if ( is_array($data[$condition['tag']]) )
						{						
							foreach ($data[$condition['tag']] as $value) 
							{								
								if ( usam_compare_data( $condition['operator'], $value, $condition['value'] ) )
								{
									$result = true;										
									break;
								}
							}							
						}					
						elseif ( $data[$condition['tag']] !== '' )
							$result = usam_compare_data( $condition['operator'], $data[$condition['tag']], $condition['value'] ); 	
						if ( $result )
							break;
					}
					else
						break;
				}
			}
		}
		return $result;
	}	
	
	private function complete_link_processing()
	{
		$urls = usam_get_parsing_site_metadata( self::$id, 'urls' );
		$completed = true;
		foreach( $urls as $k => $url_data )
		{
			if( $this->link_processing['url'] == $url_data['url'] )
				$urls[$k]['status'] = 2;
			if( !$url_data['status'] )
				$completed = false;
		}	 
		usam_update_parsing_site_metadata( self::$id, 'urls', $urls );
		return $completed;
	}	
		
	protected function calculate_result()
	{
		global $wpdb;
		$count = 0;
		$count_urls = $wpdb->get_results("SELECT COUNT(*) AS count, status FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".self::$id." GROUP BY status");		
		foreach ( $count_urls as $count_url ) 
		{
			if ( $count_url->status )
				usam_update_parsing_site_metadata( self::$id, 'links_processed', $count_url->count );				
			$count += $count_url->count;
		}
		usam_update_parsing_site_metadata( self::$id, 'count_urls', $count );
	}
	
	protected function add_url( $url )
	{
		global $wpdb;
		$wpdb->insert( USAM_TABLE_PARSING_SITE_URL, ['site_id' => self::$id, 'url' => $url, 'date_insert' => date("Y-m-d H:i:s")]);
		$x = new stdClass();
		$x->url = $url;
		$x->status = 0;
		$this->site_urls[] = $x; 
	}
	
	protected function update_url( $url )
	{
		global $wpdb;
		return $wpdb->update( USAM_TABLE_PARSING_SITE_URL, ['status' => 1], ['url' => $url], ['%d'], ['%s'] );	
	}
	
	protected function set_message_log_file( $message )
	{
		if ( !empty(self::$parsing_site['domain']) )
			usam_log_file( $message, 'web_spider_'.self::$parsing_site['domain'] );
	}
	
	
	protected function send_request( $url, $params )
	{	
		$headers = array();
		$headers[] = "Content-Type: application/json; charset=UTF-8"; 
		

		$params = array( 'GemsModel' => array( 'page' => 3, 'size_id' => 494, 'selected' => 0, 'unchecked' => 0, 'form_id' => 0, 'color_id' => 0, 'sort_id' => 0, 'nomination_id' => 0, 'origin_id' => 0, 'category_id' => 0 ), 'page' => 3 );
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);			
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10); //Максимальное количество последующих перенаправлений HTTP
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, usam_get_user_agent() ); 
		
		curl_setopt($ch, CURLOPT_POST, 1);		//CURLOPT_HTTPGET
	//	curl_setopt($ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $params ));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//	curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie);//set cookie file
	//	curl_setopt($ch,CURLOPT_COOKIEJAR, $cookie);//set cookie jar
		$webcontent = curl_exec ($ch);
		$error = curl_error($ch); 
		curl_close ($ch);							
		if ( !empty($error) ) 
		{		
			$this->set_message_log_file( $resp['error'] );	
			return false;
		}		
		$webcontent = json_decode($webcontent,true);		
		return $webcontent;		
	}
		
	public function count_number_links( $url = '', $count = 100 )
	{		
		static $urls = array();
		static $i = 0;
		static $site_url = '';
		
		$url = $url === ''?get_bloginfo('url'):$url; 
		if ( $site_url == '' )
		{
			$url = get_bloginfo('url');
			$parse_url = parse_url( $url );		
			$site_url = $parse_url['host'];
		}
		$urls[] = $url;
		$i++;
		$web = usam_get_url_object( $url );
		$dom = new DOMDocument;
		@$dom->loadHTML( $web['content'] );		
		foreach ($dom->getElementsByTagName('a') as $node) 
		{ 
			if ( $i == $count )
				break;
			if( $node->hasAttribute( 'href' ) ) 
			{
				$url = $node->getAttribute( 'href' );					
				if ( !in_array($url, $urls) && !empty($url) )	
				{  
					$parse_url = parse_url( $url );		
					if ( !empty($parse_url['host']) && $site_url === $parse_url['host'] )	
						$this->count_number_links( $url );
				}							
			}
		}	
		return $urls;
	}	
}
?>