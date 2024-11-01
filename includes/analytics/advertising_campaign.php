<?php
class USAM_Advertising_Campaign 
{	 // строковые
	private static $string_cols = array(	
		'title',
		'description',
		'code',
		'source',
		'medium',					
		'content',	
		'term',	
		'redirect',	
		'date_insert',	
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'transitions',			
	);

	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	
	private $data    = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_advertising_campaign_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}			
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_advertising_campaign' );			
		}
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_advertising_campaign' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_advertising_campaign_code' );	
		do_action( 'usam_advertising_campaign_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_advertising_campaign' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_advertising_campaign_code' );
		do_action( 'usam_advertising_campaign_update_cache', $this );
	}

	/**
	 * Удаляет
	 * @since 4.9	
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_advertising_campaign_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_CAMPAIGNS . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_advertising_campaign_delete', $id );
	}	

	/**
	 * Выбирает фактические записи из базы данных
	 * @since 4.9
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_CAMPAIGNS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_advertising_campaign_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		}
		do_action( 'usam_advertising_campaign_fetched', $this );
		$this->fetched = true;
	}	

	/**
	 * Проверить существует ли строка в БД
	 */
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства из БД
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_advertising_campaign_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_advertising_campaign_get_data', $this->data, $this );
	}
	
	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 * @since 4.9
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
		$properties = apply_filters( 'usam_advertising_campaign_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
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

		do_action( 'usam_advertising_campaign_pre_save', $this );			
		
		$where_col = $this->args['col'];
		$result = false;		
		if ( $where_col ) 
		{	// обновление			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_advertising_campaign_pre_update', $this );		

			$this->data = apply_filters( 'usam_advertising_campaign_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			$result = $wpdb->update( USAM_TABLE_CAMPAIGNS, $this->data, [$where_col => $where_val], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_advertising_campaign_update', $this );
		} 
		else 
		{   
			do_action( 'usam_advertising_campaign_pre_insert' );	
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);
				
			if ( empty($this->data['source']) )
				$this->data['source'] = '';		
			
			if ( empty($this->data['medium']) )
				$this->data['medium'] = '';		
			
			if ( empty($this->data['code']) )
				$this->data['code'] = '';	

			if ( empty($this->data['description']) )
				$this->data['description'] = '';	
			
			if ( empty($this->data['redirect']) )
				$this->data['redirect'] = '';		

			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );
								
			$this->data = apply_filters( 'usam_advertising_campaign_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_CAMPAIGNS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_advertising_campaign_insert', $this );
			}			
		} 		
		do_action( 'usam_advertising_campaign_save', $this );
		return $result;
	}
}

function usam_get_advertising_campaign( $value, $colum = 'id' )
{	
	$advertising_campaign = new USAM_Advertising_Campaign($value, $colum);	
	$advertising_campaign_data = $advertising_campaign->get_data();	
	return $advertising_campaign_data;	
}

function usam_get_traffic_sources( )
{	
	return ['yandex' => __('Яндекс','usam'), 'google' => 'Google', 'vk' => __('Вконтакте','usam'), 'targetmail' => 'myTarget', 'instagram' => 'Instagram', 'facebook' => 'Facebook'];
}

function usam_get_name_source_advertising_campaign( $name )
{	
	$sources = usam_get_traffic_sources();
	if ( isset($sources[$name]) )
		return $sources[$name];	
	else
		return $name;
}

function usam_update_advertising_campaign( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$advertising_campaign = new USAM_Advertising_Campaign( $id );	
		$advertising_campaign->set( $data );	
		return $advertising_campaign->save();
	}
	return true;
}

function usam_insert_advertising_campaign( $value )
{	
	$advertising_campaign = new USAM_Advertising_Campaign( $value );	
	$advertising_campaign->save();
	$advertising_campaign_id = $advertising_campaign->get('id');	
	return $advertising_campaign_id;		 
}

function usam_delete_advertising_campaign( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Advertising_Campaign( $value, $colum );
	return $subscriber->delete();		 
}

function usam_insert_campaign_transition( $args, $check_existence = true )
{		
	global $wpdb;
	if ( empty($args['date_insert']) )
		$args['date_insert'] = date("Y-m-d H:i:s");	
			
	$formats = ['campaign_id' => '%d', 'contact_id' => '%d', 'date_insert' => '%s'];
	$where = [];
	foreach ($formats as $key ) 
	{
		if ( empty($args[$key]) )
			return false;
	}	
	$format = [];
	foreach ($args as $key => $value ) 
	{
		if ( isset($formats[$key]) )
			$format[] = $formats[$key];
		else
			unset($args[$key]);
	}	
	$result = $wpdb->insert( USAM_TABLE_CAMPAIGN_TRANSITIONS, $args, $format );	
	return $result;
}

function usam_get_url_utm_tags( $campaign, $url = '' )
{	
	if ( !$campaign )
		return $url;
	if ( is_numeric($campaign) )
	{
		$campaign = usam_get_advertising_campaign( $campaign );
		if ( !$campaign )
			return $url;
	}
	if ( $url == '' )		
		$url = $campaign['redirect'] ? $campaign['redirect'] : home_url( '/' );
	$url_args = [];
	$args = ["utm_source" => 'source', 'utm_medium' => 'medium', 'utm_campaign' => 'code'];
	foreach ($args as $key => $value )
	{
		if ( $campaign[$value] )
			$url_args[$key] = $campaign[$value];
	}
	return add_query_arg($url_args, $url );
}