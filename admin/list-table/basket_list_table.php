<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/discounts_table.php' );
class USAM_List_Table_basket extends USAM_Discounts_Table 
{			
	protected $type_rule = 'basket';	
	private $is_bulk_edit = false;
	protected $orderby = 'id';	
		
	function get_columns()
	{		
        $columns = array(           
			'cb'       => '<input type="checkbox" />',	
			'name'     => __('Название правила', 'usam'),
			'active'   => __('Активный', 'usam'),
			'discount' => __('Скидка', 'usam'),		
			'interval' => __('Интервал', 'usam'),
			'priority' => __('Приоритет', 'usam'),	
			'date'     => __('Дата создания', 'usam'),	
        );	
		if ( $this->status != 'all' )
			unset($columns['active']);		
        return $columns;
    }
}