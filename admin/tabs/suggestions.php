<?php
class USAM_Tab_suggestions extends USAM_Tab
{	
	public function __construct()
	{	
		$this->views[] = 'table';
		if ( current_user_can( 'report_document' ) )
			$this->views[] = 'report';
	}
	
	public function get_title_tab()
	{
		return __('Коммерческие предложения', 'usam');
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'suggestion', 'title' => __('Добавить', 'usam'), 'capability' => 'add_suggestion']];
	}	
}
