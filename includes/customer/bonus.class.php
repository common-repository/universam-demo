<?php
/**
 * Класс бонусов
 */
 class USAM_Bonus
{
	private static $string_cols = [
		'description',
		'date_insert',
		'transaction_code',
		'object_type',
	];
	private static $int_cols = [
		'id',		
		'code',
		'object_id',	
		'user_id',			
		'sum',
		'type_transaction',				
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
		if ( !in_array( $col, ['id']) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_bonus' );
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
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_bonus' );				
		do_action( 'usam_bonus_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_bonus' );
		do_action( 'usam_bonus_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$result = usam_delete_bonuses_transaction(['include' => $id]);
		if ( $result )
			$this->delete_cache( );			
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;			
			$this->data = apply_filters( 'usam_bonus_data', $data );			
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
		do_action( 'usam_bonus_fetched', $this );	
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
	 * Возвращает значение указанного свойства	 * @since 4.9
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_bonus_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_bonus_get_data', $this->data, $this );
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
		
		$properties = apply_filters( 'usam_bonus_set_properties', $properties, $this );			
		foreach ( $properties as $key => $value ) 
		{
			$previous = $this->get( $key );			
			if ( $value != $previous )
			{				
				$this->changed_data[$key] = $previous;	
			}
		}			
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}							
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 * @since 4.9
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

		do_action( 'usam_bonus_pre_save', $this );	
		
		$result = false;	
		if( $this->args['col'] ) 
		{	
			if ( isset($this->data) )
				unset($this->data['date_insert']);				
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_bonus_pre_update', $this );			

			$this->data = apply_filters( 'usam_bonus_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_BONUS_TRANSACTIONS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 				
				$this->delete_cache( );		
				$code = $this->get('code');
				$bonus_card = usam_get_bonus_card( $code );
				if ( !empty($bonus_card) && $bonus_card['status'] == 'active' )
				{			
					if ( isset($this->changed_data['type_transaction']) || isset($this->changed_data['sum']))
					{
						if ( $this->data['type_transaction'] == 1 )
						{
							if ( !isset($this->changed_data['type_transaction']) )
							{
								$bonus_card['sum'] = $bonus_card['sum'] + $this->changed_data['sum'] - $this->data['sum'];
							}
							elseif ( !isset($this->changed_data['sum']) )
							{
								$bonus_card['sum'] = $bonus_card['sum'] - $this->data['sum'] - $this->data['sum'];
							}
							else
								$bonus_card['sum'] = $bonus_card['sum'] - $this->changed_data['sum'] - $this->data['sum'];
						}
						elseif ( $this->data['type_transaction'] == 0 )
						{
							if ( !isset($this->changed_data['type_transaction']) )
							{
								$bonus_card['sum'] = $bonus_card['sum'] - $this->changed_data['sum'] + $this->data['sum'];
							}
							elseif ( !isset($this->changed_data['sum']) )
							{
								$bonus_card['sum'] = $bonus_card['sum'] + $this->data['sum'] + $this->data['sum'];
							}
							else
								$bonus_card['sum'] = $bonus_card['sum'] + $this->changed_data['sum'] + $this->data['sum'];					
						}						
						usam_update_bonus_card( $bonus_card['code'], ['sum' => $bonus_card['sum']] );
					}
				}		
				do_action( 'usam_bonus_update', $this );				
			}			
		} 
		else 
		{ 
			do_action( 'usam_bonus_pre_insert' );		
			unset( $this->data['id'] );
			if ( empty($this->data['sum']) || empty($this->data['code']) )
				return false;
			
			$bonus_card = usam_get_bonus_card( $this->data['code'] ); 
			if ( empty($bonus_card) || $bonus_card['status'] != 'active' )
				return false;
			
			if ( empty($this->data['object_id']) || empty($this->data['object_type']) )
			{
				$this->data['object_id'] = 0;
				$this->data['object_type'] = '';
			}			
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();
			
			if ( !isset($this->data['type_transaction']) )
				$this->data['type_transaction'] = 0;
						
			$this->data['date_insert'] = date("Y-m-d H:i:s");			

			$this->data = apply_filters( 'usam_bonus_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );
			$result = $wpdb->insert( USAM_TABLE_BONUS_TRANSACTIONS, $this->data, $formats );					
			if ( $result ) 
			{									
				$this->set('id', $wpdb->insert_id);			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				
				if ( $this->data['type_transaction'] == 1 )
					$bonus_card['sum'] -= $this->data['sum'];
				elseif ( $this->data['type_transaction'] == 0 )			
					$bonus_card['sum'] += $this->data['sum'];
				usam_update_bonus_card( $bonus_card['code'], ['sum' => $bonus_card['sum']]);
				do_action( 'usam_bonus_insert', $this );
			}			
		} 		
		do_action( 'usam_bonus_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить бонус
function usam_update_bonus( $id, $data )
{
	if ( empty($id) || empty($data) )
		return false;
	
	$_bonus = new USAM_Bonus( $id );
	$_bonus->set( $data );
	return $_bonus->save();
}

// Получить бонус
function usam_get_bonus( $id, $colum = 'id' )
{
	$_bonus = new USAM_Bonus( $id, $colum );
	return $_bonus->get_data( );	
}

// Добавить бонус
function usam_insert_bonus( $data, $user_id = 0 )
{	
	if ( $user_id )
	{
		$bonus_card = usam_get_bonus_card( $user_id, 'user_id' ); 	
		if ( empty($bonus_card['code']) )
		{
			$option = get_option('usam_bonus_rules', [] );
			if ( !empty($option['generate_cards']) )
			{
				$code = usam_generate_bonus_card();
				usam_insert_bonus_card(['status' => 'active', 'user_id' => $user_id, 'code' => $code]);	
				$data['code'] = $code;
			}
			else
				return false;			
		}
		else
			$data['code'] = $bonus_card['code'];
	}		
	$_bonus = new USAM_Bonus( $data );
	$_bonus->save();		
	return $_bonus->get('id');
}

// Удалить бонус
function usam_delete_bonus( $id )
{
	$_bonus = new USAM_Bonus( $id );
	return $_bonus->delete();
}	

function usam_get_bonus_types( )
{
	$types = array( 'friends' =>  __('За приведенных знакомых','usam'), 'birthday' => __('За день рождения','usam'), 'review' => __('За отзыв','usam'), 'socnetwork' => __('За активность в группе','usam'), 'help' => __('За помощь нашему проекту','usam'), 'buy' => __('За покупку','usam'), 'register' => __('За регистрацию','usam'), 'accumulative' => __('По программе &laquo;Накопительные скидки&raquo;','usam'), 'discont' => __('За участие в &laquo;Программе скидок&raquo;','usam'), 'product' => __('За товар','usam'), 'coupon' => __('Использование купона в заказе','usam'), 'manager_order' => __('За выполненный заказ','usam') );	
	return $types;
}

function usam_get_bonus_type( $key )
{
	$types = usam_get_bonus_types();
	$result = false;
	if ( isset($types[$key]) )
		$result = $types[$key];
	
	return $result;
}

function usam_delete_bonuses_transaction( $args )
{
	global $wpdb;
	require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php' );
	require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );	
	$transactions = usam_get_bonuses( $args );	
	$result = false;
	if ( $transactions )
	{
		$cards = [];
		$ids = [];
		foreach ( $transactions as $transaction )
		{
			if ( $transaction->type_transaction == 1 || $transaction->type_transaction == 0 )
			{				
				if ( !isset($cards[$transaction->code]) )
					$cards[$transaction->code] = 0;			
				
				if ( $transaction->type_transaction == 1 )
					$cards[$transaction->code] -= $transaction->sum;
				elseif ( $transaction->type_transaction == 0 )
					$cards[$transaction->code] += $transaction->sum;
			}
			$ids[] = $transaction->id;			
			do_action( 'usam_bonus_before_delete', (array)$transaction );
		}
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE id IN (".implode(",",$ids).")");	
		if ( $cards )
		{
			$bonus_cards = usam_get_bonus_cards(['include' => array_keys($cards)]);	  
			foreach ( $bonus_cards as $card )
			{			
				usam_update_bonus_card( $card->code, ['sum' => $card->sum - $cards[$card->code]] );
			}
		}		
		foreach ( $ids as $id ) 
		{
			do_action( 'usam_bonus_delete', $id );	
			wp_cache_delete( $id, 'usam_bonus' );
		}
	}
	return $result;
}

function usam_get_bonuses_rules( $args = [] )
{	
	$option = get_site_option('usam_bonuses_rules');
	$rules = maybe_unserialize( $option );	
	if ( empty($rules) )
		$rules = [];
	$defaults = ['active' => 1];
	$args = wp_parse_args( $args, $defaults );	
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
		elseif ( isset($args['total_purchased']) && ($args['total_purchased'] > $rule['total_purchased'] && !empty($rule['total_purchased']) ) )
			unset($rules[$key]);
		elseif ( isset($args['active']) && $args['active'] !== 'all' && $args['active'] != $rule['active'] )
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
	return $rules;
}
?>