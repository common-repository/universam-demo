<?php
class USAM_Tab_delivery_documents extends USAM_Page_Tab
{	
	public function __construct()
	{	
		$this->views[] = 'table';
		if ( current_user_can( 'grid_document' ) )
			$this->views[] = 'grid';						
		if ( current_user_can( 'map_document' ) )
			$this->views[] = 'map';	
		if ( current_user_can( 'report_document' ) )
			$this->views[] = 'report';	
	}	
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'shipped', 'title' => __('Добавить', 'usam'), 'capability' => 'add_shipped']];	
	}	
	
	public function get_title_tab()
	{			
		if ( $this->view == 'map' )
			return __('Курьеры на карте', 'usam');
		elseif ( $this->view == 'report' )
			return __('Отчеты по доставке', 'usam');		
		else
			return __('Управление доставкой', 'usam');
	}
}