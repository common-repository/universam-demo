<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class email_Interface_Filters extends USAM_Interface_Filters
{	
	protected $filters_saved = false;
	protected function get_filters( ) 
	{				
		return [
			'date' => ['title' => __('Дата', 'usam'), 'type' => 'date'], 
			'size' => ['title' => __('Размер вложения', 'usam'), 'type' => 'numeric'], 
			'read' => ['title' => __('Прочитанные / непрочитанные', 'usam'), 'type' => 'select'], 
			'importance' => ['title' => __('Важные / неважные', 'usam'), 'type' => 'select'], 
			'counterparty' => ['title' => __('Контрагент', 'usam'), 'type' => 'counterparty'],
		];
	}		
	
	public function get_sort( ) 
	{
		return array( 'date-desc' => __('Новые', 'usam'), 'date-asc' => __('Старые', 'usam'), 'size-asc' => __('Маленькие', 'usam'), 'size-desc' => __('Большие', 'usam') );		
	}
	
	public function get_read_options() 
	{	
		return [['id' => 'not_read', 'name' => __('Не прочитанные', 'usam')], ['id' => 'read', 'name' => __('Прочитанные', 'usam')]];
	}
	
	public function get_importance_options() 
	{	
		return [['id' => 'not_importance', 'name' => __('Не важные', 'usam')], ['id' => 'importance', 'name' => __('Важные', 'usam')]];
	}
}
?>