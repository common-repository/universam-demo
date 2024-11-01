<?php
/**
 * Чат
 */ 
class USAM_Chat
{
	 // строковые
	private static $string_cols = [	
		'message',					
		'date_insert',	
		'type_message',			
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'dialog_id',	
		'contact_id',
		'guid'
	];
	// рациональные	
	private $data    = array();		
	private $fetched = false;
	private $args    = ['col' => '', 'value' => ''];	
	private $exists  = false; 
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
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_chat_message' );
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
		if ( in_array($col, self::$string_cols) )
			return '%s';

		if ( in_array($col, self::$int_cols) )
			return '%d';	
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache(  ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_chat_message' );		
		do_action( 'usam_chat_message_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_chat_message' );				
		do_action( 'usam_chat_message_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_chat_message_before_delete', $data );
			
		$this->delete_cache( );	
		
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
		usam_delete_ribbon(['event_id' => $ids, 'event_type' => 'chat']);
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CHAT_MESSAGE_STATUSES." WHERE message_id = '$id'");
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CHAT." WHERE id = '$id'");		
		
		do_action( 'usam_chat_message_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CHAT." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_chat_message_data', $data );			
			$this->fetched = true;		
			$this->update_cache( );	
		}			
		do_action( 'usam_chat_message_fetched', $this );	
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
		return apply_filters( 'usam_chat_message_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_chat_message_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_chat_message_set_properties', $this->data, $this );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( ) 
	{
		$formats = [];
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

		do_action( 'usam_chat_message_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{		
			$where_format = self::get_column_format( $this->args['col'] );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_chat_message_pre_update', $this );	
			
			$this->data = apply_filters( 'usam_chat_message_update_data', $this->data );			
			$format = $this->get_data_format();	
			
			$result = $wpdb->update( USAM_TABLE_CHAT, $this->data, [$this->args['col'] => $this->args['value']], $format, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );						
				do_action( 'usam_chat_message_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_chat_message_pre_insert' );		
			unset( $this->data['id'] );	
			
			if ( empty($this->data['contact_id']) )
				$this->data['contact_id'] = usam_get_contact_id();
			
			if ( !isset($this->data['message']) )
				return false;
			
			if ( empty($this->data['dialog_id']) )
				return false;
			
			if ( empty($this->data['guid']) )
				$this->data['guid'] = 0;
						
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );
			$this->data = apply_filters( 'usam_chat_message_insert_data', $this->data );			
			$format = $this->get_data_format();			
			$result = $wpdb->insert( USAM_TABLE_CHAT, $this->data, $format );				
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];					
				do_action( 'usam_chat_message_insert', $this );			
			}			
		} 		
		do_action( 'usam_chat_message_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_chat_message( $id, $data )
{
	$chat_message = new USAM_Chat( $id );	
	$chat_message->set( $data );
	return $chat_message->save();
}

// Получить
function usam_get_chat_message( $id, $colum = 'id' )
{
	$chat_message = new USAM_Chat( $id, $colum );
	return $chat_message->get_data( );	
}

// Добавить
function usam_insert_chat_message( $data, $status = 1 )
{	
	global $wpdb;
	
	$id = false;
	if ( empty($data['contact_id']) )
		$data['contact_id'] = usam_get_contact_id();
	if ( empty($data['dialog_id']) )
	{
		$data['dialog_id'] = usam_insert_chat_dialog($data, [ $data['contact_id'] ]);
		$contacts[$data['contact_id']] = 0;
	}
	else
	{	
		$results = $wpdb->get_results("SELECT contact_id, not_read FROM `".USAM_TABLE_CHAT_USERS."` WHERE dialog_id=".$data['dialog_id']);
		$contacts = []; 
		foreach ( $results as $result ) 
			$contacts[$result->contact_id] = $result->not_read;
						
		if ( !isset($contacts[$data['contact_id']]) && current_user_can('view_chat') )
		{	
			$wpdb->insert( USAM_TABLE_CHAT_USERS, ['dialog_id' => $data['dialog_id'], 'contact_id' => $data['contact_id']], ['%d', '%d'] );
			$contacts[$data['contact_id']] = 0;
		}
	}
	if ( isset($contacts[$data['contact_id']]) )
	{			
		$chat_message = new USAM_Chat( $data );			
		if ( $chat_message->save() )
		{		
			$id = $chat_message->get('id');
			$wpdb->insert( USAM_TABLE_CHAT_MESSAGE_STATUSES, ['message_id' => $id, 'status' => $status, 'contact_id' => $data['contact_id']]);
			foreach ( $contacts as $contact_id => $not_read ) 
			{ 
				if ( $contact_id != $data['contact_id']  )
				{
					$not_read++;
					$wpdb->query("UPDATE ".USAM_TABLE_CHAT_USERS." SET not_read=$not_read WHERE contact_id=".$contact_id." AND dialog_id=".$data['dialog_id']);
				}
			}		
		}
	}
	return $id;
}

// Удалить
function usam_delete_chat_message( $id )
{
	$chat_message = new USAM_Chat( $id );
	return $chat_message->delete();
}

function usam_get_number_new_message_dialogues( ) 
{
	global $wpdb;
	
	$contact_id = usam_get_contact_id();		
	return (int)$wpdb->get_var("SELECT SUM(not_read) FROM ".USAM_TABLE_CHAT_USERS." WHERE contact_id=$contact_id");	
}

function usam_update_status_chat_messages( $dialog_id, $contact_id, $read_ids )
{
	global $wpdb;
	if ( $read_ids )
	{
		$result = $wpdb->query("UPDATE ".USAM_TABLE_CHAT_MESSAGE_STATUSES." SET status=2 WHERE (status=1 OR status=0) AND contact_id!=$contact_id AND message_id IN (".implode(',',$read_ids).")");
		if ( $result )
		{	
			$not_read = $wpdb->get_var("SELECT COUNT(s.status) FROM `".USAM_TABLE_CHAT."` AS c INNER JOIN ".USAM_TABLE_CHAT_MESSAGE_STATUSES." AS s ON (c.id=s.message_id) WHERE c.contact_id!=$contact_id AND c.dialog_id=$dialog_id AND s.status=1");
			$wpdb->query("UPDATE ".USAM_TABLE_CHAT_USERS." SET not_read=$not_read WHERE contact_id=$contact_id AND dialog_id=$dialog_id");
		}
	}	
}
?>