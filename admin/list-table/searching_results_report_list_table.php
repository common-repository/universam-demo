<?php
require_once( USAM_FILE_PATH . '/includes/search/search_query.class.php' );
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_searching_results_report extends USAM_Main_Report_List_Table 
{		
	protected $groupby_date = 'year';
	protected $period = 'last_30_day';	
	protected $orderby = 'date';	
	protected $per_page = 0;	
	
	function column_phrase( $item ) 
    {				
		$url = add_query_arg( array( 'keyword' => $item['phrase'] ), usam_get_url_system_page( 'search' ) );
		echo "<a href='".$url."' target='_blank'>".$item['phrase']."</a>";	
    }   
   
	function get_sortable_columns() 
	{
		$sortable = array(
			'phrase' => array('phrase', false),
			'date'   => array('date', false),						
			'number_results'  => array('number_results', false),	
			'contact'  => array('contact_id', false),	
		);
		return $sortable;
	}
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	public function get_number_columns_sql()
    {       
		return array('count');
    }
	
	function get_columns()
	{
        $columns = array(   			
			'phrase'         => __('Фраза поиска', 'usam'),	
			'count'          => __('Количество поисков', 'usam'),
			'number_results' => __('Показано результатов', 'usam'),			
			'contact'        => __('Контакт', 'usam'),	
			'date'           => __('Дата', 'usam'),			
        );		
        return $columns;
    }
	
	function prepare_items() 
	{					
		$this->get_query_vars();			
		if ( $this->search == '' )
		{	
		//	$this->query_vars['groupby'] = 'phrase';
		}
		$this->query_vars['cache_contacts'] = true;
		$this->get_digital_interval_for_query( array('number_results') );
		$searching_results = new USAM_Searching_Results( $this->query_vars );
		$results = $searching_results->get_results();	
	
		$records = array();			
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{									
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			foreach ( $results as $key => $item )
			{		
				$i = sanitize_title($item->phrase);
				if ( empty($records[$i]) )
				{
					$records[$i]['number_results'] = 0; 
					$records[$i]['contact_id'] = 0;
					$records[$i]['count'] = 0;					
					$records[$i]['phrase'] = '';	
					$records[$i]['date'] = $j;					
				}
				if ( $current_date > $item->date_insert )
				{		
					break;					
				}
				else
				{			
					if ( $records[$i]['number_results'] < $item->number_results)
						$records[$i]['number_results'] = $item->number_results;			
					$records[$i]['count']++;				
					if ( $item->contact_id )
						$records[$i]['contact_id'] = $item->contact_id;
					$records[$i]['phrase'] = $item->phrase;				
					unset($results[$key]);					
				}
			}			
			$j = strtotime("-1 ".$this->groupby_date, $j);	
		}		
		$this->items = array_values($records);
	}		
}