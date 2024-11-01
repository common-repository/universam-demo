<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class plan_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		return [
			'period_type' => ['title' => __('Тип периода', 'usam'), 'type' => 'checklists'], 			
		];
	}

	public function get_period_type_options() 
	{	
		$types_period = usam_get_types_period_sales_plan();	
		$results = [];
		foreach( $types_period as $period  => $name )
			$results[] = ['id' => $period, 'name' => $name];
		return $results; 
	}	
}
?>