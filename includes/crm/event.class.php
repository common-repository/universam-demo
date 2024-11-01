<?php
class USAM_Event
{	// строковые
	private static $string_cols = [
		'date_insert',		
		'date_completion',			
		'title',		
		'description',
		'end',
		'start',			
		'type',			
		'color',	
		'schedule',
		'status',
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'user_id',	
		'importance',
		'calendar',	
	];		
	private $data = array();		
	private $changed_data = array();	
	private $fetched  = false;
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
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_event' );
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

		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_event' );	
		do_action( 'usam_event_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_event' );	
		do_action( 'usam_event_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{				
		$id = $this->get( 'id' );
		$user_id = get_current_user_id();
		if ( usam_check_current_user_role('administrator') || $user_id == $this->get( 'user_id' ) ) 
		{
			$data = $this->get_data();
			do_action( 'usam_event_before_delete', $data );		
			
			$result = usam_delete_events(['include' => array($id)]);
			
			$this->delete_cache( );		
			do_action( 'usam_event_delete', $id );
		}
		else
			$result = false;
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_EVENTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_event_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;						
			}
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_event_fetched', $this );	
		$this->fetched = true;			
	}

	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}

	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_event_get_property', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_event_get_data', $this->data, $this );
	}

	public function set( $key, $value = null ) 
	{		
		if ( is_array($key) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];
		}
		$properties = apply_filters( 'usam_event_set_properties', $properties, $this );		
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
	
	public function save()
	{
		global $wpdb;	
	
		do_action( 'usam_event_pre_save', $this );	
		if ( isset($this->data['start']) && !empty($this->data['end']) && $this->data['start'] > $this->data['end'] )
		{
			$time = strtotime($this->data['start']);		
			$this->data['end'] = date("Y-m-d H:i:s", mktime(date("H", $time)+1, 0, 0, date("m", $time), date("d", $time), date("Y", $time)));
		}	
		if ( isset($this->changed_data['status']) )
		{
			$user_id = get_current_user_id();	
			if( $this->data['status'] == 'completed' && $this->data['user_id'] !== $user_id )			
				$this->data['status'] = 'control';
			elseif( $this->data['status'] == 'completed' || $this->data['status'] == 'canceled' )
				$this->data['date_completion'] = date("Y-m-d H:i:s");
		}	
		$result = false;	
		if ( $this->args['col'] ) 
		{		
			if ( empty($this->changed_data) )		
				return true;									
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_event_pre_update', $this );
		
			$this->data = apply_filters( 'usam_event_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		

			$str = [];
			foreach ( $formats as $key => $value ) 
			{
				if ( empty($data[$key]) && in_array($key, ['start', 'end', 'date_completion']) )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";					
			}				
			$result = $wpdb->query($wpdb->prepare("UPDATE `".USAM_TABLE_EVENTS."` SET ".implode(', ', $str)." WHERE ".$this->args['col']." = '$where_format' ", array_merge(array_values($data), [$this->args['value']])));
			if ( $result ) 
			{
				$this->delete_cache();					
				foreach( $this->changed_data as $key => $value ) 
				{
					if ( isset($this->data[$key]) )
						usam_insert_change_history(['object_id' => $this->data['id'], 'object_type' => $this->data['type'], 'operation' => 'edit', 'field' => $key, 'value' => $this->data[$key], 'old_value' => $value]);	
				}				
				if ( isset($this->changed_data['status']) )
				{
					usam_update_object_count_status( $this->data['status'], $this->data['type'] );
					do_action( 'usam_event_status_changed', $this->data['status'], $this->changed_data['status'], $this->get_data(), $this );						
					if ( $this->data['status'] == 'completed' )
						$wpdb->query("DELETE FROM ".USAM_TABLE_EVENT_META." WHERE event_id = '".$this->data['id']."' AND meta_key LIKE 'reminder_date_%'");	
					if ( $this->data['status'] == 'completed' && !empty($this->data['schedule']) )
					{ 				
						$to_repeat = usam_get_event_metadata( $this->data['id'], 'to_repeat');
						if ( $to_repeat )
						{
							$new_event = $this->data;			
							$start = strtotime($new_event['start']);
							$end = strtotime($new_event['end']);
							
							switch ( $this->data['schedule'] ) 
							{
								case 'daily' :
									$new_event['start'] = date("Y-m-d H:i:s", mktime(date( "H",$start), date( "i",$start), 0, date( "m"), date("d")+$to_repeat, date( "Y")));
									$new_event['end'] = date("Y-m-d H:i:s", mktime(date( "H",$end), date( "i",$end), 0, date( "m"), date("d")+$to_repeat, date( "Y")));
								break;	
								case 'weekly' :							
									$weekly_interval = usam_get_array_metadata( $this->data['id'], 'weekly_interval');
									if ( !empty($weekly_interval) )
									{
										$w = date('w');
										foreach ( $weekly_interval as $number ) 
										{
											if ( $w < $number || $w != 0 && $number == 0 )
											{
												$day_week = $number;
												break;										
											}									
										}			
										if ( isset($day_week) )
										{
											$day_week = $day_week == 0 ? 7 : $day_week;
											$day = $day_week - $w;																
										}
										else
										{
											$day_week = $weekly_interval[0] == 0 ? 7 : $weekly_interval[0];
											$w = date('w');
											$day = $to_repeat * 7 + $w - $day_week;
										}								
										$new_event['start'] = date("Y-m-d H:i:s", mktime(date( "H",$start), date( "i",$start), 0, date( "m"), date("d")+$day, date( "Y")));
										$new_event['end'] = date("Y-m-d H:i:s", mktime(date( "H",$end), date( "i",$end), 0, date( "m"), date("d")+$day, date( "Y")));		
									}
								break;	
								case 'monthly' :
									$monthly_interval = usam_get_event_metadata( $this->data['id'], 'monthly_interval');
									if ( !empty($monthly_interval) )
									{
										$year = date('Y');
										if ( date('m') < $to_repeat )
											$year ++;							
										
										$new_event['start'] = date( "Y-m-d H:i:s", mktime(date( "H",$start), date( "i",$start), 0, $monthly_interval, $to_repeat, $year) );	
										$new_event['end'] = date( "Y-m-d H:i:s", mktime(date( "H",$end), date( "i",$end), 0, $monthly_interval, $to_repeat, $year) );	
									}
								break;	
							}		
						}
						usam_copy_event( $new_event );
					}							
				}
				do_action( 'usam_event_update', $this );	
			}					
		} 
		else 
		{   
			do_action( 'usam_event_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( usam_is_license_type('FREE') )	
				$this->data['title'] .= ' - Демо лицензия';		
		
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( empty($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			if ( empty($this->data['status']) )
				$this->data['status'] = 'not_started';		
			if ( empty($this->data['start']) && !empty($this->data['end']))
				$this->data['start'] = date( "Y-m-d H:i:s" );			
			if ( empty($this->data['type']) )
				$this->data['type'] = 'task';
						
			$this->data = apply_filters( 'usam_event_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_EVENTS, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				usam_update_object_count_status( $this->data['status'], $this->data['type'] );	
				do_action( 'usam_event_insert', $this );				
			}			
		} 		
		do_action( 'usam_event_save', $this );

		return $result;
	}
}

function usam_get_event( $id )
{
	$_event = new USAM_Event( $id );
	return $_event->get_data( );	
}

function usam_delete_event( $id ) 
{
	$_event = new USAM_Event( $id );
	return $_event->delete( );	
}

// Вставить задачу
function usam_insert_event( $data, $links = [] ) 
{
	if ( empty($data['type']) )
		$data['type'] = 'task';	
	if ( empty($data['date_insert']) )
		$data['date_insert'] = date( "Y-m-d H:i:s" );	
	$_event = new USAM_Event( $data );
	$id = false;
	if ( $_event->save() )
	{	
		$id = $_event->get('id');
		if ( !empty($links) )
		{			
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_insert_ribbon(['event_id' => $id, 'event_type' => $data['type'], 'date_insert' => $data['date_insert']], $links);
		}
	}
	return $id;
}

function usam_update_event( $id, $data ) 
{
	$_event = new USAM_Event( $id );	
	$_event->set( $data );
	return $_event->save();
}

function usam_copy_event( $id ) 
{	
	if ( is_numeric($id) )
		$event = usam_get_event( $id );
	else
		$event = $id;
	
	if ( empty($event['id']) )
		return false;
	
	$event['status'] = 'not_started';
	
	$event_users = usam_get_event_users( $event['id'] );
	$links = usam_get_ribbon_links( $event['id'], 'event' );
	foreach ($links as &$link )
		$link = (array)$link;
	$actions = usam_get_event_action_lists(['event_id' => $event['id']]);	
	$metas = usam_get_event_metadata( $event['id'] );
	
	$event_id = usam_insert_event( $event, $links );
	foreach( $event_users as $event_user ) 
		usam_set_event_user( $event_id, $event_user->user_id, $event_user->user_type );
		
	foreach( $metas as $meta )	
		usam_add_event_metadata( $event['id'], $meta->meta_key, $meta->meta_value );
	
	foreach( $actions as $action ) 
		usam_insert_event_action( (array)$action );
	
	return $event_id;
}

function usam_get_comments_event( $id ) 
{
	return usam_update_comments_cache( $id, 'event' );	
}

//Список действий
function usam_get_event_action_lists( $args ) 
{
	global $wpdb;	
	
	$query_where = '1=1';	
	if ( !empty($args['event_id']) )
	{
		$event_id = implode( ',',  (array)$args['event_id'] );		
		$query_where .= " AND event_id IN ($event_id)";
	}	
	if ( isset($args['status']) )
	{
		if ( $args['status'] != 'all' )
		{
			$status = implode( ',',  (array)$args['status'] );		
			$query_where .= " AND status IN ($status)";
		}
	}
	else
		$query_where .= " AND status!=2";
	
	$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_EVENT_ACTION_LIST." WHERE $query_where ORDER BY sort" );
	foreach( $results as $i => $data ) 
	{
		foreach( $data as $k => $result ) 
		{
			if ( $k == 'status' || $k == 'id' || $k == 'sort' )
				$results[$i]->$k = (int)$result;
		}
	}
	return $results;	
}

function usam_get_event_actions( $event_id ) 
{
	$cache_key = 'usam_event_actions';
	$cache = wp_cache_get( $event_id, $cache_key );
	if( $cache === false )
	{	
		$cache = usam_get_event_action_lists(['event_id' => $event_id]);
		wp_cache_set( $event_id, $cache, $cache_key );
	}		
	return $cache;
}

function usam_insert_event_action( $insert ) 
{
	global $wpdb;
	
	$formats = ['event_id' => '%d', 'name' => '%s','status' => '%d','sort' => '%d'];	
	$format = array();
	$insert['date_insert'] = date( "Y-m-d H:i:s" );	
	if ( !isset($insert['user_id']) )
		$insert['user_id'] = get_current_user_id();
	
	foreach ( $insert as $key => $value ) 
	{		
		if ( isset($formats[$key]) )		
			$format[] = $formats[$key];	
		else
			unset($insert[$key]);
	}	
	$result = $wpdb->insert( USAM_TABLE_EVENT_ACTION_LIST, $insert, $format ); 
	if( $result )
	{
		$event = usam_get_event( $insert['event_id'] );	
		usam_insert_change_history(['object_id' => $event['id'], 'object_type' => $event['type'], 'operation' => 'add', 'field' => 'action', 'value' => $insert['name']]);
	}
	return $wpdb->insert_id;
}

function usam_delete_event_action( $id ) 
{
	global $wpdb;

	$result = $wpdb->delete( USAM_TABLE_EVENT_ACTION_LIST, ['id' => $id], array('%d') ); 
	return $result;
}

function usam_get_event_action( $id ) 
{
	global $wpdb;

	$result = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_EVENT_ACTION_LIST." WHERE id ='$id'", ARRAY_A ); 
	return $result;
}

function usam_update_event_action( $id, $update ) 
{
	global $wpdb;	
	
	$old_action = usam_get_event_action( $id );
	if( !$old_action )
		return false;	
	
	$formats = ['name' => '%s', 'status' => '%d', 'sort' => '%d'];
	$format = array();	
	foreach ( $update as $key => $value ) 
	{		
		if ( isset($formats[$key]) && $old_action[$key] !== $value )		
			$format[] = $formats[$key];	
		else
			unset($update[$key]);
	}	
	$result = $wpdb->update( USAM_TABLE_EVENT_ACTION_LIST, $update, ['id' => $id], $format, ['%d'] ); 
	if( $result && !empty($old_action['event_id']) )
	{
		$event = usam_get_event( $old_action['event_id'] );		
		foreach( $update as $key => $value ) 
		{
			if( $key !== 'sort' )
			{	
				if( $key === 'status' && $value = 2 )
					usam_insert_change_history(['object_id' => $event['id'], 'object_type' => $event['type'], 'operation' => 'delete', 'field' => 'action', 'value' => $old_action['name']]);
				else
					usam_insert_change_history(['object_id' => $event['id'], 'object_type' => $event['type'], 'operation' => 'edit', 'field' => 'action', 'value' => $value, 'old_value' => $old_action[$key]]);
			}
		}
	}	
	return $result;
}

function usam_get_event_links( $event_id ) 
{			 
	if ( !$event_id )
		return [];
	$cache_key = 'usam_event_links';
	$cache = wp_cache_get( $event_id, $cache_key );
	if( $cache === false )
	{
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );
		$types = usam_get_events_types();
		$cache = usam_get_ribbon_query(['event_id' => $event_id, 'event_type' => array_keys($types), 'add_fields' => ['object_id', 'object_type']]);	 
		wp_cache_set( $event_id, $cache, $cache_key );
	}	
	if ( !$cache )
		$cache = [];
	return $cache;
}

