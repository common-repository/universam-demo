<?php
class USAM_Tab_affairs extends USAM_Tab
{		
	public function __construct()
	{				
		if ( !isset($_GET['table']) || $_GET['table'] != 'calendars' )
			$this->views = ['grid', 'table', 'calendar', 'report'];	
	}
	
	public function get_title_tab()
	{			
		if ( $this->view == 'report' )
			return __('Отчет по делам', 'usam');	
		elseif ( $this->table == 'calendars' )
			return __('Календари', 'usam');	
		else
			return __('Список дел', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'calendars' )		
			return array( array('form' => 'edit', 'form_name' => 'calendar', 'title' => __('Добавить календарь', 'usam') ) );	
		else
			return array( 
		//		array('form' => 'edit', 'form_name' => 'event', 'title' => __('Добавить', 'usam') ),			
			);	
	}
	
	protected function load_tab()
	{ 
		USAM_Admin_Assets::work_manager();
	}
	
	function calendar_view() 
	{			
		if ( $this->table == 'calendars' )
		{
			$this->list_table->display_table();
		}
		else
		{		
			require_once( USAM_FILE_PATH . '/admin/includes/calendar/calendar_events.class.php' );
			$calendar = new USAM_Сalendar_Events();							
			$calendar->display(); 
		}
	}	
	
}