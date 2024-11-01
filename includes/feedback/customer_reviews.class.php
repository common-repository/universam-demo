<?php
/*
 * Description: Отзывы клиентов позволяет Вашим клиентам и посетителям оставить отзывы или рекомендации ваших услуг.
 */
class USAM_Customer_Reviews
{	
	 // строковые
	private static $string_cols = [
		'date_insert',						
		'title',
		'review_text',
		'review_response',	
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'status',
		'rating',		
		'page_id',	
		'contact_id',		
	];		
	private $changed_data = array();
	private $data = array();		
	private $fetched           = false;
	private $args = array( 'col'   => '', 'value' => '' );	
	private $exists = false; // если существует строка в БД
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );			
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_review' );
		}			
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
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
		wp_cache_set( $id, $this->data, 'usam_review' );		
		do_action( 'usam_review_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_review' );	
		do_action( 'usam_review_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		$id = $this->get( 'id' );
		$page_id = $this->get( 'page_id' );
		$data = $this->get_data();
		do_action( 'usam_review_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CUSTOMER_REVIEWS." WHERE id = '$id'");
		
		if ( $page_id )
		{
			$comment = usam_get_post_meta($page_id, 'comment');
			$comment--;	
			usam_update_post_meta($page_id, 'comment', $comment);
		}
		$this->delete_cache( );		
		do_action( 'usam_review_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CUSTOMER_REVIEWS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{		
			$this->exists = true;
			$this->data = apply_filters( 'usam_review_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;	
			$this->update_cache( );	
		}		
		do_action( 'usam_review_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_review_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_review_get_data', $this->data, $this );
	}

	//Добавить данные отзыва
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
		$properties = apply_filters( 'usam_review_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = array();
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;
		}
		return $formats;
	}	
	
	private function get_update_data( ) 
	{		
		$data = [];		
		foreach( $this->changed_data as $key => $value ) 
		{							
			if( self::get_column_format( $key ) !== false )
				$data[$key] = $this->data[$key];
		}
		return $data;
	}	
	
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_review_pre_save', $this );	
		
		if ( isset($this->data['title']) )	
			$this->data['title'] = trim(strip_tags($this->data['title']));
		if ( isset($this->data['review_text']) )	
			$this->data['review_text'] = trim(strip_tags($this->data['review_text']));
		if ( isset($this->data['rating']) )	
			$this->data['rating'] = intval($this->data['rating']);
		
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
				
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_review_pre_update', $this );		
			$this->data = apply_filters( 'usam_review_update_data', $this->data );	
			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );	
			$result = $wpdb->update( USAM_TABLE_CUSTOMER_REVIEWS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );							
			if ( $result ) 
			{ 
				$this->delete_cache( );					
				if( isset($this->changed_data['status']) )
				{
					$current_status = $this->get( 'status' );	
					$id = $this->get('id');	
					do_action( 'usam_update_customer_review_status', $id, $current_status, $this->changed_data['status'], $this );
					if ( $current_status == 2 )
						do_action( 'usam_customer_review_published', $this->get('contact_id'), $id, $this );					
				}				
				do_action( 'usam_review_update', $this, $this->changed_data );
			}			
		} 
		else 
		{   
			do_action( 'usam_review_pre_insert' );			
			
			unset( $this->data['id'] );
			if ( empty($this->data['status']))
				$this->data['status'] = 1;			
			if ( empty($this->data['rating']))
				$this->data['rating'] = 5;	
			if ( empty($this->data['date_insert']))
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			if ( !isset($this->data['contact_id']))
				$this->data['contact_id'] = usam_get_contact_id();	
			if ( empty($this->data['page_id']))
				$this->data['page_id'] = 0;				
			
			$this->data = apply_filters( 'usam_review_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );				
			$result = $wpdb->insert( USAM_TABLE_CUSTOMER_REVIEWS, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				if ( $this->data['status'] == 2 )
					do_action( 'usam_customer_review_published', $this->data['contact_id'], $wpdb->insert_id, $this );						
				do_action( 'usam_review_insert', $this );				
			}			
		} 		
		do_action( 'usam_review_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить 
function usam_update_review( $id, $data )
{
	$review = new USAM_Customer_Reviews( $id );
	$review->set( $data );
	return $review->save();
}

// Получить 
function usam_get_review( $id, $colum = 'id' )
{
	$review = new USAM_Customer_Reviews( $id, $colum );
	return $review->get_data( );	
}

// Добавить 
function usam_insert_review( $data )
{
	$review = new USAM_Customer_Reviews( $data );
	$review->save();
	return $review->get('id');
}

// Удалить
function usam_delete_review( $id )
{
	$review = new USAM_Customer_Reviews( $id );
	return $review->delete();
}

function usam_reviews_url( $review, $page )
{  
	$link = get_permalink( $review->page_id );
	
	if (strpos($link,'?') === false)
		$link = trailingslashit($link) . "?cr=$page#hreview-$review->id";
	else 
		$link = $link . "&cr=$page#hreview-$review->id";              
	return $link;    
}

function usam_get_review_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('review', $object_id, USAM_TABLE_CUSTOMER_REVIEW_META, $meta_key, $single );
}

function usam_update_review_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('review', $object_id, $meta_key, $meta_value, USAM_TABLE_CUSTOMER_REVIEW_META, $prev_value );
}

function usam_delete_review_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('review', $object_id, $meta_key, USAM_TABLE_CUSTOMER_REVIEW_META, $meta_value, $delete_all );
}

function usam_add_review_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('review', $object_id, $meta_key, $meta_value, USAM_TABLE_CUSTOMER_REVIEW_META, $prev_value );
}

// Получить вложения
function usam_get_review_attachments( $id )
{
	$cache_key = 'usam_review_attachments';
	$data = wp_cache_get( $id, $cache_key ); 
	if( $data === false )
	{					
		$data = usam_get_files(['object_id' => $id, 'type' => 'review']);
		wp_cache_set( $id, $data, $cache_key );
	}	
	return apply_filters( $cache_key, $data );	
}


function _usam_update_post_rating_by_review($id, $current_status, $previous_status, $t ) 
{	
	$page_id = $t->get('page_id');
	if ( $page_id )	
	{
		$rating = $t->get('rating');
		if ( $current_status == 2 )
			usam_update_post_rating($page_id, $rating );
		elseif ( $previous_status == 2 )
			usam_update_post_rating($page_id, 0, $rating );
	}	
}
add_action( 'usam_update_customer_review_status', '_usam_update_post_rating_by_review', 10, 4 );
?>