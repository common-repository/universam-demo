<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_export_list_table.class.php' );	
class USAM_Export_Event_List_Table extends USAM_Export_List_Table
{		
	function column_object_type( $item )
	{
		$objects = usam_get_event_links( $item->id ) ;
		foreach ( $objects as $object )
		{		
			$result = usam_get_object( $object );
			if ( !empty($result['name']) )
				echo $result['name']." - ".$result['title']."\n";
		}
	} 
	
	function column_manager( $item ) 
	{	
		if ( $item->user_id )
		{
			echo usam_get_manager_name( $item->user_id );
		}
	}	
	
	function column_status( $item ) 
	{		
		echo usam_get_object_status_name( $item->status, $item->type );
	}
	
	function column_time( $item )
	{		
		if ( !empty($item->start) )
			echo usam_local_date( $item->start );
		if ( !empty($item->end) )
			echo " - ".usam_local_date( $item->end );
	} 
		
	function column_reminder( $item )
	{
		$reminder_date = usam_get_event_reminder_date( $item->id );
		if ( !empty($reminder_date) )
		{
			echo usam_local_date( $reminder_date, get_option( 'date_format', 'Y/m/d' ).' H:i' );
		}
	} 
	
	function column_calendar_id( $item )
	{
		echo usam_get_calendar_name_by_id( $item->calendar );
	} 

	function column_participants( $item )
	{
		$users = usam_get_event_users( $item->id );		
		if ( !empty($users['participant']) )
			foreach ( $users['participant'] as $user_id )
			{		
				echo usam_get_manager_name( $user_id );
				echo "<br>";
			}
	} 
	
	function column_responsible( $item )
	{
		$users = usam_get_event_users( $item->id );		
		if ( !empty($users['responsible']) )
			foreach ( $users['responsible'] as $user_id )
			{		
				echo usam_get_manager_name( $user_id );
				echo "<br>";
			}
	} 	

	function column_color( $item )
	{ 
		echo usam_get_event_type_icon( $item->type, $item->user_id );
		if ( $item->importance )
			echo '<span id="event_importance" class="dashicons dashicons-star-filled" title="'.__("Избранное","usam").'"></span>';
		else
			echo '<span id="event_importance" class="dashicons dashicons-star-empty" title="'.__("Избранное","usam").'"></span>';		
		$color = $item->color != ''?'color_'.$item->color:'';
		echo '<span class="dashicons dashicons-image-filter '.$color.'" title="'.__("Цветовая категория","usam").'"></span>';
				
		$reminder_date = usam_get_event_reminder_date( $item->id );
		if ( !empty($reminder_date) )
			echo '<span class="dashicons dashicons-bell" title="'.usam_local_date( $reminder_date, get_option( 'date_format', 'Y/m/d' ).' H:i' ).'"></span>';
	} 	
}