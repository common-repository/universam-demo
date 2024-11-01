<?php 
require_once( USAM_FILE_PATH . '/includes/simple_html_dom.php' );		
class USAM_Parser
{
	protected $options = [];
	protected $html = null;
	protected $json_mass = null;
						
	public function __construct( $options ) 
	{		
		if ( !defined('USAM_LINK_PROCESSING') )
			define('USAM_LINK_PROCESSING', 'standart' );		
		if ( is_numeric($options) )
			$this->options = usam_get_parsing_site( $options );
		else
			$this->options = $options;
	}
	
	public function get_website_data( $url )
	{
		$webcontent = $this->get_web_content( $url );	
		if ( $webcontent )		
		{	
						
	//		$Log = new USAM_Log_File( 'webcontent', true ); 
 //$Log->fwrite_array( $webcontent );
 
			$this->html = usam_str_get_html( $webcontent ); 
	
			unset($webcontent);
			if ( !empty($this->html) )
				return $this->get_site_data();	
			else
				$this->set_message_log_file( __("Не удалось обработать страницу сайта",'usam') );
		}	
		return false;
	}

	public function clearing_link( $href )
	{	
		if ( !$href || $href === '#' || strpos($href, 'tel:+7') !== false )			
			return '';	
		if ( strpos($href, '#') !== false )		
			$href = strstr($href, '#', true);
		return $href;
	}	
	
	public function get_links()
	{
		$urls = [];		 
		if ( is_object($this->html) )
		{					
			$webcontent = $this->html->innertext; 
			if( str_contains( $webcontent, '<?xml' ) && str_contains( $webcontent, 'http://www.sitemaps.org/schemas/sitemap/0.9' ))
			{
				$links = $this->html->find('loc');
				foreach( $links as $i => $a ) 				
				{ 
					$urls[] = $a->innertext; 
					unset($links[$i]);				
				}
			}
			elseif ( USAM_LINK_PROCESSING == 'regex' ) 
			{ 				
				if( preg_match_all('/<a.*?href=["\'](.*?)["\'].*?>/i', $webcontent, $matches) )
				{
					foreach( $matches[1] as $href ) 				
					{ 
						$href = $this->clearing_link( $href );
						if ( !$href )			
							continue; 
						$urls[] = $href;			
					}	
				}
			}
			else 
			{
				$links = $this->html->find('a');			
				foreach( $links as $i => $a ) 				
				{ 
					$href = $a->getAttribute( 'href' );	
					$href = $this->clearing_link( $href );
					if ( !$href )			
						continue;
					$urls[] = $href;
					unset($links[$i]);				
				}
			}			
		}
		return $urls;
	}
	
	public function clear()
	{
		if ( is_object($this->html) )
		{
			$this->html->clear();
			$this->html = null;	
		}			
	}
	
	protected function get_tags_data( $key, $doc )
	{
		$value = '';
		if ( $key == 'content' )
		{
			$value = $doc->innertext;	
			$value = preg_replace('/\s?<script[^>]*?>.*?<\/script>\s?/si', ' ', $value);
			$value = preg_replace('/\s?<style[^>]*?>.*?<\/style>\s?/si', ' ', $value);
			$value = trim(html_entity_decode($value));
		}
		elseif ( $key == 'thumbnail' || $key == 'images' )
		{ 
			if( $doc->hasAttribute( 'href' ) ) 
				$src = $doc->getAttribute( 'href' );
			elseif( $doc->hasAttribute( 'src' ) ) 
				$src = $doc->getAttribute('src');
			elseif( $doc->hasAttribute( 'data-src' ) ) 
				$src = $doc->getAttribute('data-src');
			elseif( $doc->hasAttribute( 'data-thumb' ) ) 
				$src = $doc->getAttribute('data-thumb');
			elseif( $doc->hasAttribute( 'data-bg' ) ) 
				$src = $doc->getAttribute('data-bg');	
			elseif( $doc->hasAttribute( 'data-img' ) ) 
				$src = $doc->getAttribute('data-img');			
			elseif( $doc->hasAttribute( 'content' ) ) 
				$src = $doc->getAttribute('content');	
			elseif ( $doc->tag !== 'img' )
			{ 
				$img = $doc->find('img', 0);
				if ( !empty($img) )
					$src = $this->get_tags_data( $key, $img );
			} 
			if ( $src && strpos($src, 'http://') === false && strpos($src, 'https://') === false )
				$src = $this->options['scheme'].'://'.$this->options['domain'].$src;	
			$value = $src; 
		}		
		elseif ( $key == 'variations' )
		{						
			$value = [];			
			$value['name'] = trim(html_entity_decode($doc->plaintext));
			$attributes = usam_get_parsing_site_metadata( $this->options['id'], 'variations' );	
			foreach ($attributes as $key => $attribute)
			{
				if( $doc->hasAttribute($attribute['attribute']) ) 
				{
					$value[$key] = $doc->getAttribute($attribute['attribute']);
				}				
			}
		}		
		elseif ( $key == 'brand' )
		{ 
			if ( $doc->tag == 'img' )
			{
				if( $doc->hasAttribute( 'alt' ) ) 
					$value = $doc->getAttribute( 'alt' );
				elseif( $doc->hasAttribute( 'title' ) ) 
					$value = $doc->getAttribute('title');
			}			
			else
				$value = trim(html_entity_decode($doc->innertext));	 
		}	
		elseif ( $key == 'product_identification' )
			$value = $doc->innertext?true:false;
		elseif ( $doc->tag === 'meta' && $doc->hasAttribute( 'content' ) ) 			
			$value = $doc->getAttribute('content');
		else			
			$value = trim(preg_replace("~\x{00a0}~siu", " ", html_entity_decode($doc->innertext)));
		return $value;
	}
	
