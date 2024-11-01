<?php
/**
 * Документы оплаты
 */ 
class USAM_Payment_Document
{
	 // строковые
	private static $string_cols = array(
		'name',		
		'number',	
		'date_payed',
		'transactid',
		'external_document',
		'date_insert',	
		'payment_type',	
		'status',
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'document_id',			
		'gateway_id',		
		'bank_account_id',
		'manager_id',		
	);
	// рациональные
	private static $float_cols = array(
		'sum',		
	);			
	private $changed_data = [];
	private $data     = [];		
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
		if ( ! in_array( $col, ['id', 'number', 'transactid'] ) )
			return;		
					
		$this->args = ['col' => $col, 'value' => $value];				
		if ( $col == 'number'  && $id = wp_cache_get($value, 'usam_payment_document_number') )
		{  
			$col = 'id';
			$value = $id;
		}		
		if ( $col == 'transactid'  && $id = wp_cache_get($value, 'usam_payment_transactid') )
		{   
			$col = 'id';
			$value = $id;
		}		
		if ( $col == 'id' ) 
			$this->data = wp_cache_get( $value, 'usam_payment_document' );
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
		wp_cache_set( $id, $this->data, 'usam_payment_document' );		
		if ( $number = $this->get( 'number' ) )
			wp_cache_set( $number, $id, 'usam_payment_document_number' );
		if ( $transactid = $this->get( 'transactid' ) )
			wp_cache_set( $transactid, $id, 'usam_payment_transactid' );
		do_action( 'usam_payment_document_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_payment_document' );	
		wp_cache_delete( $this->get( 'number' ), 'usam_payment_document_number' );	
		wp_cache_delete( $this->get( 'transactid' ), 'usam_payment_transactid' );			
		do_action( 'usam_payment_document_delete_cache', $this );	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PAYMENT_HISTORY." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_payment_document_data', $data );			
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
		do_action( 'usam_payment_document_fetched', $this );	
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
		return apply_filters( 'usam_payment_document_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();
		return apply_filters( 'usam_payment_document_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_payment_document_set_properties', $properties, $this );
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
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

		do_action( 'usam_payment_document_pre_save', $this );	
		
		if ( isset($this->changed_data['status']) && $this->data['status'] == 3 )
			$this->set(['date_payed' => date( "Y-m-d H:i:s" )]);
		elseif ( isset($this->changed_data['status']) && $this->data['status'] != 3 )
			$this->set(['date_payed' => '']);
		elseif ( !empty($this->data['date_payed']) )
			$this->set(['status' => 3, 'date_payed' => '']);
		if ( !empty($this->data['gateway_id']) )
		{
			$payment_gateway = usam_get_payment_gateway( $this->data['gateway_id'] );		
			$this->set(['name' => $payment_gateway['name']]);	
		}					
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
						
			$where_format = self::get_column_format( $this->args['col'] );				
			do_action( 'usam_payment_document_pre_update', $this );	

			$this->data = apply_filters( 'usam_payment_document_update_data', $this->data );			
			$data = $this->get_update_data();				
			$set = [];
			foreach( $data as $key => $value)
			{										
				if ( $key == 'date_payed' )
					if ( empty($value) )
						$set[] = "`{$key}`=NULL";
					else					
						$set[] = "`{$key}`='".date( "Y-m-d H:i:s", strtotime( $value ) )."'";
				else
					$set[] = "`{$key}`='{$value}'";						
			}
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_PAYMENT_HISTORY."` SET ".implode( ', ', $set )." WHERE ".$this->args['col']." ='$where_format'", $this->args['value']) );
			if ( $result ) 
			{
				$this->delete_cache( );							
				if ( isset($this->changed_data['status']) || isset($this->changed_data['sum']) )
					$this->mark_order_as_paid( );	
				if ( isset($this->changed_data['status']) ) 
				{		
					$current_status = $this->get( 'status' );					
					do_action( 'usam_update_payment_document_status', $current_status, $this->changed_data['status'], $this );						
					usam_update_object_count_status( $current_status, 'payment' );
					usam_update_object_count_status( $this->changed_data['status'], 'payment' );						
				}
			}
			do_action( 'usam_payment_document_update', $this );	
		} 
		else 
		{   
			do_action( 'usam_payment_document_pre_insert' );		
			unset( $this->data['id'] );						
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			if ( empty($this->data['gateway_id']) )
				$this->data['gateway_id'] = 0;
			
			if ( !isset($this->data['transactid']) )
				$this->data['transactid'] = '';
			
			if ( !isset($this->data['status']) )
				$this->data['status'] = 1;
			
			if ( isset($this->data['date_payed']) && $this->data['date_payed'] == '' )
				unset($this->data['date_payed']);
			
			if ( empty($this->data['bank_account_id']) )
				$this->data['bank_account_id'] = get_option( 'usam_shop_company', 0 );	
			
			if ( !isset($this->data['sum']) )
			{
				$this->data['sum'] = 0;
				if ( isset($this->data['document_id']) )
				{
					$order = usam_get_order( $this->data['document_id'] );
					if ( !empty($order) )
						$this->data['sum'] = $order['totalprice'];
				}				
			}
			if ( empty($this->data['payment_type']) )
				$this->data['payment_type'] = 'card';
			if ( empty($this->data['number']) )
				$this->data['number'] = usam_get_document_number( 'payment' );
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date("Y-m-d H:i:s");			

			$this->data = apply_filters( 'usam_payment_document_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );				
			$result = $wpdb->insert( USAM_TABLE_PAYMENT_HISTORY, $this->data, $formats );
			if ( $result ) 
			{					
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				$this->mark_order_as_paid( );
				usam_update_object_count_status( $this->data['status'], 'payment' );
				do_action( 'usam_payment_document_insert', $this );
			}			
		} 		
		do_action( 'usam_payment_document_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	private function mark_order_as_paid()
	{		
		$order_id = $this->get('document_id');	
		if ( $order_id )
		{
			wp_cache_delete( $order_id, 'usam_payment_order' );
			$order = new USAM_Order( $order_id );
			$order->mark_order_as_paid();
			$order->save( );
			do_action('usam_order_save', $order_id);
		}
	}
}

// Обновить документ оплаты
function usam_update_payment_document( $document_id, $data, $colum = 'id' )
{
	$_payment = new USAM_Payment_Document( $document_id, $colum );
	$_payment->set( $data );
	return $_payment->save();
}

// Получить документ оплаты
function usam_get_payment_document( $document_id, $colum = 'id' )
{
	$_payment = new USAM_Payment_Document( $document_id, $colum );
	$result = $_payment->get_data( );		
	return $result;	
}

// Добавить документ оплаты
function usam_insert_payment_document( $data, $link = [] )
{
	$_payment = new USAM_Payment_Document( $data );
	$id = 0;
	if ( $_payment->save() )
	{
		$id = $_payment->get('id');
		if ( $link )
		{
			$link['document_link_id'] = $id;
			$link['document_link_type'] = 'payment';
			usam_add_document_link( $link );
		}
	}
	return $id;
}

// Удалить документ оплаты
function usam_delete_payment_document( $document_id )
{
	if ( $document_id )
		return usam_delete_payments(['include' => [$document_id]]);
	else
		return false;
}

function usam_add_payment_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('payment', $object_id, $meta_key, $meta_value, USAM_TABLE_PAYMENT_HISTORY_META, $prev_value );
}

