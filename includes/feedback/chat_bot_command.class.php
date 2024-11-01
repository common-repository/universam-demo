<?php
/**
 * Шаблон чат-бота
 */ 
class USAM_Chat_Bot_Command
{
	 // строковые
	private static $string_cols = array(
		'message',			
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'template_id',
		'time_delay',
		'active',		
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
			$this->data = wp_cache_get( $value, 'usam_chat_bot_command' );
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
		wp_cache_set( $id, $this->data, 'usam_chat_bot_template' );		
		do_action( 'usam_chat_bot_command_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_chat_bot_command' );				
		do_action( 'usam_chat_bot_command_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_chat_bot_command_before_delete', $data );		
			
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CHAT_BOT_COMMANDS." WHERE id = '$id'");
		
		do_action( 'usam_chat_bot_command_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CHAT_BOT_COMMANDS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_chat_bot_command_data', $data );			
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
		do_action( 'usam_chat_bot_command_fetched', $this );	
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
		return apply_filters( 'usam_chat_bot_command_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_chat_bot_command_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_chat_bot_command_set_properties', $this->data, $this );			
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

		do_action( 'usam_chat_bot_command_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_chat_bot_command_pre_update', $this );				
			$where = array( $where_col => $where_val );			

			$this->data = apply_filters( 'usam_chat_bot_command_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_CHAT_BOT_COMMANDS, $this->data, $where, $format, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_chat_bot_command_update', $this );
		} 
		else 
		{   
			do_action( 'usam_chat_bot_command_pre_insert' );		
			unset( $this->data['id'] );						
						
			if ( !isset($this->data['template_id']) )
				return false;			
			
			$this->data = apply_filters( 'usam_chat_bot_command_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
					
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_CHAT_BOT_COMMANDS, $this->data, $format );
					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );			
				
				do_action( 'usam_chat_bot_command_insert', $this );				
			}			
		} 		
		do_action( 'usam_chat_bot_command_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_chat_bot_command( $id, $data )
{
	$chat_bot_command = new USAM_Chat_Bot_Command( $id );	
	$chat_bot_command->set( $data );
	return $chat_bot_command->save();
}

// Получить
function usam_get_chat_bot_command( $id, $colum = 'id' )
{
	$chat_bot_command = new USAM_Chat_Bot_Command( $id, $colum );
	return $chat_bot_command->get_data( );	
}

// Добавить
function usam_insert_chat_bot_command( $data )
{
	$chat_bot_command = new USAM_Chat_Bot_Command( $data );
	$chat_bot_command->save();
	return $chat_bot_command->get('id');
}

// Удалить
function usam_delete_chat_bot_command( $id )
{
	$chat_bot_command = new USAM_Chat_Bot_Command( $id );
	return $chat_bot_command->delete();
}

function usam_get_chat_bot_command_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('command', $object_id, USAM_TABLE_CHAT_BOT_COMMAND_META, $meta_key, $single );
}

function usam_update_chat_bot_command_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('command', $object_id, $meta_key, $meta_value, USAM_TABLE_CHAT_BOT_COMMAND_META, $prev_value );
}

function usam_delete_chat_bot_command_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('command', $object_id, $meta_key, USAM_TABLE_CHAT_BOT_COMMAND_META, $meta_value, $delete_all );
}
?>