	protected function get_json_mass( $value )
	{
		if ( $this->json_mass === null )
		{
			$docs = $this->html->find( $value['tag'] );
			$this->json_mass = [];
			if ( !empty($docs) )
			{
				foreach ($docs as $doc)
					$this->json_mass[] = json_decode($doc->innertext);
			}			
		}  
		$content = '';
		if( preg_match_all('/\[\'(.+?)\'\]/s', $value['json_mass'], $mt) )
		{	 				
			foreach ($this->json_mass as $obj)
			{								
				$content = $obj;
				foreach ($mt[1] as $k)
				{	
					if ( is_array($content) )
					{							
						$m = [];	 
						foreach( $content as $v )
							$m[] = $v->$k;
						$content = $m;		 
					}
					elseif ( isset($content->$k) )
						$content = $content->$k;
					else
					{
						$content = '';
						break;
					} 
				}
				if ( $content )
					break;
			}
		}
		return $content;
	}

	protected function get_site_data()
	{		
		$tags = (array)usam_get_parsing_site_metadata( $this->options['id'], 'tags' );	
		$response = [];
		foreach ($tags as $key => $value) 
		{
			if ( empty($value['tag']) )
				continue;	
			  
			if ( $this->options['view_product'] == 'list' || !isset($value['number']) || !empty($value['plural']) )
			{
				$docs = $this->html->find( $value['tag'] );	
				if ( !empty($docs) )	
				{
					foreach ($docs as $doc) 
					{
						$data = $this->get_tags_data( $key, $doc);
						if ( !empty($value['rules']) )
							$data = $this->processing_data( $value['rules'], $data );
						$response[$key][] = $data;
					} 
				}	
			}		
			else
			{	
				if ( !empty($value['json_mass']) )
					$response[$key] = $this->get_json_mass( $value );
				else
				{
					$doc = $this->html->find($value['tag'], $value['number']);
					if ( !empty($doc) )
						$response[$key] = $this->get_tags_data( $key, $doc );	
				}
				if ( $key == 'product_identification' && empty($response['product_identification']) )
					break;
				
				if ( isset($response[$key]) && !empty($value['rules']) )
					$response[$key] = $this->processing_data( $value['rules'], $response[$key] );
			}
		}	  
		if ( $this->options['view_product'] == 'list' )
		{			
			$results = array();
			foreach ($response as $tag => $values)
			{
				foreach ($values as $key => $value)
				{
					$results[$key][$tag] = $value;
				}
			}
			foreach ($results as $key => $values)
			{
				$results[$key]['product_identification'] = empty($tags['product_identification']) || isset($results[0]['product_identification']) && $results[0]['product_identification']?true:false;
				$results[$key]['not_available'] = empty($tags['not_available']) || isset($results[0]['not_available']) && $results[0]['not_available']?true:false;	
			}
			return $results;
		}
		else
		{			
			$response['product_identification'] = empty($tags['product_identification']) || isset($response['product_identification']) && $response['product_identification']?true:false;
			$response['not_available'] = empty($tags['not_available']) || isset($response['not_available']) && $response['not_available']?true:false;
		}	
		return $response;		
	}
	
