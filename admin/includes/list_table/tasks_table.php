<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_Tasks extends USAM_List_Table 
{	
	protected $statuses;
	protected $status = 'work';	
	protected $orderby = 'start';
	protected $order = 'ASC';
	protected $date_column = 'start';
	protected $period = '';
	protected $work_statuses = ['not_started', 'started', 'control', 'returned'];
	
	function __construct( $args = [] )
	{
		parent::__construct( $args );		
		add_action( 'admin_footer', [&$this, 'admin_footer'] );			
    }	
			
	function get_vars_query_filter()
	{
		$selected = $this->get_filter_value( 'group' );
		if ( $selected )
			$this->query_vars['group'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value( 'calendar' );
		if ( $selected )		
			$this->query_vars['calendar'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value('author');
		if ( $selected )	
			$this->query_vars['author'] = array_map('intval', (array)$selected);
		$objects = array_keys(usam_get_details_documents());
		$objects[] = 'company';
		$objects[] = 'contact';
		foreach($objects as $object_type)
		{
			$selected = $this->get_filter_value( 'o_'.$object_type );
			if ( $selected )		
			{
				$object_ids = array_map('intval', (array)$selected);
				$this->query_vars['links_query'][] = ['object_type' => sanitize_title($object_type), 'object_id' => $object_ids];	
			}
		}
	}
	
	public function get_vars_filter_task() 
	{		
		if ( !current_user_can('monitoring_events') )
		{
			$user_id = get_current_user_id();
			$user_ids = usam_get_subordinates(); 		
			$this->query_vars['user_work'] = array_merge([$user_id], (array)$user_ids);
		}
		$selected = $this->get_filter_value('user_work');
		if ( $selected )
		{
			$selected = array_map('intval', (array)$selected);		
			$department_ids = [];
			foreach($selected as $value)
			{
				$department_ids[] = str_replace("department-", "", $value);		
			}
			if ( $department_ids )
			{
				$ids = usam_get_contacts(['fields' => 'user_id', 'source' => 'employee', 'meta_query' => [['key' => 'department', 'value' => $department_ids, 'compare' => 'IN']], 'orderby' => 'name']);
				$selected = array_merge($selected, (array)$ids);
			}	
			if ( !current_user_can('monitoring_events') )
				$this->query_vars['user_work'] = array_intersect($selected, $user_ids);
		}
	}
	
	public function get_default_primary_column_name() {
		return 'title';
	}
	
	function admin_footer()
	{
		echo usam_get_modal_window( __('Добавить или удалить соисполнителя','usam'), 'my_tasks_participants', $this->get_participants_modal_window(), 'medium' );	
	}	
	
	function get_participants_modal_window()
	{
		$out = "		
		<div class='modal-body action_buttons'>	
			<div class='modal_selection'>
				<div class='row_item'>
						<label>".__('Выберете операцию', 'usam').": </label>
						<select id='operation' name='operation'>
							<option value='add'>".__('Добавить', 'usam')."</option>
							<option value='move'>".__('Перенести', 'usam')."</option>
							<option value='delete'>".__('Удалить', 'usam')."</option>
						</select>
				</div>
				<div class='row_item'>".usam_select_manager('', array('id' => 'users') )."</div>";	
				$out .= "<div class='row_item'><div class='title'><strong>".__('Выбранные задания','usam')."</strong></div><div class='selection'></div></div>
			</div>
			<div class='modal__buttons'>
				<button id = 'modal_action' type='button' class='button-primary button'>".__('Отправить', 'usam')."</button>
				<button type='button' class='button' data-dismiss='modal' aria-hidden='true'>".__('Отменить', 'usam')."</button>
			</div>
		</div>";
		return $out;
	}

	public function get_views() 
	{ 
		global $wpdb;
		
		$url = remove_query_arg(['post_status', 'paged', 'action2', 'm',  'paged', 's', 'orderby','order']);			
		if ( !empty($this->query_vars['type']) )
		{
			$query_vars = $this->get_views_query_vars();
			$query_vars['cache_contacts'] = false;	
			$query_vars['cache_objects'] = false;		
			$query_vars['fields'] = ['status', 'id'];
			unset($query_vars['groupby']);		
			$results = usam_get_events( $query_vars );
		}	
		else
			$results = [];
		$statuses = ['work' => 0];		
		$total_count = 0;	
		$object_statuses = [];
		if ( !empty( $results ) )
		{
			foreach( $results as $status )
			{
				$statuses[$status->status] = isset($statuses[$status->status])?$statuses[$status->status]+1:1;
				$total_count++;				
				if ( in_array($status->status, $this->work_statuses) )
					$statuses['work']++;
			}
			if ( !empty($this->query_vars['type']) )
				$types = $this->query_vars['type'];
			else
			{
				$events_types = usam_get_events_types();				
				$types = array_keys($events_types);
			}			
			$object_statuses = usam_get_object_statuses(['type' => $types, 'groupby' => 'name', 'cache_results' => true]);
		} 				
		$all_text = sprintf(_nx('Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status === 'all' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ), );				
	
		$all_text = sprintf(_nx('В работе <span class="count">(%s)</span>', 'В работе <span class="count">(%s)</span>', $statuses['work'], 'events', 'usam'), number_format_i18n($statuses['work']) );
		$all_class = $this->status === 'work' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'work', $url );
		$views['work'] = sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text );	

		foreach ( $object_statuses as $status )		
		{			
			$number = !empty($statuses[$status->internalname])?$statuses[$status->internalname]:0;
			if ( !$number )
				continue;
			
			$text = $text = sprintf( $status->short_name.' <span class="count">(%s)</span>', number_format_i18n( $number )	);
			$href = add_query_arg( 'status', $status->internalname, $url );
			$class = $this->status === $status->internalname ? 'class="current"' : ''; 
			$views[$status->internalname] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}		
		return $views;
	}
		
	protected function get_filter_tablenav( ) 
	{				
		return ['interval' => '', 'user' => 0];
	}	
	
	function column_title( $item )
	{	
		echo '<a class="row-title" href="'.usam_get_event_url( $item->id, $item->type ).'">'.$item->title.'</a>';		
	}
	
	function column_manager( $item ) 
	{	
		if ( $item->user_id )
		{
			echo "<a href='".usam_get_employee_url($item->user_id , 'user_id')."'>".usam_get_manager_name( $item->user_id  )."</a>";
		}
	}	
	
	function column_user( $item )
	{
		if ( $item->user_id != 0)
		{	
			?> 
			<div class="user">
				<img width="32" height="32" class="avatar avatar-32 photo" src="<?php echo get_avatar_url( $item->user_id, array('size' => 32, 'default'=>'mystery' ) ); ?>" alt="" />&nbsp;
				<span><?php echo usam_get_manager_name( $item->user_id ); ?></span>
			</div>
			<?php 
		}				
	} 
	
	function column_object_type( $item )
	{
		$objects = usam_get_event_links( $item->id ) ;
		foreach( $objects as $object )
		{			
			$result = usam_get_object( $object );
			if ( !empty($result) )
				echo $result['name']." - <a href='".$result['url']."'>".$result['title']."</a><br>";
		}
	} 
	
	function column_participants( $item )
	{
		$users = usam_get_event_users( $item->id );			
		if ( !empty($users['responsible']) )
		{
			echo "<strong>".__("Ответственный","usam")."</strong><br>";
			foreach ( $users['responsible'] as $user_id )
			{		
				echo "<a href='".usam_get_employee_url($user_id, 'user_id')."'>".usam_get_manager_name( $user_id )."</a>";
				echo "<br>";
			}						
		}
		if ( !empty($users['participant']) )
		{
			if ( !empty($users['responsible']) )
			{
				echo "<strong>".__("Исполнители","usam")."</strong><br>";	
			}
			foreach ( $users['participant'] as $user_id )
			{		
				echo "<a href='".usam_get_employee_url($user_id, 'user_id')."'>".usam_get_manager_name( $user_id )."</a>";
				echo "<br>";
			}
		}
	} 
	
	function column_responsible( $item )
	{
		$users = usam_get_event_users( $item->id );		
		if ( !empty($users['responsible']) )
			foreach ( $users['responsible'] as $user_id )
			{		
				echo "<a href='".usam_get_employee_url($user_id, 'user_id')."'>".usam_get_manager_name( $user_id )."</a>";
				echo "<br>";
			}
	} 	
	
	function column_time( $item ) 
    {		
		if(  $item->status == 'completed' )
		{
			if( $item->end )
				echo usam_local_date( $item->end );
			if ( $item->date_completion && $item->date_completion != '0000-00-00 00:00:00' )
				echo "<br><div class='date_completion'><strong>".__("Выполнено", "usam").": </strong>".usam_local_date( $item->date_completion )."</div>";	
		}
		elseif (  $item->status == 'canceled' )
		{
			echo usam_local_date( $item->end );
		}				
		elseif ( !empty($item->end) )
		{
			$timestamp = time();
			$num_days = ceil(($timestamp - strtotime($item->end))/86400);
			$num_days_start = ceil(($timestamp - strtotime($item->start))/86400);
			$message = '';
			if ( $num_days_start >= 0 ) 
			{
				if ( $num_days < 0 ) 
					$message = "<span class='item_status item_status_valid'>".human_time_diff( $timestamp, strtotime( $item->end ) )."</span>";
				else
					$message = "<span class='item_status item_status_attention'>-".human_time_diff( $timestamp, strtotime( $item->end ) )."</span>";
			}			
			if (  $item->status != 'stopped' )
				echo $message;
		}
	}
	
	function column_calendar_id( $item )
	{ 
		echo usam_get_calendar_name_by_id( $item->calendar );
	} 
	
	function column_color( $item )
	{ 
		echo usam_get_event_type_icon( $item->type, $item->user_id );
		if ( $item->importance )
			echo '<span id="event_importance" class="dashicons dashicons-star-filled" title="'.__("Избранное","usam").'"></span>';
		else
			echo '<span id="event_importance" class="dashicons dashicons-star-empty" title="'.__("Избранное","usam").'"></span>';		
		if ( $item->color )
			echo '<span class="dashicons dashicons-image-filter color_'.$item->color.'" title="'.__("Цветовая категория","usam").'"></span>';
				
		$reminder_date = usam_get_event_reminder_date( $item->id );
		if ( !empty($reminder_date) )
			echo '<span class="dashicons dashicons-bell" title="'.usam_local_date( $reminder_date, get_option( 'date_format', 'Y/m/d' ).' H:i' ).'"></span>';
	} 
	
	function column_status( $item ) 
	{		
		if ( $item->status == 'canceled' || $item->status == 'controlled' ) 
			$this->display_object_status_name( $item );
		else
		{
			$ok = true;
			if ( $item->type == 'task' )
			{		
				$user_id = get_current_user_id();
				$users = usam_get_event_users( $item->id );
				if( !empty($users['participant']) )
				{
					$ok = false;
					if( $item->user_id == $user_id )
					{ 
						if( $item->status == 'completed' ) 
						{ 
							echo "<div class='status_title'>".__('Задание выполнено', 'usam')."</div>";
							echo "<div class='event_buttons'>";
							echo "<button type='submit' class='button js_start_performing' data-status='controlled'>".__('Принять', 'usam')."</button>";
							echo "<button type='submit' class='button js_start_performing' data-status='returned'>".__('Вернуть', 'usam')."</button>";
							echo "<span class='js_status_result_controlled hide'>".__('Задание принято', 'usam')."</span>";
							echo "<span class='js_status_result_returned hide'>".__('Задание возвращено', 'usam')."</span></div>";
						}
						elseif ( $item->status == 'control' ) 
							$this->display_object_status_buttons( 'controlled', 'returned' );
						else
							$this->display_object_status_name( $item );						
					}
					else
					{
						if( $item->status == 'completed' || $item->status == 'control' ) 
							$this->display_object_status_name( $item );
						elseif( $item->status == 'not_started' ) 
							$this->display_object_status_buttons();
						elseif( $item->status == 'stopped' ) 
						{ 
							echo "<button type='submit' class='button js_start_performing' data-status='started'>".__('Продолжить выполнять', 'usam')."</button>";
							echo "<span class='js_status_result_started hide'>".__('Выполняется', 'usam')."</span>";
						}
						else
						{
							$statuses = usam_get_object_statuses_by_type( $item->type, $item->status );							
							$this->display_status_selection( $statuses, $item );		
						}
					}
				}
				elseif( $item->user_id != $user_id )
				{
					$ok = false;
					$this->display_object_status_name( $item );
				}
			}
			if( $ok )
			{
				if( $item->status == 'completed' ) 
					$this->display_object_status_name( $item );
				elseif( $item->status == 'not_started' || $item->status == 'stopped') 
				{
					?>
					<div class='event_buttons'>
						<button type='submit' class='button js_start_performing' data-status='started'><?php _e('Начать выполнять', 'usam'); ?></button>
						<span class="js_status_result_started hide"><span class="item_status item_status_valid"><?php _e('Выполняется', 'usam'); ?></span></span>
					</div>
					<?php
				}		
				else
				{				
					$statuses = usam_get_object_statuses_by_type( $item->type, $item->status );
					$this->display_status_selection( $statuses, $item );
				}
			}
		}
	}
	
	protected function display_object_status_name( $item ) 
	{ 
		if( $item->status == 'started' )
		{
			?><span class="item_status item_status_valid"><?php echo usam_get_object_status_name( $item->status, $item->type ); ?></span><?php
		}
		elseif( $item->status == 'controlled' )
		{
			?><span class="item_status status_customer"><?php echo usam_get_object_status_name( $item->status, $item->type ); ?></span><?php
		}
		else
		{
			?><span class="item_status status_blocked"><?php echo usam_get_object_status_name( $item->status, $item->type ); ?></span><?php
		}
	}
	
	protected function display_object_status_buttons( $end = 'started', $canceled = 'canceled' ) 
	{ 
		?>
		<div class='event_buttons'>
			<button type='submit' class='button js_start_performing' data-status='<?php echo $end; ?>'><?php _e('Принять', 'usam'); ?></button>
			<button type='submit' class='button js_start_performing' data-status='<?php echo $canceled; ?>'><?php _e('Отменить', 'usam'); ?></button>
			<span class='js_status_result_<?php echo $end; ?> hide'><span class='item_status item_status_valid'><?php _e('Принята', 'usam'); ?></span></span>
			<span class='js_status_result_<?php echo $canceled; ?> hide'><span class='item_status status_blocked'><?php _e('Отменена', 'usam'); ?></span></span>
		</div>
		<?php
	}	
	
	protected function display_status_selection( $statuses, $item ) 
	{
		?>
		<select data-id = "<?php echo $item->id; ?>" class = "js-select-status-record">
			<?php
			foreach( $statuses as $status )
			{
				if ( $status->visibility || $item->status == $status->internalname )
				{
					?><option <?php selected($item->status, $status->internalname); ?> value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
				}
			}
			?>
		</select>
		<?php
	}
	   
    function get_bulk_actions_display() 
	{
		$actions = [];		
		$actions['delete'] = __('Удалить', 'usam');
		$actions['participants'] = __('Исполнители', 'usam');
		$actions['completed'] = __('Завершить', 'usam');
		return $actions;
	}
	
	public function single_row( $item ) 
	{		
		$row_class = '';	
		if ( !empty($item->start) )
		{
			$event_start_time = $item->start ? strtotime(get_date_from_gmt($item->start)) : 0;
			$event_end_time = $item->end ? strtotime(get_date_from_gmt($item->end)) : 0;
			$start_day = mktime(0, 0, 0, date('m'), date_i18n('d'), date('Y'));
			$end_day = mktime(23, 59, 59, date('m'), date_i18n('d'), date('Y'));
			$tomorrow_end_day = mktime(23, 59, 59, date('m'), date_i18n('d')+1, date('Y'));
			$future_end_day = mktime(23, 59, 59, date('m'), date_i18n('d')+6, date('Y'));		
			if ( empty($item->start) )
			{
				$row_class = 'event_day';		
			}
			elseif ( $item->status == 'completed' ) 
			{
				$row_class = 'event_close';	
			}	
			elseif ( $event_end_time < current_time('timestamp' ) )
			{
				$row_class = 'event_overdue';
			}	
			elseif ( $start_day <= $event_start_time && $end_day >= $event_start_time || $start_day >= $event_start_time && $start_day <= $event_end_time )
			{						
				$row_class = 'event_day';
			}					
			elseif ( $end_day < $event_start_time && $tomorrow_end_day > $event_start_time || $end_day > $event_start_time && $end_day < $event_end_time )
			{						
				$row_class = 'event_tomorrow';
			}
			elseif ( $event_start_time>=$tomorrow_end_day && $event_start_time<=$future_end_day )
			{					
				$row_class = 'event_future';
			}
		}
		echo '<tr class ="row '.$row_class.'" id = "row-'.$item->id.'" data-id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	public function get_sortable_columns() 
	{
		if ( ! $this->sortable )
			return array();
		
		return array(
			'date'        => array('date_insert', false),
			'id'          => array('id', false),
			'user'        => array('user', false),
			'status'      => array('status', false),
			'title'       => array('title', false),
			'manager'      => array('user_id', false),
		);
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',	
			'color'         => '',						
			'title'         => __('Название', 'usam'),				
			'status'        => __('Статус', 'usam'),	
			'time'          => __('Срок', 'usam'),	
			'manager'       => __('Менеджер', 'usam'),	
			'participants'  => __('Исполнители', 'usam'),					
			'object_type'   => __('Объект', 'usam'),	
        );
        return $columns;	
    }		
}