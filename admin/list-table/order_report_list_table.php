<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
	require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan_query.class.php' );
require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
class USAM_List_Table_Order_report extends USAM_Main_Report_List_Table
{	
	protected $orderby = 'date';	
	protected $order   = 'DESC';	
	protected $groupby_date = 'month';
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	function no_items() 
	{
		_e( 'Заказы не найдены', 'usam');
	}	
	
	function get_columns()
	{
        $currency = usam_get_currency_sign();		
		$columns = [
			'date'                      => __('Дата', 'usam'),
			'quantity'                  => __('Количество', 'usam'),
			'totalprice'                => sprintf(__('Стоимость(%s)', 'usam'), $currency ),
			'percent_profit'            => __('Маржа (%)', 'usam'),
			'profit'                    => __('Прибыль', 'usam'),
			'bonus'                     => __('Оплачено бонусами', 'usam'),
			'percent_bonus'             => __('Оплачено бонусами (%)', 'usam'),			
			'plan'                      => __('План', 'usam'),
			'percent_plan'              => __('План (%)', 'usam'),
			'item_count'                => __('Товаров', 'usam'),			
			'sku_count'                 => __('SKU', 'usam'),			
			'quantity_payment'          => __('Кол-во оплаченных', 'usam'),
			'percent_payment'           => __('Оплаченных (%)', 'usam'),
			'payment'                   => sprintf(__('Оплаченных (%s)', 'usam'), $currency ),	
			'total_shipping'            => sprintf(__('Доставка (%s)', 'usam'), $currency ),
			'total_taxes'               => sprintf( __('Налог (%s)', 'usam'), $currency ),
			'average_check'             => __('Средний чек', 'usam'),
			'buyer'                     => __('Всего покупателей', 'usam'),	
			'new_buyer'                 => __('Новых покупателей', 'usam'),		
			'percent_new_buyer'         => __('Новых покупателей (%)', 'usam'),
			'contacts'                  => __('Контакты', 'usam'),			
			'new_contact'               => __('Новые контакты', 'usam'),	
			'company'                   => __('Компании', 'usam'),			
			'new_company'               => __('Новые компании', 'usam'),								
        ];
		return $columns;
    }
		
