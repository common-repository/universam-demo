<?php
require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_price_analysis_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
		//	array( array( 'title' => __('Общий итог', 'usam'), 'key' => 'total_price_analysis', 'view' => 'transparent' ) ),		
			[ ['title' => __('Цены выросли', 'usam'), 'key' => 'rise_prices_competitors', 'view' => 'graph'], ['title' => __('Цены упали', 'usam'), 'key' => 'falling_prices_competitors', 'view' => 'graph'] ],
			[['title' => __('Новинки', 'usam'), 'key' => 'products_competitors', 'view' => 'graph'],['title' => __('Конкуренты', 'usam'), 'key' => 'competitors', 'view' => 'graph']],
		);	
		return $reports;
	}		
}
?>