function usam_get_events_types() 
{		
	$types = [ 		 
		'contacting' => ['single_name' => __('Обращение','usam'), 'plural_name' => __('Обращения','usam'), 'genitive' => __('обращения','usam'), 'message_add' => __('Добавить обращение','usam'), 'message_edit' => __('Изменить обращение','usam'), 'url' => admin_url('admin.php?page=feedback&tab=contacting')],
		'convocation' => ['single_name' => __('Собрание','usam'), 'plural_name' => __('Собрания','usam'), 'genitive' => __('собрания','usam'), 'message_add' => __('Добавить собрание','usam'), 'message_edit' => __('Изменить собрание','usam'), 'url' => admin_url('admin.php?page=personnel&tab=convocation&table=convocation')],
		'meeting' => ['single_name' => __('Встреча','usam'), 'plural_name' => __('Встречи','usam'), 'genitive' => __('встречи','usam'), 'message_add' => __('Добавить встречу','usam'), 'message_edit' => __('Изменить встречу','usam'), 'url' => admin_url('admin.php?page=crm&tab=affairs')],
		'call' => ['single_name' => __('Звонок','usam'), 'plural_name' => __('Звонки','usam'), 'genitive' => __('звонка','usam'), 'message_add' => __('Добавить звонок','usam'), 'message_edit' => __('Изменить звонок','usam'), 'url' => admin_url('admin.php?page=crm&tab=affairs')],			
		'task' => ['single_name' => __('Задание','usam'), 'plural_name' => __('Задания','usam'), 'genitive' => __('задания','usam'), 'message_add' => __('Добавить задание','usam'), 'message_edit' => __('Изменить задание','usam'), 'url' => admin_url('admin.php?page=personnel&tab=tasks')],
		'project' => ['single_name' => __('Открытый проект','usam'), 'plural_name' => __('Проекты','usam'), 'genitive' => __('проекта','usam'), 'message_add' => __('Добавить открытый проект','usam'), 'message_edit' => __('Изменить открытый проект','usam'), 'url' => admin_url('admin.php?page=personnel&tab=projects')],
		'closed_project' => ['single_name' => __('Закрытый проект','usam'), 'plural_name' => __('Проекты','usam'), 'genitive' => __('проекта','usam'), 'message_add' => __('Добавить закрытый проект','usam'), 'message_edit' => __('Изменить закрытый проект','usam'), 'url' => admin_url('admin.php?page=personnel&tab=projects')],
	];
	return $types;
}

