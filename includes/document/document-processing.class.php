<?php
class USAM_Document_Processing
{
	public function __construct()
	{		
		add_filter( 'usam_lead_insert_data', [__CLASS__, 'add_manager_document']);
		add_filter( 'usam_order_insert_data', [__CLASS__, 'add_manager_document']);	
		
		add_action( 'usam_document_shipped_update', [$this, 'document_shipped_update'] );		
	}
	
	public static function add_manager_document( $data ) 
	{
		if ( !isset($data['manager_id']) )
		{
			if ( !empty($data['contact_id']) )
			{
				$contact = usam_get_contact( $data['contact_id'] );
				if ( !empty($contact) )
					$data['manager_id'] = $contact['manager_id'];
				else
					$data['contact_id'] = 0;
			}
			elseif( !empty($data['company_id']) )
			{
				$company = usam_get_company( $data['contact_id'] );
				if ( !empty($company) )
					$data['manager_id'] = $company['manager_id'];
				else
					$data['company_id'] = 0;
			}
		}
		return $data;		
	}
	
	public static function document_shipped_update( $t ) 
	{		
		$data = $t->get_data();	
		$changed_data = $t->get_changed_data();			
		if( isset($changed_data['totalprice']) )		
			usam_update_shipped_document_metadata($data['id'], 'exchange', 0);
	}
}
new USAM_Document_Processing();