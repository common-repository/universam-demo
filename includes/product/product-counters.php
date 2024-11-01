<?php
class USAM_Product_Counters
{
	public function __construct()
	{		
		add_action( 'usam_product_basket_insert', [$this, 'product_basket_insert'], 30 );	
		add_action( 'usam_product_basket_update', [$this, 'product_basket_update'], 30, 3 );		
		add_action( 'usam_product_basket_delete', [$this, 'product_basket_delete'], 30 );
					
		add_action( 'usam_order_products_delete', [$this, 'order_products_delete'], 30 );		
		add_action( 'usam_update_order_status', [$this, 'update_order_status'], 30, 4 );

		add_action( 'usam_user_post_insert', [$this, 'user_post_insert'], 30);
		add_action( 'usam_user_post_delete', [$this, 'user_post_delete'], 30 );
		
		add_action( 'usam_update_post_rating', [$this, 'update_post_rating'], 30, 5 );
		
		add_action( 'usam_review_update', [$this, 'review_update'], 30, 2 );
		add_action( 'usam_review_insert', [$this, 'review_insert'], 30 );
	}
	//comment
	function review_update( $t, $changed_data ) 
	{
		$data = $t->get_data();	
		if( isset($changed_data['rating']) )
		{	
			if ( $data['page_id'] )
				usam_update_post_rating($data['page_id'], $data['rating'], $changed_data['rating'] );	
		}
		if( isset($changed_data['rating']) || isset($changed_data['status']) )
		{			
			global $wpdb;			
			$count = (int)$wpdb->get_var( "SELECT COUNT(*) FROM ".USAM_TABLE_CUSTOMER_REVIEWS."  WHERE rating='{$data['rating']}' AND page_id='{$data['page_id']}' AND status=2" );
			usam_update_post_meta( $data['page_id'], 'rating_count_'.$data['rating'], $count );
			if( isset($changed_data['rating']) )
			{
				$count = (int)$wpdb->get_var( "SELECT COUNT(*) FROM ".USAM_TABLE_CUSTOMER_REVIEWS."  WHERE rating='{$changed_data['rating']}' AND page_id='{$data['page_id']}' AND status=2" );
				usam_update_post_meta( $data['page_id'], 'rating_count_'.$changed_data['rating'], $count );
			}
		}
		if( isset($changed_data['page_id']) || isset($changed_data['status']) )
		{
			$comment = $db = usam_get_post_meta($data['page_id'], 'comment');
			if( isset($changed_data['status']) && isset($changed_data['page_id'])  )
			{
				if( $data['status'] == 2 )
					$comment++;				
			}
			elseif( isset($changed_data['page_id'])  )
			{			
				if( $data['page_id'] )
					$comment++;
			}
			elseif( isset($changed_data['status']) )
			{		
				if( $data['status'] === 2 )
					$comment++;		
				elseif( $changed_data['status'] === 2 )
					$comment--;		
			}
			if( $comment !== $db  )
				usam_update_post_meta( $data['page_id'], 'comment', $comment );
			if( !empty($changed_data['page_id']) && ($data['status'] === 2 || !empty($changed_data['status']) && $changed_data['status'] == 2) )
			{
				$comment = usam_get_post_meta($changed_data['page_id'], 'comment');
				$comment--;	
				usam_update_post_meta($changed_data['page_id'], 'comment', $comment);
			}				
		}
	}
	
	function review_insert( $t ) 
	{
		$data = $t->get_data();	
		if( !empty($data['page_id']) && $data['status'] === 2 )
		{
			$comment = usam_get_post_meta($data['page_id'], 'comment');
			$comment++;
			usam_update_post_meta($data['page_id'], 'comment', $comment);
		}
	}
	
	function update_post_rating( $product_id, $rating_new, $rating_count, $old_rating, $old_rating_count ) 
	{
		if( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			$seller = usam_get_seller_product( $product_id );			
		}	
	}

	function product_basket_insert( $product ) 
	{
		$counter = (int)usam_get_post_meta($product['product_id'], 'basket' );
		$counter += $product['quantity'];
		usam_update_post_meta($product['product_id'], 'basket', $counter);	
	}

	function product_basket_update( $product, $changed_data, $t ) 
	{
		if ( isset($changed_data['quantity']) )
		{
			$counter = (int)usam_get_post_meta($product['product_id'], 'basket' );
			$counter += $product['quantity'] - $changed_data['quantity'];
			usam_update_post_meta($product['product_id'], 'basket', $counter);	
		}
	}

	function product_basket_delete( $product ) 
	{
		$counter = (int)usam_get_post_meta($product->product_id, 'basket' );
		$counter -= $product->quantity;
		usam_update_post_meta($product->product_id, 'basket', $counter);	
	}
		
	function update_order_status( $order_id, $status, $old_status, $purchase_log ) 
	{
		if ( $purchase_log->is_closed_order() )
		{
			$products = usam_get_products_order( $order_id );
			foreach( $products as $product )
			{
				$counter = (int)usam_get_post_meta($product->product_id, 'purchased' );
				$counter += $product->quantity;
				usam_update_post_meta($product->product_id, 'purchased', $counter);	
			}
		}
		elseif ( $old_status == 'closed' )
		{
			$products = usam_get_products_order( $order_id );
			foreach( $products as $product )
			{
				$counter = (int)usam_get_post_meta($product->product_id, 'purchased' );
				$counter -= $product->quantity;
				usam_update_post_meta($product->product_id, 'purchased', $counter);	
			}
		}
	}

	function order_products_delete( $products ) 
	{
		if ( !empty($products) )
		{
			if ( !is_array($products) )
				$products = [ $products ];	
			foreach( $products as $product )
			{
				$counter = (int)usam_get_post_meta($product->product_id, 'purchased' );
				$counter -= $product->quantity;
				usam_update_post_meta($product->product_id, 'purchased', $counter);	
			}
		}
	}

	function user_post_insert( $t ) 
	{
		$data = $t->get_data();	
		
		$counter = (int)usam_get_post_meta($data['product_id'], $data['user_list'] );
		$counter++;
		usam_update_post_meta($data['product_id'], $data['user_list'], $counter);	
		
		$counter = (int)usam_get_contact_metadata($data['contact_id'], $data['user_list'] );
		$counter++;
		usam_update_contact_metadata($data['contact_id'], $data['user_list'], $counter);	
	}
	
	function user_post_delete( $data ) 
	{ 		
		$counter = (int)usam_get_post_meta($data['product_id'], $data['user_list'] );
		$counter--;
		if ( $counter >= 0 )
			usam_update_post_meta($data['product_id'], $data['user_list'], $counter);	
		
		$counter = (int)usam_get_contact_metadata($data['contact_id'], $data['user_list'] );
		$counter--;
		if ( $counter >= 0 )
			usam_update_contact_metadata($data['contact_id'], $data['user_list'], $counter);
	}	
}
new USAM_Product_Counters();
?>