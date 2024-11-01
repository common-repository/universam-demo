<?php
/**
 * Обратная связь
 */ 
class USAM_Support_Message
{
	 // строковые
	private static $string_cols = array(
		'subject',		
		'message',		
		'date_insert',				
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'user_id',				
		'read',		
		'outbox',
		
	);
	// рациональные
	private static $float_cols = array(	);	
	/**
	 * Содержит значения извлекаются из БД
	 * @since 4.9
	 */	
	private $data     = array();		
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
	private $exists   = false; // если существует строка в БД
	
	/**
	 * Конструктор объекта
	 * @since 4.9	
	 */
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
			$this->data = wp_cache_get( $value, 'usam_support_message' );
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
		wp_cache_set( $id, $this->data, 'usam_support_message' );		
		do_action( 'usam_support_message_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_support_message' );				
		do_action( 'usam_support_message_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_support_message_before_delete', $data );
		
		$this->delete_cache( );	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SUPPORT_MESSAGE." WHERE id = '$id'");
		
		do_action( 'usam_support_message_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SUPPORT_MESSAGE." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_support_message_data', $data );			
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
		do_action( 'usam_support_message_fetched', $this );	
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
		return apply_filters( 'usam_support_message_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_support_message_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_support_message_set_properties', $this->data, $this );			
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

		do_action( 'usam_support_message_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			if ( isset($this->data['user_id']) )
				unset($this->data['user_id']);
			
			do_action( 'usam_support_message_pre_update', $this );		

			$this->data = apply_filters( 'usam_support_message_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_SUPPORT_MESSAGE, $this->data, array( $where_col => $where_val ), $format, $where_format );				
			if ( $result ) 
				$this->delete_cache( );					
			do_action( 'usam_support_message_update', $this );
		} 
		else 
		{   
			do_action( 'usam_support_message_pre_insert' );		
			unset( $this->data['id'] );	
							
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			$this->data['user_id'] = get_current_user_id();		
						
			$this->data = apply_filters( 'usam_support_message_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
					
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_SUPPORT_MESSAGE, $this->data, $format );
					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );				
			}
			do_action( 'usam_support_message_insert', $this );
		} 		
		do_action( 'usam_support_message_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_support_message( $id, $data )
{
	$support_message = new USAM_Support_Message( $id );	
	$support_message->set( $data );
	return $support_message->save();
}

// Получить
function usam_get_support_message( $id, $colum = 'id' )
{
	$support_message = new USAM_Support_Message( $id, $colum );
	return $support_message->get_data( );	
}

// Добавить
function usam_insert_support_message( $data )
{
	$support_message = new USAM_Support_Message( $data );
	$support_message->save();	
	return $support_message->get('id');	
}

// Удалить
function usam_delete_support_message( $id )
{
	$support_message = new USAM_Support_Message( $id );
	return $support_message->delete();
}

function usam_get_subject_support_message( )
{
	$types = array( 'info' => __('Помощь в настройке', 'usam'), 'error' => __('Сообщить об ошибке', 'usam'), 'order' => __('Заказ дополнения или темы', 'usam'), 'seo' => __('Заказ продвижения сайта', 'usam'), 'other' => __('Другое', 'usam') );
	return $types;
}
?>