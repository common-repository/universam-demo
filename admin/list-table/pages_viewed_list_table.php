<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
class USAM_List_Table_pages_viewed extends USAM_List_Table 
{		
	protected $order = 'desc';	
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];		
	}
	
	function column_time( $item ) 
	{
		echo human_time_diff( strtotime($item->visit_date), strtotime($item->date_insert) );
	}
	
	function column_title( $item ) 
	{
		if ( $item->post_id )
		{
			echo "<div class='title_column'><a href='$item->url'>".usam_get_product_thumbnail( $item->post_id, 'manage-products' )."</a>";
			echo "<a href='$item->url'>".get_the_title( $item->post_id )."</a></div>";
		}
		elseif ( $item->term_id )
		{
			$term = get_term( $item->term_id );	
			if ( !empty($term) )
				echo "<a href='$item->url'>".$term->name."</a></div>";		
			else
				echo "<a href='$item->url'>$item->url</a>";	
		}
		else
			echo "<a href='$item->url'>".urldecode($item->url)."</a>";		
	}
		
	function get_sortable_columns() 
	{
		$sortable = [	
			'contact'    => array('contact_id', false),		
			'date'       => array('date_insert', false)
		];
		return $sortable;
	}
		
	function get_columns(){
        $columns = [ 
			'title'         => __('Страница', 'usam'),	
			'date'          => __('Дата', 'usam'),			
			'time'          => __('Время на сайте', 'usam'),				
			'contact'       => __('Контакт', 'usam'),				
        ];
        return $columns;
    }
	
	function prepare_items() 
	{						
		$this->get_query_vars();
		$this->query_vars['fields'] = ['contact_id','visit_date','all'];			
		$this->query_vars['cache_contacts'] = true;	
		$this->query_vars['cache_posts'] = true;
	//	$this->query_vars['online'] = 1;				
		if ( empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'manager' );
			if ( $selected ) 
				$this->query_vars['manager_id'] = array_map('intval', (array)$selected);
			
			$selected = $this->get_filter_value( 'visit_id' );
			if ( $selected ) 
				$this->query_vars['visit_id'] = array_map('intval', (array)$selected);			
			
			$selected = $this->get_filter_value( 'channel' );
			if ( $selected ) 
				$this->query_vars['channel'] = array_map('sanitize_title', (array)$selected);			
		}
		$query = new USAM_Pages_Viewed_Query( $this->query_vars );	
		$this->items = $query->get_results();
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}