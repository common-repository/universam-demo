<?php
new USAM_Cron();
class USAM_Cron
{	
	public  $errors  = [];	
	function __construct()
	{	
		if ( get_option( 'usam_db_version', false ) )
		{
			add_filter('cron_schedules', [&$this, 'crony_schedules'], 11, 2 );
			add_action( 'wp', [&$this, 'start_cron'] );
			if ( !usam_is_multisite() || is_main_site() )
			{
				add_action( 'usam_five_min_cron_task', [&$this, 'working_mail'] );	
				add_action( 'usam_hourly_cron_task', [&$this, 'update_delivery_history'] );	
				if ( !USAM_DISABLE_INTEGRATIONS )
				{
					add_action( 'usam_hourly_cron_task', [$this, 'products_publish']);      // Добавить товары в контакт
					if ( get_option( 'usam_check_position_site', 0 ) )
					{
						add_action( 'usam_ten_minutes_cron_task', [&$this, 'query_position_site'], 30 );
					}				
				}
				add_action( 'usam_cron_task_day', [&$this, 'run_every_day'] );
			}		
			add_action( 'usam_hourly_cron_task', [&$this, 'process_product_discount'] );
			add_action( 'usam_theme_installer', [&$this, 'theme_installer'], 10, 1 );				
			add_action( 'usam_five_min_cron_task', ['USAM_Cron', 'start_process'], 100 );
			add_action( 'usam_exchange_rules_cron', [$this, "data_exchange"], 10, 1 );
			add_action( 'usam_install_default_db_data', array($this, 'install_default_db_data') );	
			add_action( 'usam_begining_work_day', [&$this, 'begining_work_day']);			
		}
    }	
	
	public static function start_process( )
	{		
		remove_action( 'usam_five_min_cron_task', ['USAM_Cron', 'start_process']); 
		if( !wp_next_scheduled('usam_start_process') && (!defined('USAM_SYSTEM_PROCESS') || !USAM_SYSTEM_PROCESS) )
		{					
			$events = usam_get_system_process();		
			if ( !empty($events) )
			{				
				foreach( $events as $key => $event )
				{ 
					if( $event['status'] == 'pause' )
						unset($events[$key]);
				}
				if ( empty($events) )
					return;	
				
				set_time_limit(1800);
				if ( function_exists( 'ini_get' ) )
					$max_execution_time = ini_get('max_execution_time');

				if( empty($max_execution_time ) )
					$max_execution_time = 330;	
				
				$time = time();	
				$start = true;
				foreach( $events as $key => $event )
				{ 						
					if( $event['status'] === 'start' )
					{
						if( isset($event['start_cycle']) && strtotime($event['start_cycle'])+$max_execution_time < $time )					
							$start = true;	
						else
							$start = false;
						break;
					}
				}
				if( $start )
				{
					usam_log_file('ЗАПУСК usam_start_processes', 'system_process', false);			
					usam_start_processes();
				}
			}
		}
	}	
	
	private function set_error( $error )
	{			
		$this->errors[]  =  sprintf( __('Cron. Ошибка %s'), $error );
	}
	
	private function set_log_file( )
	{	
		usam_log_file( $this->errors );
		$this->errors = array();
	}	
			
	// Интервалы времени для cron
	function crony_schedules( $schedules ) 
	{ 	 
		$schedules['five_min'] = ['interval' => 300, 'display' => __('Каждые пять минут', 'usam')];
		$schedules['ten_minutes'] = ['interval' => 600, 'display' => __('Каждые десять минут', 'usam')];
		return apply_filters('usam_crony_schedules',$schedules);
	}	
		
	function start_cron()
	{ 
		$ve = get_option( 'gmt_offset' ) > 0 ? '+' : '-';	
		if ( !wp_next_scheduled("usam_vk_publish") ) wp_schedule_event( strtotime( '08:00 tomorrow ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' ), 'daily', "usam_vk_publish" );
		if ( !wp_next_scheduled("usam_begining_work_day") ) wp_schedule_event( strtotime( '08:00 tomorrow ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' ), 'daily', "usam_begining_work_day" );
		if ( !wp_next_scheduled("usam_cron_task_day") ) wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' ), 'daily', "usam_cron_task_day" );				
		foreach ( wp_get_schedules() as $cron => $schedule ) 
		{		
			if ( !wp_next_scheduled("usam_{$cron}_cron_task") ) 
				wp_schedule_event( mktime(date("H"), 0, 1, date("n"), date("j"), date('Y')), $cron, "usam_{$cron}_cron_task" );	
		}		
	}
	
