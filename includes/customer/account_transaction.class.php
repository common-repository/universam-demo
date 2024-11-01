<?php
require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
/**
 * Класс тразакции по клиентским счетам
 */
 class USAM_Account_Transaction
{
	private static $string_cols = [
		'description',
		'date_insert',
		'transaction_code',		
	];
	private static $int_cols = [
		'id',		
		'account_id',
		'order_id',	
		'user_id',			
		'sum',	
		'type_transaction',				
	];
	private $data     = [];		
	private $changed_data = [];
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_account_transaction' );
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
	
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_account_transaction' );				
		do_action( 'usam_account_transaction_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_account_transaction' );		
		do_action( 'usam_account_transaction_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_account_transaction_before_delete', $data );
				
		$this->delete_cache( );			
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_ACCOUNT_TRANSACTIONS." WHERE id = '$id'");		
		$account = usam_get_customer_account( $data['account_id'] ); 
		if ( $data['type_transaction'] )
			$account['sum'] += $data['sum'];
		else
			$account['sum'] -= $data['sum'];						
		usam_update_customer_account( $account['id'], ['sum' => $account['sum']]);	
		
		do_action( 'usam_account_transaction_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_ACCOUNT_TRANSACTIONS." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_account_transaction_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;	
			$this->update_cache( );	
		}			
		do_action( 'usam_account_transaction_fetched', $this );	
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
		return apply_filters( 'usam_account_transaction_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_account_transaction_get_data', $this->data, $this );
	}

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
		
		$properties = apply_filters( 'usam_account_transaction_set_properties', $properties, $this );			
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );	
				$this->data[$key] = $value;						
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;	
				}
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

		do_action( 'usam_account_transaction_pre_save', $this );			
		$result = false;	
		if( $this->args['col'] ) 
		{	
			if ( isset($this->data) )
				unset($this->data['date_insert']);				
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_account_transaction_pre_update', $this );	

			$this->data = apply_filters( 'usam_account_transaction_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_ACCOUNT_TRANSACTIONS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 				
				$this->delete_cache( );		
				$account_id = $this->get('account_id');
				$account = usam_get_customer_account( $account_id ); 			
				if ( !empty($account) && $account['status'] == 'active' )
				{			
					if ( isset($this->changed_data['type_transaction']) || isset($this->changed_data['sum']))
					{
						if ( $this->data['type_transaction'] )
						{
							if ( !isset($this->changed_data['type_transaction']) )
								$account['sum'] = $account['sum'] + $this->changed_data['sum'] - $this->data['sum'];
							elseif ( !isset($this->changed_data['sum']) )
								$account['sum'] = $account['sum'] - $this->data['sum'] - $this->data['sum'];
							else
								$account['sum'] = $account['sum'] - $this->changed_data['sum'] - $this->data['sum'];
						}
						else
						{
							if ( !isset($this->changed_data['type_transaction']) )
								$account['sum'] = $account['sum'] - $this->changed_data['sum'] + $this->data['sum'];
							elseif ( !isset($this->changed_data['sum']) )
								$account['sum'] = $account['sum'] + $this->data['sum'] + $this->data['sum'];
							else
								$account['sum'] = $account['sum'] + $this->changed_data['sum'] + $this->data['sum'];					
						}		
						usam_update_customer_account( $account['account_id'], ['sum' => $account['sum']]);
					}
				}										
			}
			do_action( 'usam_account_transaction_update', $this );
		} 
		else 
		{   	
			do_action( 'usam_account_transaction_pre_insert' );		
				
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );		
			if ( empty($this->data['sum']) || empty($this->data['account_id']) )
				return false;
						
			$account = usam_get_customer_account( $this->data['account_id'] ); 	
			if ( empty($account) || $account['status'] != 'active' )
				return false;
			
			if ( !isset($this->data['order_id']) )
				$this->data['order_id'] = 0;
			
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();
			
			if ( !isset($this->data['type_transaction']) )
				$this->data['type_transaction'] = 0;
						
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );				

			$this->data = apply_filters( 'usam_account_transaction_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );	
			$result = $wpdb->insert( USAM_TABLE_ACCOUNT_TRANSACTIONS, $this->data, $formats );					
			if ( $result ) 
			{									
				$this->set('id', $wpdb->insert_id);			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				if ( $this->data['type_transaction'] )
					$account['sum'] -= $this->data['sum'];
				else
					$account['sum'] += $this->data['sum'];					
				usam_update_customer_account( $account['id'], ['sum' => $account['sum']]);					
			}
			do_action( 'usam_account_transaction_insert', $this );
		} 		
		do_action( 'usam_account_transaction_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить
function usam_update_account_transaction( $id, $data )
{
	if ( empty($id) || empty($data) )
		return false;
	
	$_account_transaction = new USAM_Account_Transaction( $id );
	$_account_transaction->set( $data );
	return $_account_transaction->save();
}

// Получить
function usam_get_account_transaction( $id, $colum = 'id' )
{
	$_account_transaction = new USAM_Account_Transaction( $id, $colum );
	return $_account_transaction->get_data( );	
}

// Добавить
function usam_insert_account_transaction( $data )
{	
	$_account_transaction = new USAM_Account_Transaction( $data );
	$_account_transaction->save();		
	return $_account_transaction->get('id');
}

// Удалить
function usam_delete_account_transaction( $id )
{
	$_account_transaction = new USAM_Account_Transaction( $id );
	return $_account_transaction->delete();
}	
?>