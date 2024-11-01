<?php
/**
 * Экспорт компаний
 */
require_once( USAM_FILE_PATH . '/includes/exchange/exporter.class.php');
class USAM_Companies_Exporter extends USAM_Exporter
{		
	public function get_args( ) 
	{		
		$args = ['order' => $this->rule['order'], 'orderby' => $this->rule['orderby'], 'paged' => $this->paged, 'number' => $this->number, 'meta_query' => [], 'date_query' => []];
		
		if( !empty($this->rule['from_dateinsert']) )
			$args['date_query'][] = ['after' => date('Y-m-d H:i:s', strtotime($this->rule['from_dateinsert'])), 'inclusive' => true];	
		if( !empty($this->rule['to_dateinsert']) )
			$args['date_query'][] = ['before' => date('Y-m-d H:i:s', strtotime($this->rule['to_dateinsert'])), 'inclusive' => true];	
		
		if ( !empty($this->rule['status']) )
			$args['status'] = $this->rule['status'];
		if ( !empty($this->rule['company_industry']) )
			$args['industry'] = $this->rule['company_industry'];
		if ( !empty($this->rule['groups']) )
			$args['group'] = $this->rule['groups'];
		if ( !empty($this->rule['company_type']) )
			$args['type'] = $this->rule['company_type'];
		
		if ( !empty($this->rule['location']) )
		{							
			$locations = usam_get_array_locations_down( $this->rule['location'] );
			$locations[] = $this->rule['location'];			
			$this->query_vars['meta_query'] = [['key' => 'contactlocation',	'value' => $locations]];					
		}		
		$args['conditions'] = array();		
		if ( !empty($this->rule['to_ordersum']) )
		{			
			$args['conditions'][] = ['key' => 'total_purchased', 'value' => $this->rule['to_ordersum'], 'compare' => '<='];	
		}
		if ( !empty($this->rule['from_ordersum']) )
		{			
			$args['conditions'][] = ['key' => 'total_purchased', 'value' => $this->rule['from_ordersum'], 'compare' => '>='];	
		}
		if ( !empty($this->rule['to_ordercount']) )
		{			
			$args['conditions'][] = ['key' => 'number_orders', 'value' => $this->rule['to_ordersum'], 'compare' => '<='];	
		}
		if ( !empty($this->rule['from_ordercount']) )
		{			
			$args['conditions'][] = ['key' => 'number_orders', 'value' => $this->rule['from_ordercount'], 'compare' => '>='];	
		} 
		return $args;
	}
	
	public function get_total( ) 
	{
		$args = $this->get_args();		
		unset($args['number']);
		return usam_get_companies( $args );	
	}
			
	protected function get_data( $param = [] ) 
	{			
		$args = $this->get_args();
		$args = array_merge($args, $param);	
		
		//$args['cache_bank_accounts'] = true; 		
		$this->properties = usam_get_properties(['type' => 'company', 'active' => 1, 'fields' => 'code=>data']);
		foreach( $this->properties as $code => $property )
		{
			if ( isset($this->rule['columns'][$code]) )
			{				
				$args['cache_meta'] = true; 
				break;
			}			
		}			
		$companies = usam_get_companies( $args );	
	
		$output	= array();			
		foreach ( $companies as $data )
		{			
			$output[] = $this->get_column( $data );
			wp_cache_delete( $data->id, 'usam_company' );	
			wp_cache_delete( $data->id, 'usam_company_meta' );		
			wp_cache_delete( $data->id, 'usam_bank_accounts' );		
		}		
		return $output;		
	}				
	
	protected function get_column( $company ) 
	{
		$results = array();			
		foreach ( $company as $key => $value )	
		{
			if ( isset($this->rule['columns'][$key]) )
				$results[$key] = $value;
		}			
		if ( !empty($this->rule['columns']['logo']) )
			$results['logo'] = usam_get_company_logo( $company->id );
		if ( !empty($this->rule['columns']['employees']) )
			$results['employees'] = usam_get_company_metadata($company->id, 'employees');
		if ( !empty($this->rule['columns']['description']) )
		{
			$description = (string)usam_get_company_metadata($company->id, 'description');
			$description = nl2br($description);
			$results['description'] = str_replace(array("\r\n","\r","\n"),"",$description);
		}
		if ( !empty($this->rule['columns']['manager']) )
		{
			$user = get_user_by('id', $company->manager_id );
			$results['manager'] = isset($user->display_name)?"$user->display_name":'';			
		}		
		if ( !empty($this->rule['columns']['type_name']) )
		{
			$results['type_name'] = usam_get_name_type_company( $company->type );	
		}
		if ( !empty($this->rule['columns']['industry_name']) )
		{
			$results['industry_name'] = usam_get_name_industry_company( $company->industry );	
		}
		if ( !empty($this->rule['columns']['group_name']) )
		{
			$groups = usam_get_company_groups( $company->id );
			$results['group_name'] = implode('|', $groups );	
		}	
		$metas = usam_get_company_metas( $company->id, 'display' );			
		foreach ( $this->rule['columns'] as $key => $value )
		{
			if ( isset($metas[$key]) )
			{
				if ( $key == 'contactlocation' )
				{
					$locations = usam_get_address_locations( $metas[$key] );	
					if ( isset($this->rule['columns']['country']) )
						$results['country'] = !empty($locations['country'])?$locations['country']:'';
					elseif ( isset($this->rule['columns']['city']) )
						$results['city'] = !empty($locations['city'])?$locations['city']:'';
				}
				else
				{
					$results[$key] = $metas[$key];	
				}
			}
		}
		if ( !empty($this->rule['columns']['revenue']) )
		{
			$results['revenue'] = usam_get_company_metadata($company->id, 'revenue'); 
		}
		return $results;
	}
}
?>