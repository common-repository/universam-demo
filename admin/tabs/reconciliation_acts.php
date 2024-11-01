<?php
class USAM_Tab_reconciliation_acts extends USAM_Page_Tab
{	
	public function get_title_tab()
	{
		return __('Акт сверки', 'usam');
	}
	
	protected function get_tab_forms()
	{	
		return [['form' => 'edit', 'form_name' => 'reconciliation_act', 'title' => __('Сформировать акт', 'usam'), 'capability' => 'add_reconciliation_act']];	
	}	
}
