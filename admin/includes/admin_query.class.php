<?php 
new USAM_Query_Admin();
final class USAM_Query_Admin
{	
	public function __construct() 
	{		
		add_filter( 'pre_get_posts', ['USAM_Query_Admin', 'product_meta_filter'], 8);
		add_filter( 'posts_join', ['USAM_Query_Admin', 'posts_join'], 50, 2);		
		add_filter( 'posts_fields', ['USAM_Query_Admin', 'fields_sql'], 8 , 2);		
	}	
	
	public static function fields_sql($sql, $wp_query = null)
	{
		global $wpdb;
		if ( !empty($wp_query->query['products_internet']) )
		{ 
			$sql = "$wpdb->posts.ID,$wpdb->posts.post_title,$wpdb->posts.post_status";				
			$sql .= ",pi.foto_url AS product_internet_foto_url ";	
			$sql .= ",pi.source AS product_internet_source ";	
			$sql .= ",pi.description AS product_internet_description ";	
			$sql .= ",pi.likes AS product_internet_likes ";				
			$sql .= ",pi.comments AS product_internet_comments ";	
			$sql .= ",pi.status AS product_internet_status";				
			$sql .= ",pi.id AS product_internet_id ";				
		}
		return $sql;
	}
	
	public static function product_meta_filter( $query )
	{
		global $wpdb;	
		if( !usam_check_current_user_role('administrator') && (current_user_can('seller_company') || current_user_can('seller_contact')) )
			$query->query_vars['author'] = get_current_user_id();			
		if ( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'usam-product' && !empty($_REQUEST) ) 
		{
			require_once( USAM_FILE_PATH.'/admin/includes/admin_product_query.class.php' );
			$query->query_vars = USAM_Admin_Product_Query::get_filter( $query->query_vars );
			remove_filter( 'pre_get_posts', ['USAM_Query_Admin', 'product_meta_filter'], 8 );
		}			
	}
	
	public static function posts_join( $sql, $wp_query ) 
	{
		global $wpdb;		
		if ( !empty($wp_query->query_vars['products_internet']) )
		{								
			$sql .= " INNER JOIN ".USAM_TABLE_PRODUCTS_ON_INTERNET." AS pi ON ($wpdb->posts.ID = pi.product_id)";	
		}	
		return $sql;	
	}
}
?>