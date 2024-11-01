<?php
// Класс работы с рассылкой
class USAM_Newsletter
{	
	// строковые
	private static $string_cols = array(
		'subject',	
		'template',
		'date_insert',	
		'sent_at',				
		'type',	
		'class',	
		'start_date',	
		'repeat_days',			
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'status',
		'mailbox_id',			
		'number_sent',			
		'number_opened',				
		'number_clicked',			
		'number_unsub',
		'number_bounce',		
		'number_forward',	
	);	
	private $changed_data = array();	
	private $data     = array();		
	private $products = null;		
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
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
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_newsletter' );
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
		wp_cache_set( $id, $this->data, 'usam_newsletter' );		
		do_action( 'usam_newsletter_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_newsletter' );	
		do_action( 'usam_newsletter_delete_cache', $this );	
	}			

	/**
	 *  Удалить
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_newsletter_before_delete', $data );
		
		$this->delete_cache( );				
		$wpdb->query( "DELETE FROM ".USAM_TABLE_NEWSLETTER_LISTS." WHERE newsletter_id = '$id'");	
		$wpdb->query( "DELETE FROM ".USAM_TABLE_NEWSLETTER_USER_STAT." WHERE newsletter_id = '$id'");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_NEWSLETTERS." WHERE id = '$id'");	
		
		do_action( 'usam_newsletter_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_NEWSLETTERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_newsletter_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache( );
		}			
		do_action( 'usam_newsletter_fetched', $this );	
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
		return apply_filters( 'usam_newsletter_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_newsletter_get_data', $this->data, $this );
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
							
		$properties = apply_filters( 'usam_newsletter_set_properties', $properties, $this );			
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

		do_action( 'usam_newsletter_pre_save', $this );		
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
			
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $this->args['col'] );							
			do_action( 'usam_newsletter_pre_update', $this );					
			$this->data = apply_filters( 'usam_newsletter_update_data', $this->data );	
						
			if ( isset($this->changed_data['status']) && $this->data['status'] == 6 && !empty($this->data['repeat_days'])  )
			{
				if ( empty($this->data['start_date']) )
					$this->data['start_date'] = date("Y-m-d H:i:s");
				$repeat_days = explode(' ', $this->data['repeat_days']); 
				$n = $repeat_days[0]?$repeat_days[0]:''; 
				$period_type = isset($repeat_days[1])?$repeat_days[1]:'day'; 
				$this->data['start_date'] = date("Y-m-d H:i:s", strtotime("+{$n} $period_type", strtotime($this->data['start_date'])));
				$this->data['status'] = 5;
			}
			if ( isset($this->changed_data['status']) && $this->data['status'] == 5 )
			{
				if ( $this->data['type'] == 'mail' ) 
					$type = 'email';
				elseif ( $this->data['type'] == 'sms' ) 
					$type = 'phone';		
				else
					$type = 'phone';
	
				if ( $this->data['class'] != 'trigger' )
					usam_add_list_newsletter_user_stat($this->data['id'], $type);
				if ( empty($this->data['start_date']) )
					$this->data['start_date'] = date("Y-m-d H:i:s");	
			}
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );	
			$result = $wpdb->update( USAM_TABLE_NEWSLETTERS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );				
			if ( $result ) 
				$this->delete_cache( );					
			do_action( 'usam_newsletter_update', $this );
		} 
		else 
		{   
			do_action( 'usam_newsletter_pre_insert' );							
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			if ( isset($this->data['sent_at']) )
				unset($this->data['sent_at']);
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( !isset($this->data['status']) )
				$this->data['status'] = 0;	
			if ( empty($this->data['type']) )
				$this->data['type'] = 'mail';	
			if ( empty($this->data['class']) )
				$this->data['class'] = 'simple';	
			if ( !isset($this->data['subject']) )
				$this->data['subject'] = '';			
			if ( !isset($this->data['mailbox_id']) )
				$this->data['mailbox_id'] = 0;

			$this->data['number_sent'] = 0;
			$this->data['number_opened'] = 0;
			$this->data['number_clicked'] = 0;
			$this->data['number_unsub'] = 0;
			$this->data['number_bounce'] = 0;
			$this->data['number_forward'] = 0;
			if ( empty($this->data['mailbox_id']) && $this->data['class'] == 'mail' )	
			{
				$mailbox = usam_get_primary_mailbox();
				$this->data['mailbox_id'] = $mailbox['id'];
			}					
			$this->data = apply_filters( 'usam_newsletter_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );			
			$result = $wpdb->insert( USAM_TABLE_NEWSLETTERS, $this->data, $formats );	
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );						
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_newsletter_insert', $this );
		} 		
		do_action( 'usam_newsletter_save', $this );
		$this->changed_data = [];
		return $result;
	}	
}

// Получить
function usam_get_newsletter( $id )
{
	$newsletter = new USAM_Newsletter( $id );
	return $newsletter->get_data( );	
}

// Добавить 
function usam_insert_newsletter( $data )
{
	$newsletter = new USAM_Newsletter( $data );	
	$result = $newsletter->save();
	return $newsletter->get('id');
}

// Обновить 
function usam_update_newsletter( $id, $data )
{
	if ( $id )
	{
		$newsletter = new USAM_Newsletter( $id );
		$newsletter->set( $data );
		return $newsletter->save();
	}
	else
		return false;
}

// Удалить
function usam_delete_newsletter( $id )
{
	$newsletter = new USAM_Newsletter( $id );
	return $newsletter->delete();
}


function usam_get_customer_newsletter_statuses(  ) 
{
	return ['open' => __('Открыли','usam'), 'not_open' => __('Не открыли','usam'), 'clicked' => __('Нажали','usam'), 'unsubscribed' => __('Отписались','usam')];
}

function usam_get_mailing_trigger_types() 
{
	return [
		'order' => ['title' => __('Заказ','usam'), 'triggers' => [
			'sale_dont_buy' => __('Давно не покупал','usam'), 
			'available_bonuses' => __('Есть доступные бонусы на счете','usam'), 			
			'order_status_change' => __('Изменение статуса заказа','usam'),
			'order_status' => __('Пока заказ в выбранном статусе','usam'),
			'order_paid' => __('Оплата заказа','usam'),
			'order_is_collected' => __('Сообщить о готовности заказа','usam'),
			'trackingid' => __('Отправить трек-номер','usam')
			],
		],
		'subscription' => ['title' => __('Подписки','usam'), 'triggers' => [
			'subscription_end' => __('Окончание подписки','usam'), 			
			'subscribe_to_newsletter' => __('Новому подписчику','usam'),
			//'adding_newsletter' => __('При добавлении в рассылку','usam'),
			],
		],
		'customer' => ['title' => __('Посетитель','usam'), 'triggers' => [
			'basket_forgotten' => __('Забытая корзина','usam'), 			
			'discount_favorites' => __('Сообщение о скидке на избранное','usam'),
			'sender_user_dontauth' => __('Давно не заходил на сайт','usam'),
			'sender_user_auth' => __('Заход на сайт','usam'),
			'webform' => __('Отправил запрос через веб-форму','usam'),
			],
		],
		'crm' => ['title' => __('CRM','usam'), 'triggers' => [
			'appeal_change' => __('Изменение статуса обращения','usam'), 			
			'response_letter' => __('Ответ на письмо','usam'),
			],
		],
		'product' => ['title' => __('Товар','usam'), 'triggers' => [
			'product_arrived' => __('Поступление отсутствующего товара','usam'), 
			],
		],
	];
}	

function usam_get_name_mailing_trigger_type( $trigger ) 
{
	$types = usam_get_mailing_trigger_types();
	$name = '';
	foreach ( $types as $type )
	{
		foreach ( $type['triggers'] as $key => $name )
		{
			if ( $trigger == $key )
				return $name;
		}
	}
	return $name;
}

function usam_get_newsletter_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('newsletter', $object_id, USAM_TABLE_NEWSLETTER_TEMPLATE_META, $meta_key, $single );
}

function usam_update_newsletter_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('newsletter', $object_id, $meta_key, $meta_value, USAM_TABLE_NEWSLETTER_TEMPLATE_META, $prev_value );
}

function usam_delete_newsletter_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('newsletter', $object_id, $meta_key, USAM_TABLE_NEWSLETTER_TEMPLATE_META, $meta_value, $delete_all );
}

function usam_add_newsletter_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('newsletter', $object_id, $meta_key, $meta_value, USAM_TABLE_NEWSLETTER_TEMPLATE_META, $prev_value );
}
?>