function usam_get_event_url( $id, $type, $form = 'view' ) 
{ 
	$events_types = usam_get_events_types();
	if ( isset($events_types[$type]) )	
		$url = add_query_arg( array('form' => $form, 'id' => $id, 'form_name' => $type), $events_types[$type]['url'] );
	else
		$url = '';
	return $url;
}

function usam_get_event_type_name( $type ) 
{		
	$events_types = usam_get_events_types( );
	if ( isset($events_types[$type]) )
		$result = $events_types[$type]['single_name'];
	else
		$result = $events_types['task']['single_name'];
	return $result;
}

// Добавить системное событие
function usam_insert_system_event( $data, $links = [], $reminder_date = '' ) 
{	
	$data['status'] = 'started';		
	if ( empty($data['type']) )
		$data['type'] = 'event';
	
	if ( empty($data['user_id']) )		
		$user_ids = get_users( array( 'orderby' => 'nicename', 'role__in' => array('shop_manager','administrator'), 'fields' => 'ID' ) );
	else
	{
		$user_ids[] = $data['user_id'];		
		unset($data['user_id']);
	}	
	if ( empty($data['calendar']) )
		$data['calendar'] = usam_get_id_system_calendar('affair');
	
	$event_id = usam_insert_event( $data, $links );		
	if ( empty($reminder_date) )
		$reminder_date = date( "Y-m-d H:i:s");
	
	usam_update_event_reminder_date( $event_id, $reminder_date );
	foreach ( $user_ids as $user_id )	
		usam_set_event_user( $event_id, $user_id);
	
	return $event_id;
}