	protected function processing_data( $rules, $value )
	{			
		foreach ($rules as $rule)
		{
			$rule['replace'] = isset($rule['replace'])?$rule['replace']:'';	
			switch ( $rule['operator'] ) 
			{
				case 'replace' :							
					if ( $rule['search'] )
					{
						if ( is_array($value) )
							foreach ($value as $k => $v)
								$value[$k] = str_replace($rule['search'], $rule['replace'], $v);
						else
							$value = str_replace($rule['search'], $rule['replace'], $value);
					}
				break;
				case 'regular' :	
					if ( $rule['search'] )
					{
						if ( is_array($value) )
							foreach ($value as $k => $v)
								$value[$k] = preg_replace($rule['search'], $rule['replace'], $v);
						else
							$value = preg_replace($rule['search'], $rule['replace'], $value);						
					}
				break;				
				case 'preg_match_all' :						
					if ( $rule['replace'] )
					{
						preg_match_all($rule['replace'], $value, $matches);
						$value = isset($matches[1])?$matches[1]:[];
					}
				break;
				case 'preg_match' :						
					if ( $rule['replace'] )
					{
						preg_match($rule['replace'], $value, $matches);
						$value = isset($matches[1])?$matches[1]:[];
					}
				break;				
				case 'strip_tags' :							
					if ( is_array($value) )
						$value = array_map('strip_tags', $value);
					else
						$value = strip_tags( $value );					
				break;
				case 'trim' :				
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = trim($v, $rule['replace']);
					}
					else
						$value = trim($value, $rule['replace']);
				break;
				case 'ltrim' :				
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = ltrim($v, $rule['replace']);
					}
					else
						$value = ltrim($value, $rule['replace']);
				break;
				case 'rtrim' :				
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = rtrim($v, $rule['replace']);
					}
					else
						$value = rtrim($value, $rule['replace']);
				break;
				case 'explode' :				
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = explode($v, $rule['replace']);
					}
					else
						$value = explode($value, $rule['replace']);
				break;	
				case 'float' :	
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = $this->to_float( $v );
					}
					else
						$value = $this->to_float( $value );
				break;
				case 'tabs' :				
					if ( is_array($value) )
					{
						foreach ($value as $k => $v)
							$value[$k] = trim($v);
					}
					else 
						$value = trim($value); 
				break;					
			}
		}		
		return $value;
	}
	
	protected function get_cookie()
	{			
		static $cookie = null;
		if ( !$cookie )		
			$cookie = @tempnam ("/tmp", "CURLCOOKIE".$this->options['id']);  
		return $cookie;
	}
	
	protected function delete_cookie()
	{			
		$cookie = $this->get_cookie();
		if ( file_exists($cookie) )
			unlink($cookie);
	}
	
	private function curl_login( $page_url, $base_url, $user_data ) 
	{		
		$cookie = $this->get_cookie();	
		
		$error_page = array();
		$ch = curl_init();			
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36');   
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);// файл, откуда читаются куки.
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);// файл, куда пишутся куки после закрытия коннекта
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Автоматом идём по редиректам
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Не проверять SSL сертификат
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Не проверять Host SSL сертификата
		curl_setopt($ch, CURLOPT_URL, $page_url); 
		curl_setopt($ch, CURLOPT_REFERER, $base_url); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $user_data); 
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response['html'] = curl_exec($ch);
		$info = curl_getinfo($ch);
		if($info['http_code'] != 200 && $info['http_code'] != 404) {
			$error_page[] = array(1, $page_url, $info['http_code']);
		}		
		$response['code'] = $info['http_code'];
		$response['errors'] = $error_page;
		curl_close($ch);			
		return $response;
	}
	
	public function site_login() 
	{		
		$authorization_parameters = usam_get_parsing_site_metadata( $this->options['id'], 'authorization_parameters' );
		if( $authorization_parameters )
		{	
			$login_page = usam_get_parsing_site_metadata( $this->options['id'], 'login_page' );
			$login_page = $login_page ? $login_page : $url;
			$response = $this->curl_login( $login_page, $this->options['domain'], json_decode($authorization_parameters, true) );
			if( empty($response['html']) || $response['code'] != 200) 
				return false;
		}	
		return true;		
	}
	
	private function get_args_by_string( $string, $args, $value ) 
	{	
		if( preg_match_all('/\[\'(.+?)\'\]/s', $string, $mt) )
		{						
			$a =& $args;
			foreach( $mt[1] as $k )
			{	
				$a[$k] = $value;
				$a =& $a[$k];
			}
		}
		return $args;
	}
	
	public function get_web_content( $url ) 
	{					
		$cookie = $this->get_cookie();
		if( !usam_is_license_type('LITE') && (int)usam_get_parsing_site_metadata( $this->options['id'], 'authorization' ) )
		{ 
			if( !$this->site_login() )
			{	
				$this->set_message_log_file( sprintf( __("Ошибка авторизации.",'usam'), $url ) );	
				return false;						
			}
		}
		$headers = [];
		$_headers = usam_get_parsing_site_metadata( $this->options['id'], 'headers' );
		if( $_headers )
			$headers = explode ("\n", $_headers);			
		$headers[] = 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_ENCODING , "") ; //Установить HTTP-заголовок «Accept-Encoding». В этом заголовке будут перечислены все поддерживаемые curl форматы сжатия. Будет произведено автоматическое декодирование сжатого контента
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);			
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);//Максимальное количество последующих перенаправлений HTTP		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers ); 			
		if ( $this->options['proxy'] )
		{
			static $proxy_server = null;
			if ( $proxy_server === null )
				$proxy_server = usam_get_proxy(); 		
			
			curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_PROXY, $proxy_server);		
		}	
		else
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
		
		//curl_setopt($ch, CURLOPT_INTERFACE, 'ip адрес'); дополнительные IP адреса на хостинге. В этом случае переключение IP при парсинге выполняется следующей опцией CURL:
		
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if( (int)usam_get_parsing_site_metadata( $this->options['id'], 'authorization' ) )
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);// файл, откуда читаются куки.
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);// файл, куда пишутся куки после закрытия коннекта	
		 
		$webcontent = curl_exec ($ch);		
		if( $webcontent === false)
		{
			$error = curl_error($ch); 
			$this->set_message_log_file( sprintf( __("Страница %s не доступна. %s",'usam'), $url, $error ) );	
			$webcontent = '';
		}
		else
		{
			if ( stripos($webcontent, 'charset=windows-1251') !== false ) 
				$webcontent = iconv("WINDOWS-1251", "UTF-8//IGNORE", $webcontent);
		} 		
		curl_close($ch);	
		return $webcontent;
	}	
	
	protected function to_float( $number )
	{		
		$number = preg_replace("|[^0-9.,]|i", "", html_entity_decode(strip_tags($number)) ); 
		return usam_string_to_float( $number );
	}
	
	protected function set_message_log_file( $message )
	{
		if ( !empty($this->options['domain']) )
			usam_log_file( $message, 'web_spider_'.$this->options['domain'] );
	}
}


