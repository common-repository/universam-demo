<?php
/* =====================  Загрузка данных в общие результаты ================================================ */
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
class USAM_General_Results_Report
{		
	private $start_date_interval = '';
	private $end_date_interval = '';	
	
	public function load( $type )
	{ 			
		$f = new Filter_Processing();
		$date = $f->get_date_interval();
		
		$this->start_date_interval = $date['from'] == ''?'':date('Y-m-d H:i:s', $date['from']);					
		$this->end_date_interval = $date['to'] == ''?'':date("Y-m-d H:i:s", $date['to']);		
		if ( method_exists($this, $type) )
			$results = $this->$type( );	
		else
			$results = apply_filters( 'usam_general_results_report_'.$type, false, $this->get_query_vars() );
		return $results;
	}
	
	private function get_filter_value( $key, $default = null ) 
	{ 	
		if ( isset($_POST[$key]) )
		{
			if ( is_array($_POST[$key]) )
				$select = stripslashes_deep($_POST[$key]);
			elseif ( stripos($_POST[$key], ',') !== false )
				$select = explode(',',stripslashes($_POST[$key]));
			else
				$select = stripslashes($_POST[$key]);
		}
		else
			$select = $default;		
		return $select;
	}
	
	private function get_query_vars( $query_vars = [] ) 
	{		
		$column_date = isset($query_vars['column_date']) ? $query_vars['column_date'] : 'date_insert';
		if ( $this->start_date_interval )
			$query_vars['date_query'][] = ['after' => get_gmt_from_date($this->start_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];	
		if ( $this->end_date_interval )
			$query_vars['date_query'][] = ['before' => get_gmt_from_date($this->end_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];			
		$selected = $this->get_filter_value( 'weekday' );
		if ( !empty($selected) )	
		{
			$weekday = array_map( 'intval', $selected );
			$query_vars['date_query'][] = array( 'dayofweek' => $weekday, 'compare' => 'IN', 'column' => $column_date );		
		}		
		return $query_vars;
	}	
	
	private function total_payment_orders( )
	{
		$query_vars = $this->get_query_vars( ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);		
		$query_vars['fields'] = ['type', 'totalprice'];
		$query_vars['type'] = ['payment_order', 'payment_received'];
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
		$documents = usam_get_documents( $query_vars );	
		
		$payment_order = 0;
		$payment_received = 0;		
		$count_payment_order = 0;
		$count_payment_received = 0;	
		foreach ( $documents as $document )
		{
			if ( $document->type == 'payment_order' )
			{
				$payment_order += $document->totalprice;
				$count_payment_order++;
			}
			else
			{
				$payment_received += $document->totalprice;
				$count_payment_received++;
			}
		}			
		return [['title' => esc_html__('Оборот', 'usam'), 'value' => usam_get_formatted_price($payment_received)],['title' => esc_html__('Прибыль', 'usam'), 'value' => usam_get_formatted_price($payment_received-$payment_order)], ['title' => esc_html__('Расходы', 'usam'), 'value' => usam_get_formatted_price($payment_order)]];
	}
	
	private function implementation_plan()
	{				
		$contact_id = usam_get_contact_id();
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan_query.class.php' );	
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan.class.php' );		
		require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
		$departments = usam_get_departments( array( 'chief' => $contact_id ) );			
		
		$subordinates = usam_get_subordinates( );	
		$subordinates[] = get_current_user_id();	
		$subordinates = array_unique($subordinates);
							
		$sales_plans = usam_get_sales_plans(['active' => 1]);
		$statistics = [];
		if ( !empty($sales_plans) )
		{
			foreach ( $sales_plans as $sales_plan )
			{			
				$amounts = usam_get_sales_plan_amounts( $sales_plan->id );
				$sales_plan_sum = 0;
				if ( $sales_plan->plan_type == 'people' )
				{
					foreach ( $amounts as $id => $amount )
					{
						if ( in_array($id, $subordinates) )
							$sales_plan_sum += $amount;
					}
					$title = __("Выполнение плана по людям", "usam");
				}				
				elseif ( $sales_plan->plan_type == 'department' )
				{					
					$department_id = usam_get_contact_metadata( $contact_id, 'department' );
					$sales_plan_sum = isset($amounts[$department_id]) ? $amounts[$department_id] : 0;
					$title = __("Выполнение плана по отделам", "usam");
				}				
				elseif ( $sales_plan->plan_type == 'company' )
				{
					$contact = usam_get_contact( $contact_id );
					$sales_plan_sum = isset($amounts[$contact['company_id']]) ? $amounts[$contact['company_id']] : 0;					
					$title = __("Выполнение плана по компании", "usam");
				}		
				else
					continue;
				$query_vars = array('status' => 'closed', 'manager_id' => $subordinates, 'date_query' => array( array('after' => $sales_plan->from_period, 'before' => $sales_plan->to_period, 'inclusive' => true) ));
				$value = 0;
				$percent = 0;
				if ( $sales_plan_sum )
				{
					$orders = usam_get_orders( $query_vars );
					foreach ( $orders as $order )
					{			
						if ( $sales_plan->target == 'quantity' )
							$value++;
						else
							$value += $order->totalprice;
					}
					$percent = round($value/$sales_plan_sum*100,1);
				}				
				$result = $sales_plan_sum - $value;			
				$statistics = [['title' => esc_html__('Выполнено', 'usam'), 'value' => usam_get_formatted_price($value)], ['title' => esc_html__('Осталось', 'usam'), 'value' => usam_get_formatted_price($result)], ['title' => $title, 'value' => $percent.'%']];
			}
		}
		else
		{
			$statistics = array( array( 'title' => esc_html__('Выполнено', 'usam'), 'value' => 0), array( 'title' => esc_html__('Осталось', 'usam'), 'value' => 0), array( 'title' => esc_html__('Выполнение плана', 'usam'), 'value' => '0%' ) );
		}
		return $statistics;
	}
	
	private function attendance()
	{
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/metrika.class.php' );
		$metrika = new USAM_Yandex_Metrika();
		$result_yandex = ['visits' => 0, 'page_views' => 0, 'new_visitors' => 0, 'users' => 0];	
		if ( $metrika->auth() )
		{
			$results = $metrika->get_statistics(['date1' => date('Y-m-d', strtotime($this->start_date_interval)), 'date2' => date('Y-m-d'), 'limit' => 10000, 'group' => 'day']);
			foreach ( $results as $result )
			{				
				$result_yandex['visits'] += $result['visits'];
				$result_yandex['page_views'] += $result['page_views'];
				$result_yandex['users'] += $result['users'];
				$result_yandex['new_visitors'] += $result['users']*$result['new_visitors']/100;
			}			
		}	
		$p = $result_yandex['users']?round($result_yandex['new_visitors']*100/$result_yandex['users'],1):0;
		return [['title' => esc_html__('Визитов', 'usam'), 'value' => $result_yandex['visits']], ['title' => esc_html__('Просмотров', 'usam'), 'value' => $result_yandex['page_views']], ['title' => esc_html__('Доля новых посетителей', 'usam'), 'value' => "{$p}%"]];
	}	
	
	private function telephony_total( )
	{					
		require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );
		$balance = apply_filters( 'usam_telephony_balance', 0 );
		
		$query_vars = $this->get_query_vars( ); 
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$query_vars['manager_id'] = array_map('intval', (array)$selected);
		$query_vars['fields'] = 'sum_time';		
		$calls = usam_get_calls( $query_vars );	
		$query_vars['fields'] = 'sum_size';	
		$query_vars['type'] = 'telephony';
		$files = usam_get_files( $query_vars );	

		$sum_time = !empty($calls[0])?$calls[0]:0;
		$filesize = !empty($files[0])?$files[0]:0;		
		
		return [['title' => esc_html__('Баланс', 'usam'), 'value' => $balance], ['title' => esc_html__('Время разговоров', 'usam'), 'value' => usam_seconds_times($sum_time)], ['title' => esc_html__('Размер записей', 'usam'), 'value' => size_format( $filesize ) ]];
	}	
	
	private function monitor_total( ) 
	{		
		$query_vars = $this->get_query_vars(['fields' => 'status', 'online' => true]); 
		$contacts = usam_get_contacts( $query_vars );
		$visits = usam_get_visits(['fields' => 'views', 'online' => true]);			
		return [['title' => esc_html__('Контактов онлайн', 'usam'), 'value' => count($contacts)], ['title' => esc_html__('Визитов', 'usam'), 'value' => count($visits)], ['title' => esc_html__('Просмотров', 'usam'), 'value' => array_sum($visits)]];
	}
	
	private function financial_company_total( ) 
	{
		return [['title' => esc_html__('Чистая прибыль', 'usam'), 'value' => 0], ['title' => esc_html__('Выручка', 'usam'), 'value' => 0], ['title' => esc_html__('Численность персонала', 'usam'), 'value' => 0]];
	}	
	
	private function total_files( )
	{	
		$query_vars['fields'] = array( 'sum_size', 'count' );	
		$files = usam_get_files( $query_vars );	

		$filesize = !empty($files[0])?$files[0]->sum_size:0;
		$count = !empty($files[0])?$files[0]->count:0;		

		return [['title' => esc_html__('Количество файлов', 'usam'), 'value' => $count], ['title' => esc_html__('Размер', 'usam'), 'value' => size_format( $filesize )]];
	}
	
	private function employee_total( ) 
	{	
		$id = $this->get_filter_value( 'id' );
		$contact = usam_get_contact( $id );	
		$events1 = usam_get_events(['number' => 1, 'fields' => 'count', 'user_work' => $contact['user_id'], 'status__not_in' => array('controlled', 'completed'), 'type' => ['task', 'meeting', 'call', 'event']]);	
		$events2 = usam_get_events(['number' => 1, 'date_query' => ['before' => date('Y-m-d H:i:s', strtotime('-30 days')), 'inclusive' => true], 'fields' => 'count', 'user_work' => $contact['user_id'], 'status' => ['controlled', 'completed'], 'type' => ['task', 'meeting', 'call', 'event']]);	
		$events3 = usam_get_events(['number' => 1, 'date_query' => ['before' => date('Y-m-d H:i:s'), 'inclusive' => true, 'column' => 'end'], 'fields' => 'count', 'user_work' => $contact['user_id'], 'status__not_in' => ['controlled', 'completed'], 'type' => ['task', 'meeting', 'call', 'event']]);
		return [
			['title' => esc_html__('Количество дел', 'usam'), 'value' => !empty($events1)?$events1:0], 
			['title' => esc_html__('Количество завершенных дел за 30 дней', 'usam'), 'value' => !empty($events2)?$events2:0], 
			['title' => esc_html__('Количество просроченных дел', 'usam'), 'value' => !empty($events3)?$events3:0], 			
		];
	}	
	
	private function employees_total( ) 
	{		
		$new = usam_get_events(['number' => 1, 'fields' => 'count', 'status' => ['started', 'not_started'], 'type' => ['task', 'meeting', 'call', 'event']]);
		$total = usam_get_events(['number' => 1, 'fields' => 'count', 'date_query' => ['after' => '1 months ago', 'inclusive' => true, 'column' => 'date_completion'], 'status' => ['completed' ,'stopped','canceled','controlled'], 'type' => ['task', 'meeting', 'call', 'event']]);
		$expired = usam_get_events(['number' => 1, 'fields' => 'count', 'date_query' => ['before' => date('Y-m-d H:i:s'), 'inclusive' => true, 'column' => 'end'], 'status__not_in' => ['completed' ,'stopped','canceled','controlled'], 'type' => ['task', 'meeting', 'call', 'event']]);		
		return [['title' => esc_html__('Всего в работе', 'usam'), 'value' => $new], ['title' => esc_html__('Из них просроченных', 'usam'), 'value' => $expired], ['title' => esc_html__('Всего завершенных за месяц', 'usam'), 'value' => $total]];
	}
	
	private function delivery_documents_total()
	{		
		return [['title' => esc_html__('Выполнено','usam'), 'value' => 0], ['title' => esc_html__('Осталось','usam'), 'value' => 0], ['title' => esc_html__('Выполнение плана','usam'), 'value' => '0%']];
	}
	
	private function customer_account_total( ) 
	{		
		global $wpdb;			
		$id = $this->get_filter_value( 'id' );
		$sales = $wpdb->get_row( "SELECT SUM(sum) AS sum, COUNT(*) AS count FROM ".USAM_TABLE_ACCOUNT_TRANSACTIONS." WHERE account_id ='$id' AND type_transaction=0" );
		$transaction = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_ACCOUNT_TRANSACTIONS." WHERE account_id ='$id' AND type_transaction=0 ORDER BY id DESC", ARRAY_A );
				
		return array( 
			array('title' => esc_html__('Количество зачеслений', 'usam'), 'value' => !empty($sales->count)?$sales->count:0), 
			array( 'title' => esc_html__('Сумма зачеслений', 'usam'), 'value' => !empty($sales->sum)?$sales->sum:0), 
			array( 'title' => esc_html__('Последнее зачесление', 'usam'), 'value' => !empty($transaction['date_insert'])?human_time_diff(strtotime($transaction['date_insert']), time() ):"" ) 
		);
	}
	
