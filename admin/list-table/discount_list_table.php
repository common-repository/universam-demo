<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/discounts_table.php' );
class USAM_List_Table_discount extends USAM_Discounts_Table 
{			
	protected $type_rule = 'product';		
	function column_products( $item ) 
	{
		echo "<a href='".admin_url("edit.php?post_type=usam-product&all_posts=1&discount=$item->id")."'>$item->products</a>";
	}

	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'name'        => __('Название правила', 'usam'),
			'active'      => __('Активность', 'usam'),
			'discount'    => __('Скидка', 'usam'),
			'interval'    => __('Интервал', 'usam'),			
			'type_prices' => __('Типы цен', 'usam'),	
			'products'    => __('Товары', 'usam'),	
			'date'        => __('Дата создания', 'usam'),		
        );	
		if ( $this->status != 'all' )
			unset($columns['active']);
        return $columns;
    }
}
?>