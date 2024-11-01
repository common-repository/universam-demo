<?php
class USAM_Contacting
{	// строковые
	private static $string_cols = [
		'date_insert',		
		'date_completion',			
		'status',
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'manager_id',	
		'post_id',
		'contact_id',
		'importance',
	];		
	private $data = array();		
	private $changed_data = array();	
	private $fetched  = false;
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
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_contacting' );
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
		wp_cache_set( $id, $this->data, 'usam_contacting' );	
		do_action( 'usam_contacting_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_contacting' );	
		do_action( 'usam_contacting_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{				
		$id = $this->get( 'id' );
		
		$result = usam_delete_contactings(['include' => array($id)]);		
		$this->delete_cache( );				
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CONTACTINGS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_contacting_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;						
			}
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_contacting_fetched', $this );	
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
		return apply_filters( 'usam_contacting_get_property', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_contacting_get_data', $this->data, $this );
	}

	public function set( $key, $value = null ) 
	{		
		if ( is_array($key) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];
		}
		$properties = apply_filters( 'usam_contacting_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = [];
			
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
	
		do_action( 'usam_contacting_pre_save', $this );	
		if ( isset($this->changed_data['status']) && ( $this->data['status'] == 'completed' || $this->data['status'] == 'canceled' ) )
			$this->data['date_completion'] = date("Y-m-d H:i:s");
		$result = false;	
		if ( $this->args['col'] ) 
		{		
			if ( empty($this->changed_data) )		
				return true;									
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_contacting_pre_update', $this );
		
			$this->data = apply_filters( 'usam_contacting_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		

			$str = [];
			foreach ( $formats as $key => $value ) 
			{
				if ( empty($data[$key]) && in_array($key, ['date_completion']) )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";					
			}				
			$result = $wpdb->query($wpdb->prepare("UPDATE `".USAM_TABLE_CONTACTINGS."` SET ".implode(', ', $str)." WHERE ".$this->args['col']." = '$where_format' ", array_merge(array_values($data), [$this->args['value']])));
			if ( $result ) 
			{
				$this->delete_cache( );	
				$id = $this->get('id');				
				foreach( $this->changed_data as $key => $value ) 
				{
					if ( isset($this->data[$key]) )
						usam_insert_change_history(['object_id' => $id, 'object_type' => 'contacting', 'operation' => 'edit',	'field' => $key, 'value' => $this->data[$key], 'old_value' => $value]);	
				}				
				if ( isset($this->changed_data['status']) )
					usam_update_object_count_status( $this->data['status'], 'contacting' );
				do_action( 'usam_contacting_update', $this );	
			}					
		} 
		else 
		{   
			do_action( 'usam_contacting_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );			
		
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( !isset($this->data['contact_id']) )
				$this->data['contact_id'] = usam_get_contact_id();	
			if ( empty($this->data['status']) )
				$this->data['status'] = 'not_started';							
			$this->data = apply_filters( 'usam_contacting_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_CONTACTINGS, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				usam_update_object_count_status( $this->data['status'], 'contacting');	
				do_action( 'usam_contacting_insert', $this );				
			}			
		} 		
		do_action( 'usam_contacting_save', $this );

		return $result;
	}
}

function usam_get_contacting( $id )
{
	$class = new USAM_Contacting( $id );
	return $class->get_data( );	
}

function usam_delete_contacting( $id ) 
{
	$class = new USAM_Contacting( $id );
	return $class->delete( );	
}

// Вставить задачу
function usam_insert_contacting( $data, $links = [] ) 
{
	if ( empty($data['date_insert']) )
		$data['date_insert'] = date( "Y-m-d H:i:s" );	
	$class = new USAM_Contacting( $data );
	$id = false;
	if ( $class->save() )
	{	
		$id = $class->get('id');
		if ( !empty($links) )
		{			
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_insert_ribbon(['contacting_id' => $id, 'event_type' => 'contacting', 'date_insert' => $data['date_insert']], $links);
		}
	}
	return $id;
}

function usam_update_contacting( $id, $data ) 
{
	$class = new USAM_Contacting( $id );	
	$class->set( $data );
	return $class->save();
}

function usam_get_comments_contacting( $id ) 
{
	return usam_update_comments_cache( $id, 'contacting' );	
}

function usam_get_contacting_links( $contacting_id ) 
{			 
	if ( !$contacting_id )
		return [];
	$cache_key = 'usam_contacting_links';
	$cache = wp_cache_get( $contacting_id, $cache_key );
	if( $cache === false )
	{
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );
		$types = usam_get_contactings_types();
		$cache = usam_get_ribbon_query(['event_id' => $contacting_id, 'contacting_type' => array_keys($types), 'add_fields' => ['object_id', 'object_type']]);	 
		wp_cache_set( $contacting_id, $cache, $cache_key );
	}	
	if ( !$cache )
		$cache = [];
	return $cache;
}

function usam_delete_contactings( $args ) 
{	
	global $wpdb;	
	$args['fields'] = ['id', 'status'];
	require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
	$contactings = usam_get_contactings( $args );	
	if ( empty($contactings) )
		return false;	
	
	usam_update_object_count_status( false );
	
	$ids = array();
	foreach ($contactings as $contacting )
	{
		usam_update_object_count_status( $contacting->status, 'contacting' );
		$ids[] = $contacting->id;
		do_action( 'usam_contacting_before_delete', (array)$contacting );	
	}			
	$wpdb->query( "DELETE FROM " . USAM_TABLE_CONTACTING_META . " WHERE contacting_id IN ('".implode("','", $ids)."')" );
	$wpdb->query( "DELETE FROM " . USAM_TABLE_CONTACTINGS . " WHERE id IN ('".implode("','", $ids)."')" );		
	
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
	require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
	usam_delete_comments(['object_id' => $ids, 'object_type' => 'contacting'], true);	
	usam_delete_ribbon(['event_id' => $ids, 'event_type' => 'contacting']);
	usam_delete_object_files( $ids, 'contacting' );
	usam_update_object_count_status( true );
	foreach ($contactings as $contacting )
		do_action( 'usam_contacting_delete', $contacting->id );	
	return count($contactings);	
}

function usam_get_contacting_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('contacting', $object_id, USAM_TABLE_CONTACTING_META, $meta_key, $single );
}

function usam_add_contacting_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('contacting', $object_id, $meta_key, $meta_value, USAM_TABLE_CONTACTING_META, $prev_value );
}

function usam_update_contacting_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('contacting', $object_id, $meta_key, $meta_value, USAM_TABLE_CONTACTING_META, $prev_value );
}

function usam_delete_contacting_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('contacting', $object_id, $meta_key, USAM_TABLE_CONTACTING_META, $meta_value, $delete_all );
}
?>