<?php
require_once(USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once(USAM_FILE_PATH . '/includes/analytics/advertising_campaigns_query.class.php');
class USAM_List_Table_advertising_campaigns extends USAM_List_Table
{	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'  => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_title( $item ) 
    {			
		$this->row_actions_table( $this->item_view($item->id, $item->title, 'advertising_campaign'), $this->standart_row_actions( $item->id, 'advertising_campaign', ['copy' => __('Копировать', 'usam')] ));
		echo $item->description;	
	}

	function column_platform( $item ) 
    {
		$platforms = usam_get_integrations('trading-platforms');
		if ( isset($platforms[$item['platform']]) )
			echo $platforms[$item['platform']];	
	}	
	
	function column_source( $item ) 
    {
		return usam_get_name_source_advertising_campaign( $item->source );
	}
	
	function column_transitions( $item ) 
    {
		if( $item->transitions )
			return "<span class='item_status item_status_valid'>{$item->transitions}</span>";
		else
			return $item->transitions;
	}
	
	function column_link( $item ) 
    {
		echo "<p class='js-copy-clipboard'>".home_url( '/' ).'ac/'.$item->code."</p>";
		echo "<p class='js-copy-clipboard'>".usam_get_url_utm_tags( (array)$item )."</p>";
	}		
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'title'     => array('title', false),			
			'source'    => array('source', false),			
		);
		return $sortable;
	}	
	
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'title'       => __('Название компании', 'usam'),			
		//	'description' => __('Описание', 'usam'),
			'transitions' => __('Переходов', 'usam'),
			'source'      => __('Источник', 'usam'),
			'link'        => __('Ссылка', 'usam'),			
			'date'        => __('Дата', 'usam'),			
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{
			$selected = $this->get_filter_value( 'sources' );
			if ( $selected )
				$this->query_vars['source'] = array_map('sanitize_title', (array)$selected);
			$this->get_digital_interval_for_query(['transitions']);
		}		
		$query = new USAM_Advertising_Campaigns_Query( $this->query_vars );
		$this->items = $query->get_results();
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>