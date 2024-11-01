<?php
/**
 * История изменений
 */ 
class USAM_Change_History
{
	 // строковые
	private static $string_cols = array(
		'object_type',		
		'date_insert',	
		'end',			
		'operation',			
		'field',		
		'value',	
		'old_value',	
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'object_id',
		'sub_object_id',
		'user_id',			
	);
	// рациональные
	private static $float_cols = array(
	);		
	private $data         = [];	
	private $changed_data = [];		
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
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_change_history' );
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
		wp_cache_set( $id, $this->data, 'usam_change_history' );		
		do_action( 'usam_change_history_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_change_history' );			
		do_action( 'usam_change_history_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_change_history_before_delete', $data );	
		
		$this->delete_cache( );						
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CHANGE_HISTORY." WHERE id = '$id'");
		
		do_action( 'usam_change_history_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CHANGE_HISTORY." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_change_history_data', $data );			
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
		do_action( 'usam_change_history_fetched', $this );	
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
		return apply_filters( 'usam_change_history_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_change_history_get_data', $this->data, $this );
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
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}
		$this->data = apply_filters( 'usam_change_history_set_properties', $this->data, $this );			
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

		do_action( 'usam_change_history_pre_save', $this );	
		$result = false;	
		if( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;				
			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format(  $this->args['col'] );
			do_action( 'usam_change_history_pre_update', $this );		
			$this->data = apply_filters( 'usam_change_history_update_data', $this->data );			
			$data = $this->get_update_data();	
			if ( !isset($data['date_insert']) )
				unset( $data['date_insert'] );
			if ( !isset($data['user_id']) )
				unset( $data['user_id'] );			
			$formats = $this->get_data_format( $data );				
			$result = $wpdb->update( USAM_TABLE_CHANGE_HISTORY, $data, [$this->args['col'] => $this->args['value']], $formats, [$where_format] );			
			if ( $result ) 
			{				
				$this->delete_cache();					
			}
			do_action( 'usam_change_history_update', $this );	
		} 
		else 
		{   
			do_action( 'usam_change_history_pre_insert' );		
			unset( $this->data['id'] );					
			
			if ( empty($this->data['object_type']) )
				return false;
			
			if ( !isset($this->data['object_id']) )
				return false;
			
			if ( !isset($this->data['operation']) )
				return false;			
														
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );			
			$this->data['user_id'] = get_current_user_id();		
			if ( !isset($this->data['value']) )
				$this->data['value'] = '';		
			if ( !isset($this->data['old_value']) )
				$this->data['old_value'] = '';		

			$this->data = apply_filters( 'usam_change_history_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_CHANGE_HISTORY, $this->data, $formats );
			if ( $result ) 
			{	
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
			}
			do_action( 'usam_change_history_insert', $this );
		} 		
		do_action( 'usam_change_history_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить 
function usam_update_change_history( $id, $data )
{
	$change_history = new USAM_Change_History( $id );
	$change_history->set( $data );
	return $change_history->save();
}

// Получить
function usam_get_change_history( $id )
{
	$change_history = new USAM_Change_History( $id );
	$result = $change_history->get_data( );		
	return $result;	
}

// Добавить 
function usam_insert_change_history( $data )
{
	if ( isset($data['field']) && $data['field'] == 'customer_type' )
		return 0;
	$change_history = new USAM_Change_History( $data );
	$change_history->save();
	$id = $change_history->get('id');	
	return $id;
}

// Удалить 
function usam_delete_change_history( $id )
{
	$change_history = new USAM_Change_History( $id );
	return $change_history->delete();
}

function usam_employee_viewing_objects( $object ) 
{	
	require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php' );
	$user_id = get_current_user_id();
					
	$today = getdate();			
	// Если нет объекта, то отсортируем по дате и узнаем было ли не завершено задание сегодня //'status__not_in' => 3,
	$args = array('number' => 1, 'operation' => 'view', 'fields' => 'id','date_query' => array('year' => $today["year"], 'monthnum' => $today["mon"], 'day' => $today["mday"], 'column' => 'date_insert'), 'user_id' => $user_id, 'order' => 'DESC');
	$args = array_merge( $args, $object );		
	$id = usam_get_change_history_query( $args );
	if( empty($id) )		
	{
		$object['operation'] = 'view';			
		$id = usam_insert_change_history( $object );		
	}
	if ( !empty($id) )
	{			
		$anonymous_function = function($a) use ( $id )
		{
			?>
			<script>			
				addEventListener("beforeunload", (e) => usam_api('manager/affair/complete/'+<?php echo $id; ?>, 'GET'));
			</script>
			<?php	
		};
		add_action('admin_footer', $anonymous_function, 100);			
	}
}

function usam_work_completed( $args )
{
	$today = getdate();	
	$default = array('number' => 1, 'operation' => 'delivery_deliver', 'fields' => 'id','date_query' => array('year' => $today["year"], 'monthnum' => $today["mon"], 'day' => $today["mday"], 'column' => 'date_insert'), 'object_id' => $id, 'object_type' => 'shipped_document', 'order' => 'DESC');
	$args = array_merge( $args, $default );	
	require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php'  );
	$id = usam_get_change_history_query( $args );
	if ( $id ) 
		usam_update_change_history( $id, array( 'end' => date( "Y-m-d H:i:s" ) ) );	
}
?>