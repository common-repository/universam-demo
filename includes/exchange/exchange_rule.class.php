<?php
class USAM_Exchange_Rule
{
	private static $string_cols = [
		'name',		
		'type',
		'type_file',	
		'encoding',	
		'splitting_array',					
		'start_date',
		'end_date',		
		'exchange_option',		
		'file_data',	
		'time',		
		'schedule',			
		'orderby',
		'order',		
	];
	private static $int_cols = [
		'id',
		'headings',	
		'start_line',	
		'end_line',	
	];	
	private $changed_data = [];	
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_exchange_rule' );
		}			
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

		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_exchange_rule' );	
		do_action( 'usam_exchange_rule_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_exchange_rule' );
		do_action( 'usam_exchange_rule_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_exchange_rule_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_EXCHANGE_RULES." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_exchange_rule_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_EXCHANGE_RULES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_exchange_rule_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_exchange_rule_fetched', $this );	
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
		return apply_filters( 'usam_exchange_rule_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_exchange_rule_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_exchange_rule_set_properties', $properties, $this );
		if ( ! is_array($this->data) )
			$this->data = array();	
	
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
		
	public function save()
	{
		global $wpdb;

		do_action( 'usam_exchange_rule_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			if ( empty($this->changed_data) )
				return true;
			
			if ( isset($this->changed_data['splitting_array']) && $this->data['splitting_array'] == '' )
				$this->data['splitting_array'] = '|';
		
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_exchange_rule_pre_update', $this );	
			
			$this->data = apply_filters( 'usam_exchange_rule_update_data', $this->data );
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_EXCHANGE_RULES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );	
				foreach ( $this->changed_data as $key => $old_value ) 
				{ 
					if ( $key != 'start_date' && $key != 'end_date' && isset($this->data[$key]) )
					{						
						usam_insert_change_history(['object_id' => $this->data['id'], 'object_type' => 'exchange_rule', 'operation' => 'update', 'field' => $key, 'value' => $this->data[$key], 'old_value' => $old_value]);	
					}
				}				
				do_action( 'usam_exchange_rule_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_exchange_rule_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );		
			
			if ( isset($this->data['start_date']) )
				unset($this->data['start_date']);
			
			if ( isset($this->data['end_date']) )
				unset($this->data['end_date']);
			
			if ( empty($this->data['splitting_array']) )
				$this->data['splitting_array'] = '|';	
			
			$this->data = apply_filters( 'usam_exchange_rule_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_EXCHANGE_RULES, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_exchange_rule_insert', $this );				
			}			
		} 		
		do_action( 'usam_exchange_rule_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_exchange_rule( $id, $colum = 'id' )
{
	$exchange_rule = new USAM_Exchange_Rule( $id, $colum );
	return $exchange_rule->get_data( );	
}

function usam_delete_exchange_rule( $id ) 
{
	$exchange_rule = new USAM_Exchange_Rule( $id );
	$result = $exchange_rule->delete( );
	return $result;
}

function usam_insert_exchange_rule( $data, $metas = [] ) 
{
	$exchange_rule = new USAM_Exchange_Rule( $data );	
	if ( $exchange_rule->save() )
	{
		$id = $exchange_rule->get('id');
		if( $id )
			foreach( $metas as $key => $value )
				usam_update_exchange_rule_metadata( $id, $key, $value );
		return $id;
	}
	else
		return false;
}

function usam_update_exchange_rule( $id, $data ) 
{
	if ( $id )
	{
		$exchange_rule = new USAM_Exchange_Rule( $id );
		$exchange_rule->set( $data );
		return $exchange_rule->save();
	}
	else
		return false;
}

function usam_start_exchange( $rule )
{
	if ( is_numeric($rule) )
	{
		$rule = usam_get_exchange_rule( $rule );
		$data = $rule['id'];
	}
	else
	{
		$data = $rule;
		if ( !empty($rule['id']) )
		{
			$metadatas = usam_get_exchange_rule_metadata( $rule['id'] );
			foreach($metadatas as $metadata )
			{
				if ( !isset($data[$metadata->meta_key]) )
					$data[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
			}
		}
	}
	if ( !usam_check_process_is_running('exchange_'.$rule['type']."-".$rule['id']) && !usam_check_process_is_running('load_data-'.$rule['id']) && !usam_check_process_is_running('after_exchange_'.$rule['type']."-".$rule['id']) )
	{		
		usam_update_exchange_rule_metadata( $rule['id'], 'process', '' );					
		return usam_create_system_process( __("Подготовка обмена", "usam" ), $data, 'preparation_exchange_data', 1, 'load_data-'.$rule['id'] );
	}
	else
		return false;
}

function usam_get_automations_exchange_rule( ) 
{	
	return ['ftp' => __('Забирать из FTP', 'usam'), 'url' => __('Файл по ссылке', 'usam'), 'local' => __('Через папку обмена', 'usam'), 'folder' => __('Через папку в библиотеке файлов', 'usam'), 'email' => __('Загружать из письма', 'usam')];
}

function usam_get_exchange_rule_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('rule', $object_id, USAM_TABLE_EXCHANGE_RULE_META, $meta_key, $single );
}

function usam_update_exchange_rule_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('rule', $object_id, $meta_key, $meta_value, USAM_TABLE_EXCHANGE_RULE_META, $prev_value );
}

function usam_delete_exchange_rule_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('rule', $object_id, $meta_key, USAM_TABLE_EXCHANGE_RULE_META, $meta_value, $delete_all );
}
?>