<?php
// IP телефония
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Telephony extends USAM_Application
{
	protected function set_call( $phone, $status )
	{				
		require_once( USAM_FILE_PATH . '/includes/crm/call.class.php' );	
		$call_id = usam_insert_call( array('call_type' => 'outgoing', 'status' => $status, 'phone' => $phone) );
		$event_id = $this->add_affair( $phone, $call_id );
		return $event_id;
	}
	
	protected function save_complete_call( $id )
	{		
		require_once( USAM_FILE_PATH . '/includes/crm/call.class.php' );	
		$call = usam_get_call( $id );		
		if ( empty($call) )
			return false;
		
		if ( $call['status'] == 'compound'  )
			$call['status'] = 'cancel';
		elseif ( $call['status'] == 'answered' )
		{
			$call['status'] = 'completed';
			$call['time'] = time() - strtotime($call['date_insert']);
		}	
		usam_update_call( $id, $call );
	}
	
	protected function add_affair( $phone, $call_id )
	{							
		$meta_query = array('relation' => 'OR');
		$properties = usam_get_properties( array( 'type' => 'contact', 'active' => 1, 'field_type' => array('mobile_phone', 'phone'), 'fields' => 'code' ) );	
		foreach ( $properties as $property ) 
		{
			$meta_query[] = array( 'key' => $property, 'value' => $phone, 'compare' => '=' );
		}			
		$contact_ids = array();
		$contacts = usam_get_contacts( array('meta_query' => $meta_query ) );				
		if ( !empty($contacts) )
		{
			foreach ( $contacts as $contact ) 
			{
				$contact_ids[] = $contact->id;				
			}
		}
		$meta_query = array('relation' => 'OR');
		$properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => array('mobile_phone', 'phone'), 'fields' => 'code' ) );	
		foreach ( $properties as $property ) 
		{
			$meta_query[] = array( 'key' => $property, 'value' => $phone, 'compare' => '=' );
		}	
		$companies = usam_get_companies( array('meta_query' => $meta_query ) );				
		$company_ids = array();
		if ( !empty($companies) )
		{
			foreach ( $companies as $company ) 
			{
				$company_ids[] = $company->id;				
			}
		}
		if ( count($companies) == 1 && count($contacts) == 1 )
		{
			$title = sprintf( __('Звонок в компанию %s контакту %s', 'usam'), $companies[0]->name, $contacts[0]->appeal );
		}
		elseif ( !empty($companies) && count($companies) == 1 )
		{
			$title = sprintf( __('Звонок в компанию %s', 'usam'), $companies[0]->name );
		}
		elseif ( !empty($contacts) && count($contacts) == 1 )
		{
			$title = sprintf( __('Звонок контакту %s', 'usam'), $contacts[0]->appeal );
		}	
		else
		{
			$title = __('Звонок', 'usam');
		}				
		$time = time();
		$user_id = get_current_user_id();
		$event = ['title' => $title, 'user_id' => $user_id, 'type' => 'call', 'start' => date('Y-m-d H:i:s', $time )];
		$links = [];
		if ( !empty($contact_ids) )
			foreach ( $contact_ids as $contact_id ) 
				$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];
		if ( !empty($company_ids) )
			foreach ( $company_ids as $company_id ) 
				$links[] = ['object_id' => $company_id, 'object_type' => 'company'];
		$event_id = usam_insert_event( $event, $links );			
		usam_update_event_metadata($event_id, 'call_id', $call_id );	
		return $event_id;
	}
	
	public function possibility_to_call( $result )
	{		
		return false;
	}
			
	protected function outgoing_call( $phone, $call_id = null, $time = null )
	{	
		if ( empty($phone) )
			return false;				
		
		require_once( USAM_FILE_PATH . '/includes/crm/call.class.php' );	
		require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );
		$call = usam_get_calls(['phone' => $phone, 'status' => 'compound', 'number' => 1]);
		if ( !empty($call) ) 		
		{
			usam_update_call( $call['id'], ['status' => 'completed', 'call_id' => $call_id]);			
		}			
		return $event_id;
	}	
}
?>