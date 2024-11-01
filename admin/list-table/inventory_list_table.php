<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_inventory extends USAM_Product_List_Table 
{
	function column_default( $item, $column_name ) 
	{ 
		$storages = usam_get_storages();
		foreach ( $storages as $storage )
		{
			if ( $column_name == 'storage_'.$storage->id )
			{
				return do_action( "manage_usam-product_posts_custom_column", 'storage_'.$storage->id, $item->ID );				
			}
		}
		return parent::column_default( $item, $column_name );
    }   	
	
	function get_sortable_columns() 
	{
		$sortable = array();
		$storages = usam_get_storages();
		foreach ( $storages as $storage )
		{				
			$sortable['storage_'.$storage->id] = 'storage_'.$storage->id;
		}			
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [
		//	'cb'            => '<input type="checkbox" />',	
			'product_title' => __('Имя', 'usam'),
			'price'         => __('Цена', 'usam'),				
			'stock'         => __('Запас', 'usam'),		
        ];
		$storages = usam_get_storages();
		foreach ( $storages as $storage )
		{				
			$t = strlen($storage->title) > 10 ? '...':'';
			$columns['storage_'.$storage->id] = mb_substr($storage->title,0,10).$t;
		}			
        return $columns;
    }
}