<?php
class USAM_Reports_View
{						
	protected $id = null;
	protected $period = 'last_30_day';	
	
	function __construct( $args = array() )
	{	
		if ( !empty($_REQUEST['id']) )
			$this->id = sanitize_title($_REQUEST['id']);
    }	
	
	public function display_interface_filters(  ) 
	{ 
		require_once( USAM_FILE_PATH . "/admin/interface-filters/reports_view_interface_filters.class.php" );
		$class = "Reports_View_Interface_Filters";
		$interface_filters = new $class(['period' => $this->period]);	
		?>
		<div id='reports_filters'>
			<?php $interface_filters->display(); ?>
		</div>
		<?php
	}
	
	public function display( $filter = true ) 
	{ 
		if ( $filter )
			$this->display_interface_filters();
		$this->display_reports();
	}
	
	public function display_reports() 
	{ 
		$reports = $this->get_report_widgets();
		foreach ( $reports as $report2 )
		{			
			?>
			<div class="reports_rows">
				<?php 					
				foreach ( $report2 as $report )
				{
					$content = '';
					$method = $report['key']. "_report_box";		
					if ( $report['view'] == 'box' )
					{
						ob_start();	
						if ( method_exists($this, $method) )
							$this->$method();
						else
							do_action( 'usam_report_widget_'.$report['key'], $this->id );
						$content = ob_get_clean();
					}				
					elseif ( $report['view'] == 'graph' )
					{
						ob_start();	
						?>
						<div id="statistics_block_<?php echo $report['key']; ?>" class="statistics_block">
							<span class ="statistics_block__item" v-for="item in statistics_block.<?php echo $report['key']; ?>">
								<span class="statistics_block__item-title">{{item.title}}</span>
								<span class="statistics_block__item-digit" v-html="item.value"></span>
							</span>
						</div>	
						<div class = "graph js-lzy-graph"><svg id ="<?php echo $report['key']; ?>_graph"></svg></div>
						<?php 
						$content = ob_get_clean();
					}				
					elseif ( $report['view'] == 'data_list' )
					{ 
						ob_start();	
						?>
						<div id="data_list_block_<?php echo $report['key']; ?>">	
							<?php 
							$results = $this->$method();				
							if ( !empty($results) )
								$this->display_data_list_block( $report['key'], $results );	
							?>
						</div>
						<?php 
						$content = ob_get_clean();
					}				
					elseif ( $report['view'] == 'data' )
					{ 
						ob_start();	
						?>
						<div id="<?php echo $report['key']; ?>" class="js-lzy-data-report">	
							<?php $results = $this->$method(); ?>
						</div>
						<?php 						
						$content = ob_get_clean();
					}
					elseif ( $report['view'] == 'transparent' )
					{
						?>
						<div class="reports_rows__row">
							<div class="statistic_total">
								<div class="statistic_total__title"><?php echo $report['title']; ?></div>
								<?php $this->display_total_results_report( $report['key'] ); ?>
							</div>
						</div>
						<?php 
						continue;						
					}
					elseif ( $report['view'] == 'loadable_table' )
					{
						ob_start();	
						$results = $this->$method();
						if ( !empty($results) )
							$this->display_loadable_table( $report['key'], $results );
						$content = ob_get_clean();							
					}	
					elseif ( $report['view'] == 'way' )
					{
						?>				
						<div class="reports_rows__row">
							<div class="statistic_total__title"><?php echo $report['title']; ?></div>
							<?php $this->$method( $report['key'] ); ?>
						</div>
						<?php 
						continue;						
					}					
					if ( $content )
					{
						?>				
						<div class="reports_rows__row">
							<div id ="report_box-<?php echo $report['key']; ?>" class="report_rows__box">
								<div class="report_rows__title"><?php echo $report['title']; ?></div>
								<div class="report_rows__content">
									<?php echo $content; ?>
								</div>
							</div>
						</div>
						<?php 		
					} 
				}
				?>
			</div>
			<?php 	
		}
	}
	
	protected function get_report_widgets() 
	{
		return array();
	}
	