	private function affairs_total( ) 
	{	
		$query_vars = $this->get_query_vars( array( 'fields' => 'status' ) ); 
		$events = usam_get_events( $query_vars );
		$started = 0;
		$not_started = 0;		
		foreach ( $events as $status )
		{			
			if ( $status == 'started' )
				$started++;
			if ( $status == 'not_started' )
				$not_started++;				
		}		
		$total = usam_get_events( array( 'number' => 1, 'fields' => 'count') );			
		return [['title' => esc_html__('Новых дел', 'usam'), 'value' => count($events)], ['title' => esc_html__('Выполняется', 'usam'), 'value' => $started], ['title' => esc_html__('Запланировано', 'usam'), 'value' => $not_started]];
	}	
		
	private function companies_total( ) 
	{		
		$new = usam_get_companies( array( 'number' => 1, 'fields' => 'count', 'year' => date('Y'), 'monthnum' => date('n') ) );
		$total = usam_get_companies( array( 'number' => 1, 'fields' => 'count' ) );
		$satisfied = usam_get_companies( array( 'number' => 1, 'fields' => 'count', 'status' => array('prospect','favourite') ) );		
		return array( array( 'title' => esc_html__('Новых компаний', 'usam'), 'value' => $new), array( 'title' => esc_html__('Всего удовлетворенных', 'usam'), 'value' => $satisfied), array( 'title' => esc_html__('Всего компаний', 'usam'), 'value' => $total ) );
	}	
	
