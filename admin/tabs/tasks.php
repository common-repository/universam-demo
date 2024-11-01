<?php
class USAM_Tab_tasks extends USAM_Tab
{		
	protected $views = ['grid', 'table', 'calendar', 'report'];
	
	//'lead' => array( 'title' => __('Руковожу','usam'), 'type' => 'table' )	
	
	public function get_title_tab()
	{			
		if ( $this->view == 'report' )
			return __('Отчет по заданиям', 'usam');		
		elseif ( $this->table == 'calendars' )
			return __('Календари', 'usam');	
		else
			return __('Ваши задания', 'usam');
	}
		
	protected function get_tab_forms()
	{
		if ( $this->table == 'calendars' )		
			return [['form' => 'edit', 'form_name' => 'calendar', 'title' => __('Добавить календарь', 'usam')]];	
		else
			return [
				['form' => 'edit', 'form_name' => 'task', 'title' => __('Добавить задание', 'usam')],			
			];	
	}
	
	protected function load_tab()
	{ 
		USAM_Admin_Assets::work_manager();
	}
	
	function calendar_view() 
	{			
		if ( $this->table == 'calendars' )
			$this->list_table->display_table();
		else
		{		
			require_once( USAM_FILE_PATH . '/admin/includes/calendar/calendar_events.class.php' );
			$calendar = new USAM_Сalendar_Events();							
			$calendar->display(); 
		}
	}
}