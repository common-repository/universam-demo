<?php
class USAM_Discount_Rule
{	
	 // строковые
	private static $string_cols = array(	
		'name',
		'description',
		'code',
		'type_rule',
		'dtype',
		'start_date',
		'end_date',		
		'condition',
		'date_insert',	
		'term_slug',
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'active',	
		'priority',
		'end',	
		'product_id',	
		'parent_id',
		'included',		
	);
	// цифровые
	private static $float_cols = array(		
		'discount',		
	);
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
	private $data    = array();
	private $changed_data = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_discount_rule_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}			
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_discount_rule' );			
		} 
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_discount_rule' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_discount_rule_code' );	
		do_action( 'usam_discount_rule_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		$id = $this->get('id');		
		wp_cache_delete( $id, 'usam_discount_rule' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_discount_rule_code' );
		do_action( 'usam_discount_rule_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');
		if ( !$id )
			return false;
		
		$data = $this->get_data();
		do_action( 'usam_discount_rule_before_delete', $data );
		
		if( $this->get('type_rule') == 'fix_price' )
			$wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCT_PRICE." WHERE meta_key = 'fix_price_{$id}'");
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_DISCOUNT_RULE_META . " WHERE rule_id = %d", $id ) );	
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS . " WHERE discount_id = %d", $id ) );	
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_DISCOUNT_BASKET . " WHERE discount_order_id = %d", $id ) );			
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_DISCOUNT_RULES . " WHERE id = %d", $id ) );		
		
		$this->delete_cache( );	
		do_action( 'usam_discount_rule_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_DISCOUNT_RULES . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_discount_rule_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->update_cache( );	
		} 
		do_action( 'usam_discount_rule_fetched', $this );
		$this->fetched = true;
	}	

	/**
	 * Проверить существует ли строка в БД
	 */
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства из БД
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_discount_rule_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_discount_rule_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_discount_rule_set_properties', $properties, $this );			
		if ( ! is_array($this->data) )
			$this->data = array();
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
	 * Вернуть формат столбцов таблицы
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

		do_action( 'usam_discount_rule_pre_save', $this );	
		
		$where_col = $this->args['col'];
		$result = false;		
		if ( $where_col ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;
			
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );			
	
			do_action( 'usam_discount_rule_pre_update', $this );					
			$where = array( $this->args['col'] => $where_format);

			$this->data = apply_filters( 'usam_discount_rule_update_data', $this->data );			
			
			$format = $this->get_data_format( );				
													
			foreach( $this->data as $key => $value)
			{				
				if (  $key == 'date_insert' )
					continue;
				
				if (  $key == 'start_date' || $key == 'end_date' )
					if ( empty($value) )
						$set[] = "`{$key}`=NULL";
					else					
						$set[] = "`{$key}`='".date( "Y-m-d H:i:s", strtotime( $value ) )."'";
				else
					$set[] = "`{$key}`='{$value}'";						
			}			
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_DISCOUNT_RULES."` SET ".implode( ', ', $set )." WHERE $where_col ='$where_format'", $where_val) );	
			if ( $result ) 
			{	
				$this->delete_cache( );					
				do_action( 'usam_discount_rule_update', $this );				
			}			
		} 
		else 
		{   // создание	
			do_action( 'usam_discount_rule_pre_insert' );	
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );														
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			if ( isset($this->data['start_date']) && empty($this->data['start_date']) )
				unset($this->data['start_date']);
			
			if ( isset($this->data['end_date']) && empty($this->data['end_date']) )
				unset($this->data['end_date']);
			
			if ( empty($this->data['description']) )
				$this->data['description'] = '';		

			if ( empty($this->data['parent_id']) )
				$this->data['parent_id'] = 0;			
			
			$this->data = apply_filters( 'usam_discount_rule_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_DISCOUNT_RULES, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_discount_rule_insert', $this );
			}			
		} 		
		do_action( 'usam_discount_rule_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_discount_rule( $value, $colum = 'id' )
{	
	$discount_rule = new USAM_Discount_Rule($value, $colum);	
	$discount_rule_data = $discount_rule->get_data();	
	return $discount_rule_data;	
}

function usam_update_discount_rule( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$discount_rule = new USAM_Discount_Rule( $id );	
		$discount_rule->set( $data );	
		return $discount_rule->save();
	}
	return false;
}

function usam_insert_discount_rule( $value )
{	
	$discount_rule = new USAM_Discount_Rule( $value );	
	$discount_rule->save();
	$discount_rule_id = $discount_rule->get('id');	
	return $discount_rule_id;		 
}

function usam_delete_discount_rule( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Discount_Rule( $value, $colum );
	return $subscriber->delete();		 
}

function usam_get_discount_rule_link( $id, $type = 'basket' )
{
	$url = admin_url("admin.php?page=manage_discounts&tab={$type}&form=edit&form_name=product_{$type}&id={$id}");	
	return $url;
}

function usam_get_discount_rule_name( $order_discount, $type_price = false )
{
	if ( !$type_price )
		$type_price = usam_get_manager_type_price();
	$interval = '';
	if ( !empty($order_discount->start_date) && $order_discount->start_date != '0000-00-00 00:00:00' )	
		$interval =  __('с', 'usam').' '.usam_local_date($order_discount->start_date, "d.m.Y");		
	
	if ( !empty($order_discount->end_date) && $order_discount->end_date != '0000-00-00 00:00:00' )
	{
		if ( $interval != '' )
			$interval .= ' ';
		$interval .= __('по', 'usam').' '.usam_local_date($order_discount->end_date, "d.m.Y");	
	}
	if ( $interval != '' )
		$interval = ' '.__('действует', 'usam').' '.$interval;
	
	return esc_html($order_discount->name).' '.round($order_discount->discount,2).($order_discount->dtype=='p'?'%':usam_get_currency_sign_price_by_code( $type_price )).$interval;
}

function usam_copy_discount_rule( $id, $args = [] ) 
{
	$discount = usam_get_discount_rule( $id );
	$discount['active'] = 0;	
	$discount = array_merge( $discount, $args );		
	$new_id = usam_insert_discount_rule( $discount );	
	$metadata = usam_get_discount_rule_metadata( $id );
	foreach ( $metadata as $value )
		usam_update_discount_rule_metadata($new_id, $value->meta_key, maybe_unserialize($value->meta_value) );
		
	return $new_id;
}

function usam_get_discount_rule_metadata( $discount_rule_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('rule', $discount_rule_id, USAM_TABLE_DISCOUNT_RULE_META, $meta_key, $single );
}

function usam_update_discount_rule_metadata($discount_rule_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('rule', $discount_rule_id, $meta_key, $meta_value, USAM_TABLE_DISCOUNT_RULE_META, $prev_value );
}

function usam_delete_discount_rule_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('rule', $object_id, $meta_key, USAM_TABLE_DISCOUNT_RULE_META, $meta_value, $delete_all );
}