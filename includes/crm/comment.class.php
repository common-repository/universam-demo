<?php
class USAM_Comment
{
	// строковые
	private static $string_cols = array(
		'date_insert',		
		'object_type',		
		'message',	
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'user_id',		
		'object_id',
		'status',		
	);			
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
			$this->data = wp_cache_get( $value, 'usam_comment' );
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
		wp_cache_set( $id, $this->data, 'usam_comment' );	
		do_action( 'usam_comment_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_comment' );	
		do_action( 'usam_comment_delete_cache', $this );	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COMMENTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_comment_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_comment_fetched', $this );	
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
		return apply_filters( 'usam_comment_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_comment_get_data', $this->data, $this );
	}

	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
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
		$properties = apply_filters( 'usam_comment_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
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

		do_action( 'usam_comment_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_comment_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_comment_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_COMMENTS, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_comment_update', $this );
		} 
		else 
		{   
			do_action( 'usam_comment_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			$this->data['date_insert']     = date( "Y-m-d H:i:s" );	
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			
			if ( empty($this->data['message']) )
				$this->data['message'] = '';
			
			if ( empty($this->data['object_id']) )
				return false;
			
			if ( empty($this->data['object_type']) )
				return false;		
						
			$this->data = apply_filters( 'usam_comment_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_COMMENTS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );
				do_action( 'usam_comment_insert', $this );				
			}			
		} 		
		do_action( 'usam_comment_save', $this );

		return $result;
	}
}

function usam_get_comment( $id, $colum = 'id' )
{
	$comment = new USAM_Comment( $id, $colum );
	return $comment->get_data( );	
}

function usam_delete_comment( $id ) 
{
	if ( $id )
		$result = usam_delete_comments(['include' => $id]);
	else
		$result = false;
	return $result;
}

// Вставить комментарий
function usam_insert_comment( $data, $links = [] ) 
{
	$comment = new USAM_Comment( $data );
	$id = false;
	if ( $comment->save() )
	{
		$id = $comment->get('id');
		if ( !empty($links) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_insert_ribbon(['event_id' => $id, 'event_type' => 'comment'], $links);
		}
	}
	return $id;
}

function usam_update_comment( $id, $data ) 
{
	$comment = new USAM_Comment( $id );
	$comment->set( $data );
	return $comment->save();
}

function usam_add_comment_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('comment', $object_id, $meta_key, $meta_value, USAM_TABLE_COMMENT_META, $prev_value );
}

function usam_get_comment_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('comment', $object_id, USAM_TABLE_COMMENT_META, $meta_key, $single );
}

function usam_update_comment_metadata($object_id, $meta_key, $meta_value, $prev_value = true ) 
{
	return usam_update_metadata('comment', $object_id, $meta_key, $meta_value, USAM_TABLE_COMMENT_META, $prev_value );
}

function usam_delete_comment_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('comment', $object_id, $meta_key, USAM_TABLE_COMMENT_META, $meta_value, $delete_all );
}

function usam_delete_comments( $args, $delete = false ) 
{	
	global $wpdb;	
	require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
	$items = usam_get_comments( $args );	
	if ( empty($items) )
		return false;		
	$ids = [];
	foreach ( $items as $item )
	{			
		if ( $item->status == 1 || $delete )
		{
			$ids[] = $item->id;	
			do_action( 'usam_comment_before_delete', (array)$item );
		}
		else
			usam_update_comment($item->id, ['status' => 1]);
	}
	if ( $ids )
	{
		$wpdb->query("DELETE FROM ".USAM_TABLE_COMMENT_META." WHERE comment_id IN (".implode(",", $ids).")");
		$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE object_type='comment' AND object_id IN (".implode(",", $ids).")");
		$wpdb->query( "DELETE FROM ".USAM_TABLE_COMMENTS." WHERE id IN ('".implode("','", $ids)."')" );		
		
		foreach ( $items as $item )
		{
			wp_cache_delete( $item->id, 'usam_comment' );	
			wp_cache_delete( $item->id, 'usam_comment_meta' );			
			
			do_action( 'usam_comment_delete', $item->id );		
		}
	}
	return count($ids);
}
?>