	private function contacts_total( ) 
	{		
		$query_vars = $this->get_query_vars(['number' => 1, 'fields' => 'count', 'user_id__not_in' => 0]); 
		$user_contacts = usam_get_contacts( $query_vars );
		$query_vars = $this->get_query_vars(['number' => 1, 'fields' => 'count']); 
		$new_contacts = usam_get_contacts( $query_vars );
		$total = usam_get_contacts(['number' => 1, 'fields' => 'count']);			
		return [['title' => esc_html__('Новых контактов', 'usam'), 'value' => $new_contacts], ['title' => esc_html__('С личным кабинетом', 'usam'), 'value' => $user_contacts], ['title' => esc_html__('Всего клиентов', 'usam'), 'value' => $total]];
	}	
	
	private function company_total( ) 
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );		
		$sale = $wpdb->get_row( "SELECT AVG(totalprice) AS avg, SUM(totalprice) AS sum, COUNT(*) AS count, type_price FROM ".USAM_TABLE_ORDERS." WHERE company_id =".$id." AND status='closed'" );		
		$args = ['type_price' => $sale->type_price,'currency_symbol' => false];
		return array( 
			['title' => esc_html__('Средний чек', 'usam'), 'value' => !empty($sale->avg)?usam_get_formatted_price($sale->avg, $args):0], 
			['title' => esc_html__('Количество заказов', 'usam'), 'value' => !empty($sale->count)?$sale->count:0], 
			['title' => esc_html__('Всего куплено', 'usam'), 'value' => !empty($sale->sum)?usam_get_formatted_price($sale->sum, $args):0],
		);
	}	
	
	private function company_last_order( ) 
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );
		$type_price = usam_get_manager_type_price();
		$sale = $wpdb->get_row( "SELECT date_insert, totalprice, id, type_price, number_products FROM ".USAM_TABLE_ORDERS." WHERE company_id =".$id." AND status='closed' ORDER BY id DESC" );	
		return array( 
			['title' => esc_html__('Количество дней', 'usam'), 'value' => !empty($sale->date_insert)?human_time_diff( strtotime($sale->date_insert), time() ):""],				
			['title' => esc_html__('Сумма покупки', 'usam'), 'value' => !empty($sale->totalprice)?usam_get_formatted_price($sale->totalprice, ['type_price' => $sale->type_price,'currency_symbol' => false]):0], 			
			['title' => esc_html__('Количество товаров', 'usam'), 'value' => !empty($sale->number_products)?$sale->number_products:0], 
		);
	}
	
	private function company_results_newsletter()
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );
		$properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
		$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_COMPANY_META." WHERE company_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			
		$statistics = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_NEWSLETTER_USER_STAT." WHERE communication IN ('".implode("','",$communications)."')" );
		$clicked = 0;
		$open = 0;
		foreach ( $statistics as $statistic )
		{
			$clicked += $statistic->clicked;
			if ( !empty($statistic->opened_at) )
				$open++;
		}	
		return array( 
			array('title' => esc_html__('Отправлено', 'usam'), 'value' => count($statistics) ), 
			array( 'title' => esc_html__('Открыто', 'usam'), 'value' => $open ), 
			array( 'title' => esc_html__('Нажатий', 'usam'), 'value' => $clicked)
		);
	}	
	
	private function contact_results_newsletter()
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );
		$properties = usam_get_properties( array( 'type' => 'contact', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
		$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_CONTACT_META." WHERE contact_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			
		$statistics = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_NEWSLETTER_USER_STAT." WHERE communication IN ('".implode("','",$communications)."')" );
		$clicked = 0;
		$open = 0;
		foreach ( $statistics as $statistic )
		{
			$clicked += $statistic->clicked;
			if ( !empty($statistic->opened_at) )
				$open++;
		}	
		return array( 
			array('title' => esc_html__('Отправлено', 'usam'), 'value' => count($statistics) ), 
			array( 'title' => esc_html__('Открыто', 'usam'), 'value' => $open ), 
			array( 'title' => esc_html__('Нажатий', 'usam'), 'value' => $clicked)
		);
	}
	
	private function contact_total( ) 
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );
		$sale = $wpdb->get_row( "SELECT AVG(totalprice) AS avg, SUM(totalprice) AS sum, COUNT(*) AS count, type_price FROM ".USAM_TABLE_ORDERS." WHERE contact_id =".$id." AND status='closed'" );		
		$args = ['type_price' => $sale->type_price,'currency_symbol' => false];
		return array( 
			['title' => esc_html__('Средний чек', 'usam'), 'value' => !empty($sale->avg)?usam_get_formatted_price($sale->avg, $args):0], 
			['title' => esc_html__('Количество заказов', 'usam'), 'value' => !empty($sale->count)?$sale->count:0], 
			['title' => esc_html__('Всего куплено', 'usam'), 'value' => !empty($sale->sum)?usam_get_formatted_price($sale->sum, $args):0],
		);
	}	
	
	private function contact_last_order( ) 
	{	
		global $wpdb;
		$id = $this->get_filter_value( 'id' );
		$sale = $wpdb->get_row( "SELECT date_insert, totalprice, id, type_price, number_products FROM ".USAM_TABLE_ORDERS." WHERE contact_id =".$id." AND status='closed' ORDER BY id DESC" );
		return array( 
			['title' => esc_html__('Количество дней', 'usam'), 'value' => !empty($sale->date_insert)?human_time_diff( strtotime($sale->date_insert), time() ):""],				
			['title' => esc_html__('Сумма покупки', 'usam'), 'value' => !empty($sale->totalprice)?usam_get_formatted_price($sale->totalprice, ['type_price' => $sale->type_price,'currency_symbol' =>false]):0], 			
			['title' => esc_html__('Количество товаров', 'usam'), 'value' => !empty($sale->number_products)?$sale->number_products:0], 
		);
	}
	
	private function bonus_card_total( ) 
	{		
		global $wpdb;			
		$id = $this->get_filter_value( 'id' );
		$sales = $wpdb->get_row( "SELECT SUM(sum) AS sum, COUNT(*) AS count FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE code ='".$id."' AND type_transaction=1" );
		$order = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE code ='".$id."' AND type_transaction=1 ORDER BY id DESC", ARRAY_A );					
		return [
			['title' => esc_html__('Количество заказов', 'usam'), 'value' => !empty($sales->count)?$sales->count:0], 
			[ 'title' => esc_html__('Всего куплено', 'usam'), 'value' => !empty($sales->sum)?$sales->sum:0], 
			['title' => esc_html__('Последний заказ', 'usam'), 'value' => !empty($order['date_insert'])?human_time_diff(strtotime($order['date_insert']), time() ):""] 
		];
	}
	
	private function advertising_campaign_total( ) 
	{		
		global $wpdb;			
		$id = $this->get_filter_value( 'id' );
		$campaign = usam_get_advertising_campaign( $id );	
		$orders = usam_get_orders(['fields' => 'count', 'meta_key' => 'campaign_id', 'meta_value' => $id, 'number' => 1]);	
		$date_insert = usam_get_orders(['fields' => 'date_insert', 'meta_key' => 'campaign_id', 'meta_value' => $id, 'number' => 1]);	
		return array( 
			['title' => esc_html__('Переходов', 'usam'), 'value' => $campaign['transitions']], 
			['title' => esc_html__('Количество заказов', 'usam'), 'value' => $orders], 
			['title' => esc_html__('Последний заказ', 'usam'), 'value' => $date_insert?human_time_diff(strtotime($date_insert), time() ):""] 
		);
	}
	
	private function subscription_total( ) 
	{		
		$id = $this->get_filter_value( 'id' );
		require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal_query.class.php' );
		$renewal = usam_get_subscription_renewal_query(['subscription_id' => $id, 'fields' => ['count', 'totalprice']]);
		$date = usam_get_subscription_renewal_query(['subscription_id' => $id, 'fields' => 'date_insert', 'number' => 1, 'order' => 'DESC', 'orderby' => 'date_insert']);		
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php' );		
		$subscription = usam_get_subscription( $id );
		$args = ['type_price' => $subscription['type_price'],'currency_symbol' => false];
		return array( 
			['title' => esc_html__('Количество продлений', 'usam'), 'value' => !empty($renewal[0])?$renewal[0]->count:0], 
			['title' => esc_html__('На общую сумму', 'usam'), 'value' => !empty($renewal[0])?usam_get_formatted_price($renewal[0]->totalprice, $args):0], 
			['title' => esc_html__('Последнее продление', 'usam'), 'value' => !empty($date)?human_time_diff(strtotime($date), time() ):"" ] 
		);
	}
	
	
	private function order_analytics( ) 
	{				
		$id = $this->get_filter_value( 'id' );	
		$campaign_id = usam_get_order_metadata($id , 'campaign_id' );		
		$data['campaign'] = usam_get_advertising_campaign( $campaign_id );	
		return $data;
	}		
}
?>