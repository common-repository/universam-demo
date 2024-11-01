<?php
class USAM_Bank_Account
{
	// строковые
	private static $string_cols = array(
		'name',		
		'bic',
		'number',		
		'bank_ca',
		'currency',
		'address',
		'swift'
	);
	// цифровые
	private static $int_cols = array(
		'id',			
		'company_id',
	);
	private $data = array();		
	private $fetched           = false;
	private $args = array( 'col'   => '', 'value' => '' );	
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_bank_account' );
		}			
		// кэш существует
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
		wp_cache_set( $id, $this->data, 'usam_bank_account' );	
		do_action( 'usam_bank_account_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_bank_account' );	
		do_action( 'usam_bank_account_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_bank_account_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_ACC_NUMBER." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_bank_account_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COMPANY_ACC_NUMBER." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_bank_account_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_bank_account_fetched', $this );	
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
		return apply_filters( 'usam_bank_account_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_bank_account_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_bank_account_set_properties', $properties, $this );

		if ( !is_array($this->data) )
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

		do_action( 'usam_bank_account_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_bank_account_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_bank_account_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_COMPANY_ACC_NUMBER, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_bank_account_update', $this );
		} 
		else 
		{   
			do_action( 'usam_bank_account_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['company_id']) )
				return false;		
			
			if ( empty($this->data['number']) )
				return false;
			
			$this->data = apply_filters( 'usam_bank_account_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_COMPANY_ACC_NUMBER, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_bank_account_insert', $this );
		} 		
		do_action( 'usam_bank_account_save', $this );

		return $result;
	}
}

function usam_get_bank_account( $id, $colum = 'id' )
{
	$bank_account = new USAM_Bank_Account( $id, $colum );
	return $bank_account->get_data();	
}

function usam_delete_bank_account( $id ) 
{
	$bank_account = new USAM_Bank_Account( $id );
	$result = $bank_account->delete( );
	return $result;
}

// Вставить 
function usam_insert_bank_account( $data ) 
{
	$bank_account = new USAM_Bank_Account( $data );
	$bank_account->save();
	return $bank_account->get('id');
}

function usam_update_bank_account( $id, $data ) 
{
	$bank_account = new USAM_Bank_Account( $id );
	$bank_account->set( $data );
	return $bank_account->save();
}

function usam_get_display_company_by_acc_number( $bank_account_id, $link = true, $account = true )
{
	$bank_account = usam_get_bank_account( $bank_account_id );	
	$select_bank_account = '';
	if ( !empty($bank_account) )
	{
		$company = usam_get_company( $bank_account['company_id'] );	
		$select_bank_account = $company['name'];
		if ( $account )
		{
			$currency = usam_get_currency_sign( $bank_account['currency'] );
			$select_bank_account .= " - ".$bank_account['name']." ".$bank_account['number']." $currency";			
		}
		if ( $link )
			$select_bank_account = '<a href="'.usam_get_company_url( $bank_account['company_id'] ).'" target="_blank">'.$select_bank_account."</a>";
	}
	return $select_bank_account;
}

function usam_get_company_bank_accounts( $company_id ) 
{
	$object_type = 'usam_bank_accounts';
	$cache = array();
	if ( $company_id )
	{ 
		$cache = wp_cache_get( $company_id, $object_type );			
		if ( $cache === false )			
		{							
			global $wpdb;	
			$cache = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COMPANY_ACC_NUMBER." WHERE company_id = %d", $company_id) );
			wp_cache_set( $company_id, $cache, $object_type );						
		}
		else
			$cache = array();
	}
	return $cache;
}
?>