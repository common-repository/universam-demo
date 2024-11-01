<?php
class USAM_Facebook_API
{	
	private $errors  = array();
	private $error;
	private $API_URL = 'https://graph.facebook.com/';
	private $version = '6.0';
	private $options;
	private $client_secret = null;
	private $profile = array();
	
	public function __construct( $profile_id )
	{
		if ( is_array($profile_id) )
			$this->profile = $profile_id;
		else
			$this->profile = usam_get_social_network_profile( $profile_id );
		
		if ( empty($this->profile) )
			return false;	
		
		$this->options = get_option('usam_fb_autopost', array() );		
		$api = get_option('usam_fb_api');		
		$this->client_secret = !empty($api['client_secret'])?$api['client_secret']:$this->client_secret;
	}
	
	public function get_errors( )
	{
		return $this->errors;
	}
	
	private function set_error( $error )
	{	
		$this->error = $error;
		if ( is_string($error) )
			$this->errors[]  =  sprintf( __('Ошибки вКонтакте. Ошибка: %s'), $error);
		else
			$this->errors[] = sprintf( __('Ошибки вКонтакте. Приложение вызвало ошибку №%s. Текст ошибки: %s'), $error['message'], $error['code'] );		
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
		$this->errors = array();
	}		
	
	protected function get_url( $function )
	{
		return $this->API_URL."v{$this->version}/$function";
	}
	
	function send_request( $params, $function, $method = 'POST' )
	{		return false;
		if ( empty($this->profile) )
			return false;
		
		$params['access_token'] = isset($params['access_token']) ? $params['access_token'] : $this->profile['access_token'];				
		$headers = array();
	//	$headers["Accept"] = 'application/json';
	//	$headers["Content-type"] = 'application/json';	
	//	$headers["Authorization"] = 'Bearer ' . $access_token;	
			 
		$url = $this->get_url($function);
		$data = wp_remote_post($url, array('body' => $params, 'sslverify' => true, 'timeout' => 5, 'headers' => $headers, 'method' => $method ));	
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		}		
		$resp = json_decode($data['body'],true);				
		if ( isset($resp['error'] ) ) 
		{		
			$this->set_error( $resp['error'] );	
			return false;
		}		
		return $resp['response'];		
	}	
	
	public function get_group_info( $params = array() ) 
	{						
		$result = $this->send_request( $params, $this->profile['code'], 'GET' );				
		return $result;
	}		
	
	public function wall_post( $args ) 
	{
		if ( empty($args['message']) && empty($args['link']) )
			return false;	
		
		$params = array(				
			'message' => !empty($args['message'])?$args['message']:'', 
		); 				
		if ( isset($args['link']) )					
			$params['link'] = $args['link'];				
		
		$result = $this->send_request( $params, $this->profile['code'].'/feed' );				
		return $result;
	}	
	
	public function publish_post( $post, $args = array() ) 
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
		$params['attachments'] = $attachments;
		if ( $args['add_link'] )
			$params['link'] = usam_get_url_utm_tags( $args['campaign'], usam_product_url( $post->ID ) );	
		$params['message'] = $message; 	
		$result = $this->wall_post( $params );	

		if ( isset($result['post_id']) )
		{	
			$publish_date = isset($args['publish_date'])?date("Y-m-d H:i:s", $args['publish_date']):date("Y-m-d H:i:s");
			usam_update_post_meta( $post->ID, 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'], $publish_date );
			usam_update_post_meta( $post->ID, 'post_id_'.$this->profile['type_social'].'_'.$this->profile['code'], $result['post_id'] );
		}	
		return true;
	}
	
		private function get_post_args( $post ) 
	{  		
		$args = array('title' => get_the_title($post->ID),
					  'excerpt' => $this->make_excerpt($post),
					  'link' => usam_product_url( $post->ID ),
		);
		if ( $post->post_type == 'usam-product' )
		{
			$price = usam_get_product_price( $post->ID, $this->options['type_price'] ); 
			$old_price = usam_get_product_old_price( $post->ID, $this->options['type_price'] );
			$price_currency = usam_get_formatted_price( $price, array( 'type_price' => $this->options['type_price'] ) );	
			if ($old_price > 0 )
			{
				$discont = round(100 - $price*100/$old_price, 0);				
				$price_and_discont = $price_currency." - СКИДКА: $discont%";	
				$old_price_currency	= usam_get_formatted_price( $old_price, array( 'type_price' => $this->options['type_price'] ) );		
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
	
	public function publish_product( $post, $category_id, $album_id = 0 ) 
	{					
		if ( is_numeric($post) )
			$post = get_post($post);
	 
		$market_id = usam_get_product_meta( $post->ID, 'fb_market_id_'.$this->profile['code'] );		
		if ( $market_id ) 
		{
			$result = $this->edit_product( $post );	
			if ( $result !== false )
				return;	
		}		
		$thumbnail = $this->get_post_thumbnail_photo( $post ); 		
		if ( empty($thumbnail) )
			return false;	
		
		$params = array( 'main_photo' => 1, 'group_id' => $this->profile['code'] );		
		$main_photo = $this->upload_product_photos( $thumbnail, $params );	
		
		if ( empty($main_photo) )
			return false;			
		
		$images = $this->get_post_photo( $post, 4 ); 		
		if ( empty($images) )
		{			
			$params = array( 'main_photo' => 0, 'group_id' => $this->profile['code'] );		
			$photo_ids = $this->upload_product_photos($images, $params );
		}			
		$description = $this->make_excerpt( $post );
		$description .= chr(10).usam_product_url( $post->ID );		

		$params = array(			
			'name' => $post->post_title,
			'description' => $description,
			'category_id' => $category_id,
			'price' => usam_get_product_price( $post->ID, $this->options['type_price'] ),	
			'main_photo_id' => $main_photo[0],			
		); 		
		$params['owner_id'] = $this->profile['owner_id'];
		if ( !empty($photo_ids) )
		{			
			$params['photo_ids'] = implode(',',$photo_ids);	
		} 
		$resp = $this->send_request( $params, 'market.add' );
		if ( isset($resp['market_item_id']) )
		{				
			usam_update_product_meta( $post->ID, 'fb_market_publish_date_'.$this->profile['code'], date("Y-m-d H:i:s") );
			usam_update_product_meta( $post->ID, 'fb_market_id_'.$this->profile['code'], $resp['market_item_id'] );
			
			if ( $album_id )
				$this->product_add_to_album( $post->ID, $album_id );
			return true;
		}
		else	
			return false;
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
	
	
	public function message_post( $chat_id, $text )
    {
        return $this->send_request('sendMessage', array('chat_id' => $chat_id, 'text' => $text));
    }
	
	public function send_message( $args )
    {		
        if ( !empty($args['contact_id']) )
			$args['user_id'] = usam_get_contact_metadata( $args['contact_id'], 'facebook_user_id' );
		
		if ( $args['user_id'] )
			return $this->send_request('sendMessage', array('chat_id' => $args['user_id'], 'text' => $args['message']));
		else
			return false;
    }
	
}
?>