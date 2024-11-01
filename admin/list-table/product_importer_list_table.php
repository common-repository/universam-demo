<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/export_list_table.php' );
class USAM_List_Table_product_importer extends USAM_Export_Table
{	
	protected $rule_type = 'product_import';		
	function get_bulk_actions_display() 
	{	
		$actions = [
			'start_import' => __('Запустить', 'usam'),
			'delete_products' => __('Удалить загруженные товары', 'usam'),
			'delete'       => __('Удалить', 'usam'),			
		];
		return $actions;
	}	
	
	function column_name( $item ) 
    {
		$actions = ['copy' => __('Копировать', 'usam')];
		if ( $item->exchange_option !== '' && $item->exchange_option !== 'email' && !$this->is_start( $item ) )
			$actions['start_import'] = __('Запустить', 'usam');
		$this->row_actions_table( $this->item_edit($item->id, $item->name, $item->type), $this->standart_row_actions($item->id, $item->type, $actions) );			
	}
	
	function column_results( $item ) 
    {		
		?><a href="<?php echo add_query_arg(['post_type' => 'usam-product', 'exchange_rules' => $item->id], admin_url('edit.php') ); ?>"><?php echo $this->get_results( $item ); ?></a><?php
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'      => '<input type="checkbox" />',
			'name'    => __('Название правила', 'usam'),			
			'schedule'  => __('Автоматизация', 'usam'),	
			'active'  => __('Статус', 'usam'),					
			'process'   => __('Процесс', 'usam'),		
			'start_date' => __('Запущен', 'usam'),			
			'results'    => __('Добавлено / Обновлено', 'usam'),
			'exchange_option'  => __('Вариант обмена', 'usam')						
        );		
        return $columns;
    }
}
?>