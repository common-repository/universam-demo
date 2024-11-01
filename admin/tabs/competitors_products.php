<?php
class USAM_Tab_competitors_products extends USAM_Page_Tab
{
	public function __construct()
	{
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
		$sites = usam_get_parsing_sites(['site_type' => 'competitor', 'number' => 1]);
		if ( empty($sites) )
			$this->blank_slate = true;
		$this->views = ['table', 'report'];
	}	
	
	public function table_view() 
	{					
		if ( $this->blank_slate )
		{
			$buttons = [ 
				['title' => __("Добавить сайт конкурента","usam"), 'url' => admin_url("admin.php?page=exchange&tab=parser") ],
			];
			$this->display_connect_service( __('Добавьте сайт конкурента, чтобы увидеть потрясающие возможности анализа товаров и цен.', 'usam'), $buttons );	
		}
		else
		{
			$this->display_tab_sections();	
			$this->list_table->display_table(); 
		}
	}
	
	public function get_title_tab()
	{			
		return __('Товары конкурентов', 'usam');	
	}	
}