<?php
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_lists extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{
		$actions = array(
			'cleaning'   => __('Удалить подписчиков', 'usam'),			
			'delete'     => __('Удалить', 'usam'),				
		);
		return $actions;
	}
		
	function column_title( $item )
	{	
		$this->row_actions_table( $this->item_edit($item->id, $item->name, 'list'), $this->standart_row_actions($item->id, 'list') );
    }
	
	function column_view( $item )
	{	
		$this->logical_column( $item->view );
    }	
	
	function get_sortable_columns()
	{
		$sortable = array(
			'title'            => array('title', false),		
			'subscribed'       => array('subscribed', false),		
			'unconfirmed'      => array('unconfirmed', false),
			'daunsubscribedta' => array('unsubscribed', false),
			'data'             => array('data', false),
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'            => '<input type="checkbox" />',			
			'title'         => __('Название', 'usam'),
			'view'          => __('Виден в личном кабинете', 'usam'),			
			'subscribed'    => __('Подписан', 'usam'),					
			'unconfirmed'   => __('Неподтвержден', 'usam'),
			'unsubscribed'  => __('Отписан', 'usam'),		
			'description'   => __('Описание', 'usam'),				
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();	
		if ( empty($this->query_vars['include']) )
		{		
	
		}
		$query = new USAM_Mailing_Lists_Query( $this->query_vars );
		$this->items = $query->get_results();						
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}			
	}
}
?>