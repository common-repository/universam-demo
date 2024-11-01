<?php
global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_TAX_PRODUCT_ORDER, USAM_TABLE_EXCHANGE_RULES, USAM_TABLE_PARSING_SITES, USAM_TABLE_DELIVERY_SERVICE_META) );


$delivery_service = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_DELIVERY_SERVICE );
foreach( $delivery_service as $delivery )	
{
	if ( !empty($delivery->setting) )
	{
		$setting =  maybe_unserialize($delivery->setting);		
		foreach( $setting as $key => $value )	
		{
			if ( $key == 'handler_setting' || $key == 'restrictions' )
			{
				foreach( $value as $k => $v )	
					usam_update_delivery_service_metadata($delivery->id, $k, $v);
			}
			else
				usam_update_delivery_service_metadata($delivery->id, $key, $value);
		}
	}
}	
$groups = $wpdb->get_results( "SELECT * FROM ".usam_get_table_db('property_groups')." WHERE type='order'" );
foreach( $groups as $group )
{
	if ( $group->type_payer_id )
		usam_update_property_group_metadata($group->id, 'type_payer', $group->type_payer_id);	
}	