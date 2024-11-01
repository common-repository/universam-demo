<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_units extends USAM_List_Table
{			
	function get_sortable_columns()
	{
		$sortable = array(
			'title'  => 'title',		
			'short'  => 'short',
			);
		return $sortable;
	}
	
	function column_title( $item ) 
    { 
		$this->row_actions_table( $item['title'], $this->standart_row_actions( $item['id'], 'unit' ) );	
	}	
		
	function get_columns()
	{
        $columns = array(  				
			'cb'             => '<input type="checkbox" />',
			'title'          => __('Название', 'usam'),	
			'short'          => __('Сокращение', 'usam'),	
			'code'           => __('Код', 'usam'),
			'numerical'      => __('ОКЕИ', 'usam'),		
			'international_code'  => __('Международный код', 'usam'),	
			'external_code'  => __('Внешний код', 'usam'),			
        );
        return $columns;
    }
	
	function prepare_items() 
	{	
		$items = usam_get_list_units();		
		if ( !empty($items) )
		{
			foreach( $items as $id => $item)	
			{
				if ($this->search )
				{
					foreach ( $item as $value ) 
					{
						if ( stripos($value, $this->search) !== false )
						{
							$this->items[] = $item;
							break;
						}
					}
				}
				elseif ( empty($this->record) || in_array($item['id'], $this->records) )
				{						
					$this->items[] = $item;
				}
			}
		}
		$this->total_items = count($this->items);		
		$this->forming_tables();
	}
}
?>