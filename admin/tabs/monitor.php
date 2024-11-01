<?php
class USAM_Tab_monitor extends USAM_Tab
{	
	protected $views = ['report', 'table','map'];	
		
	public function get_title_tab()
	{			
		if ( $this->view == 'map' )
			return __('Контакты онлайн на карте', 'usam');
		elseif ( $this->view == 'report' )
			return __('Отчеты по посещениям', 'usam');	
		elseif ($this->table == 'pages_viewed' )
			return __('Просматриваемые страницы', 'usam');
		elseif ($this->table == 'visits' )
			return __('Визиты', 'usam');	
		else
			return __('Контакты на сайте', 'usam');			
	}
	
	public function get_tab_sections() 
	{ 
		$tables = array( 'contacts_online' => array( 'title' => __('Контакты на сайте','usam'), 'type' => 'table' ), 'visits' => array( 'title' => __('Визиты','usam'), 'type' => 'table' ), 'pages_viewed' => array( 'title' => __('Просмотры','usam'), 'type' => 'table' ) );		
		return $tables;
	}			
}