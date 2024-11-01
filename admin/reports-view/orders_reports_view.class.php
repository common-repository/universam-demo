<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Orders_Reports_View extends USAM_Reports_View
{				
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Выполнение плана', 'usam'), 'key' => 'implementation_plan', 'view' => 'transparent']],	
			[['title' => __('Воронка продаж','usam'), 'key' => 'sales_funnel', 'view' => 'graph'],['title' => __('Скорость обработки заказов','usam'), 'key' => 'order_status_processing_speed', 'view' => 'graph']],
			[['title' => __('Вклад менеджеров в продажи','usam'), 'key' => 'managerial_contribution_sales', 'view' => 'graph'],['title' => __('Выполнение плана продаж','usam'), 'key' => 'execution_plan_managers', 'view' => 'graph']],
			[['title' => __('Способы доставки','usam'), 'key' => 'delivery_methods', 'view' => 'graph'], ['title' => __('Склады выдачи','usam'), 'key' => 'storage_pickup', 'view' => 'graph']],
			[['title' => __('Способы оплаты','usam'), 'key' => 'payment_methods', 'view' => 'graph']],
			[['title' => __('Вариант оплаты','usam'), 'key' => 'payment_options', 'view' => 'graph'],['title' => __('Самовывоз-доставка','usam'), 'key' => 'pickup_delivery', 'view' => 'graph']],
			[['title' => __('Количество клиентов у менеджеров','usam'), 'key' => 'load_managers', 'view' => 'graph'],['title' => __('Количество задач у менеджеров','usam'), 'key' => 'number_tasks_managers', 'view' => 'graph']],
			[['title' => __('Продажи по городам','usam'), 'key' => 'orders_city', 'view' => 'loadable_table'],['title' => __('Лучшие компании','usam'), 'key' => 'orders_best_company', 'view' => 'loadable_table']],			
			[['title' => __('Результаты посещаемости', 'usam'), 'key' => 'attendance', 'view' => 'transparent']],
			[['title' => __('Визиты', 'usam'), 'key' => 'site_traffic', 'view' => 'graph'],['title' => __('Просмотры', 'usam'), 'key' => 'site_views', 'view' => 'graph']],
			[['title' => __('Оплаченные заказы', 'usam'), 'key' => 'paid_orders', 'view' => 'graph'], ['title' => __('Прибыль', 'usam'), 'key' => 'profit', 'view' => 'graph']],
			[['title' => __('Оформлено заказов', 'usam'), 'key' => 'received', 'view' => 'graph'], ['title' => __('Добавлено в корзину', 'usam'), 'key' => 'baskets', 'view' => 'graph']],	
		);	
		return $reports;
	}	
	
	public function sales_funnel_graph( )
	{
		require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php'  );
		$type = isset($_GET['tab']) && $_GET['tab'] == 'leads'?'lead':'order';
		$statuses = usam_get_object_statuses(['order' => 'DESC', 'type' => $type]); 
		$select_statuses = array();
		$statuses_name = array();
		foreach ( $statuses as $status )
		{
			$select_statuses[] = $status->internalname;
			$statuses_name[$status->internalname] = $status->name;
		}					
		$query_vars = $this->get_query_vars(['fields' => ['value'],'value' => $select_statuses, 'object_type' => 'order', 'operation' => 'edit', 'field' => 'status', 'orderby' => 'object_id']); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['user_id'] = $selected;	
		
		$change_orders = usam_get_change_history_query( $query_vars );
		if ( empty($change_orders) )
			return '';
	
		$sales_funnel = array();	
		foreach ( $change_orders as $change_order )
		{	
			if ( isset($sales_funnel[$change_order->value]) )
				$sales_funnel[$change_order->value]++;
			else
				$sales_funnel[$change_order->value] = 1;
		}					
		asort($sales_funnel);
		
		$count_orders = count($change_orders);
		
		$funnel = array();
		$data_graph = array();
		foreach ( $sales_funnel as $key => $value )
		{				
			$p = round($value*100/$count_orders,1);
			$data_graph[] = array( 'y_data' => $statuses_name[$key], 'x_data' => $p, 'label' => "<div class='title_bar_signature'>".$p."%</div><div class='description_bar_signature'>".__("Количество","usam").": ".$value."</div>" );	
			$funnel[] = array( 'title' => $statuses_name[$key], 'percent' => $p."%" );	
		}				
		//$this->display_funnel( $funnel );
	}	
	
	function display_funnel( $items )
	{
		?>				
		<div class="funnel">
			<div class="funnel__pi">
			<?php
			foreach ( $items as $key => $item )
			{
				?><div class="funnel__level funnel__level_<?php echo $key; ?>"></div><?php 
			}
			?>				
			</div>			
		</div>
		
		<?php
		foreach ( $items as $key => $item )
		{
			?>				
			<div class="funnel__title"><div class="funnel__name"><?php echo $item['title']; ?></div><div class="funnel__percent"><?php echo $item['percent']; ?></div></div>
			<?php 
		}			
	}
	
	public function orders_city_report_box()
	{	
		return array( __('Города','usam'), __('Клиентов','usam') );
	}
	
	public function orders_best_company_report_box()
	{	
		return array( __('Компания','usam'), __('Куплено','usam') );
	}
}
?>