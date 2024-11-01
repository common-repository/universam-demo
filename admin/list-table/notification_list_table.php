<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_notification extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_name( $item )
	{		
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'notification' ) );
	}		
	
	function column_order( $item )
	{		
		if ( !empty($item['events']['order']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['order']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";
		if ( !empty($item['events']['order']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";
	}
	
	function column_feedback( $item )
	{		
		if ( !empty($item['events']['feedback']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['feedback']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";
		if ( !empty($item['events']['feedback']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";
	}
		
	function column_low_stock( $item )
	{		
		if ( !empty($item['events']['low_stock']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['low_stock']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";
		if ( !empty($item['events']['low_stock']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";
	}
	
	function column_no_stock( $item )
	{		
		if ( !empty($item['events']['no_stock']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['no_stock']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";
		if ( !empty($item['events']['no_stock']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";
	}
	
	function column_email( $item )
	{		
		if ( !empty($item['events']['email']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['email']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";
		if ( !empty($item['events']['email']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";
	}
	
	function column_chat( $item )
	{		
		if ( !empty($item['events']['chat']['email']) )
			echo "<p>".__('На почту', 'usam')."</p>";
		if ( !empty($item['events']['chat']['sms']) )
			echo "<p>".__('В СМС', 'usam')."</p>";		
		if ( !empty($item['events']['chat']['messenger']) )
			echo "<p>".__('В мессенжер', 'usam')."</p>";		
	}
		
	function get_sortable_columns()
	{
		$sortable = array(
			'name'     => array('name', false),			
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',			
			'name'       => __('Название', 'usam'),
			'active'     => __('Активность', 'usam'),
			'order'     => __('Заказ', 'usam'),		
			'email'     => __('Письма', 'usam'),	
			'chat'     => __('Чат', 'usam'),	
			'feedback'     => __('Обращения', 'usam'),		
			'low_stock'     => __('Низкий запас', 'usam'),	
			'no_stock'     => __('Нет в наличии', 'usam'),	
        );		
        return $columns;
    }
	
	
	function prepare_items() 
	{		
		$option = get_site_option('usam_notifications');
		$notifications = maybe_unserialize( $option );	

		if ( empty($notifications) )
			$this->items = array();	
		else
			foreach( $notifications as $key => $item )
			{	
				if ( empty($this->records) || in_array($item['id'], $this->records) )
				{					
					$this->items[] = $item;
				}
			}			
		$this->total_items = count($this->items);	
		$this->forming_tables();	
	}
}
?>