function usam_get_object( $object ) 
{ 
	$result = array();
	if ( empty($object->object_id) )
		return $result;	
	$object_type = isset($object->object_type) ? $object->object_type : $object->type;	 
	switch ( $object_type ) 
	{
		case 'product' :
			$result['name'] = __('Товар','usam');
			$result['url'] = usam_product_url( $object->object_id );
			$result['title'] = get_the_title( $object->object_id );			
			$result['img'] = usam_get_product_thumbnail_src( $object->object_id, 'manage-products' );
		break;		
		case 'post' :						
			$result['name'] = __('Страница','usam');
			$result['url'] = get_permalink( $object->object_id );
			$result['title'] = get_the_title( $object->object_id );
			
			$field = get_post_field( 'post_type', $object->object_id );				
			if ( $field != 'page' )
				$result['img'] = usam_get_product_thumbnail_src( $object->object_id, 'manage-products' );
		break;
		case 'order' :
			$result['name'] = __('Заказ','usam');
			$order = usam_get_order( $object->object_id );
			if ( $order )
			{
				$result['url'] = usam_get_url_order( $object->object_id );
				$result['title'] = $order['number'];
			}
		break;	
		case 'shipped_document' : 		
			$result['name'] = __('Отгрузка','usam');
			$result['url'] = add_query_arg( array('page' => 'orders', 'tab' => 'shipped', 'form' => 'edit', 'form_name' => 'shipped', 'id' => $object->object_id ), admin_url('admin.php') );
			$result['title'] = $object->object_id;
		break;
		case 'review' :
			$result['name'] = __('Отзыв','usam');
			$review = usam_get_review( $object->object_id );
			$result['url'] = add_query_arg( array('page' => 'feedback', 'tab' => 'reviews', 'form' => 'edit', 'form_name' => 'review', 'id' => $object->object_id ), admin_url('admin.php') );
			$result['title'] = !empty($review['title'])?$review['title']:$object->object_id;
		break;			
		case 'email' :
		case 'sent_letter' :
		case 'inbox_letter' :
			$email = usam_get_email( $object->object_id );
			if ( $email )
			{
				$result['name'] = $email['type'] == 'inbox_letter' ? __('Входящее письмо','usam') : __('Исходящее письмо','usam') ;
				$result['url'] = add_query_arg( array('page' => 'feedback', 'tab' => 'email', 'f' => $email['folder'], 'm' => $email['mailbox_id'], 'email_id' => $object->object_id ), admin_url('admin.php') );
				$result['title'] = empty($email)?"ID".$object->object_id:$email['title']; 
			}
		break;
		case 'contact' :
			$contact = usam_get_contact( $object->object_id );
			if ( empty($contact['name']) )
				$name = $object->object_id;
			else
				$name = $contact['name'];
			
			$result['img'] = usam_get_contact_foto( $object->object_id );
			$result['name'] = __('Контакт','usam');
			$result['url'] = usam_get_contact_url( $object->object_id );
			$result['title'] = $name;
		break;
		case 'company' :
			$company = usam_get_company( $object->object_id );
			$result['img'] = usam_get_company_logo( $object->object_id );
			$result['name'] = __('Компания','usam');
			$result['url'] = usam_get_company_url( $object->object_id );
			$result['title'] = $company['name'];
		break;	
		case 'contacting' :
			require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
			$contacting = usam_get_contacting( $object->object_id );				
			$result['name'] = __('Обращение','usam');
			if( $contacting )
			{
				$result['url'] = usam_get_event_url( $object->object_id, 'contacting' );
				$result['title'] = $contacting['id'];
			}
		break;			
		default :
			$details = usam_get_details_document( $object_type );
			if ( $details )
			{
				$result['name'] = $details['single_name'];
				$document = usam_get_document( $object->object_id );
				if ( $document )
				{					
					$result['url'] = usam_get_document_url( $object->object_id, $object_type );
					$result['title'] = $document['name'];
				}
				else
				{
					$result['url'] = '';
					$result['title'] = __('Удалено','usam');
				}
			}
			else
			{							
				$name = usam_get_event_type_name( $object_type );
				if ( $name )
				{
					$result['name'] = $name;
					$event = usam_get_event( $object->object_id );	
					if ( $event )
					{				
						$result['url'] = usam_get_event_url( $object->object_id, $object_type );
						$result['title'] = $event['title'];
					}
					else
					{
						$result['url'] = '';
						$result['title'] = __('Удалено','usam');
					}
				}
			}
		break;	
	}	
	return $result;
}

