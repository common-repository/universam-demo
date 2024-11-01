<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_payment_gateway extends USAM_List_Table
{	
    private $type_location;
	protected $orderby = 'sort';
	protected $order = 'ASC';
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	function column_name( $item )
	{	
		$name = $this->item_edit( $item->id, $item->name, 'payment_gateway' );
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, 'payment_gateway', ['copy' => __('Копировать', 'usam')] ) );		
	}					
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'     => array('name', false),			
			'type'     => array('type', false),			
			'sort'     => array('sort', false),			
			'active'   => array('active', false),	
			);
		return $sortable;
	}
		
	function get_columns()
	{
        USAM_Admin_Assets::sort_fields( 'payment_gateway' );
		$columns = array(           
			'cb'          => '<input type="checkbox" />',					
			'name'        => __('Название', 'usam'),
			'description' => __('Описание', 'usam'),
			'active'      => __('Активность', 'usam'),		
			'id'          => __('ID', 'usam'),	
			'drag'        => '&nbsp;',			
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$args = array( 
			'fields' => 'all',	
			'active' => 'all',
			'search' => $this->search, 
			'order' => $this->order, 
			'orderby' => $this->orderby, 
			'number' => $this->per_page,	
		);			
		if ( !empty( $this->records ) )
			$args['include'] = $this->records;	
		else
			$args['paged'] = $this->get_pagenum();	
	
		$payment_gateways = new USAM_Payment_Gateways_Query( $args );	
		$this->items = $payment_gateways->get_results();		
			
		$total_items = $payment_gateways->get_total();
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}
?>