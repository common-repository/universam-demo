<?php
class USAM_Event_Handling 
{
	public function __construct()
	{	
		add_action('usam_bank_account_update', [$this, 'company_bank_account_update']);
	}
	
	function company_bank_account_update( $t ) 
	{
		$id = $t->get('id');
		if ( $id == get_option('usam_shop_company', '') )
			update_option('usam_shop_requisites_shortcode', []);
	}
}
?>