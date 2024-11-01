<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_excluded_products_management extends USAM_Product_List_Table 
{
	private $type_price = '';
	private $storages = array();
	
    function __construct( $args = array() )
	{	
		parent::__construct( $args );
		$this->storages = usam_get_storages( );					
    }	
	
	function get_columns()
	{
        $columns = array(           
			'cb'            => '<input type="checkbox" />',	
			'product_title' => __('Имя', 'usam'),
			'sku'           => __('Артикул', 'usam'),
			'price'         => __('Цена', 'usam'),				
			'stock'         => __('Запас', 'usam'),		
        );
        return $columns;
    }
	
	function query_vars( $query_vars ) 
	{	
		$query_vars['productmeta_query'] = array( array( 'key' => 'excluded_products_management', 'value' => '0', 'compare' => '!=','type' => 'numeric' ));	
		return $query_vars;
	}
}