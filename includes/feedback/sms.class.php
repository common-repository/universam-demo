<?php
/**
 * Класс СМС сообщений.
 */
class USAM_SMS
{		
	 // строковые
	private static $string_cols = array(
		'phone',		
		'message',
		'date_insert',		
		'sent_at',		
		'folder',	
		'server_message_id',
		'user_id',
	);
	// цифровые
	private static $int_cols = [
		'id',
	];	
	private $data = array();		
	private $fetched = false;
	private $args = ['col' => '', 'value' => ''];	
	private $exists = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', 'server_message_id' ) ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'server_message_id'  && $id = wp_cache_get( $value, 'usam_sms_server_message_id' ) )
		{  
			$col = 'id';
			$value = $id;
		}		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_sms' ); 
		}			
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_sms' );
		if ( $server_message_id = $this->get( 'server_message_id' ) )
			wp_cache_set( $server_message_id, $id, 'usam_sms_server_message_id' );	
		do_action( 'usam_sms_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_sms' );
		wp_cache_delete( $this->get( 'server_message_id' ), 'usam_sms_server_message_id' );		
		do_action( 'usam_sms_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;	
			
		if ( $this->exists() == false )
			return;
		
		$id = $this->get( 'id' );
		$folder = $this->get( 'folder' );		
		if ( $folder != 'deleted' )
		{
			$this->set(['folder' => 'deleted']);
			$this->save();
		}
		else
		{
			$data = $this->get_data();
			do_action( 'usam_sms_before_delete', $data );
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_delete_ribbon(['event_id' => $ids, 'event_type' => 'sms']);
			$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SMS." WHERE id = '$id'");
			$this->delete_cache( );		
			
			do_action( 'usam_sms_delete', $id );
		}
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SMS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_sms_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_sms_fetched', $this );	
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
		return apply_filters( 'usam_sms_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_sms_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_sms_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
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

		do_action( 'usam_sms_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_sms_pre_update', $this );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			$this->data = apply_filters( 'usam_sms_update_data', $this->data );	
			$format = $this->get_data_format( );			
			$data = $this->data;
			
			$str = array();
			foreach ( $format as $key => $value ) 
			{
				if ( $data[$key] === null )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";	
			}			
			$sql = "UPDATE `".USAM_TABLE_SMS."` SET ".implode( ', ', $str )." WHERE $where_col = '$where_format' ";
			$result = $wpdb->query( $wpdb->prepare( $sql, array_merge( array_values( $data ), array( $where_val ) ) ) );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_sms_update', $this );
		} 
		else 
		{   
			do_action( 'usam_sms_pre_insert' );					
			unset( $this->data['id'] );			
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( empty($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();					
			$this->data = apply_filters( 'usam_sms_insert_data', $this->data );
			$format = $this->get_data_format(  );					
			$result = $wpdb->insert( USAM_TABLE_SMS, $this->data, $format );			
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
			}
			do_action( 'usam_sms_insert', $this );
		} 		
		do_action( 'usam_sms_save', $this );

		return $result;
	}
}

function usam_update_sms( $id, $data )
{
	$_sms = new USAM_SMS( $id );
	$_sms->set( $data );
	return $_sms->save();
}

// Получить 
function usam_get_sms( $id, $colum = 'id' )
{
	$_sms = new USAM_SMS( $id, $colum );
	$data = $_sms->get_data( ); 
	return $data;	
}

function usam_insert_sms( $data, $links = [] )
{
	$id = false;
	$_sms = new USAM_SMS( $data );	
	if ( $_sms->save() )
	{
		$id = $_sms->get('id');
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
		usam_insert_ribbon(['event_id' => $id, 'event_type' => 'sms'], $links);		
	}
	return $id;
}


// Удалить 
function usam_delete_sms( $id )
{
	$_sms = new USAM_SMS( $id );
	return $_sms->delete();
}
?>