<?php
class USAM_Tab_documents extends USAM_Page_Tab
{	
	public function get_title_tab()
	{
		return __('Документы в работе', 'usam');
	}
	
	protected function get_tab_forms()
	{
		return array( 				
			array('form' => 'edit', 'form_name' => 'decree', 'title' => __('Приказ', 'usam'), 'capability' => 'add_decree' ) ,
			array('form' => 'edit', 'form_name' => 'contract', 'title' => __('Договор', 'usam'), 'capability' => 'add_contract' ),	
		);			
		
	}
}