function usam_get_proxy( $country = 'RU' )
{	
	/*
	$data = wp_remote_post('http://htmlweb.ru/json/proxy/get', ['body' => ['api_key' => '0f57a9e88a0bf31a36fa51e131ac8f91', 'country' => $country, 'work' => 1, 'short'], 'sslverify' => true, 'timeout' => 5, 'method' => 'GET']);
	if ( is_wp_error($data) )
		return false;
	$resp = json_decode($data['body'],true);	
	$timezone = isset($resp['offset'])?$resp['offset']:false;	
	*/
		
	
	$proxy = ['85.26.146.169:80', '80.244.230.86:8080', '185.15.172.212:3128', '131.196.191.251:9876', '89.43.31.134:3128', '185.148.223.76:3128', '147.139.138.14:3128', '176.31.68.254:20065'];		
	$key = array_rand($proxy);	
	return $proxy[$key]; 	
}

function usam_get_user_agent( )
{
	$user_agents = [
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
			'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
			'Opera/9.80 (Windows NT 5.1) Presto/2.12.388 Version/12.10',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET4.0C; .NET4.0E)',
			'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/536.5 (KHTML, like Gecko) YaBrowser/1.1.1084.5409 Chrome/19.1.1084.5409 Safari/536.5',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU) AppleWebKit/531.9 (KHTML, like Gecko) Version/4.0.3 Safari/531.9.1',
			'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7',
			'Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1',
			'Mozilla/4.5b1 [en] (X11; I; Linux 2.0.35 i586)',
			'Mozilla/5.0 (compatible; Konqueror/2.2.2; Linux 2.4.14-xfs; X11; i686)',
			'Mozilla/5.0 (Macintosh; U; PPC; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
			'Mozilla/5.0 (Windows; U; Win98; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
			'Mozilla/5.0 (X11; U; Linux 2.4.2-2 i586; en-US; m18) Gecko/20010131 Netscape6/6.01',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:0.9.3) Gecko/20010801',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.7) Gecko/20060909 Firefox/1.5.0.7',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.6) Gecko/20040413 Epiphany/1.2.1',
			'Opera/9.0 (Windows NT 5.1; U; en)',
			'Opera/8.51 (Windows NT 5.1; U; en)',
			'Opera/7.21 (Windows NT 5.1; U)',
			'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.8.0.6) Gecko/20060928 Firefox/1.5.0.6',
			'Opera/9.02 (Windows NT 5.1; U; en)',
			'Opera/8.54 (Windows NT 5.1; U; en)'
	];
	$key = array_rand($user_agents);
	$user_agent = $user_agents[$key]; 
	return $user_agent;
}
?>