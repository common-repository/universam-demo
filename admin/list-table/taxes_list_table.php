<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_taxes extends USAM_List_Table
{	
	function __construct( $args = array() )
	{	
       parent::__construct( array(         
            'plural'    => 'taxes',  
            'ajax'      => true,
			'screen'    => 'manage_prices_taxes',
		) );			
    }	
	
	function get_bulk_actions_display() 
	{
		$actions = [
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		];
		return $actions;
	}
	
	function column_name( $item )
	{	
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'tax' ) );		
	}	
	
	function column_rate( $item )
	{	
		echo $item->value;		
	}	
	
	function column_is_in_price( $item )
	{	
		$this->logical_column( $item->is_in_price );		
	}		
	
	function column_location( $item )
	{	
		$setting = maybe_unserialize( $item->setting );			
		$i = 0;
		foreach($setting['locations'] as $id )
		{			
			$title = usam_get_full_locations_name( $id );
			if ( $i > 0 )
				echo '<hr size="1" width="90%">';
			echo $title." ($id)";			
			$i++;
		}
	}	
			
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'       => array('type', false),			
			'sort'       => array('sort', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'name'             => __('Название', 'usam'),
			'rate'             => __('Ставка', 'usam'),
			'active'           => __('Активность', 'usam'),		
			'sort'             => __('Сортировка', 'usam'),	
			'is_in_price'      => __('Включен в цену', 'usam'),
			'location'         => __('Местоположения', 'usam'),										
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		global $wpdb;		
		
		$this->get_standart_query_parent();		
			
		if ( $this->search != '' )
		{			
			$this->where[] = "name='".$this->search."'";			
		}
		$where = implode( ' AND ', $this->where );	
			
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_TAXES." WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query);	
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		$this->forming_tables();		
	}
}
?>