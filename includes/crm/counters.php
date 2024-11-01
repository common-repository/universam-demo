<?php
class USAM_Counters
{
	public function __construct()
	{		
	//	add_action( 'usam_review_insert', [__CLASS__, 'review_insert'], 30 );	
	//	add_action( 'usam_review_update', [__CLASS__, 'review_update'], 30, 2 );		
	//	add_action( 'usam_review_before_delete', [__CLASS__, 'review_delete'], 30 );
		
		add_action( 'usam_notification_insert', [__CLASS__, 'notification_insert'], 30 );	
		add_action( 'usam_notification_update', [__CLASS__, 'notification_update'], 30 );		
		add_action( 'usam_notification_delete', [__CLASS__, 'notification_delete'], 30 );
		
		add_action( 'usam_user_seller_insert', [__CLASS__, 'user_seller_insert'], 30 );	
		add_action( 'usam_user_seller_delete', [__CLASS__, 'user_seller_delete'], 30 );
		
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			add_action( 'usam_insert_product', [__CLASS__, 'insert_product'], 30, 3 );
			add_action('after_delete_post ',  [__CLASS__, 'delete_product'], 30, 2);
			add_action( 'transition_post_status', [__CLASS__, 'transition_post_status'], 30, 3 );
		}
	}
	
	public static function transition_post_status( $new_status, $old_status, $post )
	{		
		if ( $post->post_type == 'usam-product' && ($old_status == 'publish' || $new_status == 'publish') )
		{
			$seller_id = usam_get_product_meta( $post->ID, 'seller_id' );
			if ( $seller_id )
				usam_update_counter_sellers_products_quantity( $seller_id );
		}
	}
		
	public static function delete_product( $product_id, $post ) 
	{
		if ( $post->post_status == 'publish' )
		{			
			$seller_id = usam_get_id_seller( $post->post_author ); //Товар уже удален
			if ( $seller_id )
				usam_update_counter_sellers_products_quantity( $seller_id );		
		}
	}
		
	public static function insert_product( $product_id, $data, $attributes ) 
	{
		if ( $data['post_status'] == 'publish' )
		{
			$seller_id = usam_get_product_meta( $product_id, 'seller_id' );
			if ( $seller_id )
				usam_update_counter_sellers_products_quantity( $seller_id );		
		}
	}
	
	private static function aggregate_rating( $page_id ) 
	{
		global $wpdb;
		$aggregate_rating = $wpdb->get_var("SELECT AVG(rating) AS `aggregate_rating` FROM `".USAM_TABLE_CUSTOMER_REVIEWS."` WHERE `page_id`=$page_id AND `status`=2");			
		usam_update_post_meta($page_id, 'average_review_rating', $aggregate_rating);	
	}
	
	public static function review_insert( $t ) 
	{
		if ( $t->get('status') == 2 && $t->get('page_id') )
		{
			$counter = (int)usam_get_post_meta( $t->get('page_id'), 'total_reviews' );
			$counter++;
			usam_update_post_meta($t->get('page_id'), 'total_reviews', $counter);	
			self::aggregate_rating( $t->get('page_id') );
		}
	}
	
	public static function review_update( $t, $changed_data ) 
	{	
		if ( (isset($changed_data['status']) || isset($changed_data['page_id'])) && $t->get('page_id') )
		{
			$counter = (int)usam_get_customer_reviews(['fields' => 'count', 'number' => 1, 'status' => 2, 'page_id' => $t->get('page_id')]);
			usam_update_post_meta($t->get('page_id'), 'total_reviews', $counter);	
			self::aggregate_rating( $t->get('page_id') );
		}
		if ( !empty($changed_data['page_id']) )
		{
			$counter = (int)usam_get_customer_reviews(['fields' => 'count', 'number' => 1, 'status' => 2, 'page_id' => $changed_data['page_id']]);
			usam_update_post_meta($changed_data['page_id'], 'total_reviews', $counter);	
			self::aggregate_rating( $changed_data['page_id'] );
		}
	}	

	public static function review_delete( $data ) 
	{
		if ( $data['status'] == 2 && $data['page_id'] )
		{
			$counter = (int)usam_get_post_meta( $data['page_id'], 'total_reviews' );
			$counter--;
			usam_update_post_meta($data['page_id'], 'total_reviews', $counter);	
			self::aggregate_rating( $data['page_id'] );
		}
	}

	public static function notification_insert( $t ) 
	{
		$user_id = $t->get('user_id');
		$contact = usam_get_contact($user_id, 'user_id');
		$counter = usam_get_contact_metadata( $contact['id'], 'unread_notifications' );
		$counter++;
		usam_update_contact_metadata( $contact['id'], 'unread_notifications', $counter );
	}
	
	public static function notification_update( $t ) 
	{
		$status = $t->get('status');		
		$user_id = $t->get('user_id');		
		require_once(USAM_FILE_PATH.'/includes/crm/notifications_query.class.php');
		$counter = usam_get_notifications(['status' => 'started', 'fields' => 'count', 'author' => $user_id, 'number' => 1]);	
		$contact = usam_get_contact($user_id, 'user_id');
		usam_update_contact_metadata( $contact['id'], 'unread_notifications', $counter );	
	}	

	public static function notification_delete( $data ) 
	{
		if( $data['status'] == 'started' )
		{
			$contact = usam_get_contact($data['user_id'], 'user_id');
			$counter = usam_get_contact_metadata( $contact['id'], 'unread_notifications' );
			$counter--;
			usam_update_contact_metadata( $contact['id'], 'unread_notifications', $counter );	
		}
	}
	
	public static function user_seller_insert( $t ) 
	{
		$contact_id = $t->get('contact_id');
		$counter = usam_get_contact_metadata( $contact_id, 'sellers_counter' );
		$counter++;
		usam_update_contact_metadata( $contact_id, 'sellers_counter', $counter );
	}	

	public static function user_seller_delete( $data ) 
	{		
		$counter = usam_get_contact_metadata( $data['contact_id'], 'sellers_counter' );
		$counter--;
		usam_update_contact_metadata( $data['contact_id'], 'sellers_counter', $counter );	
	}
}
new USAM_Counters();
?>