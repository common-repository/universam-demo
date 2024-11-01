<?php
/**
 * Профили социальных сетей
 */ 
class USAM_Social_Network_Profile
{
	 // строковые
	private static $string_cols = array(
		'name',		
		'photo',	
		'code',		
		'uri',	
		'access_token',	
		'type_social',			
		'app_id',	
		'type_price',		
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'subscribers_count',	
		'from_group',			
		'birthday',	
		'contact_group',		
	);
	private static $float_cols = array(	);			
	private $data     = array();		
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
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_social_network_profile' );
		}		
		// кэш существует
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
		wp_cache_set( $id, $this->data, 'usam_social_network_profile' );		
		do_action( 'usam_social_network_profile_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_social_network_profile' );				
		do_action( 'usam_social_network_profile_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_social_network_profile_before_delete', $data );
		
		$this->delete_cache( );						
		
		$wpdb->query("DELETE FROM ".USAM_TABLE_SOCIAL_NETWORK_PROFILE_META." WHERE profile_id = '$id'");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SOCIAL_NETWORK_PROFILES." WHERE id = '$id'");	
		
		do_action( 'usam_social_network_profile_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SOCIAL_NETWORK_PROFILES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_social_network_profile_data', $data );			
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
		do_action( 'usam_social_network_profile_fetched', $this );	
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
		return apply_filters( 'usam_social_network_profile_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_social_network_profile_get_data', $this->data, $this );
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
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_social_network_profile_set_properties', $this->data, $this );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 * @since 4.9
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

		do_action( 'usam_social_network_profile_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	
			
			do_action( 'usam_social_network_profile_pre_update', $this );				
			$where = array( $where_col => $where_val );			

			$this->data = apply_filters( 'usam_social_network_profile_update_data', $this->data );			
			$format = $this->get_data_format( );
			$result = $wpdb->update( USAM_TABLE_SOCIAL_NETWORK_PROFILES, $this->data, $where, $format, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_social_network_profile_update', $this );
		} 
		else 
		{   
			do_action( 'usam_social_network_profile_pre_insert' );		
			unset( $this->data['id'] );

			if ( !isset($this->data['birthday']) )
				$this->data['birthday'] = 0;		
			 
			if ( !isset($this->data['contact_group']) )
				$this->data['contact_group'] = 0;	
								
			$this->data = apply_filters( 'usam_social_network_profile_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
										
			$result = $wpdb->insert( USAM_TABLE_SOCIAL_NETWORK_PROFILES, $this->data, $format );		
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );			
				
				do_action( 'usam_social_network_profile_insert', $this );				
			}			
		} 		
		do_action( 'usam_social_network_profile_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_social_network_profile( $id, $data )
{
	$social_network_profile = new USAM_Social_Network_Profile( $id );	
	$social_network_profile->set( $data );
	return $social_network_profile->save();
}

// Получить
function usam_get_social_network_profile( $id, $colum = 'id' )
{
	$social_network_profile = new USAM_Social_Network_Profile( $id, $colum );
	return $social_network_profile->get_data( );	
}

// Добавить
function usam_insert_social_network_profile( $data )
{
	$social_network_profile = new USAM_Social_Network_Profile( $data );
	$social_network_profile->save();
	return $social_network_profile->get('id');
}

// Удалить
function usam_delete_social_network_profile( $id )
{
	$social_network_profile = new USAM_Social_Network_Profile( $id );
	return $social_network_profile->delete();
}

function usam_send_message_to_messenger( $social_network, $args )
{
	$message_id = 0;
	if ( !empty($social_network['type_social']) )
	{
		switch ( $social_network['type_social'] ) 
		{
			case 'telegram' :	
				require_once( USAM_APPLICATION_PATH . '/social-networks/telegram_api.class.php' );
				$telegram = new USAM_Telegram_API( $social_network );			
				$message_id = $telegram->send_message( $args );
			break;			
			case 'vk_group' :	
			case 'vk' :	
				require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
				$vkontakte = new USAM_VKontakte_API( $social_network );	
				$message_id = $vkontakte->send_message( $args );				
			break;
			case 'viber' :	
				require_once( USAM_APPLICATION_PATH . '/social-networks/viber_api.class.php' );
				$telegram = new USAM_Viber_API( $social_network );			
				$message_id = $telegram->send_message( $args );
			break;
			case 'skype' :	
				require_once( USAM_APPLICATION_PATH . '/social-networks/skype_api.class.php' );
				$skype = new USAM_Skype_API( $social_network );			
				$message_id = $skype->send_message( $args );
			break;
		}
	}
	return $message_id;
}

function usam_get_social_network_profile_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('profile', $object_id, USAM_TABLE_SOCIAL_NETWORK_PROFILE_META, $meta_key, $single );
}

function usam_update_social_network_profile_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('profile', $object_id, $meta_key, $meta_value, USAM_TABLE_SOCIAL_NETWORK_PROFILE_META, $prev_value );
}

function usam_delete_social_network_profile_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('profile', $object_id, $meta_key, USAM_TABLE_SOCIAL_NETWORK_PROFILE_META, $meta_value, $delete_all );
}

function usam_get_social_networks_authorize_url( $type )
{
	$redirect_uri = home_url("api/{$type}-auth");
	$url = '';
	switch ( $type ) 
	{					
		case 'vk':	
			$api = get_option('usam_vk_api');	
			if ( !empty($api['client_id']) )
				$url = "http://oauth.vk.com/authorize?client_id={$api['client_id']}&redirect_uri={$redirect_uri}&response_type=code";
		break;
		case 'ok':	
			$api = get_option('usam_odnoklassniki');	
			if ( !empty($api['client_id']) )
				$url = "http://www.odnoklassniki.ru/oauth/authorize?client_id={$api['client_id']}&redirect_uri={$redirect_uri}&response_type=code";
		break;		
		case 'facebook':	
			$api = get_option('usam_fb_api');	
			if ( !empty($api['client_id']) )
				$url = "https://www.facebook.com/dialog/oauth?client_id={$api['client_id']}&redirect_uri={$redirect_uri}&response_type=code";
		break;
		case 'google':	
			$api = get_option('usam_google');				
			if ( !empty($api['client_id']) )
			{
				$params = array(
					'client_id'     => $api['client_id'],
					'redirect_uri'  => $redirect_uri,
					'response_type' => 'code',
					'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
					'state'         => '123'
				);
				$url = "https://accounts.google.com/o/oauth2/auth?".urldecode(http_build_query($params));
			}
		break;
	}
	return $url;
}
?>