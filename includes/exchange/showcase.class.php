<?php
class USAM_Showcase
{
	private static $string_cols = [
		'domain',		
		'access_token',
		'status',	
		'name',
		'login',
		'settings'
	];
	private static $int_cols = [
		'id',
		'number_products',	
		'products'
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_showcase' );
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
		wp_cache_set( $id, $this->data, 'usam_showcase' );	
		do_action( 'usam_showcase_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_showcase' );
		do_action( 'usam_showcase_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_showcase_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SHOWCASES." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_showcase_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SHOWCASES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$data['settings'] = (array)maybe_unserialize($data['settings']);
			$this->exists = true;
			$this->data = apply_filters( 'usam_showcase_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_showcase_fetched', $this );	
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
		return apply_filters( 'usam_showcase_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_showcase_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_showcase_set_properties', $properties, $this );
		if( ! is_array($this->data) )
			$this->data = array();	
					
		foreach( $properties as $key => $value ) 
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

		do_action( 'usam_showcase_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			if ( empty($this->changed_data) )
				return true;		
				
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_showcase_pre_update', $this );	
			
			$this->data = apply_filters( 'usam_showcase_update_data', $this->data );
			$data = $this->get_update_data();
			if( isset($data['settings']) )
				$data['settings'] = maybe_serialize($data['settings']);		
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_SHOWCASES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache();							
				do_action( 'usam_showcase_update', $this, $this->changed_data );
			}
		} 
		else 
		{   
			do_action( 'usam_showcase_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );
			
			$this->data = apply_filters( 'usam_showcase_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$data = $this->data;
			$data['settings'] = maybe_serialize($data['settings']);		
			$result = $wpdb->insert( USAM_TABLE_SHOWCASES, $data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_showcase_insert', $this );				
			}			
		} 		
		do_action( 'usam_showcase_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_showcase( $id, $colum = 'id' )
{
	$showcase = new USAM_Showcase( $id, $colum );
	return $showcase->get_data( );	
}

function usam_delete_showcase( $id ) 
{
	$showcase = new USAM_Showcase( $id );
	$result = $showcase->delete( );
	return $result;
}

function usam_insert_showcase( $data ) 
{
	$showcase = new USAM_Showcase( $data );	
	if ( $showcase->save() )
		return $showcase->get('id');
	else
		return false;
}

function usam_update_showcase( $id, $data ) 
{
	if ( $id )
	{
		$showcase = new USAM_Showcase( $id );
		$showcase->set( $data );
		return $showcase->save();
	}
	else
		return false;
}

function usam_get_statuses_showcase(  ) 
{
	return ['disabled' => __('Отключена', 'usam'), 'active' => __('Активна', 'usam')];
}

function usam_get_showcase_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_showcase( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}
?>