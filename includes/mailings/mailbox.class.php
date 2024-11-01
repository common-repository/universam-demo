<?php
/**
 * Почтовые ящики
 */ 
class USAM_Mailbox
{
	 // строковые
	private static $string_cols = [
		'name',		
		'email',		
		'delete_server_day',
		'template',
		'template_name',
	];
	// цифровые
	private static $int_cols = [
		'id',	
		'sort',
		'delete_server',
		'delete_server_deleted',
	];
	// рациональные
	private static $float_cols = [];		
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
		if ( ! in_array( $col, ['id', 'email'] ) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );			
		if ( $col == 'email'  && $id = wp_cache_get( $value, 'usam_mailbox_email' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_mailbox' );
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
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		$email = $this->get( 'email' );
		$data = $this->get_data( );
		wp_cache_set( $id, $data, 'usam_mailbox' );	
		wp_cache_set( $email, $id, 'usam_mailbox_email' );		
		do_action( 'usam_mailbox_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_mailbox' );	
		wp_cache_delete( $this->get( 'email' ), 'usam_mailbox_email' );	
		do_action( 'usam_mailbox_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_mailbox_before_delete', $data );		
		
		$result = false;
		
		$this->set(['delete_server_deleted' => 0]);
		$this->save();
		$wpdb->query("DELETE FROM ".USAM_TABLE_EMAIL_FOLDERS." WHERE mailbox_id = '$id'");
		usam_delete_emails(['mailbox_id' => $id], true );
		$this->delete_cache( );	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_MAILBOX." WHERE id = '$id'");
		do_action( 'usam_mailbox_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_MAILBOX." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{				
			$this->exists = true;
			$this->data = apply_filters( 'usam_mailbox_data', $data );			
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
		do_action( 'usam_mailbox_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 * @since 4.9
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 * @since 4.9
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_mailbox_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_mailbox_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();			
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_mailbox_set_properties', $this->data, $this );			
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

		do_action( 'usam_mailbox_pre_save', $this );	
		$where_col = $this->args['col'];			
	
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );
			
			do_action( 'usam_mailbox_pre_update', $this );				
			$where = array( $this->args['col'] => $where_format);

			$this->data = apply_filters( 'usam_mailbox_update_data', $this->data );			
			$format = $this->get_data_format( );
			$this->data_format( );
			
			$result = $wpdb->update( USAM_TABLE_MAILBOX, $this->data, array( $where_col => $where_val ), $format, $where_format );	
			if ( $result ) 
				$this->delete_cache( );	
			
			do_action( 'usam_mailbox_update', $this );
		} 
		else 
		{   
			do_action( 'usam_mailbox_pre_insert' );		
			unset( $this->data['id'] );				
									
			if ( !isset($this->data['sort']) )
				$this->data['sort'] = 100;						

			$this->data = apply_filters( 'usam_mailbox_insert_data', $this->data );			
			$format = $this->get_data_format(  );
			$this->data_format( );				
				
			$result = $wpdb->insert( USAM_TABLE_MAILBOX, $this->data, $format );
					
			if ( $result ) 
			{	
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );	
				
				$system_folders = usam_get_system_email_folders();							
				foreach ( $system_folders as $slug => $name )
					usam_insert_email_folder( array('name' => $name, 'mailbox_id' => $this->data['id'], 'slug' => $slug) );					
			}
			do_action( 'usam_mailbox_insert', $this );
		} 		
		do_action( 'usam_mailbox_save', $this );

		return $result;
	}
}

// Обновить почтовый ящик
function usam_update_mailbox( $id, $data, $colum = 'id' )
{
	$_class = new USAM_Mailbox( $id, $colum );
	$_class->set( $data );
	return $_class->save();
}

// Получить 
function usam_get_mailbox( $id, $colum = 'id' )
{
	$result = false;
	if( $id )
	{
		$_class = new USAM_Mailbox( $id, $colum );
		$result = $_class->get_data( );			
	}
	return $result;	
}

// Добавить
function usam_insert_mailbox( $data )
{
	$_class = new USAM_Mailbox( $data );
	$_class->save();	
	return $_class->get('id');
}

// Удалить
function usam_delete_mailbox( $id )
{
	$_class = new USAM_Mailbox( $id );
	return $_class->delete();
}

function usam_get_mailbox_users( $id ) 
{
	$object_type = 'usam_mailbox_users';	
	if( ! $cache = wp_cache_get( $id, $object_type ) )			
	{				
		global $wpdb;	
		$cache = $wpdb->get_col( "SELECT user_id FROM ".USAM_TABLE_MAILBOX_USERS." WHERE id = '$id'" );	
		wp_cache_set( $id, $cache, $object_type );						
	}
	return $cache;	
}

// Получить основную почту сайта
function usam_get_primary_mailbox( )
{		
	$email = get_option("usam_return_email");
	if ( $email )
		return usam_get_mailboxes(['email' => $email, 'number' => 1]);
	else
		return [];
}


function usam_get_mailbox_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('mailbox', $object_id, USAM_TABLE_MAILBOX_META, $meta_key, $single );
}

function usam_update_mailbox_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('mailbox', $object_id, $meta_key, $meta_value, USAM_TABLE_MAILBOX_META, $prev_value );
}

function usam_delete_mailbox_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('mailbox', $object_id, $meta_key, USAM_TABLE_MAILBOX_META, $meta_value, $delete_all );
}
?>