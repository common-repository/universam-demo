<?php
class USAM_System_Report
{	// строковые
	private static $string_cols = [
		'type',		
		'operation',		
		'start_date',		
		'end_date',
		'status',
		'filename',
		'description'
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'user_id',	
		'add',	
		'update'
	];	
	private $data = [];		
	private $is_status_changed = false;	
	private $fetched           = false;
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
			$this->data = wp_cache_get( $value, 'usam_system_report' );
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
		wp_cache_set( $id, $this->data, 'usam_system_report' );	
		do_action( 'usam_system_report_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_system_report' );	
		do_action( 'usam_system_report_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_system_report_before_delete', $data );
		
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SYSTEM_REPORTS." WHERE id = '$id'");	
		do_action( 'usam_system_report_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SYSTEM_REPORTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_system_report_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}			
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_system_report_fetched', $this );	
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
		return apply_filters( 'usam_system_report_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_system_report_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_system_report_set_properties', $properties, $this );
		
		if ( array_key_exists( 'status', $properties ) ) 
		{	
			$previous_status = $this->get( 'status' );
			if ( $properties['status'] != $previous_status )
				$this->is_status_changed = true;			
		}			
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
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;	
		do_action( 'usam_system_report_pre_save', $this );	
		$where_col = $this->args['col'];				
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_system_report_pre_update', $this );
			
			$this->data = apply_filters( 'usam_system_report_update_data', $this->data );			
			$format = $this->get_data_format();
						
			$result = $wpdb->update( USAM_TABLE_SYSTEM_REPORTS, $this->data, [$where_col => $where_val], $format, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );				
			}
			do_action( 'usam_system_report_update', $this );			
		} 
		else 
		{   
			do_action( 'usam_system_report_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			$this->data['start_date'] = date( "Y-m-d H:i:s" );	
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			if ( empty($this->data['status']) )
				$this->data['status'] = 'started';					
						
			$this->data = apply_filters( 'usam_system_report_insert_data', $this->data );
			$format = $this->get_data_format();
					
			$result = $wpdb->insert( USAM_TABLE_SYSTEM_REPORTS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_system_report_insert', $this );				
			}			
		} 		
		do_action( 'usam_system_report_save', $this );

		return $result;
	}
}

function usam_get_system_report( $id )
{
	$_system_report = new USAM_System_Report( $id );
	return $_system_report->get_data( );	
}

function usam_delete_system_report( $id ) 
{
	$_system_report = new USAM_System_Report( $id );
	return $_system_report->delete( );	
}

function usam_insert_system_report( $data ) 
{
	$_system_report = new USAM_System_Report( $data );
	$_system_report->save();
	return $_system_report->get('id');	
}

function usam_update_system_report( $id, $data ) 
{
	$_system_report = new USAM_System_Report( $id );	
	$_system_report->set( $data );
	return $_system_report->save();
}
?>