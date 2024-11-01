<?php
class USAM_Bonus_Card
{
	// строковые
	private static $string_cols = [
		'date_insert',		
		'status',
	];
	// цифровые
	private static $int_cols = [
		'code',		
		'user_id',	
		'sum',			
	];		
	private static $float_cols = ['percent'];
	
	private $changed_data = [];	
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД
	
	public function __construct( $value = false, $col = 'code' ) 
	{ 
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, ['code', 'user_id']) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'user_id'  && $code = wp_cache_get( $value, 'usam_bonus_card_userid' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'code';
			$value = $code;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'code' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_bonus_card' );
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
		$code = $this->get( 'code' );
		wp_cache_set( $code, $this->data, 'usam_bonus_card' );	
		if ( $user_id = $this->get( 'user_id' ) )
			wp_cache_set( $user_id, $code, 'usam_bonus_card_userid' );	
		do_action( 'usam_bonus_card_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'user_id' ), 'usam_bonus_card_userid' );
		wp_cache_delete( $this->get( 'code' ), 'usam_bonus_card' );	
		do_action( 'usam_bonus_card_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$code = $this->get('code');
		$data = $this->get_data();
		do_action( 'usam_bonus_card_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_BONUS_CARDS." WHERE code = '$code'");		
		
		$this->delete_cache( );		
		do_action( 'usam_bonus_card_delete', $code );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_BONUS_CARDS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_bonus_card_data', $data );	
			foreach ( $this->data as $key => $value ) 
			{			
				$format = self::get_column_format( $key );
				if ( $format === '%d' )		
					$this->data[$key] = (int)$value;
				elseif ( $format === '%f' )
					$this->data[$key] = (float)$value;	
			}			
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_bonus_card_fetched', $this );	
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
		return apply_filters( 'usam_bonus_card_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_bonus_card_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_bonus_card_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = [];
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

		do_action( 'usam_bonus_card_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_bonus_card_pre_update', $this );		

			$this->data = apply_filters( 'usam_bonus_card_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_BONUS_CARDS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				foreach( $this->changed_data as $key => $value ) 
				{ 
					if ( isset($this->data[$key]) )
						$this->change_history( 'edit', $key, $this->data[$key], $value );
				}
				$this->delete_cache( );			
				do_action( 'usam_bonus_card_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_bonus_card_pre_insert' );			
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( empty($this->data['code']) )
				$this->data['code'] = usam_generate_bonus_card();
			
			if ( empty($this->data['user_id']) )
				return false;	
				
			$result = $wpdb->get_var( "SELECT code FROM ".USAM_TABLE_BONUS_CARDS." WHERE code ='".$this->data['code']."' OR user_id ='".$this->data['user_id']."'" );
			if ( $result ) 
				return false;	
			
			$this->data = apply_filters( 'usam_bonus_card_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );
			$result = $wpdb->insert( USAM_TABLE_BONUS_CARDS, $this->data, $formats );
			if ( $result ) 
			{
				$this->args = ['col' => 'code', 'value' => $this->data['code']];				
			}
			do_action( 'usam_bonus_card_insert', $this );
		} 		
		do_action( 'usam_bonus_card_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	private function change_history( $operation, $field = '', $value = '', $old_value = '' ) 
	{ 	
		usam_insert_change_history(['object_id' => $this->data['code'], 'object_type' => 'bonus_card', 'operation' => $operation, 'field' => $field, 'value' => $value, 'old_value' => $old_value]);
	}
}

function usam_get_bonus_card( $code, $colum = 'code' )
{
	$bonus_card = new USAM_Bonus_Card( $code, $colum );
	return $bonus_card->get_data( );	
}

function usam_delete_bonus_card( $code ) 
{
	$bonus_card = new USAM_Bonus_Card( $code );
	$result = $bonus_card->delete( );
	return $result;
}

function usam_insert_bonus_card( $data ) 
{
	$bonus_card = new USAM_Bonus_Card( $data );
	if ( $bonus_card->save() )
		return $bonus_card->get('code');
	else
		return false;
}

function usam_update_bonus_card( $code, $data ) 
{
	$bonus_card = new USAM_Bonus_Card( $code );
	$bonus_card->set( $data );
	return $bonus_card->save();
}

function usam_get_statuses_bonus_card(  ) 
{
	return ['inactive' => __('Не активна', 'usam'), 'active' => __('Активна', 'usam'), 'blocked' => __('Заблокирована', 'usam')];
}

function usam_get_bonus_card_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_bonus_card( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}

function usam_get_available_user_bonuses( $user_id = 0 )
{
	if ( $user_id == 0 )
		$user_id = get_current_user_id();	
		
	if ( $user_id == 0 )
		return 0;
	
	$bonus_card = usam_get_bonus_card( $user_id, 'user_id' ); 		
	if ( empty($bonus_card['sum']) )
		return 0;
	
	return $bonus_card['sum'];
}

function usam_generate_bonus_card( $format = '*********' )
{	
	require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php' );
	$str = preg_replace('/[^\*]/', '', $format);
	$strlen = strlen ($str);		
	$code = preg_replace('/[\*]/', '', $format);	
	do 
	{
		$bonus_card_code = $code.usam_rand_string( $strlen, '1234567890' );			
		$bonus_cards = usam_get_bonus_cards(['include' => $bonus_card_code]);
		if ( empty($bonus_cards) )
			break;
	}	
	while ( true );
	return $bonus_card_code;
}
?>