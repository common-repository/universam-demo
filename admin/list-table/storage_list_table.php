<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_storage extends USAM_List_Table
{	
	protected $orderby = 'id';
	protected $order   = 'asc'; 
	protected $period  = ''; 
	protected $date_column = '';	

	function no_items() 
	{
		_e( 'Нет складов' );
	}		
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
			'activate_issuing'    => __('Включить выдачу', 'usam'),
			'deactivate_issuing'    => __('Отключить выдачу', 'usam'),
			'activate_shipping'    => __('Включить отгрузку', 'usam'),
			'deactivate_shipping'    => __('Отключить отгрузку', 'usam'),
			'activate'    => __('Активировать', 'usam'),
			'deactivate'   => __('Отключить', 'usam'),
			'bulk_actions' => __('Открыть массовые действия', 'usam')
		);
		return $actions;	
	}
	
	function column_title( $item )
	{	
		$name = "<span class='element_name'>".$this->item_edit($item->id, $item->title, 'storage')."</span> <span>(№$item->id)</span>";			
		$address = "";
		if ( $item->location_id )
		{
			$location = usam_get_location( $item->location_id );	
			if ( $location )
				$address .= $location['name'].' ';	
		}		
		$address .= usam_get_storage_metadata( $item->id, 'address');
		if ( $address )
			$name .= '<p class="address">'.esc_html(usam_get_storage_metadata( $item->id, 'address')).'</p>';
		
		$branch_number = usam_get_storage_metadata($item->id, 'branch_number');
		if ( $branch_number )
			$name .= ' <strong>№'.$branch_number.'</strong>';
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, 'storage' ) );
	}
	
	function column_address( $item )
	{	
		if ( $item->location_id )
		{
			$location = usam_get_location( $item->location_id );	
			if ( $location )
				echo esc_html($location['name']);	
		}
		echo '<p>'.esc_html(usam_get_storage_metadata( $item->id, 'address')).'</p>';
	}
	
	function column_issuing( $item )
	{	
		$this->logical_column( $item->issuing );	
	}
	
	function column_shipping( $item )
	{	
		$this->logical_column( $item->shipping );
	}	
	
	function column_owner( $item )
	{	
		static $gateways = null;		
		if ( $gateways === null )
			$gateways = usam_get_data_integrations( 'shipping', ['name' => 'Name'] );
		
		if ( $item->owner )
			echo isset($gateways[$item->owner])?esc_html($gateways[$item->owner]['name']):'';
		else			
			__('Ваш','usam');
	}
	
	function column_schedule( $item )
	{	
		echo esc_html(usam_get_storage_metadata( $item->id, 'schedule'));
	}
	
	function column_type( $item )
	{	
		$types = ['shop' => esc_html__('Магазин', 'usam'), 'warehouse' => esc_html__('Склад', 'usam'), 'postmart' => esc_html__('Постамат', 'usam')];
		echo isset($types[$item->type])? $types[$item->type] : '';
	}	
	
	protected function column_cb( $item ) 
	{			
		$checked = in_array($item->id, $this->records )?"checked='checked'":""; 
		echo "<input id='checkbox-".$item->id."' type='checkbox' name='cb[]' value='".$item->id."' ".$checked.">";			
    }
		  
	function get_sortable_columns() 
	{
		$sortable = [
			'id'       => array('id', false),
			'code'     => array('code', false),
			'title'    => array('title', false),
			'sort'     => array('sort', false),
			'active'   => array('active', false),	
			'date'     => array('date', false),	
			'issuing'  => array('issuing', false),
			'type' => array('type', false),	
			'shipping' => array('shipping', false),		
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',
			'title'       => __('Название', 'usam'),				
			'type'        => __('Тип', 'usam'),	
			'active'      => __('Активность', 'usam'),							
			'issuing'     => __('Выдача', 'usam'),			
			'shipping'    => __('Отгрузка', 'usam'),
			'owner'       => __('Чей склад', 'usam'),
			'code'        => __('Внешний код', 'usam'),				
        );
        return $columns;
    }
	
	public function get_hidden_columns()
    {
        return array('address', 'schedule');
    }
	
	public function get_number_columns_sql()
    {       
		return array('id', 'sort');
    }
	
	function prepare_items() 
	{					
		$this->get_query_vars();			
		$this->query_vars['cache_meta'] = true;
		if ( empty($this->query_vars['include']) )
		{			
			$selected = $this->get_filter_value( 'issuing' );
			if ( $selected !== null )
				$this->query_vars['issuing'] = array_map('intval', (array)$selected);		
			$selected = $this->get_filter_value( 'active' );
			if ( $selected !== null )
				$this->query_vars['active'] = array_map('intval', (array)$selected);		
			
			$selected = $this->get_filter_value( 'pickup_points' );
			if ( $selected || $selected === '' )
				$this->query_vars['owner'] = array_map('sanitize_title', (array)$selected);			
			$selected = $this->get_filter_value( 'sale_area' );
			if ( $selected )
			{ 
				$selected = array_map('intval', (array)$selected);
				foreach ( $selected as $id )
					$this->query_vars['meta_query'][] = ['key' => 'sale_area_'.$id, 'value' => 1, 'compare' => '='];
			}
			$selected = $this->get_filter_value( 'location' );
			if ( $selected )
				$this->query_vars['location_id'] = array_map('intval', (array)$selected);			
		} 
		$query = new USAM_Storages_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}