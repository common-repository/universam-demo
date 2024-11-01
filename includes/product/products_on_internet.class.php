<?php
class USAM_Product_Internet
{	 // строковые
	private static $string_cols = array(
		'source',		
		'author',		
		'content_type',		
		'foto_url',	
		'description',	
		'date_insert',	
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'product_id',
		'content_id',		
		'likes',	
		'comments',	
		'sort',
		'status',
	);	
	private $data     = array();		
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
	private $exists   = false; // если существует строка в БД
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', ) ) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_product_internet' );
		}		
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';		
	
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_product_internet' );		
		do_action( 'usam_product_internet_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache(  ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_product_internet' );				
		do_action( 'usam_product_internet_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_product_internet_before_delete', $data );
		
		$this->delete_cache( );					
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCTS_ON_INTERNET." WHERE id = '$id'");
		
		do_action( 'usam_product_internet_delete', $id );
	}		
	
	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{
		global $wpdb;
		if ( $this->fetched )
			return;

		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PRODUCTS_ON_INTERNET." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_product_internet_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache( );		
		}			
		do_action( 'usam_product_internet_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 * @since 4.9
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 * @since 4.9
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_product_internet_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_product_internet_get_data', $this->data, $this );
	}

	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 * @since 4.9
	 */
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );			
		}			
		if ( ! is_array($this->data) )
			$this->data = array();	
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_product_internet_set_properties', $this->data, $this );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 * @since 4.9
	 */
	private function get_data_format( ) 
	{
		$formats = array();
		foreach ( $this->data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;	
			else
				unset($this->data[$key]);
		}
		return $formats;
	}		
	
	private function data_format( ) 
	{
		foreach ( $this->data as $key => $value ) 
		{			
			if ( in_array( $key, self::$string_cols ) && !is_array($value) )
				$this->data[$key] = stripcslashes($value);
		}
	}
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_product_internet_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_product_internet_pre_update', $this );				
			$where = array( $where_col => $where_val );			

			$this->data = apply_filters( 'usam_product_internet_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_PRODUCTS_ON_INTERNET, $this->data, $where, $format, $where_format );			
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_product_internet_update', $this );
		} 
		else 
		{   
			do_action( 'usam_product_internet_pre_insert' );		
			unset( $this->data['id'] );	
						
			if ( !isset($this->data['product_id']) )
				$this->data['product_id'] = 0;
			
			if ( !isset($this->data['author']) )
				$this->data['author'] = '';
						
			if ( !isset($this->data['status']) )
				$this->data['status'] = 0;
						
			if ( !isset($this->data['content_type']) )
				$this->data['content_type'] = 'foto';
						
			if ( !isset($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );			
			
			$this->data = apply_filters( 'usam_product_internet_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
					
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_PRODUCTS_ON_INTERNET, $this->data, $format );
					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );				
			}
			do_action( 'usam_product_internet_insert', $this );
		} 		
		do_action( 'usam_product_internet_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_product_internet( $id, $data )
{
	$product_internet = new USAM_Product_Internet( $id );	
	$product_internet->set( $data );
	return $product_internet->save();
}

// Получить
function usam_get_product_internet( $id, $colum = 'id' )
{
	$product_internet = new USAM_Product_Internet( $id, $colum );
	return $product_internet->get_data( );	
}

// Добавить
function usam_insert_product_internet( $data )
{
	$product_internet = new USAM_Product_Internet( $data );
	$product_internet->save();
	return $product_internet->get('id');
}

// Удалить
function usam_delete_product_internet( $id )
{
	$product_internet = new USAM_Product_Internet( $id );
	return $product_internet->delete();
}

function usam_internet_product_search( $product_id )
{
	$product = get_post( $product_id );
	if ( $product->post_title == '' )
		return array();
	$profiles = usam_get_social_network_profiles( array( 'type_social' => array( 'vk_group', 'vk_user') ) );
	$results = array();	
	if ( !empty($profiles) )
	{
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		$vkontakte = new USAM_VKontakte_API( $profiles[0]->id );		
		$params = array( 'q' => $product->post_title, 'offset' => 0, 'count' => 1000 );		
		$offset = 1000;
		$i = 0;
		$photo_urls = array( );	
		do
		{			
			$search_photos = $vkontakte->search_photos( $params );	
			$params['offset'] += $offset;			
			if ( !empty($search_photos) )
			{
				foreach ( $search_photos['items'] as $key => $search_photo )
				{
					$add = true;
					foreach ( $profiles as $profile )
					{
						if ( $profile->type_social == 'vk_user' && $search_photo['owner_id'] == $profile->code )
						{						
							$add = false;
							break;						
						}
						elseif ( $profile->type_social == 'vk_group' && $search_photo['owner_id'] == "-".$profile->code )
						{						
							$add = false;
							break;						
						}
					}					
					if ( $add && empty($search_photo['user_id']) )
					{
						$result = array( 'description' => $search_photo['text'], 'date_insert' => date( "Y-m-d H:i:s", $search_photo['date']) );
						if ( $search_photo['owner_id'] > 0 )					
						{
							$user = $vkontakte->get_user( $search_photo['owner_id'], array('photo_50') );
							$result['author'] = $user['last_name'].' '.$user['first_name'];
						}
						else
						{
							$group = $vkontakte->get_group( $search_photo['user_id'] );
							$result['author'] = $group['name'];
						}	
						$foto_url = '';
						$min = 300;
						foreach ( $search_photo['sizes'] as $sizes )
						{
							if ( $sizes['width'] > $min || $sizes['height'] > $min )
							{
								$foto_url = $sizes['url'];
								$min = $sizes['width'];
							}
						}						
						$result['foto_url'] = $foto_url;	
						if ( in_array($foto_url, $photo_urls) )
							continue;
							
						$photo_urls[] = $foto_url;								
						if ( isset($search_photo['post_id']) )
						{							
							$result['content_type'] = 'post';
							$result['content_id'] = $search_photo['post_id'];
							$args = array( 'type' => 'post', 'owner_id' => $search_photo['owner_id'], 'item_id' => $search_photo['post_id'] );
							$comments = $vkontakte->get_wall_comments( array( 'owner_id' => $search_photo['owner_id'], 'post_id' => $search_photo['post_id'] ) );
							if ( !empty($comments['count']) )
								$result['comments'] = $comments['count'];
						}
						elseif ( !empty($likes['album_id']) && $likes['album_id'] )
						{							
							$args = array( 'type' => 'photo', 'owner_id' => $search_photo['owner_id'], 'item_id' => $search_photo['id'] );
						}					
						if ( !empty($args) )
						{
							$likes = $vkontakte->get_likes( $args );
							if ( !empty($likes['count']) )
								$result['likes'] = $likes['count'];
						}
						$result['product_id'] = $product->ID;
						$result['source'] = 'vk';
						sleep(10);
						usam_insert_product_internet( $result );
					}
				}	
			}
			else
			{
				break;		
			}
			$i++;
			sleep(10);
		}
		while ( $i >= 10 );
	}		
	return $results;
}
?>