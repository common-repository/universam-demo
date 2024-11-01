<?php
class USAM_Subscription_Renewal
{
	// строковые
	private static $string_cols = array(
		'status',		
		'date_insert',
		'start_date',
		'end_date',
	);
	// цифровые
	private static $int_cols = array(
		'id',			
		'subscription_id',				
		'document_id',	
	);	
// рациональные
	private static $float_cols = [
		'sum',	
	];	
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
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_subscription_renewal' );
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
		wp_cache_set( $id, $this->data, 'usam_subscription_renewal' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_subscription_renewal_code' );
		do_action( 'usam_subscription_renewal_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_subscription_renewal' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_subscription_renewal_code' );	
		do_action( 'usam_subscription_renewal_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_subscription_renewal_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SUBSCRIPTION_RENEWAL." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_subscription_renewal_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SUBSCRIPTION_RENEWAL." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_subscription_renewal_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_subscription_renewal_fetched', $this );	
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
		return apply_filters( 'usam_subscription_renewal_get_subscription_renewal', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_subscription_renewal_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_subscription_renewal_set_properties', $properties, $this );
	
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

		do_action( 'usam_subscription_renewal_pre_save', $this );		
		$result = false;	
		if ( $this->args['col'] ) 
		{
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_subscription_renewal_pre_update', $this );		

			$this->data = apply_filters( 'usam_subscription_renewal_update_data', $this->data );	
			$formats = $this->get_data_format();		
			$result = $wpdb->update( USAM_TABLE_SUBSCRIPTION_RENEWAL, $this->data, [$this->args['col'] => $this->args['value']], $formats, [ $where_format ] );	
			if ( $result ) 
			{
				$this->delete_cache( );			
				do_action( 'usam_subscription_renewal_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_subscription_renewal_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );				
			
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date("Y-m-d H:i:s");	
			$this->data = apply_filters( 'usam_subscription_renewal_insert_data', $this->data );
			$format = $this->get_data_format();
			
			if ( empty($this->data['subscription_id']) )
				return false;
			
			$result = $wpdb->insert( USAM_TABLE_SUBSCRIPTION_RENEWAL, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				$this->exists = true;
				do_action( 'usam_subscription_renewal_insert', $this );
			}			
		} 		
		do_action( 'usam_subscription_renewal_save', $this );

		return $result;
	}
}

function usam_get_subscription_renewal( $id, $colum = 'id' )
{ 
	$subscription_renewal = new USAM_Subscription_Renewal( $id, $colum );
	return $subscription_renewal->get_data( );	
}

function usam_delete_subscription_renewal( $id ) 
{
	$subscription_renewal = new USAM_Subscription_Renewal( $id );
	$result = $subscription_renewal->delete( );
	return $result;
}

function usam_insert_subscription_renewal( $data ) 
{
	$subscription_renewal = new USAM_Subscription_Renewal( $data );
	if ( $subscription_renewal->save() )
		return $subscription_renewal->get('id');
	return false;
}

function usam_update_subscription_renewal( $id, $data ) 
{
	$subscription_renewal = new USAM_Subscription_Renewal( $id );
	$subscription_renewal->set( $data );
	return $subscription_renewal->save();
}


function usam_get_renew_subscription_statuses(  ) 
{
	return array( 'not_paid' => __('Не оплачена','usam'), 'paid' => __('Оплачена','usam') );
}

function usam_get_status_name_renew_subscription( $status )
{	
	$statuses = usam_get_renew_subscription_statuses( );
	if ( isset($statuses[$status]) )
		return $statuses[$status];
	return '';
}
?>