function usam_get_event_type_icon( $type, $userid = null ) 
{		
	$user_id = get_current_user_id();
	if ( $type == 'task' )
	{		
		if ( $userid == $user_id )
			$result = '<span class="event_type_icon dashicons dashicons-flag" title="'.__('Задание', 'usam').'"></span>';
		else
			$result = '<span class="event_type_icon dashicons dashicons-arrow-right-alt" title="'.__('Помогаете', 'usam').'"></span>';
	}
	elseif ( $type == 'affair' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-flag" title="'.__('Дело', 'usam').'"></span>';
	}
	elseif ( $type == 'meeting' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-groups" title="'.__('Встреча', 'usam').'"></span>';
	}
	elseif ( $type == 'call' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-phone" title="'.__('Звонок', 'usam').'"></span>';
	}	
	elseif ( $type == 'event' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-calendar-alt" title="'.__('Событие', 'usam').'"></span>';
	}
	elseif ( $type == 'sent_letter' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-email-alt" title="'.__('Исходящее письмо', 'usam').'"></span>';
	}
	elseif ( $type == 'inbox_letter' )
	{
		$result = '<span class="event_type_icon dashicons dashicons-email-alt" title="'.__('Входящее письмо', 'usam').'"></span>';
	}
	else
		$result = '';
	return $result;
}