	public static function begining_work_day( )
	{
		require_once( USAM_FILE_PATH . '/includes/document/subscriptions_query.class.php' );
		$subscriptions = usam_get_subscriptions(['date_query' => [['after' => '30 days ago', 'inclusive' => true, 'column' => 'end_date'], ['before' => date('Y-m-d H:i:s'), 'inclusive' => true, 'column' => 'start_date']], 'status' => 'signed']);
		$timestamp = time();
		foreach( $subscriptions as $subscription )
		{ 
			$date = round((strtotime($subscription->end_date) - $timestamp) / (60 * 60 * 24), 0);
			do_action( 'usam_subscription_expired', (array)$subscription, $date );
		}
	}
			
	public function data_exchange( $id )
	{
		if ( is_numeric($id) )
		{	
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
			$rule = usam_get_exchange_rule( $id );
			if ( !empty($rule) && $rule['exchange_option'] !== '' && $rule['exchange_option'] !== 'email' )
			{
				$w = date("w");
				$weekday = usam_get_exchange_rule_metadata( $id, 'weekday' );
				if ( empty($weekday) || in_array($w, $weekday) )
					usam_start_exchange( $id );	
			}
		}
	}		
	
	function query_position_site()
	{
		if ( !get_transient( 'usam_start_query_position_site' ) ) 
		{
			require_once(USAM_FILE_PATH.'/includes/seo/checking_site_position.class.php');	
			usam_query_position_site();
		}
	}
	
