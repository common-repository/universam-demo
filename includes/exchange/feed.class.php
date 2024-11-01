<?php
class USAM_Feed
{
	private static $string_cols = [
		'name',		
		'platform',			
		'start_date',
		'end_date',		
		'type_price',	
	];
	private static $int_cols = [
		'id',
		'active',
	];
	private $changed_data = [];	
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
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
		if ( ! in_array( $col, ['id']) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_feed' );
		}			
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
		wp_cache_set( $id, $this->data, 'usam_feed' );	
		do_action( 'usam_feed_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_feed' );
		do_action( 'usam_feed_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_feed_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_FEEDS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_feed_delete', $id );
		
		return $result;
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_FEEDS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_feed_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_feed_fetched', $this );	
		$this->fetched = true;			
	}

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
		return apply_filters( 'usam_feed_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_feed_get_data', $this->data, $this );
	}

	
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
		$properties = apply_filters( 'usam_feed_set_properties', $properties, $this );
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
		
	public function save()
	{
		global $wpdb;

		do_action( 'usam_feed_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			if ( empty($this->changed_data) )
				return true;
		
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_feed_pre_update', $this );			
			
			$this->data = apply_filters( 'usam_feed_update_data', $this->data );
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_FEEDS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );	
				foreach ( $this->changed_data as $key => $old_value ) 
				{ 
					if ( $key != 'start_date' && $key != 'end_date' && isset($this->data[$key]) )
					{						
						usam_insert_change_history(['object_id' => $this->data['id'], 'object_type' => 'feed', 'operation' => 'update', 'field' => $key, 'value' => $this->data[$key], 'old_value' => $old_value]);	
					}
				}				
				do_action( 'usam_feed_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_feed_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );		
			
			if ( isset($this->data['start_date']) )
				unset($this->data['start_date']);
			
			if ( isset($this->data['end_date']) )
				unset($this->data['end_date']);
			
			$this->data = apply_filters( 'usam_feed_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );						
			$result = $wpdb->insert( USAM_TABLE_FEEDS, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_feed_insert', $this );				
			}			
		} 		
		do_action( 'usam_feed_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_feed( $id, $colum = 'id' )
{
	$class = new USAM_Feed( $id, $colum );
	return $class->get_data( );	
}

function usam_delete_feed( $id ) 
{
	$class = new USAM_Feed( $id );
	$result = $class->delete( );
	return $result;
}

function usam_insert_feed( $data ) 
{
	$class = new USAM_Feed( $data );	
	if ( $class->save() )
		return $class->get('id');
	else
		return false;
}

function usam_update_feed( $id, $data ) 
{
	if ( $id )
	{
		$class = new USAM_Feed( $id );
		$class->set( $data );
		return $class->save();
	}
	else
		return false;
}

// Подключить класс
function usam_get_trading_platforms_class( $id )
{
	$rule = usam_get_feed( $id );	
	$instance = false;
	if ( !empty($rule['platform']) )
	{
		$file =  USAM_APPLICATION_PATH . '/trading-platforms/' . $rule['platform'].'.php';
		if ( file_exists( $file ) )
		{
			require_once( $file );
			$class = 'USAM_'.$rule['platform'].'_Exporter';
			$instance = new $class( $rule );				
		}
	}
	return $instance;
}

function usam_get_feed_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('feed', $object_id, USAM_TABLE_FEED_META, $meta_key, $single );
}

function usam_update_feed_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('feed', $object_id, $meta_key, $meta_value, USAM_TABLE_FEED_META, $prev_value );
}

function usam_delete_feed_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('feed', $object_id, $meta_key, USAM_TABLE_FEED_META, $meta_value, $delete_all );
}

function usam_add_feed_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('feed', $object_id, $meta_key, $meta_value, USAM_TABLE_FEED_META, $prev_value );
}
?>