<?php
function usam_get_logic_title( $logic ) 
{		
	$logics = array( 'equal' => __('равно', 'usam'),
					'not_equal' => __('не равно', 'usam'),
					'greater' => __('больше', 'usam'),
					'less' => __('меньше', 'usam'),
					'eg' => __('больше либо равно', 'usam'),
					'el' => __('меньше либо равно', 'usam'),
					'contains' => __('содержит', 'usam'),
					'not_contain' => __('не содержит', 'usam'),
					'begins' => __('начинается с', 'usam'),
					'ends' => __('заканчивается на', 'usam'),
	);
	if ( isset($logics[$logic]) )
		return $logics[$logic];
	else
		return '';
}

function usam_get_types_period_sales_plan(  ) 
{		
	$type_file_exchange = array(		
		'month'       => __('Месяц', 'usam'),
		'quarter'     => __('Квартал', 'usam'),
		'half-year'   => __('Полугодие', 'usam'),
		'year'        => __('Год', 'usam'),		
	);	
	return $type_file_exchange;
} 	

function usam_get_plan_types(  ) 
{		
	$type_file_exchange = array(		
		'people'     => __('По людям', 'usam'),
		'department' => __('По отделам', 'usam'),
		'company'    => __('По компаниям', 'usam'),
	);	
	return $type_file_exchange;
} 			
?>