	function run_every_day()
	{			
		$bonus_rules = get_option('usam_bonus_rules', ['activation_date' => 14]);
		if ( !empty($bonus_rules['activation_date']) )
		{
			require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );	
			$transactions = usam_get_bonuses(['type_transaction' => 2, 'date_query' => [['before' => date('Y-m-d H:i:s', strtotime('-'.$bonus_rules['activation_date'].' days'))]]]);
			foreach( $transactions as $transaction )
				usam_update_bonus( $transaction->id, ['type_transaction' => 0]);
		}
		require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
		$rules = usam_get_exchange_rules(['type' => 'pricelist', 'meta_key' => 'file_generation', 'meta_value' => 'day', 'schedule' => 1]);
		foreach( $rules as $rule )
		{			
			$export = new USAM_Product_Exporter( $rule->id );
			$i = $export->get_total();		
			usam_create_system_process( __("Создание прайс-листа", "usam" ).' - '.$rule->name, $rule->id, 'pricelist_creation', $i, 'exchange_'.$rule->type."-".$rule->id );	
		}	
		$this->product_day();
		if( !is_multisite() || is_main_site())
		{
			require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
			$sites = usam_get_parsing_sites(['fields' => ['id', 'name', 'site_type', 'domain'], 'active' => 1]);
			if ( !empty($sites) )
			{
				foreach($sites as $site )
				{
					$type_import = usam_get_parsing_site_metadata( $site->id, 'type_import' );
					$type_import = $type_import ? $type_import : 'insert';
					usam_start_parsing_site( (array)$site, $type_import );
				}
			}		
			$this->end_manager_working();
			$this->MySQL_backup();
		
			$api = new USAM_Service_API();
			$api->check_license();
			$inventory_management = get_option('usam_inventory_management');
			if ( !empty($inventory_management['enable']) )
			{
				$i = usam_get_total_products( array('post_status' => 'publish') );
			//	usam_create_system_process( __("Подготовка данных для управления запасом", "usam" ), 1, 'stock_management_data', $i, 'stock_management_data' );	
			}	
			usam_process_calculate_increase_sales_product();
			require_once( USAM_FILE_PATH . '/includes/directory/currency_rates_query.class.php' );
			require_once( USAM_FILE_PATH . '/includes/directory/currency_rate.class.php' );	
			$currency_rates = usam_get_currency_rates(['autoupdate' => 1]);			
			$recalculate = false;					
			foreach( $currency_rates as $currency_rate )
			{
				$rates = new USAM_ExchangeRatesCBRF( );	
				$rate = $rates->GetCrossRate( $currency_rate->basic_currency, $currency_rate->currency );	
				if ( $rate && $rate != $currency_rate->rate )
				{				
					$rate = $rate + $currency_rate->markup * $rate/100;
					if ( usam_update_currency_rate( $currency_rate->id, ['rate' => $rate] ) )
					{
						if ( !$recalculate )
						{
							$prices = usam_get_prices(['currency' => $currency_rate->currency]);
							if ( $prices )
								$recalculate = true;
						}
					}
				}
			}
			if ( $recalculate )
				usam_recalculate_price_products();	

			$delivery_services = usam_get_delivery_services(['handler__not_in' => '']);		
			foreach( $delivery_services as $service )
			{
				$merchant_instance = usam_get_shipping_class( $service->id );
				$merchant_instance->load_data();
			}			
		}		
	}
		
	function update_delivery_history()
	{
		$hour = date('G', current_time('timestamp'));
		if ( $hour > 7 )
		{
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$documents = usam_get_shipping_documents(['status' => 'referred', 'conditions' => [['key' => 'track_id', 'value' => '', 'compare' => '!='],['key' => 'method', 'value' => '', 'compare' => '!=']]]);
			if ( !empty($documents) )
			{
				usam_update_object_count_status( false );			
				foreach ( $documents as $document )	
				{					
					$shipped_instance = usam_get_shipping_class( $document->method );	
					$history = $shipped_instance->get_delivery_history( $document->track_id );
					if ( !empty($history['issued']) )
					{
						usam_update_shipped_document( $document->id, ['status' => 'shipped']);
						if ( !is_admin() )
							usam_add_notification( ['title' => sprintf(__('Отправление №%s доставлено','usam'), $document->id)], ['object_type' => 'shipped', 'object_id' => $document->id] ); 
						if ( $document->order_id )
						{
							$order = usam_get_order( $document->order_id );
							if ( !empty($order) && !usam_check_object_is_completed( $order['status'], 'order' ) && $order['paid'] == 2 )
								usam_update_order( $order['id'], ['status' => 'closed'] );
						}
					}			
				}
				usam_update_object_count_status( true );
			}
		}
	}
	
	function end_manager_working( ) 
	{
		$today = getdate();	
		require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php' );
		$change_history = usam_get_change_history_query( array( 'date_query' => array( 'year' => $today["year"], 'monthnum' => $today["mon"], 'day' => $today["mday"]-1, 'column' => 'date_insert' ), 'operation' => 'view' ) );
		if( !empty($change_history) )		
		{
			foreach ( $change_history as $item )
			{
				$end_time = strtotime($item->end);	
				if ( $end_time <= 0)
					usam_update_change_history( $item->id,  array( 'end' => date( "Y-m-d H:i:s" ) ) );
			}
		}	
	}
	
	// Планирование скидок
	public function process_product_discount( )
	{					
		$rules = usam_get_discount_rules(['active' => 1, 'type_rule' => ['product', 'fix_price']]);
		if ( !empty($rules) )
		{		
			$current_time = time();					
			
			$start_discount = array();
			$end_discount = array();
			foreach ( $rules as $key => $rule )			
			{					
				if ( ( empty($rule->start_date) || strtotime($rule->start_date) <= $current_time ) && ( empty($rule->end_date) || strtotime($rule->end_date) >= $current_time ) )
				{									
					if ( $rule->included == 0 )
					{
						$start_discount[$rule->id] = $rule;	
						usam_update_discount_rule($rule->id, ['included' => 1]);
					}
				}
				elseif ( !empty($rule->end_date) && strtotime($rule->end_date) <= $current_time )
				{							
					$end_discount[$rule->id] = $rule;
				}		
			}		
			$recalculate = false;
			if ( !empty($start_discount) )
			{						
				if ( count($start_discount)>1 )
					$title = __("Активация скидок на товары","usam" );
				else
				{
					$first = array_shift($start_discount);
					$title = sprintf( __("Активация акции &laquo;%s&raquo;(%s)","usam","usam" ), $first->name, $first->id );
				}
				$recalculate = usam_recalculate_price_products( [], $title );
			}	
			if ( !empty($end_discount) && !$recalculate )
			{
				$product_ids = usam_get_product_discount_ids( array_keys($end_discount) );	  
				if ( !empty($product_ids) )
				{
					if ( count($end_discount)>1 )
						$title = __("Отмена скидок на товары","usam" );
					else
					{
						$first = array_shift($end_discount);
						$title = sprintf( __("Отмена акции &laquo;%s&raquo; (%s)","usam","usam" ), $first->name, $first->id );
					}	
					usam_recalculate_price_products(['post__in' => $product_ids], $title);			
				}
			}	
		}
	}	
							
	public function MySQL_backup( )
	{
		if ( get_option("usam_backup_bd_active", 0) )
		{
			global $wpdb;
			$dir = USAM_BACKUP_DIR."db_backup";
			if( !is_dir($dir) )
			{			
				if ( !mkdir($dir, 0777, true) ) 
					return false;
			}	
			$file = $dir.'/'.DB_NAME.'.sql';
			if ( file_exists($file) )
				unlink($file);
			
			require_once( USAM_FILE_PATH . '/includes/technical/mysql_backup.class.php' );
			$sql_dump = new USAM_MySQL_Backup(['dumpfile' => $file]);
			foreach ( $sql_dump->tables_to_dump as $key => $table ) 
			{
				if ( $wpdb->prefix != substr( $table,0 , strlen( $wpdb->prefix ) ) )
					unset( $sql_dump->tables_to_dump[ $key ] );
			}
			$sql_dump->execute();
		}
	}
			
	/*	Описание: автоматическое изменения Товара дня
	*/
	public function product_day() 
	{				
		 // Заполняет очередь товар дня
		$pday = new USAM_Work_Product_Day();
		$pday->refill_the_queue_product_day();
		
		$pday = new USAM_Work_Product_Day();
		$pday->change_product_day();		
	}
		
	function working_mail()
	{	
		$mailboxes = usam_get_mailboxes(['cache_results' => true, 'cache_mailbox_users' => true, 'cache_meta' => true]);	
		foreach( $mailboxes as $mailbox ) 
		{
			usam_download_email_pop3_server( $mailbox->id );
			//sleep(1);
		}	
		usam_send_mails( );	
	}
	
	function install_default_db_data() 
	{	
		include(USAM_FILE_PATH . '/admin/db/db-install/default_db_data.php');	
	}	
	
	public static function theme_installer( $theme_slug ) 
	{
		wp_clear_scheduled_hook( 'usam_theme_installer', func_get_args() );				
		if ( usam_upload_theme( $theme_slug ) )
			switch_theme( $theme_slug );
	}
	
	function products_publish_profile( $rule, $profiles )
	{
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		require_once( USAM_APPLICATION_PATH . '/social-networks/ok_api.class.php' );
		
		$publish_args = ['campaign' => $rule['campaign']];
		if ( !empty($rule['exclude']) )
			$date = date("Y-m-d H:i:s", strtotime('-'.$rule['exclude'].' days'));				
		$args = ['orderby' => 'rand', 'post_status' => 'publish', 'posts_per_page' => $rule['quantity'], 'from_price' => $rule['pricemin'], 'postmeta_query' => array(), 'stock_meta_query' => array(), 'update_post_term_cache' => true, 'stocks_cache' => false];				
		if ( !empty($rule['to_price']) )
			$args['to_price'] = $rule['pricemax'];
		$args['stock_meta_query'][] = array( 'key' => 'stock', 'value' => $rule['minstock'], 'compare' => '>' );											
		if ( !empty($rule['terms']) )
		{
			$args['tax_query'] = array();
			foreach( $rule['terms'] as $taxonomy => $terms )		
			{
				if ( !empty($terms) )
					$args['tax_query'][] = array( 'taxonomy' => 'usam-'.$taxonomy, 'field' => 'id', 'terms' => $terms );
			}
		}
		foreach ( $profiles as $profile ) 
		{							
			$key = 'publish_date_'.$profile->type_social.'_'.$profile->code;
			if ( !empty($rule['exclude']) )
				$args['postmeta_query'][] = ['relation' => 'OR',['key' => $key, 'compare' => 'NOT EXISTS'],['key' => $key, 'value' => $date, 'compare' => '>=', 'type' => 'DATETIME']];	
			else
				$args['postmeta_query'][] = ['key' => $key, 'compare' => 'NOT EXISTS'];	
			$args['type_price'] = $profile->type_price;	
			$products = usam_get_products( $args ); 
			
			switch ( $profile->type_social ) 
			{
				case 'vk_group' :
				case 'vk_user' :
					$class = new USAM_VKontakte_API( (array)$profile );
				break;
				case 'ok_group' :
				case 'ok_user' :
					$class = new USAM_OK_API( (array)$profile );
				break;
			}
			if ( isset($class) )
			{
				foreach( $products as $key => $product )	
					$class->publish_post($product, $publish_args );	
			}			
		}	
	}
	
	// Добавить товары в контакт
	function products_publish()
	{						
		$option = get_site_option('usam_vk_publishing_rules');
		$rules = maybe_unserialize( $option );				
		$timestamp = current_time('timestamp');
		foreach ( $rules as $rule )
		{							
			$hour = date( 'G', $timestamp );			
			if ( usam_validate_rule($rule) && (empty($rule['date_publish']) || $timestamp > ($rule['date_publish'] + ($rule['periodicity']*3600))) && ( empty($rule['from_hour']) || $rule['from_hour'] <= $hour ) && ( empty($rule['to_hour']) || $rule['to_hour'] >= $hour ) )
			{	
				if ( !empty($rule['vk_groups']) )
				{					
					$profiles = usam_get_social_network_profiles(['type_social' => ['vk_group'], 'include' => $rule['vk_groups']]);												
					$this->products_publish_profile( $rule, $profiles );
				}
				if ( !empty($rule['vk_users']) )
				{					
					$profiles = usam_get_social_network_profiles(['type_social' => ['vk_user'], 'include' => $rule['vk_users']]);												
					$this->products_publish_profile( $rule, $profiles );	
				}	
				if ( !empty($rule['ok_groups']) )
				{					
					$profiles = usam_get_social_network_profiles(['type_social' => ['ok_group'], 'include' => $rule['ok_groups']]);												
					$this->products_publish_profile( $rule, $profiles );
				}
				if ( !empty($rule['ok_users']) )
				{					
					$profiles = usam_get_social_network_profiles(['type_social' => ['ok_user'], 'include' => $rule['ok_users']]);												
					$this->products_publish_profile( $rule, $profiles );
				}
				$rule['date_publish'] = current_time('timestamp');
				usam_edit_data( $rule, $rule['id'], 'usam_vk_publishing_rules' );	
			}	
		}			
	}	
}



