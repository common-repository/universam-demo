<?php
/**
 * Google Analytics
 */
class USAM_Google_Analytics 
{
	public static function print_script() 
	{		
		$option = get_option( 'usam_google', '' );	
		if ( empty($option['analytics_id']) )
			return false;
		$output = "<script async src='https://www.googletagmanager.com/gtag/js?id=".esc_attr($option['analytics_id'])."'></script>
		<script>
		window.dataLayer = window.dataLayer || []; 
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', '".esc_attr($option['analytics_id'])."');		
		jQuery(document).ready(function()
		{
			jQuery('body').delegate('.js-product-add', 'product-add' , function( e, product_id, response )	
			{
				var data   = {				
					action     : 'get_product_data',			    
					product_id : product_id,						
				};
				ajax_callback = function(response)
				{									
					if ( response )
					{
						gtag('event', 'add_to_cart', { 'items': [ {'id':product_id,'name':response.title,'price':response.price,'category':response.categories.join('/'),'brand':response.brand,'quantity':1} ]}); 
					}						
				};				
				usam_send(data, ajax_callback);
			});	
		});  	
		";			
		if ( !empty($option['analytics_ecommerce']) )
		{						
			if ( usam_is_product() ) 
				$output .= self::product_display( );				
			elseif ( usam_is_product_category_sale() ) 
				$output .= self::list_display( __('Товары акций','usam') );
			elseif ( usam_is_product_brand() ) 
				$output .= self::list_display( __('Товары бренда','usam') );
			elseif ( usam_is_product_category( ) ) 
				$output .= self::list_display( __('Товары категорий','usam') );
			elseif ( is_page('basket') ) 
				$output .= self::basket_display( );
			elseif ( usam_is_transaction_results('success') && isset($_GET['payment_number']) ) 
				$output .= self::add_pushes( );
		}				
		$output .= "</script>";	
		echo $output;
	}
	
	private static function list_display( $list ) 
	{  
		$output = '';
		$position = 1;
		while (usam_have_products()) :  	
			usam_the_product(); 
			
			global $post;			
			$post_title = get_the_title( $post->ID );
			$brand = usam_get_product_brand_name( $post->ID );
			$category = usam_get_product_category_name( $post->ID );	
			$price = usam_get_product_price( $post->ID );	
		
			$output = "gtag('event', 'view_item_list', { 'items': [ {'id':'$post->ID','name':'$post_title','price':'$price','category':'$category','brand':'$brand','list':'$list','position':'$position'} ]});"; 			
			$position++;
		endwhile;
		return $output;
	}
	
	private static function product_display(  ) 
	{	
		global $post;	
		
		$list = __('Просмотр товара','usam');		
		$post_title = get_the_title( $post->ID );
		$brand = usam_get_product_brand_name( $post->ID );
		$category = usam_get_product_category_name( $post->ID );	
		$price = usam_get_product_price( $post->ID );	
		
		$output = "gtag('event', 'view_item', {'items': [{'id':'$post->ID','name':'$post_title','price':'$price','category':'$category','brand':'$brand'} ]});"; 
		return $output;
	}
	
	private static function basket_display( ) 
	{
		$output = '';	
		$cart = USAM_CART::instance();	
		$products = $cart->get_products();
		if( $products )
		{					
			$list = __('Просмотр корзины','usam');
			$position = 1;		
			foreach( $products as $key => $product ) 
			{				
				$brand = usam_get_product_brand_name( $product->product_id  );
				$category = usam_get_product_category_name( $product->product_id );	
				$output = "gtag('event', 'view_item_list', { 'items': [ {'id':'$product->product_id','name':'$product->name','price':'$product->price','category':'$category','brand':'$brand','list':'$list','position':'$position'} ]});"; 
				$position++;
			}
		}
		return $output;
	}	
	
	private static function add_pushes( ) 
	{			
		$document = usam_get_payment_document( $_GET['payment_number'], 'number' );	
		if ( empty($document) )
			return '';
		
		$order = usam_get_order( $document['document_id'] );
		if ( empty($order) )
			return '';
		
		$products = usam_get_products_order( $order['id'] );	
		$items = array();
		$list = __('Покупка','usam');
		foreach( $products as $key => $product ) 
		{
			$category = usam_get_product_category_name( $product->product_id );
			$brand = usam_get_product_brand_name( $product->product_id );	
			$items[] = "{'id':'$product->product_id','name':'$product->name','category':'$category','brand':'$brand','price':'$product->price','quantity':'$product->quantity','list_position':'$key','list':'$list'}";
		}		
		$currency = usam_get_currency_price_by_code($order['type_price']);
		$coupon_name = (string)usam_get_order_metadata( $order['id'], 'coupon_name');	
		$total_tax = usam_get_tax_amount_order( $order['id'] );		
		$output = "gtag('event', 'purchase', { 'transaction_id': '".$order['id']."', 'affiliation': '".get_bloginfo('name')."', 'value':'".$order['totalprice']."', 'currency': '".$currency."','tax': '".$total_tax."','shipping': '".$order['shipping']."','coupon': '".$coupon_name."', 'items': [ ".implode(",",$items)." ]});"; 		
		return $output;
	}	
}
?>