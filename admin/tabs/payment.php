<?php
class USAM_Tab_Payment extends USAM_Page_Tab
{
	public function get_title_tab()
	{
		return __('Документы оплаты', 'usam');
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'payment', 'title' => __('Добавить', 'usam'), 'capability' => 'add_payment']];		
	}
	
	function help_tabs() 
	{	
		$help = array( 'capabilities' => __('Возможности', 'usam'), 'search' => __('Поиск', 'usam'), 'panel' => __('Контекстная панель', 'usam') );
		return $help;		
	}
}