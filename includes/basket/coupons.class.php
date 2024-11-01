<?php
/**
 * Класс купона.
 */
class USAM_Coupon
{		
	 // строковые
	private static $string_cols = array(
		'coupon_code',		
		'action',
		'is_percentage',		
		'description',		
		'start_date',
		'end_date',
		'coupon_type',	
		'date_insert',	
		'amount_bonuses_author',		
	);
	// цифровые
	private static $int_cols = array(
		'id',
		'is_used',
		'max_is_used',		
		'active',
		'user_id',		
	);	
	// рациональные
	private static $float_cols = array(
		'value',		
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
		if ( ! in_array( $col, array( 'id', 'coupon_code' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'coupon_code'  && $id = wp_cache_get( $value, 'usam_coupon_code' ) )
		{   // если находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_coupon' );
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
		$data = $this->get_data( );
		wp_cache_set( $id, $data, 'usam_coupon' );
		if ( $coupon_code = $this->get( 'coupon_code' ) )
			wp_cache_set( $coupon_code, $id, 'usam_coupon_code' );	
		do_action( 'usam_coupon_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_coupon' );
		wp_cache_delete( $this->get( 'coupon_code' ), 'usam_coupon_code' );		
		do_action( 'usam_coupon_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_coupon_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COUPON_CODES." WHERE id = '$id'");
		$this->delete_cache( );		
		do_action( 'usam_coupon_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COUPON_CODES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_coupon_data', $data );			
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
		do_action( 'usam_coupon_fetched', $this );	
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
		return apply_filters( 'usam_coupon_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_coupon_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_coupon_set_properties', $properties, $this );
	
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

		do_action( 'usam_coupon_pre_save', $this );	
		$where_col = $this->args['col'];
			
		if ( isset($this->data['coupon_code']) )	
			$this->data['coupon_code'] = mb_strtoupper(sanitize_title( $this->data['coupon_code'] ));
		$result = false;	
		if ( $where_col ) 
		{	
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_coupon_pre_update', $this );	

			$this->data = apply_filters( 'usam_coupon_update_data', $this->data );	
			$formats = $this->get_data_format();
			$data = $this->data;
			
			$str = array();
			foreach ( $formats as $key => $value ) 
			{
				if ( !$data[$key] )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";	
			}			
			$result = $wpdb->query($wpdb->prepare("UPDATE `".USAM_TABLE_COUPON_CODES."` SET ".implode(',', $str)." WHERE $where_col='$where_format'", array_merge( array_values($data), [$this->args['value']] ) ) );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_coupon_update', $this );
		} 
		else 
		{   
			do_action( 'usam_coupon_pre_insert' );	
			
			if ( empty($this->data['coupon_type']))
				$this->data['coupon_type'] = 'coupon';	
			
			if ( empty($this->data['coupon_code']))
				$this->data['coupon_code'] = usam_generate_coupon_code();
			
			if ( $this->data['coupon_type'] != 'rule' )
			{
				$coupon = new USAM_Coupon( $this->data['coupon_code'], 'coupon_code' );	
				$id = $coupon->get('id');
				if ( !empty($id) )
				{	
					$this->args = array('col' => 'id', 'value' => $id );
					return $result;				
				}
			}
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			$this->data['is_used'] = 0;				
			unset( $this->data['id'] );				
			$this->data = apply_filters( 'usam_coupon_insert_data', $this->data );
			$formats = $this->get_data_format();
			$result = $wpdb->insert( USAM_TABLE_COUPON_CODES, $this->data, $formats );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_coupon_insert', $this );
		} 		
		do_action( 'usam_coupon_save', $this );

		return $result;
	}
}

function usam_get_coupon( $value, $colum = 'id' )
{	
	if ( empty($value) )
		return array();
	$coupon = new USAM_Coupon($value, $colum);	
	$data = $coupon->get_data();	
	if ( empty($data) )
		return array();
	
	return $data;	
}

function usam_update_coupon( $id, $data )
{		
	$coupon = new USAM_Coupon( $id );	
	$coupon->set( $data );	
	return $coupon->save();
}

function usam_insert_coupon( $value )
{	
	$coupon = new USAM_Coupon( $value );	
	$coupon->save();
	return $coupon->get('id');		 
}


function usam_get_coupon_metadata( $coupon_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('coupon', $coupon_id, USAM_TABLE_COUPON_CODE_META, $meta_key, $single );
}

function usam_update_coupon_metadata($coupon_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('coupon', $coupon_id, $meta_key, $meta_value, USAM_TABLE_COUPON_CODE_META, $prev_value );
}

function usam_delete_coupon_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('coupon', $object_id, $meta_key, USAM_TABLE_COUPON_CODE_META, $meta_value, $delete_all );
}

function usam_get_coupon_url( $coupon_code )
{
	return get_bloginfo('url').'/c/'.$coupon_code;
}

function usam_generate_coupon_code( $format = '**********', $type_format = 'n' )
{	
	require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
	switch ( $type_format )
	{
		case 'ln':		
			$chars = 'ABCDEFGHJKLMNOPQRSTUVWXYZ1234567890';		
		break;
		case 'l':		
			$chars = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';			
		break;		
		case 'n':		
		default:
			$chars = '1234567890';			
		break;
	}
	$str = preg_replace('/[^\*]/', '', $format);
	$strlen = strlen ($str);		
	$code = preg_replace('/[\*]/', '', $format);	
	do 
	{
		$coupon_code = $code.usam_rand_string( $strlen, $chars );	
		$coupons = usam_get_coupons( array( 'coupon_code' => $coupon_code ) );
		if ( empty($coupons) )
			break;
	}	
	while ( true );
	return $coupon_code;
}

function usam_get_coupons_rules( $args = array() )
{	
	$option = get_site_option('usam_coupons_roles');
	$rules = maybe_unserialize( $option );	
	if ( empty($rules) )
		$rules = array();
	if ( !empty($args) )
	{
		foreach ( $rules as $key => $rule )
		{
			if ( isset($args['rule_type']) && $args['rule_type'] != $rule['rule_type'] )
				unset($rules[$key]);
			elseif ( !empty($args['include']) && !in_array($rule['id'], $args['include']) )
				unset($rules[$key]);
			elseif ( !empty($args['roles']) && array_intersect($rule['roles'], $args['roles']) )
				unset($rules[$key]);
			elseif ( !empty($args['search']) && stripos($rule['title'], $args['search'])=== false )
				unset($rules[$key]);	
			elseif ( isset($args['active']) && $args['active'] != $rule['active'] )
				unset($rules[$key]);		
			elseif ( isset($args['user_id']) && !empty($rule['roles']) )
			{				
				if ( $args['user_id'] == 0 && !in_array('notloggedin', $rule['roles']) )	
					unset($rules[$key]);
				elseif ( $args['user_id'] )
				{							
					$user = get_userdata( $args['user_id'] );
					if ( !empty($user) )
					{
						$result_in_roles = array_intersect($rule['roles'], $user->roles);	
						if ( !empty($result_in_roles) )
							unset($rules[$key]);
					}
				}				
			}	
			if ( !empty($args['location']) && !empty($rule['sales_area']))
			{		
				if ( !usam_locations_in_sales_area( $args['location'], $rule['sales_area'] ) )
					unset($rules[$key]);				
			}
			elseif ( empty($args['location']) && !empty($rule['sales_area']))		
				unset($rules[$key]);
		}
	}
	return $rules;
}
?>