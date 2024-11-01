<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_bonus_card_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Статистика по использованию бонусов', 'usam'), 'key' => 'bonus_card_total', 'view' => 'transparent']],		
			[['title' => __('Оплаченные заказы', 'usam'), 'key' => 'orders_paid_bonus_cards', 'view' => 'loadable_table'], ['title' => __('Заказы, по которым начислены бонусы', 'usam'), 'key' => 'orders_bonus_cards', 'view' => 'loadable_table']],
		];	
		return $reports;		
	}	
	
	public function orders_paid_bonus_cards_report_box()
	{	
		return [ __('Товар','usam'), __('Дата','usam')];
	}
	
	public function orders_bonus_cards_report_box()
	{	
		return [ __('Товар','usam'), __('Дата','usam')];
	}
}
?>