new USAM_Clear();
class USAM_Clear
{				
	function __construct()
	{
      	add_action( 'usam_cron_task_day', array($this, 'deleting_every_day') );		
		add_action( 'usam_hourly_cron_task', array($this, 'clear'), 1 ); 			
    }
	
	function clear()
	{					
		$this->time_keeping_baskets(); 
		if ( usam_check_type_product_sold( 'product' ) )
			$this->product_reserve_clear_period();		
	}
	
	/**
	 * Очищает корзины
	 */
	function time_keeping_baskets()
	{
		$time = (float) get_option( 'usam_time_keeping_baskets', 30 );	
		if ( $time > 0 )
		{
			$seconds = current_time('timestamp')-$time*86400;	
			$time = date('r', $seconds);	
			usam_delete_cart(['date_query' => [['column' => 'recalculation_date', 'before'  => $time]]]);
		}
	}	
	
	/**
	 * Очищает утвержденные на складе
	 */
	function product_reserve_clear_period()
	{
		$time = (float)get_option( 'usam_product_reserve_clear_period', 1 );			
		
		if ( $time > 0 )
		{			
			$seconds = current_time('timestamp')-$time*86400;
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$shippeds_document = usam_get_shipping_documents(['date_query' => [['before'  => date( 'r', $seconds)]], 'table_query' => [['key' => 'reserve', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL']], 'cache_results' => true, 'number' => 100]);	
			foreach ( $shippeds_document as $document ) 
			{			
				$shipped = new USAM_Shipped_Document( $document->id );
				$shipped->remove_all_product_from_reserve();
			}
		}		
	}
	
	public function clearing_emails()
	{		
		$mailboxes = usam_get_mailboxes(['cache_results' => true, 'cache_mailbox_users' => true]);			
		foreach ( $mailboxes as $mailbox ) 
		{			
			if ( $mailbox->delete_server && $mailbox->delete_server_day )
			{
				$pop3 = new USAM_POP3( $mailbox->id );
				$pop3->delete_message_before_date( );	
				$pop3->disconnect();				
			}
		}			
		usam_delete_emails(['folder' => 'deleted', 'meta_query' => [['key' => 'date_delete', 'value' => date('Y-m-d H:i:s', strtotime("-30 day")), 'type' => 'DATETIME', 'compare' => '<']]]);
	}
	
	public function clear_customer_products( $user_list, $day )
	{		
		global $wpdb;
		$date_insert = date( "Y-m-d H:i:s", mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - $day, date( 'Y' )));
		$wpdb->query( "DELETE FROM ".USAM_TABLE_USER_POSTS." WHERE user_list ='$user_list' AND date_insert<'$date_insert'" );	
	}
	
	public function clearing_temporary_files( )
	{		
		usam_delete_files(['date_query' => ['before' => '1 days ago', 'column' => 'date_update'], 'type' => 'temporary', 'status' => 'all'], true );
		$args = ['date_query' => ['before' => '30 days ago', 'column' => 'date_update'], 'status' => 'delete'];	
		usam_delete_files( $args, true );		
		usam_delete_folders( $args, true );		
		usam_delete_orders(['date_query' => ['before' => '30 days ago', 'column' => 'date_status_update'], 'status' => 'delete'], true );
	}
	
	public static function clear_log_files() 
	{ 		
		$log_dir = USAM_UPLOAD_DIR.'Log/';
		if (file_exists($log_dir)) 
		{			
			$dh = opendir( $log_dir );			
			while ( ($log_file = readdir( $dh )) !== false ) 
			{					
				if ( ($log_file != "..") && ($log_file != ".") && !stristr( $log_file, "~" ) )
				{						
					if ( strtotime('-40 days' ) > filectime($log_dir . $log_file) )
					{ 	
						unlink( $log_dir . $log_file );	
					}
				}
			}		
		}
	}	
	
	public static function clear_notifications() 
	{ 
		require_once(USAM_FILE_PATH.'/includes/crm/notifications_query.class.php');
		$results = usam_get_notifications(['date_query' => ['before' => '360 days ago'], 'cache_results' => true]);
		foreach ( $results as $result ) 
		{						
			usam_delete_notification( $result->id );
		}
	}
	
	public function deleting_every_day()
	{		
		if( !is_multisite() || is_main_site())
		{
			$this->clearing_emails();				
			$this->clearing_temporary_files();	
			$this->clear_notifications();	
			
		//	$this->clear_customer_products( 'view', 360 );	

			global $wpdb;	
			$wpdb->query( "DELETE FROM ".USAM_TABLE_EVENT_ACTION_LIST." WHERE status ='2'" );		
					
			require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
			usam_delete_comments(['status' => 1], true);
			usam_delete_contacts(['date_query' => ['before' => '100 days ago', 'column' => 'online'], 'status' => 'temporary']);
			
			$date_insert = date( "Y-m-d H:i:s", mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 200, date( 'Y' )));
			$wpdb->query( "DELETE FROM ".USAM_TABLE_PAGE_VIEWED." WHERE date_insert<'$date_insert'" );			
			$this->delete_sites();
		}
		$this->clear_log_files();	
	}	

	public function delete_sites()
	{				
		global $wpdb;
		$date = date( 'Y-m-d',strtotime("-1 month", time() ));	
		$site_ids = $wpdb->get_col("SELECT site_id FROM `".USAM_TABLE_STATISTICS_KEYWORDS."` WHERE `date_insert`>='{$date}'");
		
		if ( !empty($site_ids) )
			$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SITES." WHERE id NOT IN (".implode( ', ', $site_ids ).")");
	}	
}