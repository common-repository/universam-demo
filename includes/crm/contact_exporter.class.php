<?php
/**
 * Экспорт контактов
 */
require_once( USAM_FILE_PATH . '/includes/exchange/exporter.class.php');
class USAM_Contacts_Exporter extends USAM_Exporter
{		
	public function get_args( ) 
	{
		$args = ['order' => $this->rule['order'], 'orderby' => $this->rule['orderby'], 'paged' => $this->paged, 'number' => $this->number, 'meta_query' => [], 'date_query' => []];
		if ( !empty($this->rule['groups']) )
			$args['group'] = $this->rule['groups'];
		if( !empty($this->rule['from_dateinsert']) )
			$args['date_query'][] = ['after' => date('Y-m-d H:i:s', strtotime($this->rule['from_dateinsert'])), 'inclusive' => true];	
		if( !empty($this->rule['to_dateinsert']) )
			$args['date_query'][] = ['before' => date('Y-m-d H:i:s', strtotime($this->rule['to_dateinsert'])), 'inclusive' => true];
		if ( !empty($this->rule['status']) )
			$args['status'] = $this->rule['status'];
		if ( !empty($this->rule['source']) )
			$args['contact_source'] = $this->rule['source'];
		if ( !empty($this->rule['sex']) )
			$args['meta_query'][] = ['key' => 'sex','value' => $this->rule['sex'], 'compare' => '='];
		if ( !empty($this->rule['to_age']) )
		{
			$from_age = date('Y') - absint($this->rule['to_age']);
			$args['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
		}		
		if ( !empty($this->rule['from_age']) )
		{
			$to_age = date('Y') - absint($this->rule['from_age']);
			$args['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
		}		
		if ( !empty($this->rule['post']) )
			$args['meta_query'][] = ['key' => 'post','value' => $this->rule['post'], 'compare' => '='];
		
		if ( !empty($this->rule['location']) )
			$args['meta_query'][] = ['key' => 'location','value' => $this->rule['location'], 'compare' => '='];		
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
		return usam_get_contacts( $args );	
	}
			
	protected function get_data( $param = [] ) 
	{			
		$args = $this->get_args();
		$args = array_merge($args, $param);	
		
		$this->properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'fields' => 'code=>data']);		
		foreach( $this->properties as $code => $property )
		{
			if ( isset($this->rule['columns'][$code]) )
			{				
				$args['cache_meta'] = true;
				break;
			}			
		}			
		$contacts = usam_get_contacts( $args );			
	
		$output	= array();			
		foreach ( $contacts as $data )
		{			
			$output[] = $this->get_column( $data );	
	
			wp_cache_delete( $data->id, 'usam_contact' );
			wp_cache_delete( $data->id, 'usam_contact_meta' );				
		}
		return $output;		
	}
	
	protected function get_column( $contact ) 
	{
		$results = array();			
	
		if ( isset($this->rule['columns']['company_name']) )
		{
			$company = usam_get_company( $contact->company_id );					
			$results['company_name'] = !empty($company['name']) ? $company['name'] : '';				
		}						
		foreach ( $this->properties as $code => $property )
		{
			if ( isset($this->rule['columns'][$code]) )
			{
				$meta = usam_get_contact_metadata( $contact->id, $code );
				if ( is_array($meta) )
					$meta = '';
				if ( $property->field_type == 'textarea' )
					$meta = str_replace(array("\r\n","\r","\n"),"",$meta);	
				elseif ( $property->field_type == 'location' )
					$meta = usam_get_full_locations_name($meta, '%city% %region% %country%');	
				$results[$code] = $meta; 
			}
		}		
		if ( isset($this->rule['columns']['country']) || isset($this->rule['columns']['city']) )
		{
			$location = usam_get_contact_metadata( $contact->id, 'location' );	
			$locations = usam_get_address_locations( $location );	
		}
		if ( isset($this->rule['columns']['foto']) )
			$results['foto'] = usam_get_contact_foto( $contact->id );		
		if ( isset($this->rule['columns']['description']) )
		{
			$about = (string)usam_get_contact_metadata($contact->id, 'about');
			$about = nl2br($about);
			$results['about'] = str_replace(array("\r\n","\r","\n"),"",$about);	
		}		
		if ( isset($this->rule['columns']['full_name']) )
			$results['full_name'] = trim(usam_get_contact_metadata($contact->id, 'full_name'));	
		if ( isset($this->rule['columns']['post']) )
			$results['post'] = usam_get_contact_metadata($contact->id, 'post');	
		if ( isset($this->rule['columns']['sex']) )
			$results['sex'] = usam_get_contact_metadata($contact->id, 'sex');		
		if ( isset($this->rule['columns']['country']) )
			$results['country'] = !empty($locations['country'])?$locations['country']:'';
		if ( isset($this->rule['columns']['city']) )
			$results['city'] = !empty($locations['city'])?$locations['city']:'';
		if ( isset($this->rule['columns']['manager']) )
			$results['manager'] = usam_get_manager_name( $contact->manager_id  ); 
		if ( isset($this->rule['columns']['source_name']) )
			$results['source_name'] = usam_get_name_contact_source( $contact->contact_source );
		return $results;
	}
}
?>