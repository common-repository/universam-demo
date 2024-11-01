<?php
class USAM_Tab_warehouse_documents extends USAM_Page_Tab
{	
	protected function get_tab_forms()
	{ 
		if ( $this->table == 'receipts' ) 
			return [['action' => 'new', 'title' => __('Новое поступление', 'usam'), 'capability' => 'add_receipt' ]];	
		elseif ( $this->table == 'movements' )		
			return array( array('action' => 'new', 'title' => __('Новое перемещение', 'usam'), 'capability' => 'add_movement' ) );	
		elseif ( $this->table == 'partner_orders' )		
			return array( array('action' => 'new', 'title' => __('Новый заказ от партнера', 'usam'), 'capability' => 'add_partner_order') );		
		elseif ( $this->table == 'shipping_documents' )		
			return array( array('form' => 'edit', 'form_name' => 'shipped', 'title' => __('Добавить отгрузку', 'usam'), 'capability' => 'add_shipped' ) );	
		return array();		
	}
	
	public function get_title_tab() 
	{ 
		if ( $this->table == 'receipts' )		
			return __('Поступления', 'usam');	
		elseif ( $this->table == 'movements' )		
			return __('Перемещения', 'usam');	
		elseif ( $this->table == 'partner_orders' )		
			return __('Заказ от партнера', 'usam');	
		elseif ( $this->table == 'shipping_documents' )		
			return __('Документы отгрузки', 'usam');
	}	
	
	public function get_tab_sections() 
	{ 
		$tables = [
			'shipping_documents' => ['title' => __('Отгрузки','usam'), 'type' => 'table', 'capability' => 'view_shipped_lists'],
			'movements' => ['title' => __('Перемещения','usam'), 'type' => 'table', 'capability' => 'view_movement_lists'], 
			'receipts' => ['title' => __('Поступления','usam'), 'type' => 'table', 'capability' => 'view_receipt_lists'], 			
			'partner_orders' => ['title' => __('Заказ от партнера','usam'), 'type' => 'table', 'capability' => 'view_partner_order_lists']
		];	
		return $tables;
	}	
}