<?php
/**
 * Комиссия маркетплейса
 */ 
class USAM_Marketplace_Commission
{
	 // строковые
	private static $string_cols = array(
		'status',	
		'date_insert',
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'seller_id',	
		'order_id',		
	);

	// рациональные
	private static $float_cols = array(	'sum' );	
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
			$this->data = wp_cache_get( $value, 'usam_marketplace_commission' );
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
		do_action( 'usam_marketplace_commission_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_marketplace_commission' );				
		do_action( 'usam_marketplace_commission_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_marketplace_commission_before_delete', $data );
		
		$this->delete_cache( );						
		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_MARKETPLACE_COMMISSIONS." WHERE id = '$id'");
		
		do_action( 'usam_marketplace_commission_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_MARKETPLACE_COMMISSIONS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_marketplace_commission_data', $data );			
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
		do_action( 'usam_marketplace_commission_fetched', $this );	
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
		return apply_filters( 'usam_marketplace_commission_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_marketplace_commission_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_marketplace_commission_set_properties', $this->data, $this );			
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

		do_action( 'usam_marketplace_commission_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	
			
			do_action( 'usam_marketplace_commission_pre_update', $this );				
			$where = array( $where_col => $where_val );			

			$this->data = apply_filters( 'usam_marketplace_commission_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_MARKETPLACE_COMMISSIONS, $this->data, $where, $format, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_marketplace_commission_update', $this );
		} 
		else 
		{   
			do_action( 'usam_marketplace_commission_pre_insert' );		
			unset( $this->data['id'] );			
			
			if ( empty($this->data['seller_id']) )
				return false;
										
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );
			$this->data = apply_filters( 'usam_marketplace_commission_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
				
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_MARKETPLACE_COMMISSIONS, $this->data, $format );		
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id, );			
				
				do_action( 'usam_marketplace_commission_insert', $this );				
			}			
		} 		
		do_action( 'usam_marketplace_commission_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_marketplace_commission( $id, $data )
{
	$marketplace_commission = new USAM_Marketplace_Commission( $id );	
	$marketplace_commission->set( $data );
	return $marketplace_commission->save();
}

// Получить
function usam_get_marketplace_commission( $id, $colum = 'id' )
{
	$marketplace_commission = new USAM_Marketplace_Commission( $id, $colum );
	return $marketplace_commission->get_data( );	
}

// Добавить
function usam_insert_marketplace_commission( $data )
{
	$marketplace_commission = new USAM_Marketplace_Commission( $data );
	$marketplace_commission->save();
	return $marketplace_commission->get('id');
}

// Удалить
function usam_delete_marketplace_commission( $id )
{
	$marketplace_commission = new USAM_Marketplace_Commission( $id );
	return $marketplace_commission->delete();
}

function usam_get_statuses_marketplace_commission(  ) 
{
	return ['draft' => __('Не проведено','usam'), 'approved' => __('Проведено','usam')];
}

function usam_get_marketplace_commission_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_marketplace_commission( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}
?>