<?php

global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_CONTACT_META)  );

$props = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}_usam_means_communication_list  WHERE type='social' OR type='site'" );	
foreach( $props as $prop )	
{	
	if ($prop->type == 'site' ) 	
	{
		if ($prop->customer_type == 'contact' ) 	
			usam_update_contact_metadata($prop->contact_id, 'site', $prop->value );
		else
			usam_update_company_metadata($prop->contact_id, 'site', $prop->value );
	}
	elseif ($prop->type == 'social' ) 	
	{
		if ($prop->customer_type == 'contact' ) 	
			usam_update_contact_metadata($prop->contact_id, $prop->value_type, $prop->value ) ;	
		else
			usam_update_company_metadata($prop->contact_id, $prop->value_type, $prop->value );
	}
}
?>