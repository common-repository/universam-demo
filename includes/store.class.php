<?php
 /**
 * Класс управления складом
 */ 
class USAM_Storage
{
	private static $string_cols = [
		'code',		
		'title',		
		'owner',
		'type_price',		
		'type',	
	];
	private static $int_cols = [
		'id',		
		'active',			
		'issuing',		
		'shipping',	
		'sort',	
		'location_id',		
	];
	private static $float_cols = [];	
	
	private $changed_data = [];
	private $data     = [];		
	private $products = [];		
	private $fetched  = false;
	private $args     = ['col' => '', 'value' => ''];	
	private $exists   = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, ['id', 'code'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_storage_code' ) )
		{ 
			$col = 'id';
			$value = $id;
		}		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_storage' );
		}			
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
		wp_cache_set( $id, $this->data, 'usam_storage' );		
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_storage_code' );
		do_action( 'usam_storage_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_storage' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_storage_code' );
		
		do_action( 'usam_storage_delete_cache', $this );	
	}
	
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );			
		$data = $this->get_data();
		do_action( 'usam_storage_before_delete', $data );
		
		$this->delete_cache( );	
		$wpdb->query("DELETE FROM ".USAM_TABLE_STOCK_BALANCES." WHERE meta_key = 'storage_{$id}' OR meta_key = 'reserve_{$id}'");
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_STORAGE_META . " WHERE storage_id = %d", $id ) );	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_STORAGES." WHERE id = '$id'");
		do_action( 'usam_storage_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_STORAGES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_storage_data', $data );		
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
		do_action( 'usam_storage_fetched', $this );	
		$this->fetched = true;			
	}

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
		return apply_filters( 'usam_storage_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_storage_get_data', $this->data, $this );
	}
	
	public function set( $key, $value = null ) 
	{	
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];
		}
		$properties = apply_filters( 'usam_storage_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = [];
		
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
		do_action( 'usam_storage_pre_save', $this );
		
		if ( isset($this->data['sort']) && $this->data['sort']>999 )		
			$this->data['sort'] = 100;	
		
		$result = false;	
		if( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
						
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_storage_pre_update', $this );
			$this->data = apply_filters( 'usam_storage_update_data', $this->data );			
			$data = $this->get_update_data();
			if ( !$data )
				return false;
			$formats = $this->get_data_format( $data );	 
			$result = $wpdb->update( USAM_TABLE_STORAGES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );				
				if( isset($this->changed_data['shipping']) || isset($this->changed_data['active']) )
				{
					usam_recalculate_stock_products();		
				}
				do_action( 'usam_storage_update', $this );
			}			
		} 
		else 
		{   
			do_action( 'usam_storage_pre_insert' );		
			unset( $this->data['id'] );	
						
			if ( !isset($this->data['code']) )		
			$this->data['code'] = '';

			if ( !isset($this->data['title']) )		
				$this->data['title'] = '';

			if ( !isset($this->data['active']) )		
				$this->data['active'] = 0;			
			
			if ( !isset($this->data['issuing']) )		
				$this->data['issuing'] = 0;
			
			if ( !isset($this->data['owner']) )		
				$this->data['owner'] = '';		

			if ( !isset($this->data['shipping']) )		
				$this->data['shipping'] = 1;
			
			$this->data = apply_filters( 'usam_storage_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );				
			$result = $wpdb->insert( USAM_TABLE_STORAGES, $this->data, $formats );	
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				do_action( 'usam_storage_insert', $this );				
			}			
		} 		
		do_action( 'usam_storage_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить склад
function usam_update_storage( $id, $data )
{
	$shipped = new USAM_Storage( $id );
	$shipped->set( $data );
	return $shipped->save();
}

// Получить склад
function usam_get_storage( $id, $column = 'id', $add_meta = false )
{
	if ( $column === true ) 
	{
		$column = 'id';
		$add_meta = true;
	}	
	$shipped = new USAM_Storage( $id, $column );
	$data = $shipped->get_data();
	if( $data && $add_meta )
	{
		$data['address'] = (string)usam_get_storage_metadata( $data['id'], 'address');
		$data['schedule'] = (string)usam_get_storage_metadata( $data['id'], 'schedule');
		$data['phone'] = (string)usam_get_storage_metadata( $data['id'], 'phone');
		$data['phone_format'] = !empty($data['phone']) ? (string)usam_phone_format( $data['phone'], '7 (999) 999 99 99') : '';
		$data['email'] = (string)usam_get_storage_metadata( $data['id'], 'email');
		$data['longitude'] = (float)usam_get_storage_metadata( $data['id'], 'longitude');
		$data['latitude'] = (float)usam_get_storage_metadata( $data['id'], 'latitude');		
		$location = usam_get_location( $data['location_id'] );
		$data['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
	}
	return $data;
}

// Добавить склад
function usam_insert_storage( $data )
{
	$shipped = new USAM_Storage( $data );
	$shipped->save();
	return $shipped->get('id');
}

// Удалить склад
function usam_delete_storage( $id )
{
	$shipped = new USAM_Storage( $id );
	return $shipped->delete();
}

function usam_get_store_field( $id, $fled )
{ 
	$_storage = new USAM_Storage( $id );
	$data = $_storage->get_data();
	
	if ( isset($data[$fled]) )
		return $data[$fled];
		
	return false;
}

function usam_get_storage_delivery_period( $id ) 
{			
	$period_from = absint(usam_get_storage_metadata( $id, 'period_from'));
	$period_to = absint(usam_get_storage_metadata( $id, 'period_to'));
	$period_type = usam_get_storage_metadata( $id, 'period_type');
	return usam_get_delivery_period( $period_from, $period_to, $period_type );
}


function usam_get_delivery_period( $period_from = 0, $period_to = 0, $period_type = 'day', $date = '') 
{			
	switch( $period_type ) 
	{				
		case 'hour':
			$type = __('недель','usam');
		break;
		case 'month':
			$type = __('месяцев','usam');
		break;
		default:
		case 'day':
			$type = __('дней','usam');
		break;
	}
	$delivery_period = '';
	if ( $period_to )
		$delivery_period = sprintf( __('от %s до %s %s', 'usam'), $period_from, $period_to, $type );
	elseif ( $period_from )
	{			
		if ( !$date )
			$date = date("Y-m-d H:i:s");	
		$timestamp = strtotime($date . ' +'.$period_from.' '.$period_type);
		if ( date('Y') == date('Y', $timestamp) )
			$delivery_period = usam_local_date( $timestamp, 'j F' );
		else
			$delivery_period = usam_local_date( $timestamp, 'd.m.Y' );		
	}
	return $delivery_period;
}


function usam_get_storage_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('storage', $object_id, USAM_TABLE_STORAGE_META, $meta_key, $single );
}

function usam_update_storage_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('storage', $object_id, $meta_key, $meta_value, USAM_TABLE_STORAGE_META, $prev_value );
}