function usam_set_event_user( $event_id, $user_id, $user_type = 'participant' ) 
{
	global $wpdb;	
	
	if ( empty($user_id) )
		return false;
	
	if ( empty($event_id) )
		return false;
		
	$result = $wpdb->query( $wpdb->prepare("INSERT INTO `".USAM_TABLE_EVENT_USERS."` (`event_id`,`user_id`,`user_type`) VALUES ('%d','%d','%s') ON DUPLICATE KEY UPDATE `user_id`='%d'", $event_id, $user_id, $user_type, $user_id ));
	if ( $result )
	{
		do_action( 'usam_set_event_user', $event_id, $user_id, $user_type );
		$event = usam_get_event( $event_id );
		usam_insert_change_history(['object_id' => $event['id'], 'object_type' => $event['type'], 'operation' => 'add', 'field' => $user_type, 'value' => $user_id]);
	}	
	wp_cache_delete( $event_id, 'usam_event_users' );	
	return $result;	
}

function usam_delete_event_user( $args ) 
{
	global $wpdb;	
	
	$formats = ['event_id' => '%d', 'user_id' => '%d', 'user_type' => '%s'];
	$formats_delete = array();
	foreach ($args as $key => $value )
	{
		if ( isset($formats[$key]) )
			$formats_delete[] = $formats[$key];
	}		
	if ( isset($args['event_id']) )
		wp_cache_delete( $args['event_id'], 'usam_event_users' );
	$result = $wpdb->delete( USAM_TABLE_EVENT_USERS, $args, $formats_delete);
	if( $result && isset($args['event_id']) && isset($args['user_type']) && isset($args['user_id']) )
	{
		$event = usam_get_event( $args['event_id'] );
		usam_insert_change_history(['object_id' => $event['id'], 'object_type' => $event['type'], 'operation' => 'delete', 'field' => $args['user_type'], 'value' => $args['user_id']]);
	}
	return $result;
}

