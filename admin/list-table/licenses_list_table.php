<?php 
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/licenses_query.class.php'  );
class USAM_List_Table_licenses extends USAM_List_Table 
{	
	private $status_sum = array( );	
	protected $order = 'desc'; 		
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}		
			
	function column_software( $item ) 
    {
		$this->row_actions_table( $item->software, $this->standart_row_actions( $item->id ) );
    }
		
	function column_date( $item )
    {		
		echo usam_local_date( $item->license_start_date );
	}
	
	function column_license_end_date( $item )
    {		
		$day = round((strtotime($item->license_end_date) - time())/(60*60*24)); 
		?><span class="<?php echo $day > 30 ?'item_status_valid':'item_status_attention'; ?> item_status"><?php echo sprintf( _n('заканчивается через %s день', 'заканчивается через %s дней', 'usam', $day), $day); ?></span><?php
	}
				
	function column_status( $item ) 
    { 
		if ( $item->status == 1 )
			echo '<span class="item_status item_status_valid">'.__("Активна","usam").'</span>';
		elseif ( $item->status == 2 )
			echo '<span class="item_status status_blocked">'.__("Блокирована","usam").'</span>';
		else
			echo '<span class="item_status item_status_notcomplete">'.__("Не активна","usam").'</span>';
    }	
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'software'  => array('software', false),
			'date'      => array('license_start_date', false),	
			'status'    => array('status', false),			
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'software'  => __('Название продукта', 'usam'),				
			'status'    => __('Статус лицензии', 'usam'),	
			'date'      => __('Дата активации', 'usam'),		
			'license_end_date' => __('Обновления и поддержка', 'usam'),	
			'license'  => __('Лицензия', 'usam'),	
        ); 
        return $columns;
    }
	
	function prepare_items() 
	{				
		$this->get_query_vars();	
		if ( empty($this->query_vars['include']) )
		{				
			
		} 			
		$query = new USAM_Licenses_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}			
	}
}