	public function get_display_report_widgets() 
	{
		$hidden_widgets = get_user_option( 'usam_hidden_report_widgets' );	
		$widgets = $this->get_report_widgets( );
		if ( !empty($hidden_widgets[$this->tab]) ) 
		{	
			$widgets = array_diff_key($widgets, $hidden_widgets[$this->tab] );
			
		/*	foreach ( $widgets as $key => $title )
			{				
				if( isset($display_tabs[$tab]) )
				{
					self::$display_tabs[] = $display_tabs[$tab];
					unset($display_tabs[$tab]);
				}
			}			*/
		}		
		return $widgets;
	}	
	
	public function source_report_box( ) 
	{		
		?>			
		<contact-path :contact="contact" inline-template>
			<div class="contact_path">
				<div class="beginning_way">
					<div class="contact_source">
						<div class="contact_source__title"><?php esc_html_e( 'Источник', 'usam'); ?></div>
						<div class="contact_source__source_name" v-html="data.source_name"></div>
						<div class="contact_source__location" v-html="data.location_name"></div>
					</div>
				</div>
				<div class="path_groups">
					<div class="path_group" v-for="(visit, k) in visits">				
						<div class="path_group__item" :class="{'path_group__item_online':data.online}">
							<div class=""><?php _e('Визит','usam'); ?> №{{visits_count - k}}</div>
							<div class="path_group__item_date">{{localDate(visit.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</div>
							<div class="path_group__item_date">{{localDate(visit.date_insert,'H:i')}}</div>							
						</div>
						<div class="path_group__item" :class="{'path_group__item_online':data.online}">
							<div class="path_group__title"><?php _e('Источник','usam'); ?></div>
							<div class="path_group__item_source">{{visit.source}}</div>				
						</div>						
						<div class="path_group__item" :class="{'path_group__item_online':data.online}" title="<?php _e('Просмотренные страницы','usam'); ?>">
							<div class="path_group__title"><?php _e('Просмотры','usam'); ?></div>
							<div class="path_group__item_number">{{visit.views}}</div>
						</div>
						<a :href="visit.order_url" class="path_group__item" v-if="visit.order_id">
							<div class="path_group__title"><?php _e('Заказ','usam'); ?></div>
							<div class="path_group__item_number">{{visit.order_id}}</div>
						</a>	
						<div class="path_group__item path_group__item_none" v-else>
							<div class="path_group__text"><?php _e('Заказ','usam'); ?></div>
						</div>					
					</div>
				</div>		
			</div>	
		</contact-path>
		<?php 
	}
	
	function display_data_list_block( $id, $data_lists )
	{		
		?>
		<div id="<?php echo $id; ?>" class="view_data">						
			<?php
			foreach ( $data_lists as $key => $data )
			{
				?>				
				<div class ="view_data__row">
					<div class="view_data__name"><?php echo $data['title']; ?></div>
					<div class="view_data__option"><?php echo $data['value']; ?></div>
				</div>
				<?php 
			}
			?>	
		</div>			
		<?php 
	} 	
	
	protected function display_total_results_report( $id )
	{		
		?>				
		<div id="<?php echo $id; ?>" class="crm-start-row-result js-lzy-total-results">					
			<div class="crm-start-row-result-item crm-start-row-result-item-{k}" v-for="(item, k) in total.<?php echo $id; ?>">
				<div class="crm-start-row-result-item-title" v-html="item.title"></div>
				<div class="crm-start-row-result-item-total" v-html="item.value"></div>
			</div>		
		</div>
		<?php
	}
	
	public function display_loadable_table( $id, $results ) 
	{
		?>
		<div id="<?php echo $id; ?>" class="list_data js-lzy-list-data">
			<div class="fixed-captions-wrap">
				<div class="list_fixed_header">
					<div class="list_column_0 list_column list_column_primary"><?php echo $results[0]; ?></div>
					<div class="list_column_1 list_column"><?php echo $results[1]; ?></div>
				</div>
			</div>
			<div class="list_scroll">						
				<div class="list_content">
					<div class="list_row" v-for="list in lists.<?php echo $id; ?>.data">
						<div class="list_column list_column_primary" v-html="list.primary"></div>
						<div class="list_column list_column_1" ><span v-html="list.column"></span></div>
					</div>
					<div class="load_more_wrap">				
						<span class="load_more" @click="load_more_list_data" v-show="lists.<?php echo $id; ?>.more"><span class="dashicons dashicons-arrow-down-alt2"></span><?php _e('Показать больше','usam'); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}	
}
?>