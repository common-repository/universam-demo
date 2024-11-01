<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/positions_list_table.php' );	
class USAM_List_Table_view extends USAM_Positions_Table
{	
	public $orderby = 'ID';
	public $order   = 'desc';

	protected $search_engine='y';
	protected $region = 0;
	protected $keyword = '';	
	protected $host = '';
	
	protected $groupby_date = 'day';	
	protected $period = 'last_30_day';
	
	function __construct( $args = array() )
	{	
		$this->host = parse_url( get_site_url(), PHP_URL_HOST);	
		$this->set_date_period();			
		$selected = $this->get_filter_value( 'se' );
		if ( $selected )
			$this->search_engine = $selected;	
		$selected = $this->get_filter_value( 'site' );
		if ( $selected )
			$this->site_id = $selected;	
		$selected = $this->get_filter_value( 'keyword' );
		if ( $selected )			
			$this->keyword = absint($selected);				
		else	
			$this->keyword  = usam_get_keywords(['fields' => 'id', 'check' => 1, 'number' => 1]);			
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
			$url = $this->get_nonce_url( add_query_arg( array('action' => 'start'), $_SERVER['REQUEST_URI'] ) );
			?>		
			<a href="<?php echo $url; ?>" class = "button button-primary"><?php _e( 'Проверить сейчас', 'usam'); ?></a>
			<?php 									
			echo '</div>';		
			echo '<div class="alignleft actions">';					
				$this->standart_button();									
			echo '</div>';						
		}
	}			
	
	function column_n( $item ) 
    {			
		echo $item['number'];
	}
		
	public function column_default( $item, $column_name ) 
	{
		if ( is_numeric($column_name) && isset($item[$column_name]) )
		{							
			$class = stripos($item[$column_name], $this->host) !== false ? "class='active'":"";				
			$parse_url = parse_url( $item[$column_name] );		
			$domain = str_replace("www.","",$parse_url['host']);				
			echo "<span class='domain'>$domain</span><br><a href='".$item[$column_name]."' $class target='_blank'>".$parse_url['path']."</a>";
		}		
	}
	
	function get_columns()
	{	
		$columns = array(        	
			'n'      => '',
		);		
		return $this->get_columns_interval( $columns );	
    }
		
	function prepare_items() 
	{	
		global $wpdb;

		$sql_query = "SELECT * FROM ".USAM_TABLE_STATISTICS_KEYWORDS." WHERE search_engine='$this->search_engine' AND keyword_id='$this->keyword' AND location_id='$this->region' AND date_insert>='$this->start_date_interval' AND date_insert<='$this->end_date_interval' AND number<='10' ORDER BY date_insert DESC, number";		
		$statistics_keywords = $wpdb->get_results( $sql_query );	
		if ( !empty($statistics_keywords) )
		{			
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{						
				$current_date = strtotime(get_gmt_from_date(date( "Y-m-d H:i:s",$j)));
				foreach ( $statistics_keywords as $key => $statistic ) 
				{						
					if ( $current_date <= strtotime($statistic->date_insert.' 23:59:59') )
					{
						$this->items[$statistic->number]['number'] = $statistic->number;	
						$this->items[$statistic->number][$j] = $statistic->url;		
						unset($statistics_keywords[$key]);							
					}				
				}
				$j = strtotime("-1 ".$this->groupby_date, $j);
			}					
		}		
	}	
}