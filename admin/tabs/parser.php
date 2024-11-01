<?php
class USAM_Tab_parser extends USAM_Page_Tab
{		
	public function __construct()	
	{
		if ( empty($_REQUEST['id']) )
			$this->views = ['table', 'report'];
	}	
	
	public function get_title_tab()
	{					
		if ( isset($_REQUEST['id']) )
		{
			$id = absint($_REQUEST['id']);
			$parsing_site = usam_get_parsing_site( $id );
			if ( $parsing_site )
				return $parsing_site['name'];
		}
		else
			return __('Сайты для парсинга', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( !isset($_REQUEST['id']) && $this->view == 'table' )
		{
			return [ 
				['form' => 'edit', 'form_name' => 'parser_competitor', 'title' => __('Добавить конкурента', 'usam') ],
				['form' => 'edit', 'form_name' => 'parser_supplier', 'title' => __('Добавить поставщика', 'usam')] 
			];
		}
	}
}