<?php
/*
 * Класс для работы с api Одноклассники
 */
class USAM_OK_API
{	
	private $errors  = array();
	private $API_URL = "https://api.ok.ru/fb.do";
	private $version = '5.93';
	private $options;
	private $access_token;	
	private $secret_session_key;	
	private $application_key;		
	private $client_secret;	
	private $profile;
		
	public function __construct( $profile )
	{
		if ( is_array($profile) )
			$this->profile = $profile;
		else
			$this->profile =  usam_get_social_network_profile( $profile );
		
		$this->options = get_option('usam_ok_autopost', array() );
		$api = get_option('usam_odnoklassniki');		
		$this->access_token = !empty($api['access_token'])?$api['access_token']:$this->access_token;
		$this->secret_session_key = !empty($api['secret_session_key'])?$api['secret_session_key']:$this->secret_session_key;
		$this->application_key = !empty($api['application_key'])?$api['application_key']:$this->application_key;
		$this->client_secret = !empty($api['client_secret'])?$api['client_secret']:$this->client_secret;
	}	
	
	function array_in_string( $params )
	{				
		ksort($params);
		$paramsStr = "";	
		foreach($params as $key => $value)
		{
			if ( is_array($value) )
			{
				foreach($value as $k => $val)
					$paramsStr .= "{$key}[{$k}]={$val}";
			}
			else
				$paramsStr .= $key . "=" . $value;		
		}
		return $paramsStr;
	}
	
