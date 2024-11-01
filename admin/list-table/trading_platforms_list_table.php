<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php');
require_once( USAM_FILE_PATH . '/includes/exchange/feeds_query.class.php');
class USAM_List_Table_trading_platforms extends USAM_List_Table
{	
	function get_bulk_actions_display() 
	{	
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}	
	
	function column_name( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'trading_platform', ['copy' => __('Копировать', 'usam')]);
		$actions['open'] = '<a href="'.home_url('trading-platform/feed/'.$item->id).'">'.__('Посмотреть', 'usam').'</a>';	
		$name = $this->item_edit( $item->id, $item->name, 'trading_platform' );
		$this->row_actions_table( $name, $actions );	
	}

	function column_platform( $item ) 
    {
		echo usam_get_name_integration( ['group_code' => 'trading-platforms', 'service_code' => $item->platform]);
	}

	function column_link( $item ) 
    { 
		echo "<span class='js-copy-clipboard'>".home_url('trading-platform/feed/'.$item->id)."</span>";
	}
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'name'     => array('name', false),			
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = [  
			'cb'         => '<input type="checkbox" />',
			'name'       => __('Название правила', 'usam'),
			'active'     => __('Активность', 'usam'),
			'interval'   => __('Интервал', 'usam'),		
			'platform'   => __('Торговая платформа', 'usam'),
			'link'       => __('Ссылка для загрузки', 'usam'),
        ];		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();
		if ( empty($this->query_vars['include']) )
		{							
			$selected = $this->get_filter_value( 'platform' );
			if ( $selected )
				$this->query_vars['platform'] = array_map('sanitize_title', (array)$selected);
		}
		$query = new USAM_Feeds_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}
	}
}
?>