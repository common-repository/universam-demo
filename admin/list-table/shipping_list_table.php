<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_shipping extends USAM_List_Table
{	
    private $type_location;
	protected $orderby = 'sort';
	protected $order = 'ASC';	
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'copy'       => __('Копировать', 'usam'),
			'delete_storage' => __('Удалить склады самовывоза', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	function column_name( $item )
	{	
		$this->row_actions_table( $this->item_edit($item->id, $item->name, 'shipping'), $this->standart_row_actions($item->id, 'shipping', ['copy' => __('Копировать', 'usam')]) );		
	}	
	
	function column_price( $item )
	{	
		if ( $item->handler )
			echo '-';
		else
			echo usam_get_delivery_service_metadata( $item->id, 'price' );
	}
	
	function column_location( $item )
	{			
		$i = 0;
		$metas = usam_get_delivery_service_metadata($item->id, 'locations', false);	
		if ( !empty($metas) )
		{
			$location_ids = [];
			foreach( $metas as $meta )
			{
				$location_ids[] = $meta->meta_value;
			}
			$locations = usam_get_locations( ['fields' => 'name', 'include' => $location_ids] );
			echo implode(', ',$locations);
		}
	}	
	
	function column_delivery_option( $item )
	{	
		if ( $item->delivery_option )
			_e("Самовывоз","usam");		
		else
			_e("До двери","usam");
	}		
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'       => array('type', false),			
			'sort'       => array('sort', false),
			'price'       => array('price', false),
			'delivery_option' => array('delivery_option', false),
		);
		return $sortable;
	}
		
	function get_columns()
	{
       USAM_Admin_Assets::sort_fields( 'delivery_service' );
	   $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'name'             => __('Название', 'usam'),
			'description'      => __('Описание', 'usam'),
			'active'           => __('Активность', 'usam'),		
			'delivery_option'  => __('Вариант доставки', 'usam'),			
			'location'         => __('Местоположения', 'usam'),	
			'price'            => __('Стоимость', 'usam'),	
			'id'               => __('ID', 'usam'),		
			'drag'             => '&nbsp;',			
        );	
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort','price' );
    }
	
	function prepare_items() 
	{	
		$this->get_query_vars();
		$this->query_vars['cache_meta'] = true;		
		if ( empty($this->query_vars['include']) )
		{
		/*	$selected = $this->get_filter_value( 'read' );
			if ( $selected ) 
				$this->query_vars['read'] = $selected == 'read' ? 1 : 0; */
		}		
		$query = new USAM_Delivery_Service_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}	
	}
}
?>