	function send_request( $params, $function )
	{		
		$params['application_key'] = $this->application_key;	
		$params['method'] = $function;	
		$params['format'] = 'json';	
		$paramsStr = $this->array_in_string( $params );			
		$sig = strtolower(	md5( $paramsStr	. md5(	$this->access_token . $this->client_secret )	) );
		$params['sig'] = $sig;		
		$params['access_token'] = $this->access_token;			
		$data = wp_remote_get($this->API_URL, array('body' => $params, 'sslverify' => true, 'timeout' => 5));	
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		}		
		$resp = json_decode($data['body'],true);	
		if ( isset($resp['error_msg'] ) ) 
		{		
			$this->set_error( $resp['error_msg'] );	
			return false;
		}		
		return $resp;		
	}
	
	private function make_excerpt( $post ) 
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
	
	private function excerpt_strlen ($text, $max_strlen = 2688)
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
	
	//chr(10)
	private function display_post_message( $post, $args = array() )
	{			
		if ( isset($args['message_format']) )
			$message_format = $args['message_format'];		
		elseif ( $post->post_type != 'usam-product' )
			$message_format = $this->options['post_message'];
		else		
			$message_format = $this->options['product_message'];			
		
		$args = $this->get_post_args( $post );				
		return $this->get_message_publication( $message_format, $args );
	}
	
	private function get_message_publication( $message, $args )
	{	
		$args['name'] = get_bloginfo('name');
		$args['link_catalog'] = __('Товары','usam').': '.usam_get_url_system_page('products-list');
		
		$shortcode = new USAM_Shortcode();		
		$message = $shortcode->process_args( $args, $message );	
		
		return $this->message_decode( $message );
	}
	
	// Сформировать сообщение
	private function message_decode( $message )
	{	
		$message = strip_tags($message);
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
		$message = htmlspecialchars_decode($message);  	
		$message = str_replace('\n', chr(10), $message );	
		return $message;
	}
	
	private function get_post_args( $post ) 
	{  		
		$args = array('title' => get_the_title($post->ID),
					  'excerpt' => $this->make_excerpt($post),
					  'link' => usam_product_url( $post->ID ),
		);
		if ( $post->post_type == 'usam-product' )
		{
			$price = usam_get_product_price( $post->ID, $this->profile['type_price'] ); 
			$old_price = usam_get_product_old_price( $post->ID, $this->profile['type_price'] );
			$price_currency = usam_get_formatted_price( $price, ['type_price' => $this->profile['type_price']]);	
			if ($old_price > 0 )
			{
				$discont = round(100 - $price*100/$old_price, 0);				
				$price_and_discont = $price_currency." - СКИДКА: $discont%";	
				$old_price_currency	= usam_get_formatted_price( $old_price, ['type_price' => $this->profile['type_price']]);		
			}				
			else
			{
				$price_and_discont = $price_currency;
				$discont = '';
				$old_price_currency = '';
			}
			$args['price_currency'] = $price_currency;
			$args['price'] = $price;
			$args['price_and_discont'] = $price_and_discont;
			$args['old_price_currency'] = $old_price_currency;
			$args['old_price'] = $old_price;
			$args['discont'] = $discont;
		}
		return $args;
	}	
	
	// Публикует новую запись на стене.
	function publish_post( $post, $args = array() ) 
	{		
		$default = array( 'add_link' => $this->options['add_link'], 'message_format' => '', 'campaign' => '' );		
		$args = array_merge( $default, $args );	
		
		if ( empty($post) )
			return false;
		
		if ( is_numeric($post) )
			$post = get_post( $post );
			
		$options = array();
		if ( $args['message_format'] )	
			$options['message_format'] = $args['message_format'];	
		$message = $this->display_post_message( $post, $options );		
		$images = $this->get_post_photo( $post, $this->options['upload_photo_count'] ); 
		$attachments = $this->upload_photo($images, $message );
			
		$params = $args;
		if (!empty($attachments) )
			$params['attachment']['media'][] = $attachments;
		if (!empty($message) )
			$params['attachment']['media'][] = array( "type" => "text", "text" => $message );	
		if ( $args['add_link'] )
			$params['attachment']['href'] = usam_get_url_utm_tags( $args['campaign'], usam_product_url( $post->ID ) );
		$result = $this->wall_post( $params );
		if ( isset($result) )
		{				
			usam_update_post_meta( $post->ID, 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'], date('Y-m-d H:i:s') );
			usam_update_post_meta( $post->ID, 'post_id_'.$this->profile['type_social'].'_'.$this->profile['code'], $result );
		}	
		return true;
	}
		
	// Выбрать фотографии для загрузки в контакт
	private function get_post_photo($post, $number )
	{					
		if ( $number > 5 )
			$number = 5;		
		$post_images = get_children( array('post_parent' => $post->ID, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order id', 'order' => 'ASC', 'numberposts' => $number )); 
			
		$images_path = array();		
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id )
			$images_path[] = get_attached_file( $thumbnail_id );	
		if ( !empty($post_images) )
		{
			$i = 1;
			foreach($post_images as $image)
			{			
				if ( $i > $number )
					break;
				
				if ( $thumbnail_id != $image->ID )
					$images_path[] = get_attached_file( $image->ID );			
				$i++;
			}
		}
		$result = array();
		$i = 0;
		foreach($images_path as $path)
		{			
			if ( version_compare( PHP_VERSION, '5.5', '>=' ) )
				$result[ 'file' . $i ] = new CURLFile( $path );
			else 
				$result[ 'file' . $i ] = '@' . $path;				
			$i++;
		} 
		return $result;	
	}	
	
	private function upload_server( $images, $params, $function )
	{	
		global $wp_version;
		$params['count'] = count($images);	
		$result = $this->send_request( $params, $function );	
		if ( empty($result['upload_url']) )
			return false;	
// Загрузить фото		
		$curl = new Wp_Http_Curl();
		$data = $curl->request( $result['upload_url'], array( 'body' => $images, 'timeout' => 45, 'method' => 'POST', 'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),'stream' => false, 'decompress' => false, 'filename' => null, ));	
	
		if ( is_wp_error($data) )
		{
			$this->errors[] = $data->get_error_message();
			return false;	
		}
		$result = json_decode($data['body'],true);		
		return $result;
	}
	
	public function upload_photo($images, $message = '' )
	{			
		if ( $this->profile['type_social'] == 'ok_group' )
			$params['gid'] = $this->profile['code'];
		else
			$params['uid'] = $this->profile['code'];		
			
		$result = $this->upload_server( $images, $params, 'photosV2.getUploadUrl' );	
		if ( empty($result['photos']) )
			return false;	
	
		$attachments = array( "type" => "photo", "list" => array() );
		foreach($result['photos'] as $r)
		{					
			$attachments['list'][] = array("id" => $r['token']);				
		}									
		return $attachments;
	}
	
		// Публикация записи на стене
	public function wall_post( $args ) 
	{
		if ( empty($args['attachment']) )
			return false;
		
		$params = array();
		$params['attachment'] = json_encode($args['attachment'], JSON_UNESCAPED_UNICODE);			
		if ( $this->profile['type_social'] == 'ok_group' )
		{
			$params['gid'] = $this->profile['code']; // Идентификатор группы, в которой необходимо опубликовать медиатопик.
			$params['type'] = 'GROUP_THEME';
		}	
		else
		{			
			$params['type'] = 'USER';
		}									
		$result = $this->send_request( $params, 'mediatopic.post' );		
		return $result;
	}	
	
	public function get_group_info( $args ) 
	{
		if ( empty($args['uids']) )
			return false;
		
		$params['uids'] = $args['uids'];			
		if ( empty($args['fields']) )
			$params['fields'] = 'NAME,MAIN_PHOTO,ABBREVIATION,MAIN_PAGE_TAB';				
		$result = $this->send_request( $params, 'group.getInfo' );			
		return $result;
	}	
	
	public function get_group_members( $args ) 
	{
		if ( empty($args['uid']) )
			return false;
		
		$params['uid'] = $args['uid'];					
		$result = $this->send_request( $params, 'group.getMembers' );			
		return $result;
	}		
		
	public function get_errors( )
	{
		return $this->errors;
	}
	
	private function set_error( $error )
	{	
		$this->error = $error;
		if ( is_string($error) )
			$this->errors[]  =  sprintf( __('Ошибки Одноклассниках. Ошибка: %s'), $error);
		else
			$this->errors[]  =  sprintf( __('Ошибки Одноклассниках. Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $error['request_params'][1]['value'], $error['error_code'], $error['error_msg']);
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
		$this->errors = array();
	}
}
?>