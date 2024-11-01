<?php
class USAM_Customer_Account
{
	// строковые
	private static $string_cols = [
		'date_insert',
		'status',
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'user_id',
		'sum',			
	];		
	private $changed_data = [];	
	private $data = array();		
	private $fetched = false;
	private $args = ['col' => '', 'value' => ''];	
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
		if ( ! in_array( $col, ['id','user_id'] ) )
			return;		
			
		$this->args = array( 'col' => $col, 'value' => $value );				
		if ( $col == 'user_id'  && $user_id = wp_cache_get( $value, 'usam_customer_account_userid' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $user_id;
		}
		if ( $col == 'id' ) 
		{		
			$this->data = wp_cache_get( $value, 'usam_customer_account' );
		}			
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;			
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
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_customer_account' );	
		if ( $user_id = $this->get( 'user_id' ) )
			wp_cache_set( $user_id, $id, 'usam_customer_account_userid' );	
		do_action( 'usam_customer_account_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'user_id' ), 'usam_customer_account_userid' );
		wp_cache_delete( $this->get( 'id' ), 'usam_customer_account' );	
		do_action( 'usam_customer_account_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_customer_account_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CUSTOMER_ACCOUNTS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_customer_account_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CUSTOMER_ACCOUNTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_customer_account_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}			
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_customer_account_fetched', $this );	
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
		return apply_filters( 'usam_customer_account_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_customer_account_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_customer_account_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = [];
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */	
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;
		}
		return $formats;
	}	
	
	private function get_update_data( ) 
	{		
		$data = [];		
		foreach( $this->changed_data as $key => $value ) 
		{							
			if( self::get_column_format( $key ) !== false )
				$data[$key] = $this->data[$key];
		}
		return $data;
	}
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_customer_account_pre_save', $this );	
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_customer_account_pre_update', $this );		

			$this->data = apply_filters( 'usam_customer_account_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_CUSTOMER_ACCOUNTS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				foreach( $this->changed_data as $key => $value ) 
				{ 
					if ( isset($this->data[$key]) )
						$this->change_history( 'edit', $key, $this->data[$key], $value );
				}
				$this->delete_cache( );			
				do_action( 'usam_customer_account_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_customer_account_pre_insert' );			
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );				
			if ( empty($this->data['user_id']) )
				return false;				
			$this->data = apply_filters( 'usam_customer_account_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );
			$result = $wpdb->insert( USAM_TABLE_CUSTOMER_ACCOUNTS, $this->data, $formats );
			if ( $result ) 
			{
				$this->args = ['col' => 'id', 'value' => $wpdb->insert_id];	
				do_action( 'usam_customer_account_insert', $this );				
			}			
		}  		
		do_action( 'usam_customer_account_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	private function change_history( $operation, $field = '', $value = '', $old_value = '' ) 
	{ 	
		usam_insert_change_history(['object_id' => $this->data['id'], 'object_type' => 'customer_account', 'operation' => $operation, 'field' => $field, 'value' => $value, 'old_value' => $old_value]);
	}
}

function usam_get_customer_account( $id, $colum = 'id' )
{
	$customer_account = new USAM_Customer_Account( $id, $colum );
	return $customer_account->get_data( );	
}

function usam_delete_customer_account( $id ) 
{
	$customer_account = new USAM_Customer_Account( $id );
	$result = $customer_account->delete( );
	return $result;
}

function usam_insert_customer_account( $data ) 
{
	$customer_account = new USAM_Customer_Account( $data );
	$customer_account->save();
	return $customer_account->get('id');
}

function usam_update_customer_account( $id, $data ) 
{
	$customer_account = new USAM_Customer_Account( $id );
	$customer_account->set( $data );
	return $customer_account->save();
}

function usam_get_statuses_customer_account(  ) 
{
	return ['active' => __('Активен', 'usam'), 'blocked' => __('Заблокирован', 'usam')];
}

function usam_get_customer_account_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_customer_account( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}
?>