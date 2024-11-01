<?php
class USAM_Parsing_Site
{	
	private static $string_cols = [
		'name',		
		'site_type',	
		'type_price',					
		'store',		
		'domain',	
		'scheme',		
		'view_product',
		'start_date',
		'end_date',				
	];
	private static $int_cols = [
		'id',	
		'active',	
		'proxy',		
	];
	private static $float_cols = [];		
	private $data     = [];		
	private $changed_data = [];	
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
					
		$this->args = ['col' => $col, 'value' => $value];		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_parsing_site' );
		}	
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
		
		if ( in_array( $col, self::$float_cols ) )
			return '%f';
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache(  ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_chat_bot_template' );		
		do_action( 'usam_parsing_site_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_parsing_site' );				
		do_action( 'usam_parsing_site_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_parsing_site_before_delete', $data );
		
		$this->delete_cache( );		
		$wpdb->query("DELETE FROM ".USAM_TABLE_PARSING_SITE_META." WHERE site_id = '$id'");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PARSING_SITES." WHERE id = '$id'");					
		
		do_action( 'usam_parsing_site_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PARSING_SITES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_parsing_site_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;		
			$this->update_cache( );	
		}			
		do_action( 'usam_parsing_site_fetched', $this );	
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
		return apply_filters( 'usam_parsing_site_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_parsing_site_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();	
		
		foreach ( $properties as $key => $value ) 
		{
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );			
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
				$this->data[$key] = $value;
			}
		}		
		$this->data = apply_filters( 'usam_parsing_site_set_properties', $this->data, $this );				
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

		do_action( 'usam_parsing_site_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
			
			if ( isset($this->data['name']) && usam_is_license_type('FREE') )	
				unset($this->data['name']);
					
			$where_format = self::get_column_format( $this->args['col'] );			
					
			do_action( 'usam_parsing_site_pre_update', $this );			

			$this->data = apply_filters( 'usam_parsing_site_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );	
			$result = $wpdb->update( USAM_TABLE_PARSING_SITES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_parsing_site_update', $this );
		} 
		else 
		{   
			if ( usam_is_license_type('FREE') )	
				$this->data['name'] .= ' - Демо лицензия';	
			
			do_action( 'usam_parsing_site_pre_insert' );		
			unset( $this->data['id'] );			
								
			if ( !isset($this->data['active']) )
				$this->data['active'] = 1;
			
			$this->data = apply_filters( 'usam_parsing_site_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );											
			$result = $wpdb->insert( USAM_TABLE_PARSING_SITES, $this->data, $formats );
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				do_action( 'usam_parsing_site_insert', $this );				
			}	
		} 		
		do_action( 'usam_parsing_site_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить
function usam_update_parsing_site( $id, $data )
{
	$parsing_site = new USAM_Parsing_Site( $id );	
	$parsing_site->set( $data );
	return $parsing_site->save();
}

// Получить
function usam_get_parsing_site( $id, $colum = 'id' )
{
	$parsing_site = new USAM_Parsing_Site( $id, $colum );
	return $parsing_site->get_data( );	
}

// Добавить
function usam_insert_parsing_site( $data )
{
	$parsing_site = new USAM_Parsing_Site( $data );
	$parsing_site->save();
	return $parsing_site->get('id');
}

// Удалить
function usam_delete_parsing_site( $id )
{
	$parsing_site = new USAM_Parsing_Site( $id );
	return $parsing_site->delete();
}

function usam_get_parsing_site_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('site', $object_id, USAM_TABLE_PARSING_SITE_META, $meta_key, $single );
}

function usam_update_parsing_site_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('site', $object_id, $meta_key, $meta_value, USAM_TABLE_PARSING_SITE_META, $prev_value );
}

function usam_delete_parsing_site_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('site', $object_id, $meta_key, USAM_TABLE_PARSING_SITE_META, $meta_value, $delete_all );
}

function usam_start_parsing_site( $id, $type_import )
{
	if ( is_numeric($id) )
		$site = usam_get_parsing_site( $id );
	else
		$site = $id;
	$result = false;
	$process = $type_import === 'insert' ? 'parsing_site_'.$site['site_type'] : 'update_product_parsing_'.$site['site_type'];
	$code_process = $process."_".$site['id'];
	if ( !usam_check_process_is_running( $code_process ) )
	{	
		global $wpdb;
		$wpdb->query("DELETE FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE site_id=".$site['id']);	
		
		usam_update_parsing_site( $site['id'], ['start_date' => date("Y-m-d H:i:s"), 'end_date' => '']);
		usam_update_parsing_site_metadata( $site['id'], 'products_added', 0 );
		usam_update_parsing_site_metadata( $site['id'], 'products_update', 0 );
		usam_update_parsing_site_metadata( $site['id'], 'count_urls', 0 );
		usam_update_parsing_site_metadata( $site['id'], 'links_processed', 0 );
			
		if ( $site['site_type'] == 'competitor' )
			$result = usam_create_system_process( sprintf(__("Парсинг %s", "usam"), $site['name']), ['id' => $site['id']], $process, 1000000, $code_process, 1 );
		elseif ( $site['site_type'] == 'supplier' )
		{
			if ( $type_import === 'insert' )
				$result = usam_create_system_process( sprintf(__("Парсинг %s", "usam"), $site['name']), ['id' => $site['id']], $process, 1000000, $code_process, 1 );
			else
			{
				$total = usam_get_total_products(['post_status' => ['publish', 'private', 'draft', 'pending'], 'productmeta_query' => [['key' => 'webspy_link', 'value' => $site['domain'], 'compare' => 'LIKE'], ['relation' => 'OR', ['key' => 'date_externalproduct', 'value' => date('Y-m-d H:i:s', strtotime( '-1 days' )), 'compare' => '<' ], ['key' => 'date_externalproduct', 'compare' => "NOT EXISTS"]]]]);
				if ( $total )
					$result = usam_create_system_process( sprintf(__("Проверить товары на сайте %s", "usam"), $site['name']), ['id' => $site['id']], $process, $total, $code_process, 1 );
			}
		}
	}
	return $result;
}

function usam_check_parsing_is_running( $id )
{
	if ( is_numeric($id) )
		$site = usam_get_parsing_site( $id );
	else
		$site = $id;
	if ( usam_check_process_is_running( 'parsing_site_'.$site['site_type']."_".$site['id'] ) || usam_check_process_is_running( 'update_product_parsing_'.$site['site_type']."_".$site['id'] ) )
		return true;
	else
		return false;
}
?>