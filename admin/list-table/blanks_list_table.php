<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_blanks extends USAM_List_Table
{	   
	protected $orderby = 'object_name';
	protected $order = 'ASC';
	function column_title( $item )
	{				
		$actions = [
			'edit' => '<a class="usam-edit-link" href="'.$this->get_nonce_url( add_query_arg( array('form' => 'edit', 'form_name' => 'blank', 'id' => $item['id']), $this->url ) ).'">'.__('Изменить', 'usam').'</a>',
		];	
		$this->row_actions_table( $this->item_edit($item['id'],$item['title'], 'blank'), $actions );
	}	
	
	function column_object_name( $item )
	{
		if ( $item['object_name'] == 'product' )
			_e('Продукт', 'usam');
		else	
			echo __('Документ', 'usam').': '.usam_get_document_name( $item['object_name'] );
	}	
	
				   
	function get_sortable_columns()
	{
		$sortable = [
			'title' => array('title', false),
			'object_name' => array('object_name', false),				
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           		
			'title'        => __('Название', 'usam'),		
			'object_name'  => __('Объект', 'usam'),				
			'description'  => __('Описание', 'usam'),
        );		
        return $columns;
    }	
		
	function prepare_items() 
	{		
		$items = usam_get_printed_forms_document();	
		if ( !empty($items) )
			foreach( $items as $item )
			{			
				if ( empty($this->records) || in_array($item['id'], $this->records) )
				{
					if ( $this->search == '' || stripos($item['title'], $this->search) !== false )
						$this->items[] = $item;	
				}	
			}			
		$this->total_items = count($this->items);
		$this->forming_tables();	
	}
}
?>