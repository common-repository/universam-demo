<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class email_newsletter_company_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		return [
			'participation' => ['title' => __('Действия', 'usam'), 'type' => 'select'], 
			'companies_types' => ['title' => __('Типы компаний', 'usam'), 'type' => 'checklists'], 
		];
	}	

	public function get_participation_options() 
	{	
		$statuses = usam_get_customer_newsletter_statuses();		
		$results = array();
		foreach( $statuses as $type => $name )
			$results[] = ['id' => $type, 'name' => $name];
		return $results;
	}		
}
?>