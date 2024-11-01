<?php
class USAM_Tab_leads extends USAM_Page_Tab
{	
	public function __construct()
	{		
		if ( empty($_REQUEST['table']) || $_REQUEST['table'] == 'leads' )
		{		
			$this->views = ['grid', 'table', 'report'];
			if ( current_user_can( 'setting_document' ) )
				$this->views[] = 'settings';
		}	
	}	
	
	public function get_title_tab()
	{
		if ( $this->table == 'lead_status' )
			return __('Статусы лидов', 'usam');
		else
			return __('Лиды', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'lead_status' )		
			return [['form' => 'edit', 'form_name' => 'lead_status', 'title' => __('Добавить', 'usam') ]];	
		elseif ( $this->table == 'leads' || $this->view == 'grid' )		
			return [['form' => 'edit', 'form_name' => 'lead', 'title' => __('Добавить', 'usam'), 'capability' => 'add_lead']];
		return [];
	}	
	
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
		{
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );	
		}
		else
			$tables = array();
		return $tables;
	}
	
	function help_tabs() 
	{	
		$help = array( 'capabilities' => __('Возможности', 'usam'), 'search' => __('Поиск', 'usam'), 'panel' => __('Контекстная панель', 'usam') );
		return $help;
	}
	
	public function get_settings_tabs() 
	{ 
		return array( 'lead_status' => array('title' => __('Статусы лидов','usam'), 'type' => 'table' ) );
	}
}