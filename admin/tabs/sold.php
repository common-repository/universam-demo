<?php
class USAM_Tab_sold extends USAM_Page_Tab
{
	protected $views = ['table', 'report', 'settings'];//'grid',
	
	public function get_title_tab()
	{			
		if( $this->table == 'excluded_products_management' )
			return __('Исключенные из управления товары', 'usam');
		return __('Заказ товара', 'usam');
	}
	
	public function get_settings_tabs() 
	{ 
		return ['settings' => ['title' => __('Настройки','usam'), 'type' => 'section'], 'excluded_products_management' => ['title' => __('Исключенные товары','usam'), 'type' => 'table']];
	}
	
	public function display_section_settings() 
	{		
		usam_add_box( 'usam_application', __('Общие настройки', 'usam'), array( $this, 'settings_meta_box' ) );	
	}
	
	
	public function settings_meta_box()
	{
		$options = array(    	
			array( 'key' => 'inventory_management', 'type' => 'checkbox', 'title' => __('Включить управление', 'usam'), 'option' => 'enable' ),			
		); 		  
		$this->display_table_row_option( $options );  
	}
}