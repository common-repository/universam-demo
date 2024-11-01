<?php
class USAM_Tab_account_transactions extends USAM_Page_Tab
{		
	public function get_title_tab() 
	{			
		return __('Внутренние транзакции', 'usam');
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'account_transaction', 'title' => __('Добавить транзакцию', 'usam')]];			
	}
}