function usam_get_payment_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('payment', $object_id, USAM_TABLE_PAYMENT_HISTORY_META, $meta_key, $single );
}

function usam_update_payment_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('payment', $object_id, $meta_key, $meta_value, USAM_TABLE_PAYMENT_HISTORY_META, $prev_value );
}

function usam_delete_payment_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('payment', $object_id, $meta_key, USAM_TABLE_PAYMENT_HISTORY_META, $meta_value, $delete_all );
}

function usam_delete_payments( $args, $delete = false )
{	
	global $wpdb;
	if ( empty($args) )
		return 0;
	$args['fields'] = ['id', 'status'];
	require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
	$payments = usam_get_payments( $args );
	if ( empty($payments) )
		return 0;
	
	$statuses = [];
	usam_update_object_count_status( false );
	$delete_ids = [];
	foreach ( $payments as $payment ) 
	{
		usam_update_object_count_status( $payment->status, 'payment' );
		$delete_ids[] = $payment->id;
		do_action( 'usam_payment_document_before_delete', (array)$payment );	
	}
	$in = implode( ', ', $delete_ids );
		
	$wpdb->query("DELETE FROM " . USAM_TABLE_CHANGE_HISTORY . " WHERE object_id IN ($in) AND object_type='payment'");
	$wpdb->query("DELETE FROM " . USAM_TABLE_PAYMENT_HISTORY_META . " WHERE payment_id IN ($in)");
	$wpdb->query("DELETE FROM " . USAM_TABLE_PAYMENT_HISTORY . " WHERE id IN ($in)");
	$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE document_id IN (".implode(',',$delete_ids).") AND document_type='payment' OR document_link_id IN (".implode(',',$delete_ids).") AND document_link_type='payment'");	
	
	foreach ( $delete_ids as $id )
	{
		do_action( 'usam_payment_document_delete', $id );		
		wp_cache_delete( $id, 'usam_payment_document' );		
	}	
	usam_update_object_count_status( true );
	return count($delete_ids);
}
?>