function usam_get_event_users( $event_id, $group_type = true ) 
{
	$cache_key = 'usam_event_users';
	$cache = wp_cache_get( $event_id, $cache_key );
	if( $cache === false )
	{	
		global $wpdb;	
		$cache = $wpdb->get_results( "SELECT user_id, user_type FROM ".USAM_TABLE_EVENT_USERS." WHERE event_id = '$event_id'" );		
		wp_cache_set( $event_id, $cache, $cache_key );
	}	
	if ( $group_type )
	{
		$results = array();
		foreach ( $cache as $user )
		{
			$results[$user->user_type][] = absint($user->user_id);
		}
	}
	else
	{
		$results = array();
		foreach ( $cache as $user )
		{
			$results[] = absint($user->user_id);
		}
	}
	return $results;
}

function usam_get_event_reminder_date( $event_id ) 
{
	$user_id = get_current_user_id();
	$reminder_date = (string)usam_get_event_metadata( $event_id, 'reminder_date_'.$user_id );
	return $reminder_date;
}	

function usam_update_event_reminder_date( $event_id, $date ) 
{
	$user_id = get_current_user_id();
	$update = false;	
	if ( $event_id )
		$update = usam_update_event_metadata( $event_id, 'reminder_date_'.$user_id, $date );
	if ( $update )
		usam_delete_event_metadata( $event_id, 'notification' );	
	return $update;	
}

function usam_delete_event_reminder_date( $event_id ) 
{
	$user_id = get_current_user_id();
	$delete = usam_delete_event_metadata( $event_id, 'reminder_date_'.$user_id );
	if ( $delete ) 
		usam_delete_event_metadata( $event_id, 'notification' );	
	return $delete;
}

