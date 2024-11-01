<?php
class USAM_Integration_Service
{
	// строковые
	private static $string_cols = array(
		'service_code',		
		'group_code',
		'access_token',
		'login',
		'password',		
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'active',		
	);
	private $data = array();		
	private $fetched           = false;
	private $args = ['col' => '', 'value' => ''];	
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
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_application' );
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
		wp_cache_set( $id, $this->data, 'usam_application' );	
		do_action( 'usam_application_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_application' );	
		do_action( 'usam_application_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_application_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_APPLICATIONS." WHERE id = '$id'");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_APPLICATION_META." WHERE service_id = '$id'");			
		
		$this->delete_cache( );		
		do_action( 'usam_application_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_APPLICATIONS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_application_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_application_fetched', $this );	
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
		return apply_filters( 'usam_application_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_application_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_application_set_properties', $properties, $this );
	
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

		do_action( 'usam_application_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_application_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_application_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_APPLICATIONS, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_application_update', $this );
		} 
		else 
		{   
			do_action( 'usam_application_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );			
			
			$this->data = apply_filters( 'usam_application_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_APPLICATIONS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );				
			}
			do_action( 'usam_application_insert', $this );
		} 		
		do_action( 'usam_application_save', $this );

		return $result;
	}
}

function usam_get_application( $id, $colum = 'id' )
{
	$application = new USAM_Integration_Service( $id, $colum );
	return $application->get_data( );	
}

function usam_delete_application( $id ) 
{
	$application = new USAM_Integration_Service( $id );
	$result = $application->delete( );
	return $result;
}

function usam_insert_application( $data ) 
{
	$application = new USAM_Integration_Service( $data );
	$application->save();
	return $application->get('id');
}

function usam_update_application( $id, $data ) 
{
	$application = new USAM_Integration_Service( $id );
	$application->set( $data );
	return $application->save();
}

function usam_get_application_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('service', $object_id, USAM_TABLE_APPLICATION_META, $meta_key, $single );
}

function usam_update_application_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('service', $object_id, $meta_key, $meta_value, USAM_TABLE_APPLICATION_META, $prev_value );
}

function usam_delete_application_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('service', $object_id, $meta_key, USAM_TABLE_APPLICATION_META, $meta_value, $delete_all );
}

function usam_get_name_service( $service_code, $folder = 'applications' ) 
{
	static $gateways = null;
	if ( $gateways === null )
		$gateways = usam_get_integrations( $folder );
	return isset($gateways[$service_code])?$gateways[$service_code]:"";
}

function usam_get_class_application( $data ) 
{		
	if ( is_numeric($data) )
	{
		$data = usam_get_application( $data );
		$application = $data['service_code'];
		$id = $data['id'];
	}
	elseif ( is_array($data) )
	{		
		$id = $data['id'];
		$application = $data['service_code'];
	}
	else
	{
		$application = $data;
		$data = 0;
	}
	$filename = USAM_APPLICATION_PATH ."/applications/".$application.".php";
	if ( !file_exists($filename) )
		$filename = USAM_APPLICATION_PATH ."/applications/".$application."/".$application.".php";		
	if ( file_exists($filename) )
	{ 
		require_once( $filename );
		$class_name = "USAM_Application_".str_replace('-', '_', $application);	
		return new $class_name( $data );	
	}
	return null;
}

function usam_application_rest_api( WP_REST_Request $request )
{	
	$service_id = $request->get_param( 'serviceid' );	
	if ( $service_id )
	{
		$class = usam_get_class_application( $service_id );
		if ( $class )
			$class->rest_api( $request );
		else
			return new WP_Error('no_class_application', 'Invalid class application', ['status' => 404]);
	}
	else
		return new WP_Error('no_application_id', 'Invalid application id', ['status' => 404]);
}

function usam_get_data_integrations( $folder, $args ) 
{
	$dirname = $folder ? USAM_APPLICATION_PATH ."/{$folder}" : USAM_APPLICATION_PATH;	
	$gateways = [];	
	if ($dir = opendir($dirname)) 
	{		
		while ( ($file = readdir( $dir )) !== false ) 
		{
			if ( $file != ".." && $file != "." && !stristr( $file, "~" ) && !( strpos( $file, "." ) === 0 ) )
			{				
				if ( is_dir($dirname . '/' . $file) )
				{
					$filepath = $dirname . '/' . $file. '/'. $file.'.php'; 
					if ( file_exists($filepath) )
					{
						$parts = explode( '.', $file );
						$gateways[$parts[0]] = get_file_data( $filepath, $args );	
					} 
				}
				elseif ( stristr($file, '.php') ) 		
				{		
					$parts = explode( '.', $file );
					$gateways[$parts[0]] = get_file_data( $dirname. '/'.$file, $args );	
				}	
			}			
		}
	} 
	$gateways = apply_filters( 'usam_integrations', $gateways, $folder );	
	asort($gateways);	
	return $gateways;
}

//используется для trading platforms
function usam_get_name_integration( $service ) 
{
	if ( is_numeric($service) )
		$service = usam_get_application( $service );
	elseif ( !is_array($service) )
		$service = (array)$service;
	if ( !$service )
		return false;	
	
	$args = ['name' => 'Name'];
	$dirname = USAM_APPLICATION_PATH ."/".$service['group_code'];	
	$dirname = apply_filters( 'usam_gateway_path', $dirname, $service['group_code'] );
	$data = get_file_data( $dirname. '/'.$service['service_code'].'.php', $args );
	return $data['name'];
}

function usam_get_integrations( $folder = 'applications' ) 
{
	$items = usam_get_data_integrations( $folder, ['name' => 'Name'] );
	$results = [];
	foreach ($items as $key => $item )
	{
		$results[$key] = $item['name'];
	}
	return $results;
}
?>