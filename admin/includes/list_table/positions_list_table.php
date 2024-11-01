<?php
require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Positions_Table extends USAM_List_Table 
{	
	protected $search_engine='y';
	protected $region = 0;
	protected $site_id = 0;
	protected $groupby_date = 'month';	
	
	protected function get_filter_tablenav( ) 
	{ 
		return ['interval' => '', 'se' => $this->search_engine, 'site' => $this->site_id, 'region' => $this->region];
	}
	
	function column_keyword( $item ) 
    {			
		static $i = 0;
		$i++;		
		$keyword = "<div class='keyword_text'><span class='number_keyword'>$i</span><a href='https://www.google.com/search?q=".$item['keyword']."' class='google_link'  rel='noopener' target='_blank'>G</a><a href='https://yandex.ru/search/?text=".$item['keyword']."' class='yandex_link' target='_blank' rel='noopener'>Я</a><span class='phrase'>".$item['keyword']."</span></div>";	
		$keyword .= "<input type='hidden' name='cb[]' value='".$item['id']."' />";		
		echo $keyword;	
	}
	
	function colorate( $int )
	{
		global $row_item;
		
		$color = 'position_red';
		if($int <= 10)
			$color = 'position_grin';
		if($int > 10  && $int <= 20)
			$color = 'position_yellow';
				
		$pointer = '';
		$go = '';
		if ( $row_item !== false ) 
		{
			if ( !is_numeric($int) )
			{
				if ( $row_item != $int )
					$pointer = "<span class = 'dashicons pointer_down'></span>";
			}
			elseif ( !is_numeric($row_item) )
			{
				
			}
			elseif ( $row_item > $int )
			{
				$pointer = "<span class = 'dashicons pointer_up'></span>";
				$g = $row_item-$int;
				$go = "<span class ='go_position'>(+$g)</span>";
			}
			elseif ( $row_item < $int )
			{
				$pointer = "<span class = 'dashicons pointer_down'></span>";
				$g = $row_item-$int;
				$go = "<span class ='go_position'>($g)</span>";
			}
		}		
		$row_item = $int;
		
		return "<div class='results_positions'>".$pointer.'<span class = "position_number '.$color.'">'.$int.'</span>'.$go."<br /></div>"; 
	}
	
	public function single_row( $item )
	{
		global $row_item;		
		$row_item = false;
		
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function get_sortable_columns() 
	{
		$sortable = [
			'keyword'  => array('keyword', false),				
		];
		return $sortable;
	}
	public function get_columns_interval( $_columns )
	{		
		static $columns = null;
		if ( $columns === null )
		{
			$columns = $_columns;
			$current_year = mktime(0, 0, 0, 1 , 1, date("Y"));		
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{						
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
	
	function get_columns()
	{	
		$columns = [
			'keyword'      => __('Ключевые слова', 'usam'),
		];		
		return $this->get_columns_interval( $columns );	
    }
}