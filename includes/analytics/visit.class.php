<?php
class USAM_Page_Visit
{	 // строковые
	private static $string_cols = [
		'source',
		'date_insert',
		'date_update',	
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'contact_id',	
		'location_id',			
		'ip',	
		'views',		
		'visits',
	];
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_visit' );			
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
		wp_cache_set( $id, $this->data, 'usam_visit' );	
		do_action( 'usam_visit_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_visit' );			
		do_action( 'usam_visit_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_visit_before_delete', $data );
				
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_VISIT_META . " WHERE visit_id = %d", $id ) );	
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_VISITS . " WHERE id = %d", $id ) );	
		wp_cache_delete( $this->get( 'id' ), 'usam_visit_meta' );
		
		$this->delete_cache( );	
		do_action( 'usam_visit_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_VISITS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_visit_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		}
		do_action( 'usam_visit_fetched', $this );
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
		return apply_filters( 'usam_visit_get_property', $value, $key, $this );
	}

	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_visit_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_visit_set_properties', $properties, $this );	

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

		do_action( 'usam_visit_pre_save', $this );	
		
		$result = false;		
		$this->data['date_update'] = date( "Y-m-d H:i:s" );	
		if( $this->args['col'] ) 
		{		
			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_visit_pre_update', $this );

			$this->data = apply_filters( 'usam_visit_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_VISITS, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_visit_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_visit_pre_insert' );				

			if ( empty($this->data['contact_id']) )
				$this->data['contact_id'] = usam_get_contact_id();					
			if ( empty($this->data['ip']) )
				$this->data['ip'] = ip2long($_SERVER['REMOTE_ADDR']);
			if ( empty($this->data['location_id']) )
				$this->data['location_id'] = usam_get_customer_location();			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			$this->data['views'] = 1;
			$this->data['visits'] = 1;
			if( $this->data['contact_id'] )
			{
				$visits = $wpdb->get_var("SELECT COUNT(id) FROM ".USAM_TABLE_VISITS." WHERE contact_id=".$this->data['contact_id']);
				$this->data['visits'] = $visits ? $visits : 1;
			}
			$this->data = apply_filters( 'usam_visit_insert_data', $this->data );				
			$format = $this->get_data_format( );	
			$result = $wpdb->insert( USAM_TABLE_VISITS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col' => 'id', 'value' => $this->get( 'id' )];
				$this->update_cache();				
				do_action( 'usam_visit_insert', $this );
			}			
		} 		
		do_action( 'usam_visit_save', $this );
		return $result;
	}
}

function usam_get_visit( $value, $colum = 'id' )
{	
	$visit = new USAM_Page_Visit($value, $colum);	
	$visit_data = $visit->get_data();	
	return $visit_data;	
}

function usam_update_visit( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$visit = new USAM_Page_Visit( $id );	
		$visit->set( $data );	
		return $visit->save();
	}
	return true;
}

function usam_insert_visit( $value )
{		
	$visit = new USAM_Page_Visit( $value );	
	$visit->save();
	return $visit->get('id');
}

function usam_new_visit( $data = [] )
{	
	if ( !empty($_GET['qr']) )	
		$referer = 'qr-'.sanitize_text_field($_GET['qr']);				
	elseif ( !empty($_SERVER['HTTP_REFERER']) )					
		$referer = rtrim(sanitize_text_field($_SERVER['HTTP_REFERER']), '/');
	else
		$referer = '';		
	if ( empty($data['source']) )
	{		
		if ( usam_is_bot() )
			$data['source'] = 'bot';
		elseif ( $referer )
			$data['source'] = 'link';
		else
		{			
			$data['source'] = parse_url($referer, PHP_URL_HOST);
			if ( !$data['source'] )
				$data['source'] = $referer;
			$data['source'] = mb_strtolower($data['source']);
			$data['source'] = preg_replace('/(^|\/\/|\s+)('.preg_quote('www.').')/', '${1}', $data['source'], 1);
		}
	}
	if ( empty($data['contact_id']) )
		$data['contact_id'] = usam_get_contact_id();	
	$user_view_id = usam_insert_visit( $data );		
	if( $user_view_id )
	{				
		if( $referer )
			usam_add_visit_metadata( $user_view_id, 'referer', $referer );		
		$device = wp_is_mobile() ? 'mobile' : 'PC';
		usam_add_visit_metadata( $user_view_id, 'device', $device );
		if( $data['contact_id'] )
		{
			$visit = (int)usam_get_contact_metadata( $data['contact_id'], 'visit' );
			usam_update_contact_metadata( $data['contact_id'], 'visit', $visit );
		}
		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		usam_add_visit_metadata( $user_view_id, 'user_agent', $user_agent );
		$tags = ['gclid' => __('Идентификатор перехода с Google Ads','usam'), 'yclid' => __('Идентификатор перехода с Яндекс Директ','usam'), 'ymclid' => __('Идентификатор перехода с Маркета','usam'), 'fbclid' => __('Идентификатор перехода с Facebook','usam')];
		foreach( $tags as $key => $tag )
		{
			if ( !empty($_GET[$key]) )
				usam_add_visit_metadata( $user_view_id, $key, sanitize_text_field($_GET[$key]) );
		}
	}	
	return $user_view_id;
}

function usam_delete_visit( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Page_Visit( $value, $colum );
	return $subscriber->delete();		 
}

function usam_get_name_source_visit( $source )
{	
	if ( $_SERVER['SERVER_NAME'] == $source )
		$name = ['full' => __('Внутренние переходы', 'usam'), 'short' => __('Внутренние', 'usam')];		
	else
	{
		switch ( $source ) 
		{			
			case 'vk.com' :
			case 'away.vk.com' :
			case 'm.vk.com' :			
			case 'facebook.com' :
			case 'ok.ru' :
			case 'odnoklassniki.ru' :
			case 'love.mail.ru' :
			case 'instagram.com' :
			case 'mail.ru' :		
				$name = ['full' => __('Переходы из социальных сетей', 'usam'), 'short' => __('Соцсети', 'usam')];	
			break;
			case 'yandex.ru' :
			case 'google.ru' :		
			case 'google.com' :			
				$name =['full' => __('Переходы из поисковых систем', 'usam'), 'short' => __('Поисковики', 'usam')];		
			break;		
			case 'link' :
				$name = ['full' => __('Прямые заходы', 'usam'), 'short' => __('Прямые', 'usam')];
			break;
			default:
				$name = ['full' => __('Переходы по ссылкам на сайтах', 'usam'), 'short' => __('Из сайтов', 'usam')];		
			break;		
		}
	}
	return $name;	
}

function usam_get_visit_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('visit', $object_id, USAM_TABLE_VISIT_META, $meta_key, $single );
}

function usam_update_visit_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('visit', $object_id, $meta_key, $meta_value, USAM_TABLE_VISIT_META, $prev_value );
}

function usam_delete_visit_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('visit', $object_id, $meta_key, USAM_TABLE_VISIT_META, $meta_value, $delete_all );
}

function usam_add_visit_metadata($object_id, $meta_key, $meta_value, $prev_value = true ) 
{ 
	return usam_add_metadata('visit', $object_id, $meta_key, $meta_value, USAM_TABLE_VISIT_META, $prev_value );
}