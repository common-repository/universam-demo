<?php
/**
 * Класс контрагенты пользователей
 */
class USAM_User_Seller
{		
	 // строковые
	private static $string_cols = [
		'date_insert',
		'user_list',
	];
	// цифровые
	private static $int_cols = [
		'id',				
		'seller_id',
		'contact_id',		
	];		
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД
	
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
					
		$this->args = ['col' => $col, 'value' => $value];
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_user_seller' );
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
		wp_cache_set( $id, $this->data, 'usam_user_seller' );		
		do_action( 'usam_user_seller_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_user_seller' );		
		do_action( 'usam_user_seller_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_user_seller_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_USER_SELLERS." WHERE id = '$id'");	
		$this->delete_cache( );	
		do_action( 'usam_user_seller_delete', $data );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_USER_SELLERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_user_seller_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_user_seller_fetched', $this );	
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
		return apply_filters( 'usam_user_seller_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_user_seller_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_user_seller_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
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

		do_action( 'usam_user_seller_pre_save', $this );								
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_user_seller_pre_update', $this );			

			$this->data = apply_filters( 'usam_user_seller_update_data', $this->data );	
			$format = $this->get_data_format( );
			
			$result = $wpdb->update( USAM_TABLE_USER_SELLERS, $this->data, [$this->args['col'] => $this->args['value']], $format, $where_format );	
			if ( $result ) 
			{
				$this->delete_cache( );			
				do_action( 'usam_user_seller_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_user_seller_pre_insert' );			
			
			unset( $this->data['id'] );
			if ( empty($this->data['contact_id']))
				$this->data['contact_id'] = usam_get_contact_id();			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );					
			$this->data = apply_filters( 'usam_user_seller_insert_data', $this->data );
			$format = $this->get_data_format();
			$result = $wpdb->insert( USAM_TABLE_USER_SELLERS, $this->data, $format );
			if ( $result ) 
			{ 
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_user_seller_insert', $this );				
			}			
		} 		
		do_action( 'usam_user_seller_save', $this );
		return $result;
	}	
}

// Обновить 
function usam_update_user_seller( $id, $data )
{
	$user_seller = new USAM_User_Seller( $id );
	$user_seller->set( $data );
	return $user_seller->save();
}

// Получить
function usam_get_user_seller( $id, $colum = 'id' )
{
	$user_seller = new USAM_User_Seller( $id, $colum );
	return $user_seller->get_data( );	
}

// Добавить
function usam_insert_user_seller( $data )
{
	$user_seller = new USAM_User_Seller( $data );
	return $user_seller->save();
}

// Удалить 
function usam_delete_user_seller( $id )
{
	$user_seller = new USAM_User_Seller( $id );
	return $user_seller->delete();
}
?>