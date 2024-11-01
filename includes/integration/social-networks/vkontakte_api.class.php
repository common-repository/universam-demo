<?php
class USAM_VKontakte_API
{	
	private $errors  = [];
	private $error;
	private $API_URL = 'https://api.vk.com/method/';
	private $version = '5.131';
	private $options;
	private $service_token = null;
	private $profile = [];
	private $query_result = [];	
	
	public function __construct( $profile_id )
	{
		if ( is_array($profile_id) )
			$this->profile = $profile_id;
		else
			$this->profile = usam_get_social_network_profile( $profile_id );
		
		if ( empty($this->profile) )
			return false;
		
		$this->profile['owner_id'] = $this->profile['type_social'] == 'vk_group'?'-'.$this->profile['code']:$this->profile['code'];
		
		$this->options = get_option('usam_vk_autopost', []);
		$this->options['type_price'] = !empty($this->options['type_price'])?$this->options['type_price']:usam_get_customer_price_code();		
		$api = get_option('usam_vk_api');		
		$this->service_token = !empty($api['service_token'])?$api['service_token']:$this->service_token;
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
			$this->errors[] = sprintf( __('Ошибки вКонтакте. Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $error['request_params'][0]['value'], $error['error_code'], $error['error_msg']);		
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
	}		
	
	function send_request( $params, $function )
	{
		static $number_requests = 0;		
		if ( usam_is_license_type('FREE') )
			return true;			
		if ( empty($this->profile) )
			return false;
		if ( $number_requests % 3 == 0 )
			sleep(1);
		$params['access_token'] = isset($params['access_token']) ? $params['access_token'] : $this->profile['access_token'];
		$params['v'] = $this->version;	
		$params['lang'] = 'ru';			
		$functions_service_token = $this->get_functions_service_token();
		if ( in_array($function, $functions_service_token) )
		{
			if ( $this->service_token == null )
			{
				$this->set_error( __('Укажите сервисный ключ доступа в настройках.','usam') );	
				$this->set_log_file();
				return false;		
			}
			$params['access_token'] = $this->service_token;	
		}
		$data = wp_remote_post($this->API_URL.$function, ['body' => $params, 'sslverify' => true, 'timeout' => 5]);
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			$this->set_log_file();
			return false;
		}		
		$this->query_result = json_decode($data['body'],true);		
		if ( isset($this->query_result['error'] ) ) 
		{		
			$this->set_error( $this->query_result['error'] );	
			$this->set_log_file();
			return false;
		}			
		$number_requests++;
		return $this->query_result['response'];		
	}
	
	function get_functions_service_token( )
	{	
		return [
			'newsfeed.search',
			'wall.search',
			'users.get',		
			'photos.search',			
		//	'wall.post',			
			'wall.getComments',
			'friends.get',
			'groups.getMembers',
			'likes.getList',
			'groups.getById',	
			'photos.getAlbums',
	//		'messages.getConversations',	
			'wall.get',			
	//		'market.getOrders',
		];	
	}
	
	public function get_user( $id, $fields = ['sex','photo_400_orig','domain','bdate','country', 'city','connections','contacts'])
	{				
		$params = [
			'user_ids' => $id,
			'fields' => implode(',',$fields),
			'name_case' => 'nom',
		]; 			
		$users = $this->send_request( $params, 'users.get' );
		$new_contact = [];
		if( $users )
		{
			$new_contact['last_name'] = $users[0]['last_name'];
			$new_contact['first_name'] = $users[0]['first_name'];	
			if ( !empty($users[0]['photo_400_orig']) )
				$new_contact['foto'] = $users[0]['photo_400_orig'];		
			elseif ( !empty($users[0]['photo_50']) )
				$new_contact['foto'] = $users[0]['photo_50'];	
			if( !empty($users[0]['bdate']) && array_count_values(explode('.' , $users[0]['bdate'])) == 2 )
				$new_contact['birthday'] = date('Y-m-d H:i:s', strtotime($users[0]['bdate']));
			if ( isset($fields['city']) && !empty($users[0]['city']['title']) )
			{
				$location = usam_get_locations(['search' => $users[0]['city']['title'], 'fields' => 'id', 'number' => 1]);
				$new_contact['location'] = $location;		
			}
			if ( !empty($users[0]['contacts']['mobile_phone']) )
				$new_contact['mobilephone'] = $users[0]['contacts']['mobile_phone'];		
			if ( !empty($users[0]['contacts']['home_phone']) )
				$new_contact['phone'] = $users[0]['contacts']['home_phone'];		
			if ( !empty($users[0]['domain']) )
				$new_contact['vk'] = $users[0]['domain'];
		}
		return $new_contact;		
	}
	
	// Добавить товары в контакт
	public function product_day( $product_id )
	{								
		$args = array( 'pin' => $this->options['fix_product_day'], 'message_format' => $this->options['product_day_message'] );	
		return $this->publish_post( $product_id, $args);
	}
			
	function get_board_getComments( $args )
	{
		if ( empty($args['topic_id']) )
			return false;
		
		$params = array(		
			'topic_id'     => $args['topic_id'], 
		);	
		$params['group_id'] = $this->profile['code'];
		$results = $this->send_request( $params, 'board.getComments' );	
		return $results;
	}
	
	function delete_wall( $post_id )
	{		
		$params = array(			
			'post_id'      => $post_id, 
		);		
		$params['owner_id'] = $this->profile['owner_id'];
		$results = $this->send_request( $params, 'wall.delete' );	
		return $results;
	}

	//	$result = $vkontakte->delete_wall_posts( $profile, array( 'limit' => 850 ) );	
	function delete_wall_posts( $args )
	{		
		$limit = isset($args['limit'])?$args['limit']:0;
		$i = 0;
		if ( $limit )
			$count = $limit>100?100:$limit;
		else
			$count = 100;
		do
		{					
			$i += $count;				
			$items = $this->get_wall( array( 'offset' => 0, 'count' => $count ) );	
			if ( empty($items) )
				break;
			
			unset($items[0]);	
			$j = 0;
			foreach ( $items as $item )
			{						
				$result = $this->delete_wall( $item['id'] );			
				$j++;
				if ( $j == 4 )
				{
					$j = 0;
					usleep(1000000);
				}
			}	
			$stop = false;
			if ( $limit )
			{ //Если удаление по количеству			
				if ( $limit > $i )			
				{		
					$stop = true;	
				}
				$count = $limit>($i+100)?100:$limit-$i;				
			}	
		}
		while ( $stop );			
		return $i;
	}
	
	function get_wall( $args = array() )
	{	
		$params = array(		
			'offset' => isset($args['offset'])?$args['offset']:0,
			'count' => isset($args['count'])?$args['count']:20,
		);				
		$params['owner_id'] = $this->profile['owner_id'];	
		if ( !empty($args['fields']) )
		{
			$params['fields'] = 'sex, city, country, has_mobile, online, bdate, photo_50, can_see_all_posts, can_post';	
			$params['extended'] = 1;
		}			
		$results = $this->send_request( $params, 'wall.get' );	
		return $results;
	}	
	
/*
	Возвращает список пользователей, которые были приглашены в группу.	
	fields   - список дополнительных полей, которые необходимо вернуть. Cписок слов, разделенных через запятую. Доступные значения: 
	sex, bdate, city, country, photo_50, photo_100, photo_200_orig, photo_200, photo_400_orig, photo_max, photo_max_orig, online, online_mobile, lists, domain, has_mobile, contacts, connections, site, education, universities, schools, can_post, can_see_all_posts, can_see_audio, can_write_private_message, status, last_seen, common_count, relation, relatives, counters
	
			bdate дата рождения пользователя в формате DD.MM.YYYY, например "15.11.1984". 
	
	name_case - падеж для склонения имени и фамилии пользователя. Возможные значения: именительный – nom, родительный – gen, дательный – dat, винительный – acc, творительный – ins, предложный – abl. По умолчанию nom. 
	*/
	public function publish_birthday( $selected_users )
	{		
		$publish_h = 10; // Час публикации 		
		preg_match('/\{(.+?)\}/s', $this->options['birthday'], $str );
		if ( empty($str[1]) )
			return false;
						
		$user_message = '';		
		foreach ( $selected_users as $user ) 
		{						
			$args = array();
			foreach ( $user as $key => $value ) 
			{							
				$text = $value;											
				if ( $key == 'city')
				{						
					$result = $this->send_request( array('city_ids' => $value), 'database.getCitiesById' );
					if ( !empty($result[0]['name']) )
						$text = $result[0]['name'];		
					else
						$text = 'не указано';								
				}					
				$args[$key] = $text;
			}				
			if ( is_numeric($user['id']) )
				$id = 'id'.$user['id'];
			else
				$id = $user['id'];
					
			$message_publication = str_replace( '%user_link%', '['.$id.'|'.$user['first_name'].' '.$user['last_name'].']', $str[1] );
			$user_message .= $this->get_message_publication( $message_publication, $args ).chr(10);
		}
		$message = str_replace($str[0], $user_message, $this->options['birthday'] );	
		$params = array( 'message' => $this->message_decode( $message ) );
		
		if ( $publish_h < date('H') )					
			$params['publish_date'] = mktime($publish_h, 0, 0,  date('m'), $current_day, date('y'));			
	
		$result = $this->wall_post( $params );
		return $result;
	}

	// Публикация записи на стене
	public function wall_post( $args ) 
	{
		if ( empty($args['message']) && empty($args['attachments']) )
			return false;	
		
		$params = array(		
			'from_group' => $this->profile['from_group'], //1 — будет опубликована от имени группы, 0 — будет опубликована от имени пользователя
			'signed' => $this->options['from_signed'], // у записи, размещенной от имени сообщества, будет добавлена подпись (имя пользователя, разместившего запись)
			'message' => !empty($args['message'])?$args['message']:'', 
		); 		
		$params['owner_id'] = $this->profile['owner_id'];
		if (!empty($args['attachments']))
			$params['attachments'] = implode(',',$args['attachments']);	

		if ( isset($args['guid']) )
			$params['guid'] = $args['guid']; // уникальный идентификатор, предназначенный для предотвращения повторной отправки одинаковой записи. 
		
		if ( isset($args['place_id']) )
			$params['place_id'] = $args['place_id']; // идентификатор места, в котором отмечен пользователь
		
		if ( isset($args['lat']) )
			$params['lat'] = $args['lat']; //географическая широта отметки, заданная в градусах
		
		if ( isset($args['long']) ) 
			$params['long'] = $args['long']; //географическая долгота отметки, заданная в градусах
		
		if ( isset($args['mark_as_ads']) )
			$params['mark_as_ads'] = $args['mark_as_ads']; //1 — у записи, размещенной от имени сообщества, будет добавлена метка "это реклама"		
		
		if ( isset($args['publish_date']) )					
			$params['publish_date'] = $args['publish_date'];
		
		if ( isset($args['services']) )					
			$params['services'] = $args['services'];				
		
		$result = $this->send_request( $params, 'wall.post' );	
		if ( isset($result['post_id']) && !empty($args['pin']))
		{		
			$this->wall_pin( $result['post_id']);
		}		
		return $result;
	}	
	
	// Закрепляет запись на стене (запись будет отображаться выше остальных).
	public function wall_pin( $post_id ) 
	{
		$params = array( 		
			'post_id' => $post_id, 				
		); 
		$params['owner_id'] = $this->profile['owner_id'];
		return $this->send_request( $params, 'wall.pin' );	
	}
	
	public function add_like( $post_id, $type = 'post' ) 
	{
		$params = array( 		
			'item_id' => $post_id, 				
			'type'    => $type, 		
		); 
		$params['owner_id'] = $this->profile['owner_id'];		
		return $this->send_request( $params, 'likes.add' );	
	}
		
	// Публикует новую запись на стене.
	public function publish_post( $post, $args = array() ) 
	{		
		$default = array( 'add_link' => $this->options['add_link'], 'market' => 0, 'message_format' => '', 'campaign' => '' );		
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
			
		if ( $args['add_link'] )
			$attachments[] = usam_get_url_utm_tags( $args['campaign'], usam_product_url( $post->ID ) );
		
		if ( $args['market'] && $post->post_type == "usam-product" )
		{
			$market_id = usam_get_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'] );	
			if ( $market_id )
			{
				$owner_id = $this->profile['owner_id'];;	
				$attachments[] = "market{$owner_id}_{$market_id}";				
			}
		}	
		$params = $args;
		$params['attachments'] = $attachments;
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
	
	public function delete_product( $product_id ) 
	{			
		$market_id = usam_get_product_meta( $product_id, 'vk_market_id_'.$this->profile['code'] );			
		if ( $market_id )
		{							
			usam_delete_product_meta( $product_id, 'vk_market_publish_date_'.$this->profile['code']);
			usam_delete_product_meta( $product_id, 'vk_market_id_'.$this->profile['code'] );			
			$result = $this->send_request(['item_id' => $market_id, 'owner_id' => $this->profile['owner_id']], 'market.delete' );
			return $result;
		}
		return false;
	}
	
	// Обновляет товар
	//thumb_photo
	public function edit_product( $post ) 
	{			
		if ( is_numeric($post) )
			$post = get_post($post);
		
		$market_id = usam_get_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'] );
		if ( empty($market_id) )
			return false;
		
		$description = $this->make_excerpt( $post );
		$description .= chr(10).usam_product_url( $post->ID );		
		$params = [
			'item_id' => $market_id,		
			'name' => $post->post_title,
			'description' => $description,
			'price' => usam_get_product_price( $post->ID, $this->options['type_price'] ),	
			'url' => get_permalink( $post->ID ),
			'sku' => usam_get_product_meta( $post->ID, 'sku' ),			
		]; 	
		$params['owner_id'] = $this->profile['owner_id'];		
		$old_price = usam_get_product_old_price( $post->ID, $this->options['type_price'] );
		if ( $old_price )
			$params['old_price'] = $old_price;
		$result = $this->send_request( $params, 'market.edit' );
	
		if ( !$result )
		{			
			if ( isset($this->query_result['error']) && $this->query_result['error']['error_code'] == 1403 )
			{			
				usam_update_product_meta( $post->ID, 'vk_market_publish_date_'.$this->profile['code'], '' );
				usam_update_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'], 0 );
			}
		}
		return $result;
	}
	
	public function edit_photos_product( $post ) 
	{
		if ( is_numeric($post) )
			$post = get_post($post);
		
		$market_id = usam_get_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'] );
		if ( empty($market_id) )
			return false;
				
		$result = $this->send_request(['owner_id' => $this->profile['code'], 'item_ids' => $market_id, 'extended' => 1], 'market.getById' );		
		if( empty($result['items']) )
		{
			if ( isset($this->query_result['error']) && $this->query_result['error']['error_code'] == 1403 )
			{			
				usam_update_product_meta( $post->ID, 'vk_market_publish_date_'.$this->profile['code'], '' );
				usam_update_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'], 0 );
			}
			return false;		
		}
		foreach ( $result['items'] as $item )
			$this->edit_photos_vk_product( $post, $item );		
		return true;
	}
	
	private function edit_photos_vk_product( $post, $item ) 
	{					
		foreach( $item['photos'] as $photo )		
			$this->delete_photo( $photo['id'] );
		$images = $this->get_post_photo( $post, 4 ); 		
		if ( !empty($images) )
			$photo_ids = $this->upload_product_photos($images, ['group_id' => $this->profile['code']] );
		else
			return false;
		
		$main_photo = array_shift($photo_ids);		
		$params = [	
			'main_photo_id' => $main_photo,	
			'owner_id' => $this->profile['owner_id']
		]; 	
		if ( !empty($photo_ids) )
			$params['photo_ids'] = implode(',',$photo_ids);
		$result = $this->send_request( $params, 'market.edit' );
		return $result;
	}
	
	/*
	
owner_id - идентификатор владельца товара. 
name - название товара. Ограничение по длине считается в кодировке cp1251. cтрока, минимальная длина 4, максимальная длина 100, обязательный параметр
description - описание товара. строка, минимальная длина 10, обязательный параметр
category_id - идентификатор категории товара. положительное число, обязательный параметр
price - цена товара. дробное число, обязательный параметр, минимальное значение 0.01
deleted - статус товара (1 — товар удален, 0 — товар не удален). флаг, может принимать значения 1 или 0
main_photo_id идентификатор фотографии обложки товара. 

Фотография должна быть загружена с помощью метода photos.getMarketUploadServer, передав параметр main_photo. См. подробную информацию о загрузке фотографии товаров 
положительное число, обязательный параметр
photo_ids - идентификаторы дополнительных фотографий товара. 

Фотография должна быть загружена с помощью метода photos.getMarketUploadServer. См. подробную информацию о загрузке фотографии товаров 
список положительных чисел, разделенных запятыми, количество элементов должно составлять не более 4	
*/
	
	// Публикует новую запись на своей или чужой стене.
	public function publish_product( $post, $category_id, $album_id = 0 ) 
	{					
		if ( is_numeric($post) )
			$post = get_post($post);
	 
		$market_id = usam_get_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'] );		
		if ( $market_id ) 
		{
			$result = $this->edit_product( $post );	
			if ( $result !== false )
				return;	
		}			
		$images = $this->get_post_photo( $post, 4 ); 		
		if ( !empty($images) )
			$photo_ids = $this->upload_product_photos($images, ['group_id' => $this->profile['code']] );
		else
			return false;
		$main_photo = array_shift($photo_ids);
		
		$description = $this->make_excerpt( $post );
		$description .= chr(10).usam_product_url( $post->ID );				
		$params = [			
			'name' => $post->post_title,
			'description' => $description,
			'category_id' => $category_id,
			'price' => usam_get_product_price( $post->ID, $this->options['type_price'] ),
			'main_photo_id' => $main_photo,		
			'url' => get_permalink( $post->ID ),
			'sku' => usam_get_product_meta( $post->ID, 'sku' ),
			'owner_id' => $this->profile['owner_id']
		]; 		
		$old_price = usam_get_product_old_price( $post->ID, $this->options['type_price'] );
		if ( $old_price )
			$params['old_price'] = $old_price;
		if ( !empty($photo_ids) )
			$params['photo_ids'] = implode(',',$photo_ids);
		$resp = $this->send_request( $params, 'market.add' );
		if ( isset($resp['market_item_id']) )
		{				
			usam_update_product_meta( $post->ID, 'vk_market_publish_date_'.$this->profile['code'], date("Y-m-d H:i:s") );
			usam_update_product_meta( $post->ID, 'vk_market_id_'.$this->profile['code'], $resp['market_item_id'] );
			
			if ( $album_id )
				$this->product_add_to_album( $post->ID, $album_id );
			return true;
		}
		else	
			return false;
	}
	
	//Добавляет товар в одну или несколько выбранных подборок.
	public function product_add_to_album( $product_id, $album_ids  )
	{
		$market_id = usam_get_product_meta( $product_id, 'vk_market_id_'.$this->profile['code'] );				
		$album_ids = is_array($album_ids)?implode(',',$album_ids):(int)$album_ids;
		
		$params = array('item_id' => $market_id, 'album_ids' => $album_ids ); 
		$params['owner_id'] = $this->profile['owner_id'];
		return $this->send_request( $params, 'market.addToAlbum' );			
	}		
	
	public function publish_product_review( $reviews_id ) 
	{				
		$review = usam_get_review( $reviews_id );
		$market_id = usam_get_product_meta( $review['code'], 'vk_market_id_'.$this->profile['code'] );	
		if ( !$market_id )
			return false;
		
		$post = get_post( $review['code'] );
		
		$args = $this->get_review_args( $review );
		$post_args = $this->get_post_args( $post );
		$args = array_merge ($post_args, $args);	
		
		$message = $this->get_message_publication( $this->options['product_review_message'], $args );			
		
		$images = $this->get_post_photo( $post, 1 ); 
		$photos = $this->upload_photo($images, $message);			
		$params = array( 			
			'item_id'      => $market_id,  			
			'message'      => $message, 
			'from_group'   => $this->profile['from_group'], 		
			'guid'         => $review['id'],
		); 		
		$params['owner_id'] = $this->profile['owner_id'];		
		if (!empty($photos))
			$params['attachments'] = implode(',', $photos);		
		return $this->send_request( $params, 'market.createComment' );	
	}
	
	private function get_review_args( $review ) 
	{
		static $properties = null;
		$args = array();
			
		if ( $properties === null )
			$properties = usam_get_properties(['type' => 'webform', 'fields' => 'code=>data']);
						
		$customer_name = '';
		if ( isset($properties['name']) ) 
			$customer_name = usam_get_review_metadata( $review->id, 'name' );	
		if ( isset($properties['firstname']) && !$customer_name ) 
			$customer_name = usam_get_review_metadata( $review->id, 'firstname' );		
		
		$args['review_title'] = $review['title'];			
		$args['review_author'] = $customer_name?__('Автор','usam').': '.$customer_name:'';
		$args['review_excerpt'] = $review['review_text'];
		$args['review_response'] = $review['review_response'];
		
		$r = '';
		for ( $l = 1; $l <= $review['rating']; ++$l )
			$r .= '&#11088;';
		$args['review_rating'] = $r;
		
		return $args;
	}
	
	// Публикует отзыв на своей или чужой стене.
	function publish_customer_review( $reviews_id ) 
	{				
		$review = usam_get_review( $reviews_id );
		$post = get_post( $review['code'] );			
		
		$args = $this->get_review_args( $review );
		
		if ( $post->post_type == 'usam-product')
			$header_text = __('Отзыв о товаре','usam');			
		else
			$header_text = __('Отзыв покупателя','usam');	

		$args['header'] = $header = '--------------------------------------- '.mb_strtoupper($header_text).' ---------------------------------------'.chr(10);		
		
		$message = $this->get_message_publication( $this->options['reviews_message'], $args );		
		$permalink = $this->options['add_link'] ? usam_product_url( $post->ID ) : ''; 
		
		$image_path = USAM_CORE_IMAGES_PATH."/review.jpg";
		$review_image[ 'file1' ] = new CURLFile( $image_path );
	
		$attach = $this->upload_photo( $review_image); 		
		$images = $this->get_post_photo( $post, 1 ); 
		$photos = $this->upload_photo($images, $message);
		$attach += $photos;
	
		if (!empty($permalink))
			$attach[] = $permalink;		
			
		$params = array(
			'message' => $message,   
			'attachments' => $attach,		
		); 		
		return $this->wall_post( $params );	
	}
	
// Получить миниатюру для загрузки в контакт	
	private function get_post_thumbnail_photo( $post )
	{						
		$thumbnail_id = get_post_thumbnail_id( $post->ID );	
		$result = array();
		if ( $thumbnail_id  )
		{
			$path = get_attached_file( $thumbnail_id );				
			$i = 0;				
			$result[ 'file' . $i ] = new CURLFile( $path );
		}
		return $result;	
	}

// Выбрать фотографии для загрузки в контакт
	private function get_post_photo($post, $number )
	{					
		if ( $number > 5 )
			$number = 5;		
		$post_images = get_children(['post_parent' => $post->ID, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order id', 'order' => 'ASC', 'numberposts' => $number]); 
			
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
			$result['file' . $i] = new CURLFile( $path );		
			$i++;
		} 
		return $result;	
	}	
	
	private function upload_server( $images, $params, $function )
	{	
		$result = $this->send_request( $params, $function );	
		if ( empty($result['upload_url']) )
			return false;	
// Загрузить фото		
		$curl = new Wp_Http_Curl();
		$data = $curl->request( $result['upload_url'], ['body' => $images, 'timeout' => 45, 'method' => 'POST', 'user-agent' => 'Universam/'.USAM_VERSION.'; '.home_url(), 'stream' => false, 'decompress' => false, 'filename' => null]);		
		if ( is_wp_error($data) )
		{
			$this->errors[] = $data->get_error_message();
			return false;	
		}				
		$result = json_decode($data['body'],true);		
		return $result;
	}
	
	// Загрузить фото для товара в контакт
	function upload_product_photos( $images, $args )
	{			
//Возвращает адрес сервера для загрузки фотографии на стену пользователя или сообщества.
		$result = $this->upload_server( $images, $args, 'photos.getMarketUploadServer' );
		if ( empty($result['photo']) )
			return false;				
// Сохраняет фотографии после успешной загрузки на URI, полученный методом
		$params = ['server' => $result['server'], 'photo' => $result['photo'], 'hash' => $result['hash']]; 	
		$params['group_id'] = $this->profile['code'];			
		if ( !empty($result['crop_data']) )
			$params['crop_data'] = $result['crop_data'];	
		
		if ( !empty($result['crop_hash']) )
			$params['crop_hash'] = $result['crop_hash'];
		
		$result = $this->send_request( $params, 'photos.saveMarketPhoto' );	
		if ( !$result )
			return false; 	
		
		foreach($result as $r)				
			$attachments[] = $r['id'];			
		return $attachments;
	}
	
	// Загрузить фото в контакт
	public function upload_photo($images, $message = '' )
	{			
		$params = array(  );
		if ( $this->profile['type_social'] == 'vk_group' )
			$params['group_id'] = $this->profile['code'];
		else
			$params['user_id'] = $this->profile['code'];
		$result = $this->upload_server( $images, $params, 'photos.getWallUploadServer' );	
		if ( empty($result['photo']) )
			return false;	

		$params = array(
			'server' => $result['server'],
			'photo' => $result['photo'],
			'hash' => $result['hash'],
			'caption' => $message,			
		); 
		if ( $this->profile['type_social'] == 'vk_group' )
			$params['group_id'] = $this->profile['code'];
		else
			$params['user_id'] = $this->profile['code'];
	
		$result = $this->send_request( $params, 'photos.saveWallPhoto' );		
		if ( !$result )
			return false;
		
		$attachments = array();
		foreach($result as $r)
		{					
			$attachments[] = "photo".$r['owner_id']."_".$r['id'];				
		}		
		return $attachments;
	}
	
//Изменяет описание у выбранной фотографии.	
	function photo_edit( $id, $message )
	{						
		$params = ['photo_id' => $id, 'caption' => $message]; 
		$params['owner_id'] = $this->profile['owner_id'];
		$result = $this->send_request( $params, 'photos.edit' );
		return $result;
	}
	
	private function get_post_args( $post ) 
	{  		
		$args = ['title' => get_the_title($post->ID), 'excerpt' => $this->make_excerpt($post), 'link' => usam_product_url( $post->ID )];
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
	
//Загрузка фотографий в альбом пользователя	
	public function upload_photo_album( $post, $album_id )
	{		
		if( is_numeric($post) )
			$post = get_post( $post );	
		
		$message = $this->display_post_message( $post );		
		$images = $this->get_post_photo( $post, $this->options['upload_photo_count'] );
		
		$params = ['album_id' => (int)$album_id];	
// Страница пользователя или группа
		if ( $this->profile['type_social'] == 'vk_group' )
			$params['group_id'] = $this->profile['code'];	

//Возвращает адрес сервера для загрузки фотографии на стену пользователя или сообщества.
		$result = $this->upload_server( $images, $params, 'photos.getUploadServer' );	

		if ( empty($result['photos_list']) )
			return false;	
			
// Сохраняет фотографии после успешной загрузки. 
		$params = ['album_id' => $album_id,	'photos_list' => $result['photos_list'], 'server' => $result['server'],	'hash' => $result['hash'], 'caption' => $message]; 
		if ( $this->profile['type_social'] == 'vk_group' )
			$params['group_id'] = $this->profile['code'];
		else
			$params['user_id'] = $this->profile['code'];
						
		$result = $this->send_request( $params, 'photos.save' );		
		if (!$result)
			return false; 
		return $result;
	}	
			
	function get_conversations( $params )
	{	
		$params['filter'] = !empty($params['filter'])?$params['filter']:'unread';
		$params['extended'] = !empty($params['extended'])?1:0;			
		$results = $this->send_request( $params, 'messages.getConversations' );	
		return $results;		
	}
	
	function users_search( $search )
	{			
		$search_default = array(  'q' => '',  'sort' => '1', 'online' => 1, 'country' => 1 , 'count' => 1, 'offset' => 0, 'age_from' => 25 );
		$params = array_merge ($search_default, $search);
//Возвращает список пользователей в соответствии с заданным критерием поиска.

		$result = $this->send_request( $params, 'users.search' );	
		if ( $result )
		{
			foreach( (array)$result as $user)
			{
				if ( isset($user['id']) )
				{
					$params = array(
						'user_id' => $user['code'],
						'text' => ''			
					);	
					$friends_result = $this->send_request( $params, 'friends.add' );	
					if ( $this->error['error_code'] == 14)
					{
						$this->errors[] = 'Ошибка: '.$this->error['error_code'].' - требуется капча. Анкета - '.$search['code'];
						$captcha = array(
							'sid' => $this->error['captcha_sid'],
							'img' => $this->error['captcha_img']
						);
						$autopost_error = get_option('usam_vk_autopost_error');
						$autopost_error['captcha'] = $captcha;
						$autopost_error['method'] = 'friends.add';
						if (!isset($autopost_error['error_email']))
						{	
							$autopost_error['error_email'] = current_time('timestamp', 1);
						}
						update_option('usam_vk_autopost_error', $captcha); 						
						return $this->error['error_code'];
					}					
					if (is_wp_error($data))
						return $data->get_error_message();
				}		
			}			
			return true;
		}
		return false;
	}	
	
	// Публикация конкурсов
	function publish_contest( $args ) 
	{			
		$ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';
		$date = strtotime($ve . get_option( 'gmt_offset' ).' HOURS', strtotime($args['start_date']));	
			
		$params = array(
			'message' => $args['message'],   
			'publish_date' => $date,
		//	'attachments' => $attach,
			'pin' => $args['pin'],
		); 					
		$result = $this->wall_post( $params );	
		return $result['post_id'];
	}
	
	/*Найти победителя в конкурсе*/		
	function find_winner_the_contest( $args ) 
	{					
		$post_id = 22921;		
		$offset = 0;
	
		$params = array(			
			'post_id' => $post_id,
			'offset' => $offset,
			'count' => 1000,
		); 
		$params['owner_id'] = $this->profile['owner_id'];		
		$resp = $this->send_request( $params, 'wall.getReposts' );		
	
		if ( isset($resp['profiles']) )
		{				
			if ( !empty($args['in_group']) )
			{				
				$members = $this->get_group_members(  );				
				if ( empty($members) )
					return false;
				
				$users = array();
				foreach( $resp['profiles'] as $user )
				{	
					foreach( $members as $member )
					{
						if ( $user['user_id'] == $members['id']) 
							$users[] = $user;
					}
				}		
				$resp['profiles'] = $users;
				unset($users);	
			}
			$array = array();
			$some_man = count($resp['profiles']) - 1;
			for ( $i = 1; $i <= $args['winner_count']; ++$i )
			{				
				$index = rand(0, $some_man); 
				if ( in_array($index , $array) )
					$i--;
				else
					$array[] = $index;
			}
			$winners = array();
			foreach($array as $index) 
			{
				$winners = $resp['profiles'][$index];
			}		
			return $winners;
			
		}
		else	
			return false;
	}
	
	/* Получить участников группы */
	public function get_group_members( $params = array() )
	{ 
		$users = array();			
		$params['offset'] = isset($params['offset'])?(int)$params['offset']:0;
		$params['group_id'] = $this->profile['code'];
		$offset = 1000;
		do
		{			
			$result = $this->send_request( $params, 'groups.getMembers' );			
			$params['offset'] += $offset;
			if ( !$result )
				break;
			$users = array_merge($users, $result['items'] );					
		}
		while ( count($result['items']) == $offset );			
		return $users;
	}
		
	public function send_message( $args ) 
	{
		$group_access_token = usam_get_social_network_profile_metadata( $this->profile['id'], 'group_access_token' );		
		$params = array(
			'access_token' => $group_access_token, 		
			'message' => $args['message'], 
			'random_id' => $args['id'], 	 		
		//	'attachments' => $attach,
		); 		
		if ( !empty($args['contact_id']) )
			$params['user_id'] = usam_get_contact_metadata( $args['contact_id'], 'vk_id' );
		else		
			$params['user_id'] = "-".$this->profile['code'];		

		if ( !empty($args['peer_id']) )
			$params['peer_id'] = $args['peer_id'];			
		return $this->send_request( $params, 'messages.send' );
	}
		
	public function get_market_categories( $params ) 
	{		
		$params['owner_id'] = $this->profile['code'];
		$result = $this->send_request( $params, 'market.getCategories' );		
		return $result;
	}
	
	public function get_market_albums( $params = [] ) 
	{
		$params['owner_id'] = $this->profile['owner_id'];		
		$result = $this->send_request( $params, 'market.getAlbums' );		
		return $result;
	}
		
	//Возвращает заказы.
	public function get_orders( $params ) 
	{
		$result = $this->send_request( $params, 'market.getOrders' );		
		return $result;
	}
	
	//Возвращает заказы сообщества.
	public function get_group_orders( $params ) 
	{
		$result = $this->send_request( $params, 'market.getGroupOrders' );		
		return $result;
	}
	
	//Возвращает товары в заказе.
	public function get_orders_products( $params ) 
	{
		$result = $this->send_request( $params, 'market.getOrderItems' );		
		return $result;
	}
	
	public function get_albums( $params = array() ) 
	{
		$params['owner_id'] = $this->profile['owner_id'];
		$result = $this->send_request( $params, 'photos.getAlbums' );		
		return $result;
	}
	
	public function search_photos( $params ) 
	{
		$result = $this->send_request( $params, 'photos.search' );		
		return $result;
	}
	
	public function delete_photo( $photo_id ) 
	{
		$result = $this->send_request( ['photo_id' => $photo_id, 'owner_id' => $this->profile['owner_id']], 'photos.delete' );		
		return $result;
	}
		
	public function wall_search( $params ) 
	{
		$params['owner_id'] = $this->profile['owner_id'];
		$result = $this->send_request( $params, 'wall.search' );		
		return $result;
	}	
	
	public function get_group( $group_id, $fields = array() ) 
	{
		$params = array( 'group_id' => $group_id, 'fields' => implode( ',', $fields) );
		$result = $this->send_request( $params, 'groups.getById' );		
		if ( $result )
			$result = $result[0];
		return $result;
	}	
		
	public function get_likes( $params ) 
	{
		if ( !isset($params['owner_id']) )
			$params['owner_id'] = $this->profile['owner_id'];
		$result = $this->send_request( $params, 'likes.getList' );	
		return $result;
	}	
	
	public function get_wall_comments( $params ) 
	{
		if ( !isset($params['owner_id']) )
			$params['owner_id'] = $this->profile['owner_id'];
		$result = $this->send_request( $params, 'wall.getComments' );	
		return $result;
	}	
}
?>