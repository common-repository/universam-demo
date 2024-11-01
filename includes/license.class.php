<?php
class USAM_License
{
	private static $string_cols = array(
		'software',		
		'software_type',	
		'license_type',	
		'license',	
		'status',			
		'license_start_date',	
		'license_end_date',	
	);
	private static $int_cols = array(
		'id',		
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
		if ( ! in_array($col, ['id', 'license']) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_license' );
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
	
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_license' );		
		do_action( 'usam_license_update_cache', $this );
	}
	
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_license' );	
		do_action( 'usam_license_delete_cache', $this );	
	}

	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_license_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LICENSES." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_license_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_LICENSES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_license_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_license_fetched', $this );	
		$this->fetched = true;			
	}
	
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_license_get_license', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_license_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_license_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}
	
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
	
	public function save()
	{
		global $wpdb;

		do_action( 'usam_license_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_license_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_license_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_LICENSES, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_license_update', $this );
		} 
		else 
		{   
			do_action( 'usam_license_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
						
			$this->data = apply_filters( 'usam_license_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_LICENSES, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );				
			}
			do_action( 'usam_license_insert', $this );
		} 		
		do_action( 'usam_license_save', $this );

		return $result;
	}
}

function usam_get_license( $id, $colum = 'id' )
{ 
	$license = new USAM_License( $id, $colum );
	return $license->get_data( );	
}

function usam_delete_license( $id ) 
{
	$license = new USAM_License( $id );
	$result = $license->delete( );
	return $result;
}

function usam_insert_license( $data ) 
{
	$license = new USAM_License( $data );
	$license->save();
	return $license->get('id');
}

function usam_update_license( $id, $data ) 
{
	$license = new USAM_License( $id );
	$license->set( $data );
	return $license->save();
}

function usam_is_license_type( $type ) 
{
	$license = get_option ( 'usam_license' );
	if ( empty($license['type']) )
		return false;
	else
		return $license['type'] == $type;
}


function usam_get_name_type_license() 
{
	$license = get_option ( 'usam_license' );	
	$message = '';
	if ( !empty($license) )
	{
		switch ( strtoupper($license['type']) ) 
		{
			case 'TEMP' :
				$message = __('Временная', 'usam');				
			break;
			case 'FREE' :
				$message = __('Бесплатная', 'usam');	
			break;
			case 'LITE' :
				$message = __('Старт', 'usam');	
			break;	
			case 'SMALL_BUSINESS' :
				$message = __('Малый бизнес', 'usam');	
			break;			
			case 'PRO' :		
				$message = __('Профессиональная', 'usam');	
			break;
			case 'BUSINESS' :		
				$message = __('Бизнес', 'usam');	
			break;
			case 'ENTERPRISE' :		
				$message = __('Enterprise', 'usam');	
			break;			
			default:
				$message = __('Неизвестный тип', 'usam');	
		}
	}
	return $message;
}

function usam_get_name_status_license() 
{
	$license = get_option ( 'usam_license' );
	switch ( $license['status'] ) 
	{
		case 1 :
			$message = __('активна', 'usam');				
		break;
		case 2 :
			$message = __('заблокирована', 'usam');	
		break;
		default:
			$message = __('не активна', 'usam');	
	}
	return $message;
}
?>