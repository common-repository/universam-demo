<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/tasks_table.php' );		
class USAM_List_Table_time extends USAM_Table_Tasks 
{	
	protected $orderby = 'start';
	protected $order = 'DESC';
	protected $period = 'last_30_day';		
	protected $groupby_date = 'month';

	public function get_views() { }
	
	function get_bulk_actions_display() 
	{
		$actions = array();
		return $actions;
	}
		
	function column_user( $item )
	{
		if ( $item->user_id != 0)
		{	
			?> 
			<div class="user">
				<img width="32" height="32" class="avatar avatar-32 photo" src="<?php echo get_avatar_url( $item->user_id, array('size' => 32, 'default'=>'mystery' ) ); ?>" alt="" />&nbsp;
				<span><?php echo usam_get_manager_name( $item->user_id ); ?></span>
			</div>
			<?php 
		}		
	} 	
	
	function column_title( $item )
	{	
		echo $item->title;
	}
	
	function column_status( $item ) 
	{		
		echo $this->statuses[$item->status];
	}
	
	function column_time_work( $item )
	{
		if ( !empty($item->start) && !empty($item->end) )
		{
			$time = round((strtotime($item->end) - strtotime($item->start)) / 60);
			if ( $time == 0 )
				_e( 'менее минуты', 'usam');
			else
				echo $time." ".__("мин","usam");
		}
	} 
	
	protected function get_filter_tablenav( ) 
	{		
		return array( 'interval' => '' );		
	}
	
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which )
		{					
			echo '<div class="alignleft actions">';	
				$this->standart_button();									
			echo '</div>';
		}
	}   
	
	function get_sortable_columns() 
	{
		if ( ! $this->sortable )
			return array();
		
		$sortable = array(
			'employee'  => array('employee', false),				
		);
		return $sortable;
	}
	
	function get_columns()
	{
		static $columns = null;
		
		if ( $columns == null )
		{
			 $columns = array(        	
				'employee'      => __('Сотрудник', 'usam'),
			);					
			$current_year = mktime(0, 0, 0, 1 , 1, date("Y"));	
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{						
				$current_date = strtotime(get_gmt_from_date(date( "Y-m-d H:i:s",$j)));
				if ( $this->groupby_date == 'month' )
				{
					if ( $j >= $current_year )
						$format = 'F';
					else
						$format = 'm.y';
					$column = date_i18n( $format, $j );
				}
				elseif ( $this->groupby_date != 'day' )
				{
					if ( $j >= $current_year )
						$format = 'd.m';
					else
						$format = 'd.m.y';
					
					$date_from = strtotime("+1 ".$this->groupby_date, $j);
					$date_from = mktime(0, 0, 0, date("m", $date_from) , date("d", $date_from), date("Y", $date_from));					
					$column = date( $format, $j ).' - '.date( $format, $date_from-1 );			
				}
				else
				{
					if ( $j >= $current_year )
						$format = 'd.m';
					else
						$format = 'd.m.y';
					$column = date( $format, $j );	
				}
				$columns[$j] = $column;			
				$j = strtotime("-1 ".$this->groupby_date, $j);	
			}		
		}		
		return $columns;
    }
	
	function prepare_items() 
	{			
	/*	$this->get_query_vars();	
			
		
		$this->query_vars['type'] = 'work';				
		$this->query_vars['cache_meta'] = true;		
		$this->query_vars['cache_contacts'] = true;				
		$query = new USAM_Events_Query( $this->query_vars );
		$this->items = $query->get_results();
		if ( $this->per_page )
		{ 
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	*/
	}
}