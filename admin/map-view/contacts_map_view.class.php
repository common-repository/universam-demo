<?php
require_once( USAM_FILE_PATH . '/admin/includes/map_view.class.php' );
class USAM_Contacts_Map_View extends USAM_Map_View
{		
	public function get_contacts_query_vars( $query_vars = [] ) 
	{
		$this->query_vars = $this->get_query_vars();
		$this->query_vars = array_merge($this->query_vars, $query_vars);
		$this->query_vars['cache_meta'] = true;			
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['not_in__source'] = 'employee';	
		if ( empty($this->query_vars['search']) )
		{		
			$selected = $this->get_filter_value( 'manager' );
			if ( $selected )
				$this->query_vars['manager_id'] = array_map('intval', (array)$selected);	
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
				$this->query_vars['status'] = array_map('intval', (array)$selected);
			$selected = $this->get_filter_value( 'group' );
			if ( $selected )
				$this->query_vars['group'] = array_map('intval', (array)$selected);
			$selected = $this->get_filter_value( 'contacts_source' );
			if ( $selected )
				$this->query_vars['source'] = array_map('sanitize_title', (array)$selected);
			
			$selected = $this->get_filter_value( 'basket' );
			if ( $selected )
			{
				switch ( $selected ) 
				{		
					case '7day' :			
						$selected = strtotime('-7 days');
					break;	
					case '14day' :				
						$selected = strtotime('-14 days');
					break;	
					case '30day' :				
						$selected = strtotime('-30 days');
					break;
					case '90day' :				
						$selected = strtotime('-90 days');
					break;
					case '3day' :
					default:
						$selected = strtotime('-3 days');
					break;		
				}				
				$this->query_vars['abandoned_baskets'] = sanitize_title($selected);	
			}
			$selected = $this->get_filter_value( 'gender' );
			if ( $selected )
			{
				$selected = array_map('sanitize_title', (array)$selected);		
				foreach( $selected as $sex )
					$this->query_vars['meta_query'][] = ['key' => 'sex', 'value' => $sex, 'compare' => '='];
			}
			$selected = $this->get_filter_value( 'age' );
			if ( $selected )
			{
				if ( !empty($selected) )
					$values = explode('|',$selected);
				if ( !empty($values[0]) )
				{
					$from_age = date('Y') - absint($values[0]);
					$this->query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
				}
				if ( !empty($values[1]) )
				{
					$to_age = date('Y') - absint($values[1]);
					$this->query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
				}
			}	
			$selected = $this->get_filter_value( 'campaign' );
			if ( $selected )				
				$this->query_vars['campaign'] = array_map('intval', (array)$selected);
			$selected = $this->get_filter_value( 'company' );
			if ( $selected )
				$this->query_vars['company_id'] = absint($selected);		
			$selected = $this->get_filter_value( 'storage_pickup' );
			if ( $selected )				
				$this->query_vars['storage_pickup'] = array_map('intval',(array)$selected);	
			$selected = $this->get_filter_value( 'status_subscriber' );
			if ( $selected )
				$this->query_vars['status_subscriber'] = array_map('sanitize_title', (array)$selected);
			
			$selected = $this->get_filter_value( 'mailing_lists' );
			if ( $selected )
			{
				$selected = array_map('intval', (array)$selected);			
				if ( array_search(0, $selected) === false )
					$this->query_vars['list_subscriber'] = $selected;	
				else
					$this->query_vars['not_subscriber'] = true;
			}
			$this->get_meta_for_query('contact');
			$this->get_date_interval_for_query(['last_order_date', 'online']);
			$this->get_digital_interval_for_query(['total_purchased', 'number_orders', 'bonus']);		
		}
	}
	
	public function prepare_items( ) 
	{		
		$this->get_contacts_query_vars();
		$contacts = usam_get_contacts( $this->query_vars );	
		$points = [];
		foreach ( $contacts as $contact ) 
		{
			$latitude = (string)usam_get_contact_metadata( $contact->id, 'latitude' );
			$longitude = (string)usam_get_contact_metadata( $contact->id, 'longitude' );
			$address = (string)usam_get_contact_metadata( $contact->id, 'address' ); 
			$sum = $contact->total_purchased ? "<div class='map__pointer_sum'>".sprintf(__("Всего куплено %s", "usam"),"<span>$contact->total_purchased</span>")."</div>" : "";
			$points[] = array( 'id' => $contact->id, 'title' => $contact->appeal, 'description' => "<div class='map__pointer'><div class='map__pointer_foto'><img src='".usam_get_contact_foto( $contact->id )."'></div><div class='map__pointer_text'><div class='map__pointer_name'><a href='".usam_get_contact_url( $contact->id )."'>".$contact->appeal."</a></div><div class='map__pointer_address'>$address</div>$sum</div></div>", 'latitude' => $latitude, 'longitude' => $longitude );	
		}
		return $points;
	}
}
?>