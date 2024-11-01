<?php
class USAM_Instagram_API
{	
	public  $message = array();
	private $errors  = array();
	private $error;
	private $API_URL;
	private $version = '1';
	private $options;
	private $service_token = null;
	
	public function __construct( )
	{
		$this->options = get_option('usam_instagram_api', array() ); 	
		$this->API_URL = 'https://api.instagram.com';		
		
		$this->service_token = !empty($this->options['token'])?$this->options['token']:$this->service_token;
	}
	
	public function get_errors( )
	{
		return $this->errors;
	}
	
	private function set_error( $error )
	{	
		$this->error = $error;
		if ( is_string($error) )
			$this->errors[]  =  sprintf( __('Ошибки Instagram. Ошибка: %s'), $error);
		else
			$this->errors[]  =  sprintf( __('Ошибки Instagram. Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $error['request_params'][1]['value'], $error['error_code'], $error['error_msg']);
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
		$this->errors = array();
	}		
			
	function make_excerpt( $post ) 
	{ 		
		if ( !empty($post->post_excerpt) ) 
			$text = $post->post_excerpt;
		else 
		{
			$text = $post->post_content;			
		}
		$text = strip_shortcodes( $text );
		// filter the excerpt or content, but without texturizing
		if ( empty($post->post_excerpt) )
		{
			remove_filter( 'the_content', 'wptexturize' );
			$text = apply_filters('the_content', $text);
			add_filter( 'the_content', 'wptexturize' );
		} 
		else 
		{
			remove_filter( 'the_excerpt', 'wptexturize' );
			$text = apply_filters('the_excerpt', $text);
			add_filter( 'the_excerpt', 'wptexturize' );
		}	
		$pos  = strripos($text, '<table');		
		if ($pos === false )
		{ // Не найдено
			$text = str_replace(']]>', ']]&gt;', $text);	
			$text = wp_strip_all_tags($text);			
			$text = str_replace(array("\r\n","\r","\n"),"\n",$text);		
		}
		else
		{
			$html_no_attr = preg_replace("#(</?\w+)(?:\s(?:[^<>/]|/[^<>])*)?(/?>)#ui", '$1$2', $text); // очистить от классов и стилей
			$html_no_attr =	preg_replace('~(<(.*)[^<>]*>\s*<\\2>)+~i','',$html_no_attr );						// удалить пустые строки таблицы
			preg_match_all('#<td>(.+?)</td>#s', $html_no_attr, $matches); 						
			$result = array_chunk($matches[1], 2);
			$text = '';			
			foreach ( $result as $record )
			{					
				$name = wp_strip_all_tags($record[0]);
				$content = wp_strip_all_tags($record[1]);
				$text .= $name.': '.$content.chr(10);
			}
		}		
		$excerpt_more = apply_filters('excerpt_more', '...');
		$excerpt_more = html_entity_decode($excerpt_more, ENT_QUOTES, 'UTF-8');
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = htmlspecialchars_decode($text);
		
		$max = !empty($this->options['excerpt_length']) ? $this->options['excerpt_length'] : 20;
		if ($max < 1) 
			return ''; // nothing to send
		$words = explode(' ', $text);

		if (count($words) >= $max)
		{
			$words = array_slice($words, 0, $max);
			array_push ($words, $excerpt_more);
			$text = implode(' ', $words);
		}
		$text = $this->excerpt_strlen($text);	
		return $text;
	}
	
	function excerpt_strlen ($text, $max_strlen = 2688)
	{		
		if (isset($this->options['excerpt_length_strings']) && !empty($this->options['excerpt_length_strings'])) 		
			$max_strlen = $this->options['excerpt_length_strings'] > $max_strlen ? $max_strlen : $this->options['excerpt_length_strings'];		

		if (strlen($text) >= $max_strlen) 
		{
			$text = substr($text, 0, $max_strlen);
			$words = explode(' ', $text);
			array_pop($words); // strip last word

			$excerpt_more = apply_filters('excerpt_more', '...');
			$excerpt_more = html_entity_decode($excerpt_more, ENT_QUOTES, 'UTF-8');
			array_push ($words, $excerpt_more);
			$text = implode(' ', $words);
		}
		return $text;
	}	
	
	public function get_users_feed( )
	{								
		$params = ['group_id' => $profile['page_id'], 'offset' => 0, 'fields' => 'sex, city, country, has_mobile, online, bdate, photo_50, photo_100'];		
		$results = $this->send_request( $params, 'users/self/feed' );		
		return $results;
	}	
		
	public function get_media_comments( $media_id )
	{			
		$params = array();
		$results = $this->send_request( $params, "media/{$media_id}/comments" );		
		return $results;
	}
	
	/*
	ACCESS_TOKEN	Действительный токен доступа.
	MAX_ID	Вернуть носитель раньше этого max_id.
	MIN_ID	Вернуть носитель позже этого min_id.
	COUNT	Количество
	
	*/	
	public function get_user_media( $params = array() )
	{	
		if ( empty($params['count']))
			$params['count'] = 20;
		
		if ( empty($params['max_id']))
			$params['max_id'] = 20;
		
		$results = $this->send_request( $params, 'users/self/media/recent' );		
		return $results;
	}	
	
	function generate_sig( $endpoint, $params, $secret )
	{
		$sig = $endpoint;
		ksort($params);
		foreach ($params as $key => $val) {
			$sig .= "|$key=$val";
		}
		return hash_hmac('sha256', $sig, $secret, false);
	}
	
	function get_access_token( $code )
	{		
		$api = get_option('usam_vk_api');		
		$params = array( 'client_id' => $this->options['client_id'], 'client_secret' => $this->options['client_secret'], 'grant_type' => 'authorization_code', 'redirect_uri' => admin_url('admin.php?unprotected_query=instagram_token'), 'code' => $code );
		$headers["Content-Type"] = 'application/x-www-form-urlencoded';		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $params,
			'cookies' => array(),
			'sslverify' => true
		);	
		$data = wp_remote_post($this->API_URL.'/oauth/access_token', $args );
		if (is_wp_error($data))
			return $data->get_error_message();

		$resp = json_decode($data['body'],true);
		if ( isset($resp['error_message'] ) ) 
		{			
			$this->set_error( $resp['error_message'] );	
			return false;
		}	
		if ( isset($resp['access_token']) )
			return $resp['access_token'];		
		return false;
	}	
	
	function send_request( $params, $function )
	{						
		$function = '/'.$function;
		$secret = '62476ad6ec4047298b129e62aa65b017';
		$params['access_token'] = $this->service_token;
		if ( $this->service_token == '' )
		{ 				
			$this->set_error( __('Токен не получен','usam') );	
			return false;
		}	
	//	$params['client_id'] = $this->options['client_id'];
	//	$params['sig'] = $this->generate_sig( $function, $params, $secret );
		
		
		$url = "{$this->API_URL}/v{$this->version}{$function}?".http_build_query($params);
		
		$data = wp_remote_get($url, array('body' => $params, 'sslverify' => true, 'timeout' => 5));		
		if ( is_wp_error($data) )
		{ 	
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		}		
		$resp = json_decode($data['body'],true);
		if ( isset($resp['meta']['error_message'] ) ) 
		{		
			$this->set_error( $resp['meta']['error_message'] );	
			return false;
		}	
		return $resp['data'];		
	}
}
?>