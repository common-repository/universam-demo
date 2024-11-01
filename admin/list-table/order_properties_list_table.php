<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/properties_table.php' );
class USAM_List_Table_order_properties extends USAM_Properties_Table
{
	protected $property_type = 'order';	
	
	function get_columns()
	{		
        $columns = [          
			'cb'        => '<input type="checkbox" />',			
			'name'      => __('Название', 'usam'),
			'mandatory' => __('Обязательное', 'usam'),	
			'field_type'=> __('Тип поля', 'usam'),			
			'group'     => __('Группа', 'usam'),	
			'roles'     => __('Видимость поля', 'usam'),
			'drag'      => '&nbsp;',
        ];		
        return $columns;
    }
	
	function get_bulk_actions_display() 
	{	
		$actions = [
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),		
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}	
}
?>