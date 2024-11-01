<?php
/* =====================  Загрузка данных в график ================================================*/
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
class USAM_Load_Graph_Data 
{		
	private $id = null;
	private $start_date_interval = '';
	private $end_date_interval = '';	
	
	public function load( $type )
	{		
		$f = new Filter_Processing();
		$date = $f->get_date_interval('last_30_day');

		$this->start_date_interval = $date['from'] == ''?'':date('Y-m-d H:i:s', $date['from']);					
		$this->end_date_interval = $date['to'] == ''?'':date("Y-m-d H:i:s", $date['to']);			
		if ( method_exists($this, $type) )
			$results = $this->$type( );	
		else
			$results = apply_filters( 'usam_load_graph_data_'.$type, false, $this->get_query_vars() );	
		return $results;
	}
	
	private function get_filter_value( $key, $default = null ) 
	{ 	
		if ( isset($_POST[$key]) )
		{
			if ( is_array($_POST[$key]) )
				$select = stripslashes_deep($_POST[$key]);
			elseif ( stripos($_POST[$key], ',') !== false )
				$select = explode(',',stripslashes($_POST[$key]));
			else
				$select = stripslashes($_POST[$key]);
		}
		else
			$select = $default;		
		return $select;
	}	
	
	private function get_query_vars( $query_vars = [] ) 
	{		
		$column_date = isset($query_vars['column_date']) ? $query_vars['column_date'] : 'date_insert';
		if ( $this->start_date_interval )
			$query_vars['date_query'][] = ['after' => get_gmt_from_date($this->start_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];	
		if ( $this->end_date_interval )
			$query_vars['date_query'][] = ['before' => get_gmt_from_date($this->end_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];	
		$selected = $this->get_filter_value( 'weekday' );
		if ( !empty($selected) )	
		{
			$weekday = array_map( 'intval', $selected );
			$query_vars['date_query'][] = ['dayofweek' => $weekday, 'compare' => 'IN', 'column' => $column_date];		
		}		
		return $query_vars;
	}
	
	private function get_events_vertical_bars( $query_vars, $date_column ) 
	{				
		$query_vars = $this->get_query_vars( $query_vars ); 		
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_work'] = array_map('intval', (array)$selected);
		$events = usam_get_events( $query_vars );
		if ( empty($events) )
			return array();
		$data_graph = array();	
		foreach ( $events as $event )
		{			
			$data_graph[] = array( 'y_data' => usam_local_date($event->$date_column,'m.Y'), 'x_data' => (int)$event->count, 'label' => [ __("Дата","usam").": ".usam_local_date($event->$date_column,'m.Y'), __("Количество","usam").": ".$event->count]);		
		}	
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	public function get_events_horizontal_bars( $query_vars )
	{		
		$args = ['fields' => ['appeal','user_id'], 'source' => 'employee', 'order' => 'DESC', 'orderby' => 'name'];
		$selected = $this->get_filter_value( 'manager' );
		$user_ids = usam_get_subordinates( );		
		$user_ids[] = get_current_user_id();
		if ( $selected ) 
		{
			$selected = array_map('intval', (array)$selected);
			$result = array_intersect($selected, $user_ids);		
			$args['user_id'] = $result?$result:array_unique($user_ids);
		}
		else
			$args['user_id'] = array_unique($user_ids);
		$subordinates = usam_get_contacts( $args );				
		$query_vars['user_work'] = $args['user_id'];	
		$events = usam_get_events( $query_vars );
		if ( empty($events) )
			return array();
		$results = array();	
		foreach ( $events as $event )
		{			
			if ( !isset($results[$event->user_id]) )
				$results[$event->user_id] = 1;
			else
				$results[$event->user_id]++;
			$event_users = usam_get_event_users( $event->id );				
			foreach ( $event_users as $user_ids )	
			{
				foreach ( $user_ids as $user_id )	
				{
					if ( !isset($results[$user_id]) )
						$results[$user_id] = 1;
					else
						$results[$user_id]++;
				}
			}
		}		
		$data_graph = array();
		foreach ( $subordinates as $subordinate )
		{			
			$value = isset($results[$subordinate->user_id])?(int)$results[$subordinate->user_id]:0;
			$data_graph[] = array( 'y_data' => $subordinate->appeal, 'x_data' => $value, 'label' => "$value" );		
		}			
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, [$comparison, 'compare'] );
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];
	}
	
	public function completed_affairs_graph( ) 
	{						
		return $this->get_events_vertical_bars( array('fields' => array('count','date_completion'), 'groupby' => 'completion_month', 'date_query' => array(array('after' => '12 months ago'), 'column' => 'date_completion'), 'type' => array('meeting', 'call', 'event','email')),'date_completion' );
	}
	
	public function add_affairs_graph( ) 
	{		
		return $this->get_events_vertical_bars( array('fields' => array('count','start'), 'groupby' => 'start_month', 'date_query' => array(array('after' => '12 months ago'), 'column' => 'start'), 'type' => array('meeting', 'call', 'event','email')), 'start' );
	}
	
	public function completed_tasks_graph( ) 
	{						
		return $this->get_events_vertical_bars(['fields' => ['count','date_completion'], 'groupby' => 'completion_month', 'date_query' => [['after' => '12 months ago'], 'column' => 'date_completion'], 'type' => ['task', 'meeting', 'call', 'event','email']], 'date_completion' );
	}
	
	public function add_tasks_graph( ) 
	{		
		return $this->get_events_vertical_bars(['fields' => array('count','start'), 'groupby' => 'start_month', 'date_query' => [['after' => '12 months ago'], 'column' => 'start'], 'type' => ['task', 'meeting', 'call', 'event','email']], 'start' );
	}
	
	public function assignments_department_graph()
	{		
		return $this->get_events_horizontal_bars(['fields' => ['id','user_id'], 'status' => ['started', 'not_started'], 'type' => ['task', 'meeting', 'call', 'event','email'],'cache_contacts' => true]);
	}
		
	public function employee_affairs_graph()
	{		
		return $this->get_events_horizontal_bars( array( 'fields' => array('id','user_id'), 'status' => array( 'started', 'not_started' ), 'type' => array('meeting', 'call', 'event','email'),'cache_contacts' => true) );
	}
		
	public function number_bonuses_managers_graph( )
	{		
		$query_vars = ['fields' => ['sum','user_id']];
		$query_vars = $this->get_query_vars( $query_vars ); 		
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);			
		require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
		$bonuses = usam_get_bonuses( $query_vars );
		if ( empty($bonuses) )
			return array();
		$results = array();
		foreach( $bonuses as $bonus )
		{			
			$results[$bonus->user_id] = empty($results[$bonus->user_id])?$bonus->sum:$results[$bonus->user_id]+$bonus->sum;				
		}				
		$data_graph = array();
		$subordinates = usam_get_subordinates( );		
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);		
		$args = array( 'fields' => array('appeal','user_id'), 'user_id' => $subordinates, 'source' => 'employee', 'orderby' => 'name' );	
		$contacts = usam_get_contacts( $args );	
		foreach ( $contacts as $contact )
		{				
			$value = isset($results[$contact->user_id])?(int)$results[$contact->user_id]:0;
			$data_graph[] = array( 'y_data' => $contact->appeal, 'x_data' => $value, 'label' => sprintf(_n("%s бонуса", "%s бонусов", $value,"usam"), $value ) );			
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	private function contacts_source_graph()
	{		
		return $this->contacts_source();
	}
	
	private function contacts_source_online()
	{		
		return $this->contacts_source(['online' => true, 'status' => 'all']);
	}
		
	private function contacts_source( $args = [] )
	{		
		$args['fields'] = ['count','contact_source'];		
		$args['groupby'] = 'contact_source';			
		$contacts = usam_get_contacts( $args );
		if ( empty($contacts) )
			return array();
		
		$option = get_option('usam_crm_contact_source', array() );
		$contact_sources = maybe_unserialize( $option );
		$sources = array();
		foreach ( $contact_sources as $source )
		{
			$sources[$source['id']] = $source['name'];
		}		
		$data_graph = array();			
		foreach ( $contacts as $contact )
		{			
			if ( isset($sources[$contact->contact_source]) )
				$data_graph[] = array( 'y_data' => $sources[$contact->contact_source], 'x_data' => (int)$contact->count, 'label' => $contact->count );		
		}		
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	private function contacts_load_managers_graph()
	{			
		$subordinates = usam_get_subordinates( );		
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);		
		$args = array( 'fields' => array('count','manager_id'), 'manager_id' => $subordinates, 'groupby' => 'manager_id', 'orderby' => 'name' );	
		$contacts = usam_get_contacts( $args );
		$customers = array();
		foreach ( $contacts as $contact )
		{			
			$customers[$contact->manager_id] = $contact->count;				
		}				
		$data_graph = array();
		foreach ( $subordinates as $manager_id )
		{				
			$value = isset($customers[$manager_id])?(int)$customers[$manager_id]:0;
			$data_graph[] = array( 'y_data' => usam_get_manager_name( $manager_id ), 'x_data' => $value, 'label' => sprintf(_n("%s клиентов", "%s клиентов", $value,"usam"), $value ) );			
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	public function device_online_graph()
	{		
		return $this->device();
	}
	
	private function device( $query_vars = array() )
	{		
		$query_vars = $this->get_query_vars( $query_vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);

		$query_vars['fields'] = ['count','device'];
		$query_vars['groupby'] = 'device';
		$query_vars['online'] = true;
		$visits = usam_get_visits( $query_vars );	
		if ( empty($visits) )
			return array();
		
		$data_graph = array();		
		foreach ( $visits as $visit )
		{						
			$data_graph[] = ['y_data' => $visit->device == 'mobile'?__('Мобильные','usam'):__('ПК','usam'), 'x_data' => (int)$visit->count, 'label' => $visit->count];		
		}		
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	public function sex_graph()
	{		
		return $this->people_sex( );
	}
	
	public function sex_online_graph()
	{		
		return $this->people_sex(['online' => true, 'status' => 'all']);
	}
	
	public function contacts_statuses_graph()
	{		
		return $this->people_statuses( );
	}	
		
	private function people_statuses( $query_vars = array() )
	{		
		$query_vars = $this->get_query_vars( $query_vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = array('count','status');
		$query_vars['groupby'] = 'status';
		$contacts = usam_get_contacts( $query_vars );
		if ( empty($contacts) )
			return array();
		
		$data_graph = [];	
		foreach ( $contacts as $contact )
		{
			$data_graph[] = ['y_data' => usam_get_object_status_name( $contact->status, 'contact' ), 'x_data' => (int)$contact->count, 'label' => $contact->count];		
		}		
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}	
	
	private function people_sex( $query_vars = array() )
	{		
/*		$query_vars = $this->get_query_vars( $query_vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);	*/
		$query_vars['fields'] = 'count';
		$query_vars['number'] = 1;
		
		$query_vars['meta_query'] = [['key' => 'sex', 'compare' => 'NOT EXISTS'],['key' => 'sex', 'compare' => '=', 'value' => ''], 'relation' => 'OR'];
		$count = usam_get_contacts( $query_vars );		
		$data_graph = [['y_data' => __('Не известно','usam'), 'x_data' => (int)$count, 'label' => $count]];	
		foreach ( ['m' => __('Мужчин','usam'), 'w' =>__('Женщин','usam')] as $key => $title )
		{			
			$query_vars['meta_query'] = [['key' => 'sex', 'value' => $key, 'compare' => '=']];
			$count = usam_get_contacts( $query_vars );	
			$data_graph[] = ['y_data' => $title, 'x_data' => (int)$count, 'label' => $count];	
		}		
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );		
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];
	}
		
	public function contact_age_graph()
	{				
	/*	$query_vars = $this->get_query_vars(['fields' => 'count', 'number' => 1]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);	*/
		$query_vars = ['fields' => 'count', 'number' => 1]; 
		$yar = date('Y');
		$data_graph = [];
		for ($i=15; $i<=65; $i += 5)
		{
			$from_age = $yar - $i;
			$to_age = $yar - $i-5;
			$query_vars['meta_query'] = [];
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '<=', 'type' => 'DATE'];
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '>=', 'type' => 'DATE'];
			$count = usam_get_contacts( $query_vars );
			$data_graph[] = ['y_data' => $i, 'x_data' => (int)$count, 'label' => [ __("Возраст","usam").": ".$i." - ".($i+5), __("Количество","usam").": ".$count]];	
		}	
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	public function contact_base_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','date_insert'], 'groupby' => 'month']);
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);	
		
		$contacts = usam_get_contacts( $query_vars );
		if ( empty($contacts) )
			return array();
		$data_graph = array();	
		foreach ( $contacts as $contact )
		{			
			$data_graph[] = ['y_data' => usam_local_date($contact->date_insert,'m.Y'), 'x_data' => (int)$contact->count, 'label' => [__("Дата","usam").": ".usam_local_date($contact->date_insert,'m.Y'), __("Количество","usam").": ".$contact->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	public function user_registered_graph()
	{				
		$query_vars = $this->get_query_vars(['column_date' => 'users.user_registered', 'fields' => ['count'], 'add_fields' => ['user_registered'], 'groupby' => 'month_user_registered', 'user_registered' => true, 'orderby' => 'user_registered']);
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);			
		$contacts = usam_get_contacts( $query_vars );
		if ( empty($contacts) )
			return array();
		$data_graph = array();	
		foreach ( $contacts as $contact )
		{			
			$data_graph[] = ['y_data' => usam_local_date($contact->user_registered,'m.Y'), 'x_data' => (int)$contact->count, 'label' => [__("Дата","usam").": ".usam_local_date($contact->user_registered,'m.Y'), __("Количество","usam").": ".$contact->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
		
	public function sources_visit_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','source'], 'groupby' => 'source', 'source__not_in' => $_SERVER['SERVER_NAME']]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);	
		
		$visits = usam_get_visits( $query_vars );
		if ( empty($visits) )
			return array();
		
		$visits_group = [];
		foreach( $visits as $visit )		
		{
			$name = usam_get_name_source_visit( $visit->source );
			$key = sanitize_title($name['short']);
			if ( isset($visits_group[$key]) )
				$visits_group[$key]['count'] = $visit->count;
			else
				$visits_group[$key] = ['name' => $name['short'], 'count' => $visit->count];
		}	
		usort($visits_group, function($a, $b){  return ($b['count'] - $a['count']); });
		$data_graph = array();	
		foreach ( $visits_group as $visit )
		{					
			$data_graph[] = ['y_data' => $visit['name'], 'x_data' => (int)$visit['count'], 'label' => [ __("Источник","usam").": ".$visit['name'], __("Количество","usam").": ".$visit['count']]];		
		}
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph );
	}	
	
	function inbox_letters_graph( )
	{
		return $this->letters_report(['folder' => 'inbox']);
	}
	
	function sent_letters_graph( )
	{
		return $this->letters_report(['folder' => 'sent']);
	}
	
	function letters_report( $query_vars )
	{
		static $i = 0;		
		$query_vars = $this->get_query_vars( $query_vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		
		$query_vars['fields'] = array('date_insert', 'count', 'folder');
		$query_vars['groupby'] = 'day';	
		$query_vars['mailbox'] = 'user';			
		$emails = usam_get_emails( $query_vars );	

		$data_graph = array();
		foreach ( $emails as $email )
		{		
			array_unshift($data_graph, array( 'y_data' => usam_local_date($email->date_insert,'d.m.Y'), 'x_data' => (int)$email->count, 'label' => array( __("Дата","usam").": ".usam_local_date($email->date_insert,'d.m.Y'), __("Количество","usam").": ".$email->count ) ));	
		}			
		$i++;		
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph );
	}
	
	public function companies_load_managers_graph()
	{			
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);			
		$query_vars = $this->get_query_vars( array( 'fields' => array('count','manager_id'), 'manager_id' => $subordinates, 'groupby' => 'manager_id', 'orderby' => 'name' ) ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
					
		$companies = usam_get_companies( $query_vars );		
		$total = 0;
		foreach ( $companies as $company )
		{			
			$customers[$company->manager_id] = $company->count;		
			$total += $company->count;
		}		
		$data_graph = array();
		foreach ( $subordinates as $manager_id )
		{				
			$value = isset($customers[$manager_id])?(int)$customers[$manager_id]:0;
			$p = $total ? round($value*100/$total,1) : 0;	
			$data_graph[] = array( 'y_data' => usam_get_manager_name( $manager_id ), 'x_data' => $value, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".sprintf(_n("%s клиентов", "%s клиентов", $value,"usam"), $value )."</div>" );				
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}	
	
	public function companies_sales_managers_graph()
	{			
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);			
		$query_vars = $this->get_query_vars(['fields' => ['total_purchased','manager_id'], 'manager_id' => $subordinates, 'groupby' => 'manager_id', 'orderby' => 'name']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
				
		$companies = usam_get_companies( $query_vars );		
		$total = 0;
		foreach ( $companies as $company )
		{			
			$customers[$company->manager_id] = $company->total_purchased;		
			$total += $company->total_purchased;
		}		
		$data_graph = array();
		foreach ( $subordinates as $manager_id )
		{				
			$value = isset($customers[$manager_id])?(int)$customers[$manager_id]:0;
			$p = $total ? round($value*100/$total,1) : 0;	
			$data_graph[] = ['y_data' => usam_get_manager_name( $manager_id ), 'x_data' => $value, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".usam_get_formatted_price( $value )."</div>"];				
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, [$comparison, 'compare'] );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	public function companies_by_category_graph()
	{				
		$groups = usam_get_groups(['fields' => ['count','name'], 'groupby' => 'object_group', 'type' => 'company', 'orderby' => 'COUNT']);
		$total = 0;
		foreach ( $groups as $group )
		{			
			$total += $group->count;
		}			
		$data_graph = array();
		foreach ( $groups as $group )
		{			
			$count = (int)$group->count;				
			$p = round($count*100/$total,1);		
			$data_graph[] = ['y_data' => $group->name, 'x_data' => $count, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".__("Количество","usam").": ".$count."</div>"];		
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );			
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];	
	}
	
	public function sum_by_category_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['total_purchased','group_id'], 'groupby' => 'group']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		
		$companies = array( );	
		$total = 0;
		foreach ( usam_get_companies( $query_vars ) as $company )
		{			
			$total += (int)$company->total_purchased;
			$companies[$company->group_id] = $company->total_purchased;
		}			
		$data_graph = array();
		$groups = usam_get_groups(['fields' => ['count','name'], 'groupby' => 'object_group', 'type' => 'company', 'orderby' => 'COUNT']);
		foreach ( $groups as $group )
		{			
			$sum = isset($companies[$group->id])?$companies[$group->id]:0;
			$p = $total ? round($sum*100/$total,1) : 0;		
			$data_graph[] = array('y_data' => $group->name, 'x_data' => (int)$sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".usam_get_formatted_price($sum)."</div>");		
		}						
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}
	
	function companies_received_graph(  )
	{
		return $this->orders_by_date(['companies__not_in' => 0]);
	}	
	
	function companies_closed_orders_graph( )
	{				
		return $this->orders_by_date(['status' => 'closed', 'column_date' => 'date_paid', 'companies__not_in' => 0]);
	}
		
	public function companies_by_industry_graph( ) 
	{		
		$query_vars = $this->get_query_vars( array( 'fields' => array('count','industry'), 'groupby' => 'industry' ) ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		
		$total = 0;
		$companies = array();
		foreach ( usam_get_companies( $query_vars ) as $company )
		{			
			$total += $company->count;
			$companies[$company->industry] = (int)$company->count;
		}			
		$data_graph = array();
		foreach ( usam_get_companies_industry() as $key => $name )
		{		
			$count = isset($companies[$key])?$companies[$key]:0;	
			$p = round($count*100/$total,1);			
			$data_graph[] = array('y_data' => $name, 'x_data' => $count, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".__("Количество","usam").": ".$count."</div>");		
		}	
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );		
	}	
	
	public function sum_by_industry_graph( ) 
	{
		$query_vars = $this->get_query_vars( array( 'fields' => ['total_purchased','industry'], 'groupby' => 'industry' ) ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
			
		$total = 0;
		$companies = array();
		foreach ( usam_get_companies( $query_vars ) as $company )
		{			
			$total += $company->total_purchased;
			$companies[$company->industry] = $company->total_purchased;
		}			
		$data_graph = array();	
		foreach ( usam_get_companies_industry() as $key => $name )
		{			
			$sum = isset($companies[$key])?$companies[$key]:0;
			$p = $total ? round($sum*100/$total,1) : 0;			
			$data_graph[] = ['y_data' => $name, 'x_data' => (int)$sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".usam_get_formatted_price($sum)."</div>"];
		}	
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );		
	}
	
	public function companies_base_graph()
	{		
		$query_vars = $this->get_query_vars(['fields' => ['count','date_insert'], 'groupby' => 'month']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
			
		$companies = usam_get_companies( $query_vars );
		$data_graph = array();
		foreach ( $companies as $company )
		{			
			$data_graph[] = ['y_data' => usam_local_date($company->date_insert,'m.Y'), 'x_data' => (int)$company->count, 'label' => [__("Дата","usam").": ".usam_local_date($company->date_insert,'m.Y'), __("Количество","usam").": ".$company->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}	
		
	public function sent_sms_graph( )
	{
		return $this->sms_report(['folder' => 'sent']);
	}
	
	private function sms_report( $query_vars )
	{			
		$query_vars = $this->get_query_vars( $query_vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		
		$query_vars['fields'] = ['date_insert', 'count', 'folder'];
		$query_vars['groupby'] = 'day';		
		require_once( USAM_FILE_PATH .'/includes/feedback/sms_query.class.php'  );
		$sms = usam_get_sms_query( $query_vars );	
		$data_graph = array();
		$sum = 0;
		foreach ( $sms as $data )
		{	
			$sum += $data->count;
			array_unshift($data_graph, ['y_data' => usam_local_date($data->date_insert,'d.m.Y'), 'x_data' => (int)$data->count, 'label' => [ __("Дата","usam").": ".usam_local_date($data->date_insert,'d.m.Y'), __("Количество","usam").": ".$data->count]]);	
		}			
		$statistics = [
			['title' => __('Всего','usam'), 'value' => $sum],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}	
	
	public function order_status_processing_speed_graph( )
	{		
		return $this->status_processing_speed( 'order' );
	}	
	
	public function lead_status_processing_speed_graph( )
	{	
		return $this->status_processing_speed( 'lead' );
	}	
	
	private function status_processing_speed( $type )
	{
		$orders_status = usam_get_object_statuses(['order' => 'DESC', 'type' => $type, 'close' => 0, 'not_in__internalname' => 'delete']);		
		$statuses = array();
		foreach ( $orders_status as $value )
		{			
			$statuses[$value->internalname] = $value->name;
		}	
		return $this->processing_speed( $type, $statuses );
	}	
	
	public function sales_funnel_graph( )
	{
		return $this->document_funnel( 'order' );
	}
	
	public function leads_funnel_graph( )
	{
		return $this->document_funnel( 'lead' );
	}	
	
	public function document_funnel( $type )
	{
		require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php'  );	
		$statuses = usam_get_object_statuses(['order' => 'DESC', 'type' => $type, 'not_in__internalname' => 'delete']); 
		$select_statuses = [];
		$statuses_name = [];
		foreach ( $statuses as $status )
		{
			$select_statuses[] = $status->internalname;
			$statuses_name[$status->internalname] = $status->name;
		}	
		$query_vars = $this->get_query_vars(['fields' => ['value'],'value' => $select_statuses, 'object_type' => $type, 'operation' => 'edit', 'field' => 'status', 'orderby' => 'object_id']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);	
		
		$change_orders = usam_get_change_history_query( $query_vars ); 
		$data_graph = [];
		if ( !empty($change_orders) )
		{	
			$sales_funnel = [];	
			foreach ( $change_orders as $change_order )
			{	
				if ( isset($sales_funnel[$change_order->value]) )
					$sales_funnel[$change_order->value]++;
				else
					$sales_funnel[$change_order->value] = 1;
			}					
			asort($sales_funnel);
			
			$count_orders = count($change_orders);
			
			$funnel = [];			
			foreach ( $sales_funnel as $key => $value )
			{				
				$p = round($value*100/$count_orders,1);
				$data_graph[] = ['y_data' => $statuses_name[$key], 'x_data' => $p, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".__("Количество","usam").": ".$value."</div>"];	
				$funnel[] = ['title' => $statuses_name[$key], 'percent' => $p."%"];	
			}
			$s = $count_orders && !empty($sales_funnel['closed']) ? round($sales_funnel['closed']*100/$count_orders,1) : 0;							
		}
		else
		{
			$count_orders = 0;
			$s = 0;
		}
		$statistics = [['title' => __('Количество','usam'), 'value' => $count_orders], ['title' => __('Закрытые','usam'), 'value' => "$s%"]];
		//$this->display_funnel( $funnel );
		return ['graph' => 'horizontal_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}		
	
	private function delivery_methods_graph( )
	{ 
		$query_vars = $this->get_query_vars(['fields' => ['totalprice','name', 'method', 'count'], 'groupby' => 'method', 'orderby' => 'totalprice']); 	
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents( $query_vars );
		$data_graph = array();
		$sum = 0;
		foreach( $documents as $document )
		{
			$sum += $document->totalprice;	
		}	
		foreach( $documents as $document )
		{	
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->totalprice)  );
			$p = $sum > 0 ? round($document->totalprice*100/$sum,1) : 0;
			$data_graph[] = ['y_data' => "$document->name №$document->method", 'x_data' => (float)$document->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}	
	
	private function storage_pickup_graph( )
	{ 
		$query_vars = $this->get_query_vars(['fields' => ['totalprice', 'storage_pickup', 'storage_pickup_name', 'count'], 'groupby' => 'storage_pickup', 'orderby' => 'totalprice']); 	
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents( $query_vars );
		$data_graph = array();
		$sum = 0;
		foreach( $documents as $document )
		{
			$sum += $document->totalprice;	
		}
		foreach( $documents as $k => $document )
		{	
			$name = $document->storage_pickup_name ? $document->storage_pickup_name : __('Удаленный склад', 'usam').' '.$k;
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->totalprice)  );
			$p = $sum > 0 ? round($document->totalprice*100/$sum,1) : 0;
			$data_graph[] = ['y_data' => $name, 'x_data' => $document->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];	
	}	
	
	private function pickup_delivery_graph( )
	{ 
		$query_vars = $this->get_query_vars(['fields' => ['totalprice','delivery_option', 'count'], 'groupby' => 'delivery_option', 'orderby' => 'totalprice']); 	
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents( $query_vars );
		$data_graph = array();
		$sum = 0;
		foreach( $documents as $document )
		{
			$sum += $document->totalprice;	
		}	
		foreach( $documents as $document )
		{	
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->totalprice)  );
			$p = $sum > 0 ? round($document->totalprice*100/$sum,1): 0;
			$data_graph[] = ['y_data' => $document->delivery_option?__("Курьером","usam"):__("Самовывоз","usam"), 'x_data' => $document->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}
		
	private function payment_methods_graph( )
	{ 
		$query_vars = $this->get_query_vars(['fields' => ['sum','name', 'gateway_id', 'count'], 'groupby' => 'gateway_id', 'orderby' => 'sum']); 	
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$documents = usam_get_payments( $query_vars );
		$data_graph = array();
		$sum = 0;
		foreach( $documents as $document )
		{
			$sum += $document->sum;	
		}	
		foreach( $documents as $document )
		{	
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->sum)  );
			$p = $sum ? round($document->sum*100/$sum,1) : 0;
			$data_graph[] = ['y_data' => "$document->name №$document->gateway_id", 'x_data' => (float)$document->sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}

	private function payment_options_graph( )
	{ 
		$query_vars = $this->get_query_vars(['fields' => ['sum','gateway_id','count'], 'groupby' => 'gateway_id', 'orderby' => 'sum']); 			
		$data_graph = array();
		$sum = 0;
		$ids = [];
		$payments = [];
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		foreach( usam_get_payments( $query_vars ) as $document )
		{
			$sum += $document->sum;	
			$ids[] = $document->gateway_id;
			$payments[$document->gateway_id] = $document;
		}		
		if ( $ids )
		{
			$documents = [];
			foreach( usam_get_payment_gateways(['include' => $ids]) as $gateway )
			{
				if ( !isset($documents[$gateway->type]) )
					$documents[$gateway->type] = $payments[$gateway->id];
				else
				{
					$documents[$gateway->type]->sum = $payments[$gateway->id]->sum;
					$documents[$gateway->type]->count = $payments[$gateway->id]->count;
				}
			}		
			foreach( $documents as $type => $document )
			{	
				$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->sum)  );
				$p = $sum ? round($document->sum*100/$sum,1) : 0;
				$data_graph[] = ['y_data' => $type == 'a'?__("Онлайн","usam"):__("При получении","usam"), 'x_data' => (float)$document->sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
			}	
		}
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}	
		
//Нагрузка по менеджерам		
	public function load_managers_graph()
	{			
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);		
		$args = array( 'fields' => array('count','manager_id'), 'manager_id' => $subordinates, 'groupby' => 'manager_id', 'orderby' => 'name' );			
		$companies = usam_get_companies( $args );
		$contacts = usam_get_contacts( $args );
		$customers = array();
		foreach ( $contacts as $contact )
		{			
			$customers[$contact->manager_id] = $contact->count;				
		}	
		foreach ( $companies as $company )
		{			
			$customers[$company->manager_id] = $company->count;				
		}		
		$data_graph = array();
		foreach ( $subordinates as $manager_id )
		{				
			$value = isset($customers[$manager_id])?(int)$customers[$manager_id]:0;
			$data_graph[] = array( 'y_data' => usam_get_manager_name( $manager_id ), 'x_data' => $value, 'label' => sprintf(_n("%s клиентов", "%s клиентов", $value,"usam"), $value ) );			
		}						
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	public function number_tasks_managers_graph()
	{			
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);							
		$query_vars = $this->get_query_vars( array( 'fields' => array('id','user_id','type'), 'user_work' => $subordinates ) ); 
		$events = usam_get_events( $query_vars );	
		$events_manager = array();
		foreach ( $subordinates as $user_id)
		{
			$events_manager[$user_id] = 0;
		}	
		foreach ( $events as $event )
		{			
			if ( isset($events_manager[$event->user_id]) )
				$events_manager[$event->user_id]++;				
			$users = usam_get_event_users( $event->id );
			if ( !empty($users['participant']) )
			{
				foreach ( $users['participant'] as $user_id )
				{
					if ( isset($events_manager[$user_id]) )
						$events_manager[$user_id]++;	
				}
			}
		}
		asort($events_manager);
		$data_graph = array();
		foreach ( $events_manager as $manager_id => $count)
		{				
			$data_graph[] = ['y_data' => usam_get_manager_name( $manager_id ), 'x_data' =>  $count, 'label' => sprintf( _n("%s задача", "%s задач",  $count, "usam" ),  $count )];			
		}						
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];
	}
		
	private function managers_orders( $vars )
	{ 
		$query_vars = $this->get_query_vars(); 
		$query_vars['fields'] = ['date_insert', 'totalprice','manager_id', 'count'];
		$query_vars['groupby'] = 'manager_id';
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars = array_merge( $query_vars, $vars );			
		$orders = usam_get_orders( $query_vars );
		$data_graph = [];
		$sum = 0;
		foreach ( $orders as $order )
		{
			$sum += $order->totalprice;	
		}	
		foreach ( $orders as $order )
		{
			$manager_name = usam_get_manager_name( $order->manager_id );		
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $order->count,"usam"), $order->count, usam_get_formatted_price($order->totalprice)  );
			$p = round($order->totalprice*100/$sum,1);
			$data_graph[] = ['y_data' => $manager_name, 'x_data' => (float)$order->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];	
	}	
		
	public function execution_plan_managers_graph()
	{ 		
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan_query.class.php' );	
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan.class.php' );		
		$user_id = get_current_user_id();
		$subordinates = array();
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);
		
		$sales_plans = usam_get_sales_plans( array('active' => 1 ) );
		foreach ( $sales_plans as $sales_plan )
		{						
			$amounts = usam_get_sales_plan_amounts( $sales_plan->id );			
			$orders_manager = array(); 
			$orders = usam_get_orders( array('status' => 'closed', 'manager_id' => $subordinates, 'date_query' => array( array( 'after' => $sales_plan->from_period, 'before' => $sales_plan->to_period, 'inclusive' => true ) )));
			foreach ( $orders as $order )
			{			
				if ( $sales_plan->target == 'quantity' )
					$value = 1;
				else
					$value = $order->totalprice;
				if ( isset($orders_manager[$order->manager_id]) )
					$orders_manager[$order->manager_id] += $value;		
				else
					$orders_manager[$order->manager_id] = $value;	
			}				
			$data_graph = array();		
			foreach ( $subordinates as $manager_id )
			{			
				$value = isset($orders_manager[$manager_id])?$orders_manager[$manager_id]:0;	
				$amount = isset($amounts[$manager_id])?$amounts[$manager_id]:0;				
				$p = $amount ? round($value*100/$amount,1) : 0;
				$label = $sales_plan->target == 'quantity'? "{$p}% ($value)":"{$p}% (".usam_get_formatted_price($value).")";
				$sum = $sales_plan->target == 'quantity'? $amount:usam_get_formatted_price($amount);	
				$manager_name = usam_get_manager_name( $manager_id );				
				$data_graph[] = array( 'y_data' => $manager_name, 'x_data' => $value, 'label' => "<div class='title_bar_signature'>".$label."</div><div class='description_bar_signature'>".__("План","usam").": $sum</div>" ); 	
			}					
			if ( $sales_plan->target == 'quantity' )
			{
				$title = __('Количество заказов','usam'); 
			}
			else
			{
				$title = __('Сумма продаж','usam'); 
			}
			return ['graph' => 'horizontal_bars', 'data' => $data_graph];
		}
	}
		
	private function site_traffic( )
	{		
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/metrika.class.php' );
		$metrika = new USAM_Yandex_Metrika();
		return $metrika->get_statistics(['date1' => date('Y-m-d', strtotime($this->start_date_interval)), 'date2' => date('Y-m-d'), 'limit' => 10000, 'group' => 'day']);
	}
	
	function site_traffic_graph( )
	{		
		$data_graph = [];			
		$results = $this->site_traffic();
		$visits = 0;
		$users = 0;
		$new_visitors = [];
		if ( $results )
			foreach ( $results as $result )
			{				
				$visits += $result['visits'];
				$users += $result['users'];		
				$new_visitors[] = $result['new_visitors'];
				array_unshift($data_graph, ['y_data' => usam_local_date($result['from']." 00:00:00",'d.m.Y'), 'x_data' => (int)$result['visits'], 'label' => [ __("Дата","usam").": ".usam_local_date($result['from']." 00:00:00",'d.m.Y'), __("Визитов","usam").": ".$result['visits'] ]]);	
			}		
		$statistics = [
			['title' => __('Посетители','usam'), 'value' => $users],
			['title' => __('Визиты','usam'), 'value' => $visits],
			['title' => __('Доля новых посетителей','usam'), 'value' => ($new_visitors?round(array_sum($new_visitors)/count($new_visitors),1):0).'%'],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	function site_views_graph( )
	{		
		$data_graph = [];
		$results = $this->site_traffic();
		$views = 0;
		$bounce_rate = [];
		$page_depth = [];
		if ( $results )
			foreach ( $results as $result )
			{				
				$views += $result['page_views'];
				$bounce_rate[] = $result['bounce_rate'];
				$page_depth[] = $result['page_depth'];				
				array_unshift($data_graph, ['y_data' => usam_local_date($result['from']." 00:00:00",'d.m.Y'), 'x_data' => (int)$result['page_views'], 'label' => [__("Дата","usam").": ".usam_local_date($result['from']." 00:00:00",'d.m.Y'), __("Просмотров","usam").": ".$result['page_views'] ]]);	
			}		
		$statistics = [
			['title' => __('Отказов','usam'), 'value' => ($bounce_rate?round(array_sum($bounce_rate)/count($bounce_rate),1):0).'%'],
			['title' => __('Просмотры','usam'), 'value' => $views],
			['title' => __('Глубина просмотров','usam'), 'value' => $page_depth?round(array_sum($page_depth)/count($page_depth),1):0],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	function campaign_transitions_graph( )
	{			
		global $wpdb;
		$data_graph = array();
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);			
			$results = $wpdb->get_results( "SELECT *, COUNT(campaign_id) AS count FROM ".USAM_TABLE_CAMPAIGN_TRANSITIONS." WHERE campaign_id={$id} GROUP BY DAY(date_insert), MONTH(date_insert), YEAR(date_insert)" );			
			foreach ( $results as $result )
			{
				array_unshift($data_graph, ['y_data' => usam_local_date($result->date_insert,'d.m.Y'), 'x_data' => (int)$result->count, 'label' => [ __("Дата","usam").": ".usam_local_date($result->date_insert,'d.m.Y'), __("Количество","usam").": ". (int)$result->count ]]);		
			}
		}
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}	
	
	function campaigns_transitions_graph( )
	{			
		global $wpdb;
		$data_graph = array();			
		$query_vars = $this->get_query_vars(); 		
		$where = '1=1 ';
		if ( !empty($query_vars['date_query']) ) 
		{
			require_once( USAM_FILE_PATH . '/includes/query/date.php' );
			$this->date_query = new USAM_Date_Query( $query_vars['date_query'], USAM_TABLE_CAMPAIGN_TRANSITIONS );
			$where .= $this->date_query->get_sql();	
		}
		$sql = "SELECT *, COUNT(campaign_id) AS count FROM ".USAM_TABLE_CAMPAIGN_TRANSITIONS." WHERE $where GROUP BY DAY(date_insert), MONTH(date_insert), YEAR(date_insert)";
		$results = $wpdb->get_results( $sql );			
		foreach ( $results as $result )
		{
			array_unshift($data_graph, ['y_data' => usam_local_date($result->date_insert,'d.m.Y'), 'x_data' => (int)$result->count, 'label' => [ __("Дата","usam").": ".usam_local_date($result->date_insert,'d.m.Y'), __("Количество","usam").": ". (int)$result->count ]]);		
		}
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	function baskets_graph( )
	{			
		$query_vars = $this->get_query_vars(); 
		$query_vars['fields'] = array('date_insert', 'totalprice','quantity');
		$query_vars['paid'] = 2;
		$query_vars['groupby'] = 'day';
		
		require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );
		$baskets = usam_get_users_baskets( $query_vars );
		$basket_sum = 0;
		$count = 0;
		$data_graph = array();
		foreach ( $baskets as $basket )
		{
			$basket_sum += $basket->totalprice;
			$count += $basket->quantity;
			array_unshift($data_graph, array( 'y_data' => usam_local_date($basket->date_insert,'d.m.Y'), 'x_data' => (float)$basket->totalprice, 'label' => array( __("Дата","usam").": ".usam_local_date($basket->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $basket->totalprice ) ) ));		
		}		
		$yandex = $this->site_traffic();
		if ( empty($yandex['visits']) )
			$conversion = 0;
		else
			$conversion = round($count/$yandex['visits']*100,2);			
		$statistics = [
			['title' => __('Конверсия','usam'), 'value' => $conversion],
			['title' => __('Количество товаров','usam'), 'value' => $count],
			['title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($basket_sum)],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}	
	
	public function managerial_contribution_sales_graph( )
	{ 
		return $this->managers_orders(['status' => 'closed']);
	}
	
	function orders_by_date( $vars = [] )
	{	
		$query_vars = $this->get_query_vars( $vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = ['date_insert', 'totalprice', 'count'];
		$query_vars['groupby'] = 'day';			
		$orders = usam_get_orders( $query_vars );			
			
		$orders_sum = 0;
		$count = 0;
		$data_graph = [];
		foreach ( $orders as $order )
		{
			$orders_sum += $order->totalprice;
			$count += $order->count;
			array_unshift($data_graph, ['y_data' => usam_local_date($order->date_insert,'d.m.Y'), 'x_data' => (float)$order->totalprice, 'label' => [ __("Дата","usam").": ".usam_local_date($order->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $order->totalprice ) ]]);	
		}					
		$yandex = $this->site_traffic();
		if ( empty($yandex['visits']) )
			$conversion = 0;
		else
			$conversion = round($count/$yandex['visits']*100,2);	
		
		$statistics = [
			['title' => __('Конверсия','usam'), 'value' => $conversion],
			['title' => __('Количество','usam'), 'value' => $count],
			['title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($orders_sum)],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	function received_graph(  )
	{
		return $this->orders_by_date();
	}	
		
	function paid_orders_graph( )
	{				
		return $this->orders_by_date(['paid' => 2, 'column_date' => 'date_paid']);
	}
	
	function profit_graph( )
	{					
		$query_vars = $this->get_query_vars( ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = array('date_insert', 'cost_price', 'totalprice');
		$query_vars['groupby'] = 'day';	
		$orders = usam_get_orders( $query_vars );			
			
		$sum = 0;
		$profit_sum = 0;
		$data_graph = array();
		foreach ( $orders as $order )
		{
			$sum += $order->totalprice;
			$profit = $order->cost_price != 0.00?($order->totalprice-$order->cost_price):0;
			$profit_sum += $profit;
			array_unshift($data_graph, ['y_data' => usam_local_date($order->date_insert,'d.m.Y'), 'x_data' => (float)$profit, 'label' => [ __("Дата","usam").": ".usam_local_date($order->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $profit ) ]]);	
		}				
		$s = $profit_sum ? round($profit_sum*100/$sum, 0) : 0;
		$statistics = array( 			
			array( 'title' => __('Наценка','usam'), 'value' => "$s%" ),
			array( 'title' => __('Прибыль','usam'), 'value' => usam_get_formatted_price($profit_sum) ),
			array( 'title' => __('Оборот','usam'), 'value' => usam_get_formatted_price($sum) ),
		);		
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics );
	}	
//ЛИДЫ	
	public function leads_new_graph()
	{ 
		return $this->leads_by_date();
	}	

	public function leads_substandard_graph()
	{ 
		return $this->leads_by_date(['status' => 'poor_quality', 'column_date' => 'date_status_update']);
	}
	
	public function leads_managers_in_work_graph()
	{ 
		$statuses = usam_get_object_statuses(['type' => 'lead', 'fields' => 'internalname', 'close' => 0, 'not_in__internalname' => 'delete']);		
		return $this->managers_leads(['status' => $statuses]);
	}
	
	public function leads_brought_to_order_graph()
	{ 
		return $this->managers_leads(['status' => 'order']);
	}
	
	public function managers_leads( $vars = [] )
	{ 
		$data_graph = [];		
		$query_vars = $this->get_query_vars(); 
		$query_vars['fields'] = ['date_insert', 'totalprice','manager_id', 'count'];
		$statuses = usam_get_object_statuses(['type' => 'lead', 'fields' => 'internalname', 'not_in__internalname' => 'delete']);
		$query_vars['statuses'] = $statuses;
		$query_vars['groupby'] = 'manager_id';
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars = array_merge( $query_vars, $vars );			
		require_once(USAM_FILE_PATH.'/includes/document/leads_query.class.php');
		$items = usam_get_leads( $query_vars );			
		$sum = 0;
		foreach ( $items as $item )
		{
			$sum += $item->totalprice;	
		}	
		foreach ( $items as $item )
		{
			$manager_name = usam_get_manager_name( $item->manager_id );		
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $item->count,"usam"), $item->count, usam_get_formatted_price($item->totalprice)  );
			$p = $sum>0 ? round($item->totalprice*100/$sum,1) : 0;
			$data_graph[] = ['y_data' => $manager_name, 'x_data' => (float)$item->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>"];
		}
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];	
	}	
	
	function leads_by_date( $vars = [] )
	{	
		$query_vars = $this->get_query_vars( $vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = ['date_insert', 'totalprice', 'count'];
		$query_vars['groupby'] = 'day';			
		require_once(USAM_FILE_PATH.'/includes/document/leads_query.class.php');
		$items = usam_get_leads( $query_vars );			
			
		$sum = 0;
		$count = 0;
		$data_graph = [];
		foreach ( $items as $item )
		{
			$sum += $item->totalprice;
			$count += $item->count;
			array_unshift($data_graph, ['y_data' => usam_local_date($item->date_insert,'d.m.Y'), 'x_data' => (float)$item->totalprice, 'label' => [ __("Дата","usam").": ".usam_local_date($item->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $item->totalprice ) ]]);	
		}					
		$yandex = $this->site_traffic();
		if ( empty($yandex['visits']) )
			$conversion = 0;
		else
			$conversion = round($count/$yandex['visits']*100,2);	
		
		$statistics = [
			['title' => __('Конверсия','usam'), 'value' => $conversion],
			['title' => __('Количество','usam'), 'value' => $count],
			['title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($sum)],
		];
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	private function processing_speed( $object_type, $statuses )
	{
		require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php'  );	
		
		$query_vars = $this->get_query_vars(['fields' => ['date_insert','object_id','operation','old_value','value'], 'object_type' => $object_type, 'field' => 'status', 'orderby' => 'object_id']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = array_map('intval', (array)$selected);
		
		$change_history = usam_get_change_history_query( $query_vars );
		if ( empty($change_history) )
			return array();
		
		$change_status = array();	
		$order = array();
		$count_orders = array();
		$totaltime = 0;
		foreach ( $change_history as $change )
		{	
			if ( $change->operation != 'add' && !empty($order->date_insert) && $order->object_id == $change->object_id )	
			{	
				$r = strtotime($change->date_insert) - strtotime($order->date_insert);
				$totaltime += $r;
				if ( !isset($count_orders[$order->object_id]) )					
					$count_orders[$order->object_id] = 1;
				$change_status[$change->old_value][] = $r;
			}
			$order = $change;
		} 
		$data_graph = array();	
		$time = time();
		foreach ( $statuses as $key => $title )
		{			
			if ( isset($change_status[$key]) ) 
			{
				$count = count($change_status[$key]);
				$data = array_sum($change_status[$key]) / $count;
				$data = round($data, 0);				
				$label = human_time_diff( $time - $data, $time );				
			}
			else
			{
				$data = 0;
				$label = '';
			}
			$data_graph[] = array( 'y_data' => $title, 'x_data' => $data, 'label' => $label );		
		}	
		$sum = array_sum($count_orders);
		$s = $totaltime ? human_time_diff( $time - round($totaltime/$sum,0), $time ) : 0;
		$statistics = array( 
			array( 'title' => __('Количество','usam'), 'value' => $sum ),
			array( 'title' => __('Среднее время обработки','usam'), 'value' => $s ),
		);			
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph, 'statistics' => $statistics );
	}
	
	private function shipped_documents_by_date( $vars )
	{
		$query_vars = $this->get_query_vars( $vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = array('date_insert', 'totalprice','count');
		$query_vars['groupby'] = 'day';
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents( $query_vars );	
		$orders_sum = 0;
		$count = 0;
		$data_graph = array();
		foreach ( $documents as $document )
		{
			$orders_sum += $document->totalprice;
			$count += $document->count;
			array_unshift($data_graph, array( 'y_data' => usam_local_date($document->date_insert,'d.m.Y'), 'x_data' => (float)$document->totalprice, 'label' => array( __("Дата","usam").": ".usam_local_date($document->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $document->totalprice ) ) ));	
		}		
	/*	$statistics = array( 
			array( 'title' => __('Конверсия','usam'), 'value' => $conversion ),
			array( 'title' => __('Количество','usam'), 'value' => $count ),
			array( 'title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($orders_sum) ),
		);		 */
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph );
	}
//УПРАВЛЕНИЕ КУРЬЕРАМИ	
	public function courier_graph( )
	{ 
		return $this->courier_delivery_documents( array('status' => array('expect_tc', 'referred', 'courier', 'shipped' ), 'storage_pickup' => 0) );
	}		
	
	function shipped_document_status_processing_speed_graph( )
	{	
		$statuses = usam_get_object_statuses(['type' => 'shipped', 'fields' => 'code=>name', 'not_in__internalname' => 'delete']);	
		return $this->processing_speed( 'shipped_document', $statuses );
	}	
	
	function shipped_documents_received_graph(  )
	{
		return $this->shipped_documents_by_date( array('status' => ['expect_tc', 'referred', 'courier', 'shipped'], 'storage_pickup' => 0) );
	}
						
	private function courier_delivery_documents( $vars )
	{ 
		$query_vars = $this->get_query_vars( $vars ); 
		$query_vars['fields'] = array('totalprice','courier', 'count');
		$query_vars['groupby'] = 'courier';
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['courier'] = array_map('intval', (array)$selected);			
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents( $query_vars );
		$data_graph = array();
		$sum = 0;
		foreach( $documents as $document )
		{
			$sum += $document->totalprice;	
		}	
		foreach( $documents as $document )
		{
			$manager_name = usam_get_manager_name( $document->courier );		
			$description = sprintf(_n("%s на сумму %s", "%s на сумму %s", $document->count,"usam"), $document->count, usam_get_formatted_price($document->totalprice)  );
			$p = $sum ? round($document->totalprice*100/$sum,1):0;
			$data_graph[] = array( 'y_data' => $manager_name, 'x_data' => (float)$document->totalprice, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$description</div>" );
		}
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}		
	
	function uploading_files_graph(  )
	{	
		$query_vars = $this->get_query_vars(); 
		$selected = $this->get_filter_value('manager');
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = array('date_insert', 'size','count');
		$query_vars['groupby'] = 'day';			
		$files = usam_get_files( $query_vars );			
			
		$filesize = 0;
		$count = 0;
		$data_graph = array();
		foreach ( $files as $file )
		{
			$filesize += $file->size;
			$count += $file->count;
			array_unshift($data_graph, array( 'y_data' => usam_local_date($file->date_insert,'d.m.Y'), 'x_data' => round($file->size/1000,1), 'label' => array( __("Дата","usam").": ".usam_local_date($file->date_insert,'d.m.Y'), __("Размер","usam").": ".size_format( $file->size ) ) ));	
		}					
		$statistics = [
			['title' => __('Количество','usam'), 'value' => $count],
			[ 'title' => __('Размер','usam'), 'value' => size_format($filesize)],
		];	
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	function payment_received_graph(  )
	{
		return $this->documents_by_date(['type' => 'payment_received']);
	}	
		
	function payment_order_graph( )
	{				
		return $this->documents_by_date(['type' => 'payment_order']);
	}
		
	function invoices_sent_graph( )
	{			
		return $this->documents_by_date(['type' => 'invoice', 'status' => 'sent']);
	}
	
	function invoices_paid_graph( )
	{			
		return $this->documents_by_date(['type' => 'invoice', 'status' => 'paid']);
	}
	
	function invoices_draft_graph( )
	{			
		return $this->documents_by_date(['type' => 'invoice', 'status' => 'draft']);
	}
	
	function invoices_notpaid_graph( )
	{			
		return $this->documents_by_date(['type' => 'invoice', 'status' => 'notpaid']);
	}
	
	function suggestions_sent_graph( )
	{			
		return $this->documents_by_date(['type' => 'suggestion', 'status' => 'sent']);
	}
	
	function suggestions_approved_graph( )
	{			
		return $this->documents_by_date(['type' => 'suggestion', 'status' => 'approved']);
	}
	
	function suggestions_draft_graph( )
	{			
		return $this->documents_by_date(['type' => 'suggestion', 'status' => 'draft']);
	}
	
	function suggestions_declained_graph( )
	{			
		return $this->documents_by_date(['type' => 'suggestion', 'status' => 'declained']);
	}
	
	function documents_by_date( $vars )
	{	
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$query_vars = $this->get_query_vars( $vars ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);	
		$query_vars['fields'] = ['date_insert','sum','count'];
		$query_vars['groupby'] = 'day';
		$documents = usam_get_documents( $query_vars );	
		
		$orders_sum = 0;
		$count = 0;
		$data_graph = [];
		foreach ( $documents as $document )
		{
			$orders_sum += $document->sum;
			$count += $document->count;
			array_unshift($data_graph, ['y_data' => usam_local_date($document->date_insert,'d.m.Y'), 'x_data' => (float)$document->sum, 'label' => [ __("Дата","usam").": ".usam_local_date($document->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $document->sum ) ]]);					
		}		
		$statistics = [['title' => __('Количество','usam'), 'value' => $count],['title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($orders_sum)]];		
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	function products_competitors_graph( )
	{	
		require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
		$query_vars = $this->get_query_vars(  ); 
		$query_vars['fields'] = array('date_insert', 'count');
		$query_vars['groupby'] = 'month';			
		$products = usam_get_products_competitors( $query_vars );			
			
		$sum = 0;
		$count = 0;
		$data_graph = array();
		foreach ($products as $product)
		{
			$count += $product->count;
			array_unshift($data_graph, array( 'y_data' => usam_local_date($product->date_insert,'d.m.Y'), 'x_data' => (float)$product->count, 'label' => array( __("Дата","usam").": ".usam_local_date($product->date_insert,'d.m.Y'))));	
		}				
		$statistics = array( 
		//	array( 'title' => __('Конверсия','usam'), 'value' => $conversion ),
			array( 'title' => __('Количество','usam'), 'value' => $count ),
		);			
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics );
	}
	
	function competitors_graph( )
	{
		require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
		$query_vars = $this->get_query_vars(); 
		$query_vars['fields'] = array('site_id', 'count');
		$query_vars['groupby'] = 'site_id';		
		$products = usam_get_products_competitors( $query_vars );
		$sum = 0;
		foreach ( $products as $product )
		{
			$sum += $product->count;	
		}
		$data_graph = array();	
		foreach ( $products as $product )
		{
			$site = usam_get_parsing_site( $product->site_id );
			$p = round($product->count*100/$sum,1);
			$data_graph[] = array( 'y_data' => $site['name'], 'x_data' => (int)$product->count, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>$product->count</div>");
		}
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}
	
	function rise_prices_competitors_graph( )
	{
		return $this->rise_prices_competitors(['meta_price_query' => ['key' => 'difference', 'value' => '0', 'compare' => '>']]);
	}
	
	function falling_prices_competitors_graph( )
	{
		return $this->rise_prices_competitors(['meta_price_query' => ['key' => 'difference', 'value' => '0', 'compare' => '<']]);
	}
	
	function rise_prices_competitors( $vars )
	{
		require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
		$query_vars = $this->get_query_vars( $vars ); 
		$query_vars['fields'] = array('date_insert', 'count');
		$query_vars['groupby'] = 'month';
		$products = usam_get_products_competitors( $query_vars );			
			
		$sum = 0;
		$count = 0;
		$data_graph = array();
		foreach ($products as $product)
		{
			$count += $product->count;
			array_unshift($data_graph, array( 'y_data' => usam_local_date($product->date_insert,'d.m.Y'), 'x_data' => (float)$product->count, 'label' => array( __("Дата","usam").": ".usam_local_date($product->date_insert,'d.m.Y'))));	
		}				
		$statistics = array( 
		//	array( 'title' => __('Конверсия','usam'), 'value' => $conversion ),
			array( 'title' => __('Количество','usam'), 'value' => $count ),
		);			
		return array( 'graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics );
	}
	
	public function calls_graph( )
	{
		$query_vars = $this->get_query_vars( ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = array('date_insert', 'price','count');
		$query_vars['groupby'] = 'day';			
		require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );
		$calls = usam_get_calls( $query_vars );			
			
		$sum = 0;
		$count = 0;
		$data_graph = array();
		foreach ( $calls as $call )
		{
			$sum += $call->price;
			$count += $call->count;
			array_unshift($data_graph, ['y_data' => usam_local_date($call->date_insert,'d.m.Y'), 'x_data' => (float)$call->count, 'label' => [ __("Дата","usam").": ".usam_local_date($call->date_insert,'d.m.Y'), __("Сумма","usam").": ".usam_get_formatted_price( $call->price )]]);	
		}					
		$statistics = [
		//	array( 'title' => __('Конверсия','usam'), 'value' => $conversion ),
			array( 'title' => __('Количество','usam'), 'value' => $count ),
			array( 'title' => __('На сумму','usam'), 'value' => usam_get_formatted_price($sum) ),
		];		
		return ['graph' => 'vertical_bars', 'data' => $data_graph, 'statistics' => $statistics];
	}
	
	private function number_contacting_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','date_insert'], 'groupby' => 'month']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);	
		
		require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
		$events = usam_get_contactings( $query_vars );
		if ( empty($events) )
			return [];
		$data_graph = [];	
		foreach ( $events as $event )
		{			
			$data_graph[] = ['y_data' => usam_local_date($event->date_insert,'m.Y'), 'x_data' => (int)$event->count, 'label' => [ __("Дата","usam").": ".usam_local_date($event->date_insert,'m.Y'), __("Количество","usam").": ".$event->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	private function contacting_by_groups_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','group_id'], 'groupby' => 'group']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);		
		$events = [];	
		$total = 0;	
		require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
		foreach ( usam_get_contactings( $query_vars ) as $event )
		{			
			$total += (int)$event->count;
			$events[$event->group_id] = (int)$event->count;
		}		
		$data_graph = [];		
		$groups = usam_get_groups(['fields' => ['id','name']]);			
		foreach ( $groups as $group )
		{			
			$sum = isset($events[$group->id])?$events[$group->id]:0;
			$p = $total ? round($sum*100/$total,1) : 0;		
			$data_graph[] = ['y_data' => $group->name, 'x_data' => $sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".usam_currency_display($sum)."</div>"];		
		}	
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}	
	
	private function tasks_by_groups_graph()
	{				
		return $this->event_by_groups( 'task' );
	}
	
	private function event_by_groups( $type )
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','group_id'], 'groupby' => 'group', 'type' => $type]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_work'] = array_map('intval', (array)$selected);		
		$events = [];	
		$total = 0;	
		foreach ( usam_get_events( $query_vars ) as $event )
		{			
			$total += (int)$event->count;
			$events[$event->group_id] = (int)$event->count;
		}		
		$data_graph = [];
		$groups = usam_get_groups(['fields' => ['id','name'], 'type' => $type]);			
		foreach ( $groups as $group )
		{			
			$sum = isset($events[$group->id])?$events[$group->id]:0;
			$p = $total ? round($sum*100/$total,1) : 0;		
			$data_graph[] = ['y_data' => $group->name, 'x_data' => $sum, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".usam_currency_display($sum)."</div>"];		
		}	
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );	
	}
	
	private function tasks_by_departments_graph()
	{				
		return $this->event_by_departments( 'task' );
	}	
	
	private function event_by_departments( $type )
	{				
		$query_vars = $this->get_query_vars(['fields' => ['id'], 'type' => $type]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_work'] = array_map('intval', (array)$selected);		
		
		$events = usam_get_events( $query_vars );	
		if ( empty($events) )
			return [];
		
		require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
		$departments = usam_get_departments();
		
		$results = [];	
		foreach ( $events as $event_id )
		{	
			$event_users = usam_get_event_users( $event_id );				
			foreach ( $event_users as $user_ids )	
			{
				foreach ( $user_ids as $user_id )	
				{
					$contact = usam_get_contact( $user_id, 'user_id' );
					if ( $contact )
					{
						$department = usam_get_contact_metadata($contact['id'], 'department');	
						if ( !isset($results[$department]) )
							$results[$department] = 1;
						else
							$results[$department]++;
					}
				}
			}
		}		
		$data_graph = array();
		foreach ( $departments as $department )
		{			
			$value = isset($results[$department->id])?(int)$results[$department->id]:0;
			$data_graph[] = array( 'y_data' => $department->name, 'x_data' => $value, 'label' => "$value" );		
		}			
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	private function autor_tasks_by_departments_graph()
	{				
		return $this->autor_event_by_departments( 'task' );
	}
	
	private function autor_event_by_departments( $type )
	{				
		$query_vars = $this->get_query_vars(['fields' => ['id', 'user_id'], 'type' => $type]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_work'] = array_map('intval', (array)$selected);		
		$events = usam_get_events( $query_vars );	
		if ( empty($events) )
			return array();
		
		require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
		$departments = usam_get_departments();
		
		$results = array();	
		foreach ( $events as $event )
		{			
			$event_users = usam_get_event_users( $event->id );
			if ( $event_users )
			{				
				$contact = usam_get_contact( $event->user_id, 'user_id' );
				if ( $contact )
				{
					$department = usam_get_contact_metadata($contact['id'], 'department');		
					if ( !isset($results[$department]) )
						$results[$department] = 1;
					else
						$results[$department]++;
				}
			}
		}		
		$data_graph = array();
		foreach ( $departments as $department )
		{			
			$value = isset($results[$department->id])?(int)$results[$department->id]:0;
			$data_graph[] = array( 'y_data' => $department->name, 'x_data' => $value, 'label' => "$value" );		
		}			
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return array( 'graph' => 'horizontal_bars', 'data' => $data_graph );
	}
	
	private function number_tasks_graph()
	{				
		return $this->number_events( 'task' );
	}	
	
	private function number_events( $type )
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','date_insert'], 'groupby' => 'month', 'type' => $type]); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_work'] = array_map('intval', (array)$selected);	
		
		$events = usam_get_events( $query_vars );
		if ( empty($events) )
			return [];
		$data_graph = [];	
		foreach ( $events as $event )
		{			
			$data_graph[] = ['y_data' => usam_local_date($event->date_insert,'m.Y'), 'x_data' => (int)$event->count, 'label' => [ __("Дата","usam").": ".usam_local_date($event->date_insert,'m.Y'), __("Количество","usam").": ".$event->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	private function number_chat_graph()
	{				
		$query_vars = $this->get_query_vars(['fields' => ['count','date_insert'], 'groupby' => 'month']); 		
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');
		$messages = usam_get_chat_messages( $query_vars );	
		if ( empty($messages) )
			return [];
		$data_graph = [];	
		foreach ( $messages as $message )
		{			
			$data_graph[] = ['y_data' => usam_local_date($message->date_insert,'m.Y'), 'x_data' => (int)$message->count, 'label' => [__("Дата","usam").": ".usam_local_date($message->date_insert,'m.Y'), __("Количество","usam").": ".$message->count]];		
		}			
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	private function number_parser_graph()
	{				
		global $wpdb;
		$where = '1=1';
		if ( $this->start_date_interval )
			$where .= " AND date_insert>='$this->start_date_interval'";		
		if ( $this->end_date_interval )
			$where .= " AND date_insert<='$this->start_date_interval'";	
		$results = $wpdb->get_results("SELECT date_insert, COUNT(*) AS count FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE $where GROUP BY DAY(date_insert), MONTH(date_insert), YEAR(date_insert) ORDER BY date_insert DESC");	
		if ( empty($results) )
			return [];
		$data_graph = [];		
		foreach ( $results as $result )
		{			
			$data_graph[] = ['y_data' => usam_local_date($result->date_insert,'d.m.Y'), 'x_data' => (int)$result->count, 'label' => [__("Дата","usam").": ".usam_local_date($result->date_insert,'m.Y'), __("Количество","usam").": ".$result->count]];		
		}	
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	private function product_number_parser_graph()
	{				
		global $wpdb;
		$where = "meta_key LIKE 'webspy_rule_%' ";
		if ( $this->start_date_interval )
			$where .= " AND meta_value>='$this->start_date_interval'";		
		if ( $this->end_date_interval )
			$where .= " AND meta_value<='$this->start_date_interval'";	
		$results = $wpdb->get_results("SELECT meta_value, COUNT(*) AS count FROM ".USAM_TABLE_PRODUCT_META." WHERE $where GROUP BY DAY(meta_value), MONTH(meta_value), YEAR(meta_value) ORDER BY meta_value DESC");
		if ( empty($results) )
			return [];
		$data_graph = [];		
		foreach ( $results as $result )
		{			
			$data_graph[] = ['y_data' => usam_local_date($result->meta_value,'d.m.Y'), 'x_data' => (int)$result->count, 'label' => [__("Дата","usam").": ".usam_local_date($result->meta_value,'m.Y'), __("Количество","usam").": ".$result->count]];		
		}	
		return ['graph' => 'vertical_bars', 'data' => $data_graph];
	}
	
	private function chats_source_graph( )
	{		
		$args['fields'] = ['count','channel'];		
		$args['groupby'] = 'channel';			
		
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
		$dialogs = usam_get_chat_dialogs( $args );	
		if ( empty($dialogs) )
			return [];
			
		$data_graph = [];			
		foreach ( $dialogs as $dialog )
		{			
			$data_graph[] = ['y_data' => $dialog->channel, 'x_data' => (int)$dialog->count, 'label' => $dialog->count];		
		}		
		$comparison = new USAM_Comparison_Object( 'x_data', 'ASC' );
		usort( $data_graph, array( $comparison, 'compare' ) );
		return ['graph' => 'horizontal_bars', 'data' => $data_graph];
	}
}
?>