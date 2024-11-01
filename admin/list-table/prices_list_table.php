<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_prices extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_title( $item ) 
    {
		$title = $item['title'].' '.usam_get_currency_name( $item['currency'] ).'<div><b>'.__('Код', 'usam').'</b>: <span class="js-copy-clipboard">'.$item['code'].'</span></div>';		
		$this->row_actions_table( $title, $this->standart_row_actions( $item['id'], 'price' ) );	
	}
		
	function column_location( $item )
	{	
		$i = 0;
		if ( !empty($item['locations']) )
		{
			foreach( $item['locations'] as $id )
			{				
				$title = usam_get_full_locations_name( $id );
				if ( $i > 0 )
					echo '<hr size="1" width="90%">';	
				echo $title." ($id)";			
				$i++;				
			}
		}
	}	

	function column_base_type( $item ) 
    {		
		if ( !empty($item['base_type']) )
			echo usam_get_name_price_by_code( $item['base_type'] );
	}	
	
	function column_code( $item ) 
    {
		?><span class="js-copy-clipboard"><?php echo $item['code']; ?></span><?php
	}
	
	function column_underprice( $item ) 
    {		
		if ( !empty($item['underprice']) )
			echo $item['underprice']."%";
	}
	
	function column_rounding( $item ) 
    {		
		if( $item['rounding']==0 )
		{							
			$rounding = $item['rounding'];
		}
		elseif( $item['rounding']>0 )
		{ 
			$rounding = '0.';
			for ($i=1;$i<=$item['rounding']-1;$i++)
				$rounding .= '0';
			$rounding .= '1';
		}
		else
		{
			$rounding = '1';
			$count = abs($item['rounding'])-1;
			for ($i=1;$i<=$count;$i++)
				$rounding .= '0';								
		}
		echo $rounding;
	}
	
	function column_type( $item ) 
    {		
		if ( $item['type'] == 'R' )
		{
			?><span class="item_status_valid item_status"><?php _e('Розничная','usam'); ?></span><?php
		}
		elseif( $item['type'] == 'P' )
		{
			?><span class="status_blocked item_status"><?php _e('Закупочная','usam'); ?></span><?php
		}
	}
	
	function column_available( $item ) 
    {		
		$this->logical_column( $item['available'] );
	}
	  	 
	function get_sortable_columns()
	{
		$sortable = array(
			'title' => array('title', false),		
			'code'  => array('code', false),		
			'available'  => array('available', false),
			'type'  => array('type', false),
			'base_type'  => array('base_type', false),
		);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',			
			'title'      => __('Название', 'usam'),
			'underprice' => __('Наценка', 'usam'),				
			'available'  => __('Доступность', 'usam'),	
			'type'       => __('Тип цены', 'usam'),						
			'base_type'  => __('Базовый тип цен', 'usam'),	
			'location'   => __('Местоположение', 'usam'),				
			'role'       => __('Роль покупателей', 'usam'),						
        );		
        return $columns;
    }
	
	
	function prepare_items() 
	{		
		$type_prices = usam_get_prices( );	
		if ( empty($type_prices) )
			$this->items = array();	
		else
			foreach( $type_prices as $key => $item )
			{					
				if ( empty($this->records) )
				{				
					$this->items[] = $item;
				}
				elseif ( !empty($this->records) && in_array($item['id'], $this->records) )
					$this->items[] = $item;
				elseif ($this->search != '' && (stripos($item['external_code'], $this->search)!== false || stripos($item['title'], $this->search)!== false || stripos($item['code'], $this->search)!== false) )
					$this->items[] = $item;
				
			}		
		$this->total_items = count($this->items);	
		$this->forming_tables();
	}
}
?>