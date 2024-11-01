<?php
/**
 * Фильтры продавцов.
 */  
class USAM_Seller_Filters
{	
	function __construct() 
	{			
		add_action( 'usam_save_property_company_meta', [__CLASS__, 'save_company_meta'], 10, 4);
		add_action( 'usam_save_property_contact_meta', [__CLASS__, 'save_contact_meta'], 10, 4);
	}
	
	public static function save_company_meta( $id, $property, $value, $new )
	{		
		if ( $value && ($property->code == 'company_name' || $property->code == 'full_company_name') )
		{				
			require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
			$seller = usam_get_sellers(['customer_id' => $id, 'seller_type' => 'company', 'number' => 1]);	
			if ( $seller )
			{	 
				usam_update_seller($seller['id'], ['name' => $value]);
			}
		}
	}
	
	public static function save_contact_meta( $id, $property, $value, $new )
	{
		if ( $value && $property->code == 'full_name' )
		{
			require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
			$seller = usam_get_sellers(['customer_id' => $id, 'seller_type' => 'contact', 'number' => 1]);		 
			if ( $seller )
				usam_update_seller($seller['id'], ['name' => $value]);
		}
	}
}
new USAM_Seller_Filters();