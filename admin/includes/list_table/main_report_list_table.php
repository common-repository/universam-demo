<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Main_Report_List_Table extends USAM_List_Table
{
	protected $order = 'desc';	
	protected $order_status = '';
	protected $per_page = 0;
	protected $period = 'last_365_day';	
	protected $groupby_date = 'month';		
		
	function column_default( $item, $column_name ) 
	{	
		if ( is_object($item) )
			$data = $item->$column_name;
		elseif ( isset($item[$column_name]) )
			$data = $item[$column_name];		
		else
			$data = 0;
	
		if ( is_numeric($data) )
		{
			if ( $data !== 0 )
				echo $this->currency_display( $data );
		}
		else
			echo $data;			
	}
	
	public function column_date( $item ) 
	{		
		return date_i18n('d.m.y', $item['date'] );
	}	
	
	public function extra_tablenav( $which ) 
	{				
		if ( 'top' == $which )
		{						
			?>
			<div class = "graph">
				<svg id ="graph" width="900" height="400"></svg>
			</div>			
			<?php
			$this->standart_button();	
		}
	}		
	
	protected function display_tablenav( $which ) 
	{ 
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">		
			<?php 
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
	<?php
	}
}
?>