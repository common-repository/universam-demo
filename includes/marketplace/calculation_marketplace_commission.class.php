<?php
// Расчет комиссии
new USAM_Calculation_Marketplace_Commission( );
final class USAM_Calculation_Marketplace_Commission
{
	function __construct( )
	{			
		switch ( get_option('usam_options_earning_marketplace', 'sales_commission_seller' ) ) 
		{		
			case 'sales_commission_seller' : //Комиссия с продаж, платить продавец
				add_action( 'usam_order_paid', [&$this, 'controller_sales_commission_seller'], 10, 1 ); 	
			break;			
			case 'payment_listing' : //Оплата за количество в месяц 
				add_action( 'usam_cron_task_day', [&$this, 'controller_payment_listing']);
			break;	
			case 'contact_customer' : //Контакт с заказчиком
		//		add_action( 'usam_cron_task_day', array( &$this, 'controller_contact_customer') );
			break;	
			case 'subscription' : //Подписка
		//		add_action( 'usam_order_paid', array( $this, 'subscription'), 10, 1 ); 	
			break;		
		}				
	}
	
	public function controller_sales_commission_seller( $_order )
	{				
		$order_id = $_order->get( 'id' );		
		$products = usam_get_products_order( $order_id );
		$sellers_sum = array();
		foreach ( $products as $product )
		{					
			$seller_id = usam_get_product_meta( $product->product_id, 'seller_id' );
			if ( $seller_id )
			{
				if ( isset($sellers_sum[$seller_id]) )
					$sellers_sum[$seller_id] += $product->price;
				else
					$sellers_sum[$seller_id] = $product->price;	
			}
		}	
		$commission_seller = get_option('usam_sales_commission_seller', 5 );		
		foreach ( $sellers_sum as $seller_id => $sum )
		{
			$commission = round( $commission_seller * $sum / 100, 2 );
			usam_insert_marketplace_commission(['status' => 'approved', 'seller_id' => $seller_id, 'sum' => $commission, 'order_id' => $order_id]);	
		}
	}
	
	public function controller_payment_listing( )
	{				
		if ( date('d') == 1 )
		{
			require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
			$sellers = usam_get_sellers(['status' => 'approved']);			
			$days_in_month = cal_days_in_month(CAL_GREGORIAN, date("m")-1, date("Y"));
			$timestamp =  mktime(0, 0, 0, date('m')-1, $days_in_month, date("Y"));			
			$commission_seller = get_option('usam_sales_commission_seller', 5 );
			foreach ( $sellers as $seller )
			{
				$day = round((strtotime($seller->date_status_update) - $timestamp)/86400, 0);
				if ( $day > 0 )
				{					
					$user_ids = get_users( array( 'fields' => 'ID', 'meta_key' => 'seller', 'meta_value' => $seller->id ) );
					$i = usam_get_total_products( array('author__in' => $user_ids) );	
					if ( $day < $days_in_month )
					{
						$commission = round( $commission_seller / $day *100, 2 );
					}
					else
					{
						$commission = $commission_seller;
					}
					usam_insert_marketplace_commission(['status' => 'approved', 'seller_id' => $seller->id, 'sum' => $commission*$i]);
				}
			}			
		}
	}	
	
	public function controller_contact_customer( )
	{
	/*	if ( date('d') == 1 )
		{
			require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
			$sellers = usam_get_sellers(['status' => 'approved']);			
			$days_in_month = cal_days_in_month(CAL_GREGORIAN, date("m")-1, date("Y"));
			$timestamp =  mktime(0, 0, 0, date('m')-1, $days_in_month, date("Y"));			
			$commission_seller = get_option('usam_sales_commission_seller', 5 );
			foreach ( $sellers as $seller )
			{
				$day = round((strtotime($seller->date_status_update) - $timestamp)/86400, 0);
				if ( $day > 0 )
				{					
					$user_ids = get_users( array( 'fields' => 'ID', 'meta_key' => 'seller', 'meta_value' => $seller->id ) );
					$i = usam_get_total_products( array('author__in' => $user_ids) );	
					if ( $day < $days_in_month )
					{
						$commission = round( $commission_seller / $day *100, 2 );
					}
					else
					{
						$commission = $commission_seller;
					}
					usam_insert_marketplace_commission( array( 'status' => 'approved', 'seller_id' => $seller->id, 'sum' => $commission*$i ) );
				}
			}			
		}*/
	
	}
}
?>