function usam_delete_storage_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('storage', $object_id, $meta_key, USAM_TABLE_STORAGE_META, $meta_value, $delete_all );
}

function usam_delete_storages( $args ) 
{		
	global $wpdb;
	$args['active'] = 'all';
	$args['cache_results'] = false;
	$args['cache_meta'] = false;
	$storages = usam_get_storages( $args );
	if( !empty($storages) ) 
	{
		$storage_ids = [];
		foreach ( $storages as $storage )
		{
			do_action( 'usam_storage_before_delete', (array)$storage );
			$wpdb->query("DELETE FROM ".USAM_TABLE_STOCK_BALANCES." WHERE meta_key = 'storage_{$storage->id}' OR meta_key = 'reserve_{$storage->id}'");	
			$storage_ids[] = $storage->id;
		}
		$wpdb->query("DELETE FROM ".USAM_TABLE_STORAGE_META." WHERE storage_id IN (".implode(",",$storage_ids).")");		
		return $wpdb->query("DELETE FROM ".USAM_TABLE_STORAGES." WHERE id IN (".implode(",",$storage_ids).")");
	}
	return false;
}


function usam_get_storage_images( $id )
{
	$cache_key = "usam_storage_images";
	$attachments = wp_cache_get( $id, $cache_key );	
	if ($attachments === false) 
	{	
		$images = usam_get_storage_metadata( $id, 'images');
		if( $images )
		{
			$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC', 'update_post_meta_cache' => true, 'post__in' => $images]);
			foreach ( $attachments as $k => $attachment ) 
			{
				unset($attachments[$k]->guid);
				unset($attachments[$k]->to_ping);
				unset($attachments[$k]->pinged);
				unset($attachments[$k]->ping_status);
				unset($attachments[$k]->comment_count);
				unset($attachments[$k]->post_password);
				unset($attachments[$k]->post_content_filtered);
				$attachments[$k]->full = wp_get_attachment_image_url($attachment->ID, 'full' );	
				$attachments[$k]->thumbnail = wp_get_attachment_image_url($attachment->ID, 'thumbnail' );
			}
		}
		else
			$attachments = [];
		wp_cache_set($id, $attachments, $cache_key);
	}		
	return $attachments;
}
?>