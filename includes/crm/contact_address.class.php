<?php
class USAM_Contact_Address
{
	private static $string_cols = array(
		'index',		
		'street',
		'house',	
		'flat',	
		'floor',	
		'frame',
	);
	private static $int_cols = array(
		'id',				
		'contact_id',	
		'location_id',		
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
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_contact_address' );
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
		wp_cache_set( $id, $this->data, 'usam_contact_address' );		
		do_action( 'usam_contact_address_update_cache', $this );
	}
	
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_contact_address' );	
		do_action( 'usam_contact_address_delete_cache', $this );	
	}

	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_contact_address_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CONTACT_ADDRESS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_contact_address_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CONTACT_ADDRESS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_contact_address_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_contact_address_fetched', $this );	
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
		return apply_filters( 'usam_contact_address_get_contact_address', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_contact_address_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_contact_address_set_properties', $properties, $this );	
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
	
	public function save()
	{
		global $wpdb;

		do_action( 'usam_contact_address_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_contact_address_pre_update', $this );	

			$this->data = apply_filters( 'usam_contact_address_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_CONTACT_ADDRESS, $this->data, [$this->args['col'] => $where_val], $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_contact_address_update', $this );
		} 
		else 
		{   
			do_action( 'usam_contact_address_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
						
			$this->data = apply_filters( 'usam_contact_address_insert_data', $this->data );
			$format = $this->get_data_format(  );
					
			$result = $wpdb->insert( USAM_TABLE_CONTACT_ADDRESS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_contact_address_insert', $this );
		} 		
		do_action( 'usam_contact_address_save', $this );

		return $result;
	}
}

function usam_get_contact_address( $id, $colum = 'id' )
{ 
	$address = new USAM_Contact_Address( $id, $colum );
	return $address->get_data( );	
}

function usam_delete_contact_address( $id ) 
{
	$address = new USAM_Contact_Address( $id );
	$result = $address->delete( );
	return $result;
}

function usam_insert_contact_address( $data ) 
{
	$address = new USAM_Contact_Address( $data );
	$address->save();
	return $address->get('id');
}

function usam_update_contact_address( $id, $data ) 
{
	$address = new USAM_Contact_Address( $id );
	$address->set( $data );
	return $address->save();
}
?>