function usam_delete_events( $args ) 
{	
	global $wpdb;	
	$args['fields'] = ['id', 'type', 'status'];
	$events = usam_get_events( $args );	
	if ( empty($events) )
		return false;	
	
	usam_update_object_count_status( false );
	
	$ids = array();
	$objects = array();	
	foreach ($events as $event )
	{
		usam_update_object_count_status( $event->status, $event->type );
		$ids[] = $event->id;
		$objects[$event->type][] = $event->id;
	}	
	$wpdb->query( "DELETE FROM " . USAM_TABLE_EVENT_USERS . " WHERE event_id IN ('".implode("','", $ids)."')" );			
	$wpdb->query( "DELETE FROM " . USAM_TABLE_EVENT_META . " WHERE event_id IN ('".implode("','", $ids)."')" );
	$wpdb->query( "DELETE FROM " . USAM_TABLE_EVENTS . " WHERE id IN ('".implode("','", $ids)."')" );		
	
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
	require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
	usam_delete_comments(['object_id' => $ids, 'object_type' => 'event'], true);
	foreach( $objects as $type => $event_ids)
	{
		usam_delete_ribbon(['event_id' => $event_ids, 'event_type' => $type]);
		usam_delete_object_files( $event_ids, $type );
	}	
	usam_update_object_count_status( true );
	return true;	
}

function usam_get_event_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('event', $object_id, USAM_TABLE_EVENT_META, $meta_key, $single );
}

function usam_add_event_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('event', $object_id, $meta_key, $meta_value, USAM_TABLE_EVENT_META, $prev_value );
}

function usam_update_event_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('event', $object_id, $meta_key, $meta_value, USAM_TABLE_EVENT_META, $prev_value );
}

function usam_delete_event_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('event', $object_id, $meta_key, USAM_TABLE_EVENT_META, $meta_value, $delete_all );
}

/*
edit_content
edit_content
delegation_ //делегирование задачи — можно поменять исполнителя, при этом прошлый исполнитель станет наблюдателем в задаче,
add_participant добавление соисполнителя — в задачу можно добавить соисполнителя,
add_participant смена исполнителя — можно поменять исполнителя задачи,
редактирование чек-листа — возможность редактировать чек-листы в задаче,
добавление новых пунктов в чек-листы — можно добавлять новые пункты в чек-лист, но нельзя редактировать старые.

*/
function usam_check_event_access( $event, $type_access, $contact_id = null )
{				
	if( is_numeric($event) )
		$event = usam_get_event( $event );	
	
	if ( empty($event) )
		return true;		
	if ( $contact_id === null )
	{
		$contact_id = usam_get_contact_id();
		$contact = usam_get_contact( $contact_id );
		$user_id = get_current_user_id();	
	}
	else
	{
		$contact = usam_get_contact( $contact_id );
		$user_id = isset($contact['user_id'])?$contact['user_id']:0;
	}	
	if ( empty($contact) )
		return false;
	
	if( $event['type'] !== 'task' )
		return user_can($user_id, $type_access.'_'.$event['type']);
			
	$event_users = usam_get_event_users( $event['id'] );
	if( $event['user_id'] === $user_id )
	{		
		$access = true;
		if( !empty($event_users['participant']) )
		{
			if( $type_access == 'edit' || $type_access == 'edit_action' || $type_access == 'add_action' || $type_access == 'delete_action' )
				$access = false;
		}
		if( $type_access == 'edit' || $type_access == 'add' || $type_access == 'delete' || $type_access == 'view' )
			$access = user_can($user_id, $type_access.'_'.$event['type']);
	}	
	else
	{			
		if( $type_access == 'delete' )
			return false;
		$access = false;
		foreach( $event_users as $role => $users )
		{
			foreach( $users as $id )
			{
				if( $id === $user_id )
				{
					if( $type_access == 'view' )
						$access = true;					
					elseif( $role == 'participant' )
					{
						if( $type_access == 'edit_action' || $type_access == 'add_action' || $type_access == 'add_participant' || $type_access == 'comments' || $type_access == 'edit_status' )
							$access = true;
					}
					elseif( $role == 'responsible' )
					{
						if( $type_access == 'edit_action' || $type_access == 'add_action' || $type_access == 'delete_action' || $type_access == 'add_participant' || $type_access == 'delete_participant' || $type_access == 'add_observer' || $type_access == 'delete_observer' || $type_access == 'comments' || $type_access == 'edit_status' )
							$access = true;
					}
					elseif( $role == 'responsible' )
						$access = true;
					break;
				}				
			}
		}		
	}
	return $access;
}
?>