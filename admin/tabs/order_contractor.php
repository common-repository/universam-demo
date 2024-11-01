<?php
class USAM_Tab_order_contractor extends USAM_Page_Tab
{		
	public function __construct()
	{	
		$this->views[] = 'table';
	}	
	
	public function get_title_tab()
	{		
		return usam_get_document_name('order_contractor', 'plural_name');
	}
		
	protected function get_tab_forms()
	{
		return [['action' => 'new', 'title' => __('Добавить', 'usam'), 'capability' => 'add_order_contractor']];	
	}	
}
