<?php
class USAM_Tab_buyers_report extends USAM_Tab
{		
	public function get_tab_sections() 
	{  
		$tables = array( //'buyers_report' => array( 'title' => __('По клиентам','usam'), 'type' => 'table'), 
		
		'companies_report' => array( 'title' => __('По компаниям','usam'), 'type' => 'table'), 'contacts_report' => array( 'title' => __('По клиентам','usam'), 'type' => 'table' ), 'bonus_cards_report' => array( 'title' => __('По бонусным картам','usam'), 'type' => 'table') );
		return $tables;
	}
	
	public function get_title_tab()
	{	
		if ( $this->table == 'company_report') 
			return __('Отчет по компании', 'usam');
		elseif ( $this->table == 'contact_report') 
			return __('Отчет по контакту', 'usam');
		elseif ( $this->table == 'companies_report') 
			return __('Отчет по компаниям', 'usam');	
		elseif ( $this->table == 'contacts_report') 
			return  __('Отчет по контактам', 'usam');	
		elseif ( $this->table == 'contacts_report') 
			return __('Отчет по контактам', 'usam');	
		else
			return __('Отчет по клиентам', 'usam');			
	}	
}