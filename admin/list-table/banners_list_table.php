<?php
require_once( USAM_FILE_PATH . '/includes/theme/banners_query.class.php' );	
require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_banners extends USAM_List_Table
{		
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),	
			'copy'      => __('Копировать', 'usam'),
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_name( $item )
	{	
		if ( $item->object_url )
			echo "<div class='row_image'>".$this->item_edit( $item->id, "<div class='image_container'><img src='".$item->object_url."' loading='lazy'></div>", 'banner' ).'</div>';
		$this->row_actions_table( $this->item_edit($item->id, $item->name, 'banner'), $this->standart_row_actions( $item->id, 'banner', ['copy' => __('Копировать', 'usam')] ) );
	}
	
	function column_status( $item )
	{	
		echo "<span class='".($item->status=='active'?'item_status_valid':'status_blocked')." item_status'>".usam_get_status_name_banner( $item->status )."</span>";
	}
		
	function column_banner_location( $item )
	{	
		$locations = usam_get_banner_location($item->id);
		$checklist = usam_register_banners();
		foreach ( $locations as $location ) 
			echo isset($checklist[$location])?$checklist[$location].'<br>':'';		
	}
		
	function get_sortable_columns() 
	{
		$sortable = array(
			'id'       => array('id', false),
			'name'     => array('title', false),
			'size'     => array('size', false),
			'date'     => array('date', false),	
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox">',
			'name'        => __('Название', 'usam'),	
			'status'      => __('Публикация', 'usam'),		
			'banner_location'  => __('Расположение в шаблоне', 'usam'),			
			'date'        => __('Дата', 'usam'),				
        );
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{			
			$selected = $this->get_filter_value( 'banner_location' );
			if ( $selected )
				$this->query_vars['banner_location'] = array_map('sanitize_title', $selected);			
		} 
		$query = new USAM_Banners_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}