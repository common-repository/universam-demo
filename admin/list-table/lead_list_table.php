<?php			
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_lead extends USAM_List_Table  
{		
	protected $orderby = 'appeal';	
		
	function column_appeal( $item ) 
    { 			
		$url = add_query_arg( array('page' => 'personnel','tab' => 'tasks', 'table' => 'tasks', 'user' => $item['user_id']), admin_url('admin.php'));	
		if ( !empty($item['contact_id']) )
		{
			$name = "<div class='user_block'>";
			$name .= "<a href='$url' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item['contact_id'] ) )."'></a>";
			$name .= '<a class="row-title js-object-value" href="'.$url.'">'.$item['appeal'].'</a>';	
			$name .= "<div class='user_block'>";
		}
		else
			$name = __('Нет в базе','usam');
		echo $name;	
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'appeal'      => array('appeal', false),				
			'does'        => array('does', false),	
			'commissioned' => array('commissioned', false),		
			'helps'      => array('helps', false),				
			);
		return $sortable;
	}
	
	function get_columns()
	{		
        $columns = array(           
			'appeal'         => __('Контакт', 'usam'),				
			'does'           => __('Делает', 'usam'),		
			'expired'        => __('Из них просрочено', 'usam'),					
			'commissioned'   => __('Поручил', 'usam'),					
			'helps'          => __('Помогает', 'usam'),				
        );	
		
		$managers = usam_get_subordinates( );	
		if ( !empty($managers) )
			$columns['commissioned2'] = __('Поручили другие', 'usam');
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array( 'does', 'commissioned', 'helps');
    }
	
	function prepare_items() 
	{			
		$author = get_current_user_id();		
		$args = array( 'type' => array('assigned_task', 'task'), 'status__not_in' => array( 2, 3, 4 ), 'cache_contacts' => true );				
	
		$managers = $subordinates = usam_get_subordinates( );			
		
		if ( empty($managers) )
			$args['author'] = $author;
		
		$managers[] = $author;			
		$args['user_work'] = $managers;
		$events = usam_get_events( $args );			
		
		foreach ( $events as $event )
		{
			$event_users = usam_get_event_users( $event->id );	
			$users = array();
			foreach ( $event_users as $user_ids )
				$users = array_merge( $users, $user_ids );		
			
			if ( in_array( $event->user_id, $managers) )
				$users[] = $event->user_id;				
			
			$users = array_unique($users);
			
			$managers = array_merge( $managers, $users );			
			foreach ( $users as $user_id )
			{			
				if ( !isset($this->items[$user_id]) )
					$this->items[$user_id] = array( 'does' => 0, 'commissioned' => 0, 'commissioned2' => 0, 'expired' => 0, 'helps' => 0, 'user_id' => $user_id, 'appeal' => '' );
								
				if ( $event->type == 'assigned_task' )
				{
					if ( $event->user_id == $author )
						$this->items[$user_id]['commissioned']++;
					else
					{
						if ( in_array($user_id, $subordinates) )
							$this->items[$user_id]['commissioned2']++;					
						continue;
					}
				}
				else
					$this->items[$user_id]['helps']++;
				
				$event_start_time = strtotime(get_date_from_gmt($event->start));			
				$start_day = mktime(0, 0, 0, date('m'), date_i18n('d')+1, date('Y'));
				$event_end_time = strtotime(get_date_from_gmt($event->end));	
				
				if ( $event_start_time <= $start_day  )
					$this->items[$user_id]['does']++;
				
				if ( $event_end_time < current_time('timestamp' ) )
					$this->items[$user_id]['expired']++;			
			}				
		}
		$managers = array_unique($managers);
		$contacts = usam_get_contacts( array( 'user_id' => $managers, 'source' => 'employee' ) );	
		foreach ( $contacts as $contact )
		{		
			if ( !isset($this->items[$contact->user_id]) )
				$this->items[$contact->user_id] = array( 'does' => 0, 'commissioned' => 0, 'expired' => 0, 'helps' => 0, 'user_id' => $contact->user_id );
			
			$this->items[$contact->user_id]['contact_id'] = $contact->id;
			$this->items[$contact->user_id]['appeal'] = $contact->appeal;
		}	
		$this->items = array_values($this->items);
		$this->total_items = count($this->items);		
		$this->forming_tables();
	}
}
?>