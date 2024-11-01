<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/positions_list_table.php' );	
class USAM_List_Table_positions extends USAM_Positions_Table
{	
	public $orderby = 'ID';
	public $order   = 'desc';	
	protected $period = 'last_365_day';
	
	function __construct( $args = [] )
	{		
		$selected = $this->get_filter_value( 'se' );
		if ( $selected )
			$this->search_engine = $selected;	
		$selected = $this->get_filter_value( 'site' );
		if ( $selected )
			$this->site_id = $selected;	
		$selected = $this->get_filter_value('region');
		if ( $selected )
			$this->region = $selected;
		else
		{
			$location_ids = usam_get_search_engine_regions(['fields' => 'location_id', 'search_engine' => $this->search_engine, 'number' => 1]);	
			$this->region = $location_ids[0];
		}		
		parent::__construct( $args );
		add_action( 'usam_form_display_table_before', [&$this, 'form_display_table']);
    }	
		
	public function extra_tablenav_display( $which ) 
	{		
		if ( 'top' == $which )
		{					
			?>		
			<div class = "graph">
				<svg id ="graph" width="900" height="400"></svg>
			</div>	
			<?php 		
			
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
	
	public function form_display_table( ) 
	{
		$count = count($this->items);
		if ( !$count )
			return false;
		
		$statistics = ['visibility' => 0, 'improved' => 0,  'notchanged' => 0,  'worsened' => 0, 'top3' => 0, 'top10' => 0, 'top30' => 0];	
		foreach($this->items as $item )
		{
			unset($item['id']);
			unset($item['keyword']);
			foreach($item as $column => $value )
			{ 			
				if ( is_numeric($value) )
				{							
					$mode = next($item); 
					if ( !empty($mode) )
					{
						if ( is_numeric($mode) )
						{ 
							if ( $mode > $value)
								$statistics['improved']++; 
							elseif ( $mode == $value)
								$statistics['notchanged']++; 
							else
								$statistics['worsened']++; 
						}
					}					
					if ( $value <= 3 )
						$statistics['top3']++; 
					if ( $value <= 10 )
						$statistics['top10']++; 
					if ( $value <= 30 )
						$statistics['top30']++; 
				}	
				break;		
			}
		}		
		if ( $count )
			$statistics['visibility'] = round(($statistics['top3']+$statistics['top10'])*100 / $count, 1);		
		?>
		<div class="table_statistics">	
			<div class="table_statistics__item">				
				<div class="table_statistics__item_name"><?php _e( 'Видимость', 'usam'); ?></div>			
				<?php $prec = round($statistics['visibility'] * 360 /100, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0%</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['top3']+$statistics['top10']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Улучшились', 'usam'); ?></div>
				<?php $prec = round($statistics['improved']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['improved']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Не изменились', 'usam'); ?></div>
				<?php $prec = round($statistics['notchanged']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['notchanged']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Ухудшились', 'usam'); ?></div>
				<?php $prec = round($statistics['worsened']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['worsened']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Топ 3', 'usam'); ?></div>
				<?php $prec = round($statistics['top3']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['top3']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Топ 10', 'usam'); ?></div>
				<?php $prec = round($statistics['top10']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['top10']; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="table_statistics__item">
				<div class="table_statistics__item_name"><?php _e( 'Топ 30', 'usam'); ?></div>
				<?php $prec = round($statistics['top30']/$count * 360, 0); ?>
				<div class="table_statistics__item_circle">
					<div class="active_circle js-active-circle" data-prec="<?php echo $prec; ?>">
						<div class="active_circle__border">
							<span class="active_circle__prec js-active-border-prec">0</span>
							<span class="table_statistics__item_counter"><?php echo $statistics['top30']; ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}	
	
	public function column_default( $item, $column_name ) 
	{
		if ( is_numeric($column_name) )
			echo $this->colorate( $item[$column_name] );
		else
			echo $item[$column_name];
	}
	
	public function get_title_graph( ) 
	{
		return __('Эффективность продвижения','usam');		
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
				$item[$j] = '-';					
				$infinity = false;
				$k = 0;					
				$sum = 0;				
				$max = 1;
				$min = 99;
				$start = '';
				$end = '';					
				$current_date = strtotime(get_gmt_from_date(date( "Y-m-d H:i:s",$j)));
				foreach($statistics_keywords as $key => $statistic )
				{	
					if ( $keyword->id == $statistic->keyword_id )
					{										
						if ( $current_date > strtotime($statistic->date_insert.' 23:59:59') )												
							break;							
						else
						{ 
							if ( $statistic->number > 0 )
							{				
								$sum += $statistic->number;
								$k++;
								if ( $statistic->number > $max )
									$max = $statistic->number;
								if ( $statistic->number < $min )
									$min = $statistic->number;
								
								if ( $end === '' )
									$end = $statistic->number;
								$start = $statistic->number;
							}
							else
							{
								$infinity = true;
							}
							unset($statistics_keywords[$key]);			
						}
					}
				}				
				if ( $k > 0 && !$infinity )
				{
				//	$average = round($sum/$k,0);	
				/*	if ( $k > 1 )
						$item[$j] = "$start - $end";
					else
						$item[$j] = $end;
					*/					
					$item[$j] = $end;
				}
				$j = strtotime("-1 ".$this->groupby_date, $j);	
			}		
			$this->items[] = $item;	
		}				
		$data = array();
		foreach ( $this->items as $item )
		{			
			foreach ( $item as $time => $value )
			{
				if ( is_numeric($time) )
				{
					if ( is_numeric($value) )					
						$number = $value;					
					else
						$number = 100;
					
					if ( $number > 100 )
						$number = 100;
					
					if ( isset($data[$time]) )				
						$data[$time] += $number;
					else
						$data[$time] = $number;
				}				
			}
		}		
		$count = count($keywords);
		foreach ( $data as $time => $value )
		{		
			$this->data_graph[] = ['y_data' => date_i18n( "d.m.y", $time ), 'x_data' => 100-round($value/$count,0)];
		}				
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}	
}