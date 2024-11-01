<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/positions_list_table.php' );	
class USAM_List_Table_links extends USAM_Positions_Table
{	
	public $orderby = 'ID';
	public $order   = 'desc';
	
	protected $period = 'last_90_day';
	protected $groupby_date = 'week';	
	function __construct( $args = array() )
	{	
		$selected = $this->get_filter_value( 'se' );
		if ( $selected )
			$this->search_engine = $selected;
		$selected = $this->get_filter_value( 'site' );
		if ( $selected )
			$this->site_id = $selected;	
		$selected = $this->get_filter_value( 'region' );
		if ( $selected )
			$this->region = $selected;
		else
		{
			$location_ids = usam_get_search_engine_regions(['fields' => 'location_id', 'search_engine' => $this->search_engine, 'number' => 1]);	
			$this->region = $location_ids[0];
		}
		parent::__construct( $args );
    }
	
	public function extra_tablenav_display( $which ) 
	{		
		if ( 'top' == $which )
		{
			echo '<div class="alignleft actions">';	
			$url = $this->get_nonce_url( add_query_arg(['action' => 'start'], $_SERVER['REQUEST_URI'] ) );
			?>		
			<a href="<?php echo $url; ?>" class = "button button-primary"><?php _e( 'Проверить сейчас', 'usam'); ?></a>
			<?php 									
			echo '</div>';		
			echo '<div class="alignleft actions">';					
				$this->standart_button();									
			echo '</div>';						
		}
	}		
		
	public function column_default( $item, $column_name ) 
	{ 
		if ( is_array($item[$column_name]) )
		{			
			foreach ( $item[$column_name] as $value ) 
			{			
				$host = parse_url($value['url'], PHP_URL_PATH);	
				$host = $host=='/'?$value['url']:$host;
				echo $this->colorate( $value['number'] )."<a href='".$value['url']."' target='_blank'>$host</a><br>";
			}
		}
	}	

	function prepare_items() 
	{	
		global $wpdb;
			
		$this->get_query_vars();
		$this->query_vars['check'] = 1;		
		$query = new USAM_Keywords_Query( $this->query_vars );
		$keywords = $query->get_results();
		$this->total_items = $query->get_total();	

		$sql_query = "SELECT * FROM ".USAM_TABLE_STATISTICS_KEYWORDS." WHERE search_engine='$this->search_engine' AND location_id='$this->region' AND site_id='$this->site_id' AND date_insert>='$this->start_date_interval' AND date_insert<='$this->end_date_interval' ORDER BY date_insert DESC";
		$statistics_keywords = $wpdb->get_results($sql_query);	
		foreach($keywords as $keyword )
		{
			$item = array( 'id' => $keyword->id, 'keyword' => $keyword->keyword );	
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{						
				$item[$j] = array();			
				$infinity = false;
				$k = 0;					
				$sum = 0;				
				$max = 1;
				$min = 99;	
				$current_date = strtotime(get_gmt_from_date(date( "Y-m-d H:i:s",$j)));
				foreach($statistics_keywords as $key => $statistic )
				{	
					if ( $keyword->id == $statistic->keyword_id )
					{
						if ( $current_date > strtotime($statistic->date_insert.' 23:59:59') )												
							break;								
						else
						{				
							if ( empty($item[$j][$statistic->url]) )							
								$item[$j][$statistic->url] = array( 'url' => $statistic->url, 'number' => $statistic->number, 'count' => 1 );							
							else
							{
								$number = $item[$j][$statistic->url]['number'] * $item[$j][$statistic->url]['count'];									
								$item[$j][$statistic->url]['count']++;								
								$number += $statistic->number;	
								$item[$j][$statistic->url]['number'] = round( $number / $item[$j][$statistic->url]['count'], 0 );			
							}
							unset($statistics_keywords[$key]);			
						}
					}
				}					
				$j = strtotime("-1 ".$this->groupby_date, $j);		
			}				
			$this->items[] = $item;	
		}
		$this->set_pagination_args( array(	'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}	
}