<?php
class USAM_WebForm 
{	
	 // строковые
	private static $string_cols = [
		'title',	
		'start_date',
		'end_date',		
		'template',
		'code',		
		'language',	
		'settings',
		'action'				
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'active',	
		'actuation_time',		
	];
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	private $data    = array();
	private $changed_data = [];		
	private $fetched = false;	
	private $args    = ['col' => '', 'value' => ''];
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
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_webform_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}			
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_webform' );			
		}
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
		wp_cache_set( $id, $this->data, 'usam_webform' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_webform_code' );	
		do_action( 'usam_webform_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_webform' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_webform_code' );
		do_action( 'usam_webform_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_webform_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_WEBFORMS . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_webform_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_WEBFORMS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$data['settings'] = maybe_unserialize($data['settings']);
			$this->exists = true;
			$this->data = apply_filters( 'usam_webform_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		}
		do_action( 'usam_webform_fetched', $this );
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
		return apply_filters( 'usam_webform_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_webform_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_webform_set_properties', $properties, $this );			
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
	
	public function save()
	{
		global $wpdb;

		do_action( 'usam_webform_pre_save', $this );	
		
		$result = false;		
		if ( $this->args['col'] ) 
		{			
			if ( empty($this->changed_data) )
				return true;			

			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_webform_pre_update', $this );
			$this->data = apply_filters( 'usam_webform_update_data', $this->data );			
			$data = $this->get_update_data();
			if ( !$data )
				return false;
			$formats = $this->get_data_format( $data );		
			$set = [];
			if( isset($data['settings']) )
				$data['settings'] = maybe_serialize($data['settings']);	
			foreach( $data as $key => $value)
			{										
				if( $key == 'start_date' || $key == 'end_date' )
					if ( empty($value) )
						$set[] = "`{$key}`=NULL";
					else					
						$set[] = "`{$key}`='".date( "Y-m-d H:i:s", strtotime( $value ) )."'";
				else
					$set[] = "`{$key}`='{$value}'";						
			}		
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_WEBFORMS."` SET ".implode( ', ', $set )." WHERE ".$this->args['col']." ='$where_format'", $this->args['value']) );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_webform_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_webform_pre_insert' );	
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			if ( empty($this->data['code']) )
				$this->data['code'] = '';	
			
			if ( empty($this->data['action']) )
				$this->data['action'] = 'contacting';			
											
			$this->data = apply_filters( 'usam_webform_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );		
			$data = $this->data;
			$data['settings'] = maybe_serialize($data['settings']);							
			$result = $wpdb->insert( USAM_TABLE_WEBFORMS, $data, $formats );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = array('col'   => 'id',  'value' => $this->get( 'id' ) );				
				do_action( 'usam_webform_insert', $this );
			}			
		} 		
		do_action( 'usam_webform_save', $this );
		return $result;
	}
}

function usam_get_webform( $value, $colum = 'id' )
{	
	$webform = new USAM_WebForm($value, $colum);	
	$webform_data = $webform->get_data();	
	return $webform_data;	
}

function usam_update_webform( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$webform = new USAM_WebForm( $id );	
		$webform->set( $data );	
		return $webform->save();
	}
	return true;
}

function usam_insert_webform( $value )
{	
	$webform = new USAM_WebForm( $value );	
	$webform->save();
	$webform_id = $webform->get('id');	
	return $webform_id;		 
}

function usam_delete_webform( $value, $colum = 'id' )
{	
	$subscriber = new USAM_WebForm( $value, $colum );
	return $subscriber->delete();		 
}

function usam_get_webform_theme( $code )
{	
	$cache_key = 'usam_webform';
	$cache = wp_cache_get( $code, $cache_key );		
	if ( $cache === false )		
	{	
		$webforms = usam_get_webforms_theme();
		foreach ( $webforms as $webform )
		{			
			$webform = (array)$webform;
			wp_cache_set( $webform['code'], $webform, 'usam_webform' );
			if ( $code == $webform['code'] )
				$cache = $webform;
		}
	}
	return $cache;
}

function usam_get_webforms_theme()
{	
	$cache_key = 'usam_webforms';
	$webforms = wp_cache_get( $cache_key );		
	if ( $webforms === false )		
	{	
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php' );
		$args = ['acting_now' => 1, 'cache_meta' => true];
		$language = usam_get_contact_language();
		if ( $language )
			$args['language'] = ['', $language];
		$webforms = usam_get_webforms( $args );
		wp_cache_set($cache_key, $webforms);		
	}
	return $webforms;
}

function usam_get_topic_webform( ) 
{ 
	$topic = array( 		
		'ask_question'     =>  __('Задать вопрос', 'usam'),
		'sale'             =>  __('Получить информацию об акциях, купонах, скидках', 'usam'),
		'product_info'     =>  __('Уточнить характеристики товара', 'usam'),
		'back_call'        =>  __('Обратный звонок', 'usam'),
		'notify_stock'     =>  __('Сообщить при поступлении товара', 'usam'),
		'shipping_info'    =>  __('Уточнить информацию по доставке заказа', 'usam'),
		'cancel_order'     =>  __('Отменить заказ', 'usam'),
		'return_product'   =>  __('Уточнить условия возврата товара', 'usam'),
		'unsubscribe'      =>  __('Отписать от рассылки', 'usam'),			
		'customer_reviews' =>  __('Оставить отзыв о работе магазина', 'usam'),
		'buy_product'      =>  __('Заказать товар', 'usam'),
		'error_site'       =>  __('Ошибка или некорректная работа сайта', 'usam'),
		'product_error'    =>  __('Ошибка в описании товара', 'usam'),	
		'price_comparison' =>  __('Есть дешевле?', 'usam'),			
	);		
	return $topic;
}