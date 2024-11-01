<?php
/**
 * Продавцы
 */ 
class USAM_Seller
{
	private static $string_cols = [
		'seller_type',
		'date_insert',		
		'name',
	];
	private static $int_cols = [
		'id',	
		'customer_id',
		'manager_id',		
		'rating',
		'number_products',		
	];	
	private static $float_cols = [];		
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
		if ( ! in_array( $col, ['id'] ) )
			return;		
					
		$this->args = ['col' => $col, 'value' => $value];		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_seller' );
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
		do_action( 'usam_seller_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_seller' );				
		do_action( 'usam_seller_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_seller_before_delete', $data );
		
		$this->delete_cache( );						
		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SELLERS." WHERE id = '$id'");					
		
		do_action( 'usam_seller_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SELLERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_seller_data', $data );			
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
		do_action( 'usam_seller_fetched', $this );	
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
		return apply_filters( 'usam_seller_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_seller_get_data', $this->data, $this );
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
		
		$properties = apply_filters( 'usam_seller_set_properties', $properties, $this );	
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
	* Вернуть формат столбцов таблицы
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

		do_action( 'usam_seller_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
					
			$where_format = self::get_column_format( $this->args['col'] );			
			do_action( 'usam_seller_pre_update', $this );				

			$this->data = apply_filters( 'usam_seller_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		
			$result = $wpdb->update( USAM_TABLE_SELLERS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_seller_update', $this );
		} 
		else 
		{   
			do_action( 'usam_seller_pre_insert' );		
			unset( $this->data['id'] );
					
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );									
			$this->data = apply_filters( 'usam_seller_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );										
			$result = $wpdb->insert( USAM_TABLE_SELLERS, $this->data, $formats );			
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];			
				
				do_action( 'usam_seller_insert', $this );				
			}	
		} 		
		do_action( 'usam_seller_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить
function usam_update_seller( $id, $data )
{
	$seller = new USAM_Seller( $id );	
	$seller->set( $data );
	return $seller->save();
}

// Получить
function usam_get_seller( $id, $colum = 'id' )
{		
	$seller = new USAM_Seller( $id, $colum );
	$data = $seller->get_data();	
	return $data;
}

function usam_get_seller_data( $id, $colum = 'id' )
{		
	if( !$id )
		return [];
	$seller = new USAM_Seller( $id, $colum );
	$data = $seller->get_data();				
	if ( !$data )
		return [];		
		
	if( $data['seller_type'] == 'company' )
	{
		$seller_data = usam_get_company( $data['customer_id'] );
		if ( !$seller_data )
			return [];
		$seller_data['url'] = usam_get_company_url( $data['customer_id'] );
	}
	else
	{
		$seller_data = usam_get_contact( $data['customer_id'] );	
		if ( !$seller_data )
			return [];
		$seller_data['url'] = usam_get_contact_url( $data['customer_id'] );
	}
	$seller_data['seller_type'] = $data['seller_type'];
	$seller_data['seller_id'] = $id;
	$seller_data['rating'] = $data['rating'];
	$seller_data['date_insert'] = $data['date_insert'];
	return $seller_data;
}

// Добавить
function usam_insert_seller( $data )
{
	$seller = new USAM_Seller( $data );
	$seller->save();
	return $seller->get('id');
}

// Удалить
function usam_delete_seller( $id )
{
	$seller = new USAM_Seller( $id );
	return $seller->delete();
}

function usam_get_seller_statuses(  ) 
{
	return ['pending' => __('В ожидании', 'usam'), 'approved' => __('Одобрен', 'usam'), 'rejected' => __('Отклонен', 'usam'), 'blocked' => __('Заблокирован', 'usam')];
}

function usam_get_seller_status_name( $key_status ) 
{
	$statuses = usam_get_seller_statuses( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}

function usam_get_logo_seller( $seller, $size = [100, 100] ) 
{
	$seller = (array)$seller;
	if ( !empty($seller) )
	{ 
		if ( $seller['seller_type'] == 'company' )
			return usam_get_company_logo( $seller['customer_id'], $size );		
		else
			return usam_get_contact_foto( $seller['customer_id'], 'id', $size );
	}
	return false;
}

function usam_get_seller_link( $slug, $path = '' ) 
{
	$permalinks = get_option( 'usam_permalinks' );
	$seller_base = empty($permalinks['seller_base']) ? 'seller' : $permalinks['seller_base'];
	if ( $path )
		return home_url($seller_base."/$slug/$path");
	else
		return home_url($seller_base.'/'.$slug);
}


function usam_get_seller_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('seller', $object_id, USAM_TABLE_SELLER_META, $meta_key, $single );
}

function usam_update_seller_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('seller', $object_id, $meta_key, $meta_value, USAM_TABLE_SELLER_META, $prev_value );
}

function usam_delete_seller_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('seller', $object_id, $meta_key, USAM_TABLE_SELLER_META, $meta_value, $delete_all );
}

function usam_add_seller_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('seller', $object_id, $meta_key, $meta_value, USAM_TABLE_SELLER_META, $prev_value );
}
?>