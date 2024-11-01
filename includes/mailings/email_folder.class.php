<?php
// Класс работы с папками писем
class USAM_Email_Folders
{	
	// строковые
	private static $string_cols = array(
		'name',		
		'slug',				
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'mailbox_id',	
		'count',
		'not_read',		
	);
	// рациональные
	private static $float_cols = array(
		
	);		
	
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_email_folder' );
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
		$data = $this->get_data( );
		wp_cache_set( $id, $data, 'usam_email_folder' );		
		do_action( 'usam_email_folder_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_email_folder' );	
		do_action( 'usam_email_folder_delete_cache', $this );	
	}			

	/**
	 *  Удалить
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_email_folder_before_delete', $data );		
			
		$mailbox_id = $this->get( 'mailbox_id' );		
		$slug = $this->get( 'slug' );				
		
		$system_folders = usam_get_system_email_folders();
		$system_folders = array_keys($system_folders);			
		if ( !in_array($slug, $system_folders) )
		{
			$wpdb->delete( USAM_TABLE_EMAIL, array('mailbox_id' => $mailbox_id, 'folder' => $slug), array('%d','%s') );		
			$this->delete_cache();	
			$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_EMAIL_FOLDERS." WHERE id= '$id'");	
		}		
		do_action( 'usam_email_folder_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_EMAIL_FOLDERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{						
			$this->exists = true;
			$this->data = apply_filters( 'usam_email_folder_data', $data );			
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
		do_action( 'usam_email_folder_fetched', $this );	
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
		return apply_filters( 'usam_email_folder_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_email_folder_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_email_folder_set_properties', $this->data, $this );			
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

		do_action( 'usam_email_folder_pre_save', $this );	
		$where_col = $this->args['col'];				
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	
						
			do_action( 'usam_email_folder_pre_update', $this );				
			
			$where = array( $this->args['col'] => $where_format);	
				
			$this->data = apply_filters( 'usam_email_folder_update_data', $this->data );	
				
			$format = $this->get_data_format( );	
			$result = $wpdb->update( USAM_TABLE_EMAIL_FOLDERS, $this->data, array( $where_col => $where_val ), $format, $where_format );	
			if ( $result ) 
			{ 	
				$this->delete_cache( );			
			}
			do_action( 'usam_email_folder_update', $this );
		} 
		else 
		{   
			do_action( 'usam_email_folder_pre_insert' );				
			
			if ( !isset($this->data['mailbox_id']) )
				$this->data['mailbox_id'] = 0;	
			
			if ( empty($this->data['name']) )
				return false;
			
			if ( !isset($this->data['slug']) )
				$this->data['slug'] = sanitize_title($this->data['name']);			
			
			if ( !isset($this->data['count']) )
				$this->data['count'] = 0;	
			if ( !isset($this->data['not_read']) )
				$this->data['not_read'] = 0;	
			
			$this->data = apply_filters( 'usam_email_folder_insert_data', $this->data );			
			$format = $this->get_data_format( );				
			
			$result = $wpdb->insert( USAM_TABLE_EMAIL_FOLDERS, $this->data, $format );	
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );						
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );				
			}
			do_action( 'usam_email_folder_insert', $this );
		} 		
		do_action( 'usam_email_folder_save', $this );

		return $result;
	}	
}

// Получить
function usam_get_email_folder( $id )
{
	$email_folder = new USAM_Email_Folders( $id );
	return $email_folder->get_data( );	
}

// Добавить 
function usam_insert_email_folder( $data )
{
	$email_folder = new USAM_Email_Folders( $data );	
	$result = $email_folder->save();
	return $email_folder->get('id');
}

// Обновить 
function usam_update_email_folder( $id, $data )
{
	$email_folder = new USAM_Email_Folders( $id );
	$email_folder->set( $data );
	return $email_folder->save();
}

// Удалить
function usam_delete_email_folder( $id )
{
	$email_folder = new USAM_Email_Folders( $id );
	return $email_folder->delete();
}

function usam_get_system_email_folders( )
{
	$system_folders = array( 'inbox' => __('Входящие', 'usam'), 'drafts' => __('Черновики', 'usam'), 'sent' => __('Отправленные', 'usam'), 'spam' => __('Спам', 'usam'), 'deleted' => __('Удаленные', 'usam'), 'outbox' => __('Исходящие', 'usam') );
	return $system_folders;
}
?>