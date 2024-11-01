<?php
class USAM_User_Referral
{
	// строковые
	private static $string_cols = [
		'date_insert',		
		'status',
	];
	// цифровые
	private static $int_cols = [
		'id',
		'counter',		
		'user_id',						
	];	
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД
	
	public function __construct( $value = false, $col = 'id' ) 
	{ 
		if ( empty($value) )
			return;
			
		if ( is_array($value) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array($col, ['id', 'user_id']) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];
		if ( $col == 'user_id'  && $id = wp_cache_get( $value, 'usam_user_referral_userid' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_user_referral' );
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
		wp_cache_set( $id, $this->data, 'usam_user_referral' );	
		if ( $user_id = $this->get( 'user_id' ) )
			wp_cache_set( $user_id, $id, 'usam_user_referral_userid' );	
		do_action( 'usam_user_referral_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'user_id' ), 'usam_user_referral_userid' );
		wp_cache_delete( $this->get( 'id' ), 'usam_user_referral' );	
		do_action( 'usam_user_referral_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_user_referral_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_USER_REFERRALS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_user_referral_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_USER_REFERRALS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_user_referral_data', $data );	
			foreach ( $this->data as $key => $value ) 
			{			
				$format = self::get_column_format( $key );
				if ( $format === '%d' )		
					$this->data[$key] = (int)$value;
			}			
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_user_referral_fetched', $this );	
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
		if ( empty($this->data) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_user_referral_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_user_referral_get_data', $this->data, $this );
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
			$properties = [$key => $value];			
		}		
		$properties = apply_filters( 'usam_user_referral_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = [];
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

		do_action( 'usam_user_referral_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_user_referral_pre_update', $this );			
			$where = [$this->args['col'] => $where_val];

			$this->data = apply_filters( 'usam_user_referral_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$data = $this->data;
			$result = $wpdb->update( USAM_TABLE_USER_REFERRALS, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_user_referral_update', $this );
		} 
		else 
		{   
			do_action( 'usam_user_referral_pre_insert' );			
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );				
			if ( empty($this->data['user_id']) )
				return false;				
			if ( empty($this->data['status']) )
				$this->data['status'] = 'active';
			if ( !isset($this->data['counter']) )
				$this->data['counter'] = 0;					
			$id = $wpdb->get_var( "SELECT id FROM ".USAM_TABLE_USER_REFERRALS." WHERE user_id ='".$this->data['user_id']."'" );			
			if ( $id ) 
			{
				$this->args = ['col' => 'id', 'value' => $id];				
				return true;	
			}
			$this->data = apply_filters( 'usam_user_referral_insert_data', $this->data );
			$format = $this->get_data_format();
					
			$result = $wpdb->insert( USAM_TABLE_USER_REFERRALS, $this->data, $format );
			if ( $result ) 
			{
				$this->set('id', $wpdb->insert_id);			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
			}
			do_action( 'usam_user_referral_insert', $this );
		} 		
		do_action( 'usam_user_referral_save', $this );

		return $result;
	}
}

function usam_get_user_referral( $id, $colum = 'id' )
{
	$user_referral = new USAM_User_Referral( $id, $colum );
	return $user_referral->get_data( );	
}

function usam_delete_user_referral( $id ) 
{
	$user_referral = new USAM_User_Referral( $id );
	$result = $user_referral->delete( );
	return $result;
}

function usam_insert_user_referral( $data ) 
{
	$user_referral = new USAM_User_Referral( $data );
	if ( $user_referral->save() )
		return $user_referral->get('id');
	else
		return false;
}

function usam_update_user_referral( $id, $data ) 
{
	$user_referral = new USAM_User_Referral( $id );
	$user_referral->set( $data );
	return $user_referral->save();
}

function usam_get_statuses_user_referral(  ) 
{
	return ['inactive' => __('Не активен', 'usam'), 'active' => __('Активен', 'usam'), 'blocked' => __('Заблокирован', 'usam')];
}
?>