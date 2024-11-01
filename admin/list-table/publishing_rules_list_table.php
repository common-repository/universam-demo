<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_publishing_rules extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_title( $item ) 
    {
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'publishing_rule' ) );	
	}
	
	function column_exclude( $item ) 
    {
		echo sprintf( _n('на %s день', 'на %s дней', $item['exclude'], 'usam'), $item['exclude'] );		
	}
	
	function column_periodicity( $item ) 
    {
		echo sprintf( _n('каждые %s часа', 'каждые %s часа', $item['periodicity'], 'usam'), $item['periodicity'] );		
	}
	
	function column_date_publish( $item ) 
    {
		if ( !empty($item['date_publish']) )
			echo date_i18n( get_option( 'date_format', 'Y/m/d' )." H:i", $item['date_publish']);		
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'title'     => array('title', false),		
			'code'    => array('code', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',	
			'title'      => __('Название правила', 'usam'),
			'active'      => __('Активность', 'usam'),
			'date_publish' => __('Последняя публикация', 'usam'),	
			'periodicity' => __('Периодичность', 'usam'),		
			'exclude'     => __('Исключить', 'usam'),				
			'interval'  => __('Интервал', 'usam'),
			'pricemin'    => __('Мин цена', 'usam'),
			'pricemax'    => __('Мах цена', 'usam'),
			'minstock'    => __('Остаток', 'usam'),	
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$option = get_site_option('usam_vk_publishing_rules' );
		$data = maybe_unserialize( $option );	
		if ( !empty($data) )			
			foreach( $data as $key => $item )
			{	
				if ( empty($this->records) || in_array($item['id'], $this->records) )
				{				
					$this->items[] = $item;
				}
			}		
		$this->total_items = count($this->items);	
		$this->forming_tables();
	}
}
?>