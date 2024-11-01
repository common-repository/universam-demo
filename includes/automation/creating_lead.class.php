<?php
require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );
class USAM_Creating_Lead
{
	public function from_letter( $email ) 
	{		
		$args = ['name' => $email['title'], 'source' => 'email'];
		$contact_ids = usam_get_contact_ids_by_field('email', $email['from_email']);
		$payers = usam_get_group_payers(['type' => 'contact']);
		$contact_id = 0;
		$company_id = 0;
		$fields = [];
		$metas = [];	
		if ( $contact_ids )
		{
			$contact_id = $contact_ids[0];
			$args['type_price'] = usam_get_customer_price_code( $contact_id );
			$args['contact_id'] = $contact_id;		
			$metas = usam_get_contact_metas( $contact_id );
		}
		else
		{
			$company_ids = usam_get_company_ids_by_field('email', $email['from_email']);
			if ( !$company_ids )
			{
				$contact_id = usam_insert_contact(['full_name' => $email['from_name'], 'contact_source' => 'email', 'email' => $email['from_email']]); 
				$args['contact_id'] = $contact_id;
				$payers = usam_get_group_payers(['type' => 'company']);				
			}
			else
			{
				$company_id = $company_ids[0];				
				$metas = usam_get_company_metas( $company_id );
				$args['company_id'] = $company_id;
			}
		}
		if ( $metas )
			$fields = usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );		
		$args['type_payer'] = $payers[0]['id'];
		$properties =  usam_get_properties(['type' => 'order', 'field_type' => 'email', 'type_payer' => $payers[0]['id']]);		
		foreach ($properties as $property ) 
		{
			 $fields[$property->code] = $email['from_email'];
		}	 
		$lead_id = usam_insert_lead( $args );
		usam_add_lead_customerdata( $lead_id, $fields );	
				
		$links = [['object_id' => $lead_id, 'object_type' => 'lead']];
		if ( $contact_id )
			$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];
		if ( $company_id )
			$links[] = ['object_id' => $company_id, 'object_type' => 'company'];
		usam_set_ribbon(['event_id' => $email['id'], 'event_type' => 'email'], $links);		
	}		
	
	public function from_webform( $event, $webform, $webform_data, $properties ) 
	{				
		$args = ['name' => $event['title'], 'source' => 'webform'];			
		$payers = usam_get_group_payers(['type' => 'contact']);
		$fields = usam_get_webform_data_from_CRM( $webform_data, 'order', $payers[0]['id'] );	
		
		$contact_id = 0;
		$company_id = 0;
		$products = [];
		$links = usam_get_event_links( $event['id'] );		
		foreach ($links as $link ) 
		{
			if ( $link->object_type=='contact' )	
			{
				$contact_id = $link->object_id;
				$args['type_price'] = usam_get_customer_price_code( $contact_id );
			}
			elseif ( $link->object_type=='company' )			
				$company_id = $link->object_id;
			elseif ( $link->object_type=='product' )			
				$products[] = ['product_id' => $link->object_id, 'quantity' => 1];
		}			
		$lead_id = usam_insert_lead( $args, $products );
		usam_add_lead_customerdata( $lead_id, $fields );	
					
		$links = [['object_id' => $lead_id, 'object_type' => 'lead']];
		if ( $contact_id )
			$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];
		if ( $company_id )
			$links[] = ['object_id' => $company_id, 'object_type' => 'company'];
		usam_set_ribbon(['event_id' => $event['id'], 'event_type' => $event['type']], $links);
	}
}
?>