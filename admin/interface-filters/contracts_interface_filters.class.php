<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class contracts_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = [
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'],
			'number'  => ['title' => __('Номер', 'usam'), 'type' => 'numeric'],
			'group' => ['title' => __('Группы', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contract']],
			'manager' => ['title' => __('Менеджер', 'usam'), 'type' => 'checklists'],
			'company_own' => ['title' => __('Ваша фирма', 'usam'), 'type' => 'checklists'],
			'counterparty' => ['title' => __('Контрагент', 'usam'), 'type' => 'counterparty'],
		];
		if ( isset($_GET['form']) )
			unset($filters['counterparty']);
		return $filters;
	}	
	
	public function get_sort( ) 
	{
		return ['date-desc' => __('По дате &#8595;', 'usam'), 'date-asc' => __('По дате &#8593;', 'usam'), 'number-desc' => __('По номеру &#8595;', 'usam'), 'number-asc' => __('По номеру &#8593;', 'usam'), 'title-asc' => __('По названию А-Я', 'usam'), 'title-desc' => __('По названию Я-А', 'usam')];
	}
}
?>