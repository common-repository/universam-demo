<?php
class USAM_Tab_searching_results_report extends USAM_Tab
{	
	protected  $per_page = 20;
		
	public function get_title_tab()
	{			
		return __('Что искали посетители на сайте', 'usam');	
	}
}