<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class Product_report_Interface_Filters extends Report_Interface_Filters
{	
	protected $search_box = true;
	protected function get_filters( ) 
	{				
		return ['product' => ['title' => __('Выберите товар', 'usam'), 'type' => 'autocomplete', 'query' => ["product_type" => 'simple,variation', 'status' => 'publish,draft'], 'request' => 'products']];
	}			
}
?>