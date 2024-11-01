<?php
/**
 * Диалоги чата
 */ 
class USAM_Chat_Dialog
{
	private static $string_cols = array(
		'date_insert',	
		'channel',	
		'type',	
		'name',		//Если группа сохранить имя
	);
	private static $int_cols = array(
		'id',
		'channel_id',
		'contact_id',		
		'manager_id',		
	);
	private static $float_cols = array(	);	
		
	private $data     = array();		
	private $fetched  = false;
	private $args     = ['col'   => '', 'value' => ''];	
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
			$this->data = wp_cache_get( $value, 'usam_chat_dialog' );
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
		wp_cache_set( $id, $this->data, 'usam_chat_dialog' );		
		do_action( 'usam_chat_dialog_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_chat_dialog' );				
		do_action( 'usam_chat_dialog_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
			
		$id = $this->get( 'id' );			
		$this->delete_cache();	
		if ( $id )
			usam_delete_dialogs(['include' => $id]);
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CHAT_DIALOGS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_chat_dialog_data', $data );			
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
		do_action( 'usam_chat_dialog_fetched', $this );	
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
		return apply_filters( 'usam_chat_dialog_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_chat_dialog_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_chat_dialog_set_properties', $this->data, $this );			
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

		do_action( 'usam_chat_dialog_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{			
			$where_format = self::get_column_format( $this->args['col'] );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_chat_dialog_pre_update', $this );	

			$this->data = apply_filters( 'usam_chat_dialog_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_CHAT_DIALOGS, $this->data, [$this->args['col'] => $this->args['value']], $format, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );						
				do_action( 'usam_chat_dialog_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_chat_dialog_pre_insert' );		
			unset( $this->data['id'] );									
			if ( empty($this->data['contact_id']) )
				$this->data['contact_id'] = usam_get_contact_id();
			if ( empty($this->data['channel']) )
				$this->data['channel'] = 'chat';	
			if ( empty($this->data['type']) )
				$this->data['type'] = 'personal';
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			$this->data = apply_filters( 'usam_chat_dialog_insert_data', $this->data );			
			$format = $this->get_data_format();						
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_CHAT_DIALOGS, $this->data, $format );					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];			
				
				do_action( 'usam_chat_dialog_insert', $this );				
			}			
		} 		
		do_action( 'usam_chat_dialog_save', $this );
		return $result;
	}
}

// Обновить
function usam_update_chat_dialog( $id, $data )
{
	if ( $id && $data )
	{
		$chat_dialog = new USAM_Chat_Dialog( $id );	
		$chat_dialog->set( $data );
		return $chat_dialog->save();
	}
	return false;
}

// Получить
function usam_get_chat_dialog( $id, $colum = 'id' )
{
	$chat_dialog = new USAM_Chat_Dialog( $id, $colum );
	return $chat_dialog->get_data( );	
}

// Добавить
function usam_insert_chat_dialog( $data, $users = [] )
{	
	$chat_dialog = new USAM_Chat_Dialog( $data );
	$dialog_id = false;
	if ( $chat_dialog->save() )
	{
		$dialog_id = $chat_dialog->get('id');
		$contact_id = $chat_dialog->get('contact_id');
		if ( !in_array($contact_id, $users) )
			$users[] = $contact_id;
		global $wpdb;
		foreach( $users as $user )
		{
			if ( $user )
				$wpdb->insert( USAM_TABLE_CHAT_USERS, ['dialog_id' => $dialog_id, 'contact_id' => $user], ['%d', '%d'] );
		}
	}
	return $dialog_id;
}

function usam_delete_dialogs( $args ) 
{	
	global $wpdb;	
	require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');	
	require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');
	$dialogs = usam_get_chat_dialogs( $args );	
	if ( empty($dialogs) )
		return false;	
	
	$ids = array();
	foreach( $dialogs as $dialog )
	{
		do_action( 'usam_chat_dialog_before_delete', (array)$dialog );
		$ids[] = $dialog->id;
	}	
	if ( $ids )
	{	
		$number = 1000;
		do 
		{  
			$messages = usam_get_chat_messages(['dialog_id' => $ids, 'number' => $number]);
			$messages_ids = [];
			foreach( $messages as $message )
			{
				$messages_ids[] = $message->message_id;
			}
			if ( $messages_ids )
				$wpdb->query( "DELETE FROM " . USAM_TABLE_CHAT_MESSAGE_STATUSES . " WHERE message_id IN ('".implode("','", $messages_ids)."')" );
		} 
		while ( count($messages_ids) == $number );
		usam_delete_object_files( $ids, 'chat' );
		$wpdb->query( "DELETE FROM " . USAM_TABLE_CHAT . " WHERE dialog_id IN ('".implode("','", $ids)."')" );
		$wpdb->query( "DELETE FROM " . USAM_TABLE_CHAT_USERS . " WHERE dialog_id IN ('".implode("','", $ids)."')" );
		$wpdb->query( "DELETE FROM " . USAM_TABLE_CHAT_DIALOGS . " WHERE id IN ('".implode("','", $ids)."')" );		
		
		foreach( $dialogs as $dialog )
			do_action( 'usam_chat_dialog_delete', $dialog->id );
		return true;	
	}
	return false;
}

function usam_get_users_chat( $dialog_id )
{
	global $wpdb;
	$cache_key = 'usam_users_chat';
	if( ! $cache = wp_cache_get($cache_key, $dialog_id ) )
	{		
		$cache = $wpdb->get_results("SELECT * FROM `".USAM_TABLE_CHAT_USERS."` WHERE dialog_id=$dialog_id" );
		wp_cache_set( $cache_key, $cache, $dialog_id );
	}	
	return $cache;
}

function usam_get_chat_users( $id )
{
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare("SELECT contact_id FROM ".USAM_TABLE_CHAT_USERS." WHERE dialog_id=%d", $id) );	
	if ( $ids )
		$ids = array_map('intval', $ids);
	return $ids;
}
?>