	function prepare_items() 
	{	
		global $wpdb;	
			
		$where = array( '1=1' );
		$join  = array();			
		$selects = array( 'p.id, p.contact_id, p.company_id, p.type_payer, p.totalprice, p.number_products, p.cost_price, p.shipping, p.status, p.paid' );				
		$selected = $this->get_filter_value( 'date_group' );		
		if ( $selected == 'paid' )
			$date_colum = 'date_paid';		
		else
			$date_colum = 'date_insert';
		
		$order_status = $this->get_filter_value( 'status' );
		if ( $order_status )
		{ 
			$selected = array_map('sanitize_title', (array)$order_status);
			$where[] = "p.status IN ('".implode( "','", $selected )."')";
		}		
		if ( $this->end_date_interval )
		{
			$where[] = "p.{$date_colum}<='$this->end_date_interval'";
			$where_end_date = "date_insert<='$this->end_date_interval'";
		}
		else
			$where_end_date = '1=1';
			
		if ( $this->start_date_interval )
		{
			$where[] = "p.{$date_colum}>='$this->start_date_interval'";
			$where_start_date = "date_insert>='$this->start_date_interval'";
		}
		else
			$where_start_date = '1=1';
		$selects[] = "p.{$date_colum} AS date";	
		$orderby = "p.{$date_colum}";
					
		$selected = $this->get_filter_value( 'payer' );
		if ( !empty($selected) )
			$where[] = "p.type_payer IN ('".implode( "','", (array)$selected )."')";
		$selected = $this->get_filter_value( 'seller' );
		if ( $selected )
		{ 
			$selected = array_map('intval', (array)$selected);
			$where[] = "p.bank_account_id IN ('".implode( "','", $selected )."')";
		}
		$selected = $this->get_filter_value('code_price');
		if ( !empty($selected) )
		{ 
			$selected = array_map('sanitize_text_field', (array)$selected);
			$where[] = "p.type_price IN ('".implode( "','", $selected )."')";
		}				
		if ( $this->get_filter_value( 'storage_pickup' ) || $this->get_filter_value( 'shipping' ) )
			$join[] = " INNER JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = p.id)";
		$selected = $this->get_filter_value( 'storage_pickup' );
		if ( !empty($selected) && $selected != 'all' )
		{
			$storage = array_map('intval', (array)$selected);
			$where[] = USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup IN (".implode(',',  $storage).")"; 
		}
		$selected = $this->get_filter_value( 'shipping' );
		if ( !empty($selected) && $selected != 'all' )
		{
			$shipping = array_map('intval', (array)$selected);
			$where[] = USAM_TABLE_SHIPPED_DOCUMENTS.".method IN (".implode(',',  $shipping).")"; 
		}
		$selected = $this->get_filter_value( 'discount' );
		if ( !empty($selected) )
		{			
			$discount = array_map('intval', (array)$selected);
			$join[] = " INNER JOIN ".USAM_TABLE_DISCOUNT_RULES." ON (".USAM_TABLE_DISCOUNT_RULES.".order_id = p.id)";
			$where[] = USAM_TABLE_DISCOUNT_RULES.".id IN (". implode( ',', $discount ).")"; 
		}	
		$selected = $this->get_filter_value('contacts');
		if ( $selected )
			$where[] = "p.contact_id IN ('".implode( "','", (array)$selected )."')";
		
		$selected = $this->get_filter_value('companies');
		if ( $selected )
			$where[] = "p.company_id IN ('".implode( "','", (array)$selected )."')";
		
		$product_day = $this->get_filter_value( 'product_day' );
		$selected_brand = $this->get_filter_value( 'brands' );
		$selected_category = $this->get_filter_value( 'category' );
		if ( !empty($selected_brand) || !empty($selected_category) || !empty($product_day) )
			$join[] = " INNER JOIN ".USAM_TABLE_PRODUCTS_ORDER." ON (".USAM_TABLE_PRODUCTS_ORDER.".order_id = p.id)";
		
		if ( !empty($product_day) )
		{			
			$product_day = array_map('intval', (array)$product_day);	
			$where[] = USAM_TABLE_PRODUCTS_ORDER.".product_day IN (".implode( ',',  $product_day ).")"; 			
		}		
		if ( !empty($selected_brand) || !empty($selected_category) )
		{			
			$join[] = " INNER JOIN {$wpdb->prefix}term_relationships AS tr INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
			$where[] = USAM_TABLE_PRODUCTS_ORDER.".product_id=tr.object_id"; 		
		}
		if ( !empty($selected_brand) )
		{			
			$selected_brand = array_map('intval', (array)$selected_brand);
			$category_children = get_option( 'usam-brands_children', array());
			$selected = array();
			foreach ( $selected_brand as $id )
			{
				$selected[] = $id;
				if ( isset($category_children[$id]) )
					$selected = array_merge( $selected, $category_children[$id] );	
			}		
			$selected = array_unique($selected);
			$where[] = "tt.term_id IN (".implode( ',', $selected ).")"; 
		}	
		if ( !empty($selected_category) )
		{			
			$selected_category = array_map('intval', (array)$selected_category);
			$category_children = get_option( 'usam-category_children', array());
			$selected = array();
			foreach ( $selected_category as $id )
			{
				$selected[] = $id;				
				if ( isset($category_children[$id]) )
					$selected = array_merge( $selected, $category_children[$id] );	
			}		
			$selected = array_unique($selected);
			$where[] = "tt.term_id IN (".implode( ',', $selected ).")";
		}			
		$selected = $this->get_filter_value( 'payment' );
		if ( !empty($selected) )
		{
			$payment = array_map('intval', (array)$selected);
			$join[] = " INNER JOIN ".USAM_TABLE_PAYMENT_HISTORY." ON (".USAM_TABLE_PAYMENT_HISTORY.".document_id=p.id)";
			$where[] = USAM_TABLE_PAYMENT_HISTORY.".gateway_id IN (".implode( ',', $payment ).")"; 
		}		
		$selected = $this->get_filter_value( 'campaign' );
		if ( !empty($selected) )
		{
			$campaign = array_map('intval', (array)$selected);
			$join[] = " INNER JOIN ".USAM_TABLE_ORDER_META." ON (".USAM_TABLE_ORDER_META.".order_id=p.id AND ".USAM_TABLE_ORDER_META.".meta_key ='campaign_id')";
			$where[] = USAM_TABLE_ORDER_META.".meta_value IN (".implode( ',', $campaign ).")"; 
		}			
		$selected = $this->get_filter_value( 'group' );
		if ( !empty($selected) )
		{
			$join[] = " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id=p.id)";
			$where[] = USAM_TABLE_GROUP_RELATIONSHIPS.".group_id IN (".implode( ',', array_map('intval', (array)$selected) ).")"; 
		}		
		$selected = $this->get_filter_value( 'sum' );
		if ( $selected )	
		{		
			$values = explode('|',$selected);
			if ( $values[0] )
				$where[] = "p.totalprice>=".absint($values[0]);
			if ( isset($values[1]) )
				$where[] = "p.totalprice<=".absint($values[1]);
		}	
		$selected = $this->get_filter_value( 'shipping_sum' );
		if ( $selected )	
		{		
			$values = explode('|',$selected);
			if ( $values[0] )
				$where[] = "p.shipping>=".absint($values[0]);
			if ( isset($values[1]) )
				$where[] = "p.shipping<=".absint($values[1]);
		}			
		$selected = $this->get_filter_value( 'prod' );
		if ( $selected )	
		{		
			$values = explode('|',$selected);
			if ( $values[0] )
				$where[] = "p.number_products>=".absint($values[0]);
			if ( isset($values[1]) )
				$where[] = "p.number_products<=".absint($values[1]);
		}
		$selected = $this->get_filter_value( 'bonus' );
		if ( $selected )	
		{		
			$values = explode('|',$selected);
			if ( $values[0] )
				$where[] = "bonus.sum>=".absint($values[0]);
			if ( isset($values[1]) )
				$where[] = "bonus.sum<=".absint($values[1]);
		}
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$where[] = "p.manager_id IN (".implode(",",array_map('intval', (array)$selected)).")";	
		
		$selected = $this->get_filter_value( 'weekday' );
		if ( !empty($selected) )
		{
			$selected = is_array($selected)?$selected:array($selected);
			$dayofweek = [];
			foreach ( $selected as $day )
			{
				$dayofweek[] = $wpdb->prepare("DAYOFWEEK(p.{$date_colum}) = %d", sanitize_title($day) );
			}
			$where[] = " (".implode( ' OR ', $dayofweek ).")";		
		}	
		if ( usam_check_current_user_role('shop_crm') )
		{			
			$user_ids = usam_get_subordinates();
			$user_ids[] = get_current_user_id(); 		
		//	$where[] = "p.manager_id IN (".implode( ',', $user_ids ).")";	
		}	
		else
		{
			$view_group = usam_get_user_order_view_group( );
			if ( !empty($view_group) )
			{ 
				if ( !empty($view_group['type_prices']) )
					$where[] = "p.type_price IN ('".implode("','", $view_group['type_prices'])."')";
			}
		}	
		$where = implode( ' AND ', $where );	
		
		$this->query_vars['meta_query'] = [];		
		$this->get_string_for_query(['coupon_name'], 'meta_query');
		$meta_query = new USAM_Meta_Query();
		$meta_query->parse_query_vars( $this->query_vars );	
		if ( !empty($meta_query->queries) ) 
		{
			$clauses = $meta_query->get_sql( 'order', USAM_TABLE_ORDER_META, 'p', 'id', $this );
			$join[] = $clauses['join'];
			$where .= $clauses['where'];
		}	
		
		$join[] = " LEFT JOIN ( SELECT order_id, SUM(tax) AS total_tax FROM ".USAM_TABLE_TAX_PRODUCT_ORDER." GROUP BY order_id ) AS taxes ON (taxes.order_id=p.id) ";
		$join[] = " LEFT JOIN ( SELECT object_id AS order_id, SUM(sum) AS sum FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE object_type='order' GROUP BY object_id) AS bonus ON (bonus.order_id=p.id)";				
		$selects[] = "(SELECT date_insert FROM ".USAM_TABLE_CONTACTS." AS c WHERE p.contact_id = c.id AND p.company_id=0 AND $where_start_date AND $where_end_date) AS new_contact";
		$selects[] = "(SELECT date_insert FROM ".USAM_TABLE_CONTACTS." AS c WHERE p.company_id = c.id AND $where_start_date AND $where_end_date) AS new_company";
		$selects[] = ' IFNULL((SELECT COUNT(*) FROM '.USAM_TABLE_PRODUCTS_ORDER.' AS c WHERE c.order_id = p.id ),0) AS sku_count';	
		$selects[] = ' IFNULL(taxes.total_tax,0) AS total_tax';		
		$selects[] = ' IFNULL(bonus.sum,0) AS bonus';			
		$selects = implode( ', ', $selects );		
		$join = implode( ' ', $join );		

		$all_orders = $wpdb->get_results("SELECT $selects FROM ".USAM_TABLE_ORDERS." AS p $join WHERE $where ORDER BY $orderby DESC");
						
		$query_vars = [];
		if ( $this->start_date_interval )
			$query_vars['date_query'][] = ['after' => get_gmt_from_date($this->start_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => 'date_insert'];	
		if ( $this->end_date_interval )
			$query_vars['date_query'][] = ['before' => get_gmt_from_date($this->end_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => 'date_insert'];			
		$sales_plans = usam_get_my_sales_plan( $query_vars );
				
		$new_contact = [];
		$new_company = [];		
		$records = [];	
		$i = 0;			
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{							
			$records[$i]['date'] = $j;
			$records[$i]['quantity'] = 0; //Общее количество
			$records[$i]['item_count'] = 0;
			$records[$i]['sku_count'] = 0;			
			$records[$i]['totalprice'] = 0;			
			$records[$i]['total_shipping'] = 0; //Стоимость доставки (Рубль)
			$records[$i]['total_taxes'] = 0;		
			$records[$i]['average_check'] = 0;	
			$records[$i]['quantity_payment'] = 0;
			$records[$i]['percent_payment'] = 0;
			$records[$i]['payment'] = 0;		
			$records[$i]['contacts'] = 0;			
			$records[$i]['new_contact'] = 0;			
			$records[$i]['company'] = 0;	
			$records[$i]['new_company'] = 0;	
			$records[$i]['buyer'] = 0;	
			$records[$i]['new_buyer'] = 0;	
			$records[$i]['profit'] = 0;	
			$records[$i]['percent_profit'] = 0;	
			$records[$i]['percent_new_buyer'] = 0;	
			$records[$i]['plan'] = 0;	
			$records[$i]['percent_plan'] = 0;
			$records[$i]['bonus'] = 0;		
			$records[$i]['percent_bonus'] = 0;							
			$plan_target = '';
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			foreach ( $all_orders as $key => $item )
			{						
				if ( $current_date > $item->date )
				{
					if ( $records[$i]['quantity'] > 0 && $records[$i]['quantity_payment'] > 0) 
						$records[$i]['percent_payment'] = round($records[$i]['quantity_payment'] / $records[$i]['quantity'] *100, 1);	
					if ( $records[$i]['buyer'] > 0 && $records[$i]['new_buyer'] > 0) 
						$records[$i]['percent_new_buyer'] = round($records[$i]['new_buyer'] / $records[$i]['buyer'] *100, 1);	
					if ( $records[$i]['profit'] > 0 ) 
						$records[$i]['percent_profit'] = round( $records[$i]['profit'] *100 / ($records[$i]['totalprice'] - $records[$i]['profit']), 1);	
					if ( $records[$i]['bonus'] > 0 ) 
						$records[$i]['percent_bonus'] = round( $records[$i]['bonus'] *100 / ($records[$i]['totalprice'] - $records[$i]['bonus']), 1);							
					if ( $records[$i]['plan'] > 0 ) 
					{
						if ( $plan_target == 'sum' ) 
							$records[$i]['percent_plan'] = round($records[$i]['totalprice'] / $records[$i]['plan'] *100, 1);
						else
							$records[$i]['percent_plan'] = round($records[$i]['quantity'] / ( $records[$i]['plan'] *100 ), 1);
					}						
					break;					
				}
				else
				{	
					$records[$i]['quantity']++;					
					$records[$i]['totalprice'] += $item->totalprice;	
					$records[$i]['bonus'] += $item->bonus;					
					$records[$i]['total_shipping'] += $item->shipping;
					$records[$i]['total_taxes'] += $item->total_tax;								
					if ( $item->company_id )
						$records[$i]['company']++;
					elseif ( $item->contact_id )
						$records[$i]['contacts']++;						
					if ( !empty($item->new_contact) )
					{									
						if ( $current_date <= $item->new_contact && !in_array($item->new_contact, $new_contact) )
						{
							$records[$i]['new_contact']++;
							$new_contact[] = $item->new_contact;
						}						
					}
					if ( !empty($item->new_company) )
					{
						if ( $current_date <= $item->new_company && !in_array($item->new_company, $new_company) )
						{
							$records[$i]['new_company']++;
							$new_company[] = $item->new_company;
						}
					}
					$records[$i]['new_buyer'] = $records[$i]['new_contact'] + $records[$i]['new_company'];
					$records[$i]['buyer'] = $records[$i]['contacts'] + $records[$i]['company'];					
					if ( $item->paid == 2 )
					{
						$records[$i]['quantity_payment']++; //Кол-во оплаченных
						$records[$i]['payment'] += $item->totalprice;	
					}							
					$records[$i]['item_count'] += $item->number_products;		//Кол-во товаров	
					$records[$i]['sku_count'] += $item->sku_count;	
					$records[$i]['profit'] += $item->cost_price != '0.00' ? $item->totalprice - $item->cost_price : 0;
					if ( $this->order_status == $item->status || $this->order_status == 'all' )
					{ 
						if ( $item->status != '' )
						{
							$colum_key = $item->status.'_currency';
							$records[$i][$colum_key] += $item->totalprice;					
							$colum_key = $item->status."_quantity";
							$records[$i][$colum_key]++;			
						}
					}
					foreach ( $sales_plans as $sales_plan )
					{ 
						if ( $sales_plan->period_type == $this->groupby_date && $sales_plan->from_period <= $item->date && $sales_plan->to_period >= $item->date )
						{							
							$records[$i]['plan'] = $sales_plan->my_sum;
							$plan_target = $sales_plan->target;							
						}						
					}		
					unset($all_orders[$key]);					
				}
			}			
			if ( $records[$i]['quantity'] > 0 && $records[$i]['totalprice'] > 0) 
				$records[$i]['average_check'] = round($records[$i]['totalprice'] / $records[$i]['quantity'], 1);	
			
			if ( $records[$i]['quantity'] > 0 && $records[$i]['quantity_payment'] > 0) 
				$records[$i]['percent_payment'] = round($records[$i]['quantity_payment'] / $records[$i]['quantity'] *100, 1);		
						
			$j = strtotime("-1 ".$this->groupby_date, $j);	
			$i++;			
		}		
		$this->items = $records;
		foreach ( $this->items as $i => $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['totalprice'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Стоимость', 'usam').': '.$this->currency_display( $item['totalprice'] )) ) );	
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{		
					case 'date' :
						$this->results_line[$key] = '';
					break;			
					default:			
						$this->results_line[$key] += $value;
					break;			
				}				
			}
		}		
		$count = count($this->items);
		if ( $count )
		{
			$this->results_line['average_check'] = round($this->results_line['average_check']/$count, 0);
			$this->results_line['percent_payment'] = round($this->results_line['percent_payment']/$count, 0);		
			$this->results_line['percent_new_buyer'] = round($this->results_line['percent_new_buyer']/$count, 0);	
		}
	}
	
	public function get_title_graph( ) 
	{
		return __('Стоимость всех заказов','usam');		
	}
}
?>