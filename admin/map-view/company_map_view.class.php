<?php
require_once( USAM_FILE_PATH . '/admin/includes/map_view.class.php' );
class USAM_Company_Map_View extends USAM_Map_View
{		
	public function prepare_items( ) 
	{		
		$this->query_vars = $this->get_query_vars();
		$this->query_vars['cache_meta'] = true;			
		$this->query_vars['cache_thumbnail'] = true;
		if ( empty($this->query_vars['search']) )
		{		
			$selected = $this->get_filter_value( 'manager' );
			if ( $selected )
				$this->query_vars['manager_id'] = array_map('intval', (array)$selected);	
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
				$this->query_vars['status'] = array_map('sanitize_title', $selected);			
			$selected = $this->get_filter_value( 'industry' );
			if ( $selected )
				$this->query_vars['industry'] = array_map('sanitize_title', $selected);			
			$selected = $this->get_filter_value( 'companies_types' );
			if ( $selected )
				$this->query_vars['type'] = array_map('sanitize_title', (array)$selected);			
			$selected = $this->get_filter_value( 'group' );
			if ( $selected )
				$this->query_vars['group'] = array_map('intval', (array)$selected);
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
			$this->get_date_interval_for_query(['last_order_date']);
			$this->get_digital_interval_for_query(['total_purchased', 'number_orders']);
			
			$selected = $this->get_filter_value( 'user_id' );
			if ( $selected )
				$this->query_vars['manager_id'] = array_map('intval', (array)$selected);
		}				
		$this->get_meta_for_query('company');
		$companies = usam_get_companies( $this->query_vars );
		$points = array();
		foreach ( $companies as $company ) 
		{
			$latitude = (string)usam_get_company_metadata( $company->id, 'latitude' );
			$longitude = (string)usam_get_company_metadata( $company->id, 'longitude' );
			$address = (string)usam_get_company_metadata( $company->id, 'address' ); 
			$sum = $company->total_purchased ? "<div class='map__pointer_sum'>".sprintf(__("Всего куплено %s", "usam"),"<span>$company->total_purchased</span>")."</div>" : "";
			$points[] = array( 'title' => $company->name, 'id' => $company->id, 'description' => "<div class='map__pointer'><div class='map__pointer_foto'><img src='".usam_get_company_logo( $company->id )."'></div><div class='map__pointer_text'><div class='map__pointer_name'><a href='".usam_get_company_url( $company->id )."'>".$company->name."</a></div><div class='map__pointer_address'>$address</div>$sum</div></div>", 'latitude' => $latitude, 'longitude' => $longitude );	
		}
		return $points;
	}
}
?>