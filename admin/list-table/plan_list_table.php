<?php	
require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan_query.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_plan extends USAM_List_Table 
{	
	protected $order = 'DESC';	

	function column_period_type( $item ) 
    {		
		$types_period = usam_get_types_period_sales_plan();
		$title = isset($types_period[$item->period_type])?$types_period[$item->period_type]:'';
		$this->row_actions_table( $this->item_edit( $item->id, $title, 'plan' ), $this->standart_row_actions( $item->id, 'plan', ['copy' => __('Копировать', 'usam')] ) );	
	}
	
	function column_plan_type( $item ) 
	{		
		$plan_types = usam_get_plan_types( );
		echo isset($plan_types[$item->plan_type])?$plan_types[$item->plan_type]:'';
	}
	
	function column_period( $item ) 
	{				
		$y = date('Y', strtotime($item->from_period) );
		if ( $item->period_type == 'month' )
		{			
			$month = date_i18n('F', strtotime($item->from_period));
			printf(__('%s %s года', 'usam'),$month, $y);
		}
		elseif ($item->period_type == 'quarter' )
		{
			$month = date('n', strtotime($item->from_period));	
			$quarter = intval(($month+2)/3);
			printf(__('%s квартал %s года', 'usam'),$quarter, $y);
		}
		elseif ( $item->period_type == 'half-year' )
		{								
			echo  date('n', strtotime($item->from_period) )<6?sprintf(__('I полугодие %s года', 'usam'),$y):sprintf(__('II полугодие %s года', 'usam'),$y);			
		}
		elseif ( $item->period_type == 'year' )
		{
			printf(__('%s год', 'usam'),$y);
		}
	}
	
	function column_target( $item ) 
	{				
		echo $item->target=='quantity'?__('Количество сделок', 'usam'):__('Сумма продаж', 'usam');
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
	
	public function get_sortable_columns() 
	{
		if ( ! $this->sortable )
			return array();
		
		return array(
			'date'        => 'id',
			'period_type' => 'period_type',
			'period'      => 'period',		
			'target'      => 'target',		
			'date'        => 'date_insert',
		);
	}
	
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',
			'period_type' => __('Тип периода', 'usam'),					
			'period'      => __('Период', 'usam'),	
			'target'      => __('Цель', 'usam'),
			'plan_type'   => __('Тип плана', 'usam'),			
			'date'        => __('Дата', 'usam'),						
        );
        return $columns;	
    }	
	
	function prepare_items() 
	{						
		$this->get_query_vars();		
		if ( empty($this->query_vars['include']) )
		{
			$selected = $this->get_filter_value( 'period_type' );
			if ( $selected )
				$this->query_vars['period_type'] = array_map('sanitize_title', (array)$selected);
		}		
		$user_ids = usam_get_subordinates( );	
		if ( !$user_ids )
		{
			$contact_id = usam_get_contact_id();
			$department_id = usam_get_contact_metadata($contact_id, 'department');
			if ( $department_id )
			{
				$department = usam_get_department( $department_id );
				if ( $department['chief'] )
					$user_ids[] = $department['chief'];		
			}				
		}
		$user_ids[] = get_current_user_id();		
		$this->query_vars['manager_id'] = $user_ids;			
		
		$query = new USAM_Sales_Plans_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}