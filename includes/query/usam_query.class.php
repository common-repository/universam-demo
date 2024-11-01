<?php
/**
 * Основные запрос в базу данных для шаблона
 */
USAM_Query::init();
final class USAM_Query
{	
	private static $instance = null;
	private static $terms_ids = array();	
	private static $dictionary_search = false;
	private static $meta_sku = false;	
		
    public static function get_instance() 
	{
       if ( is_null( self::$instance ) )
			self::$instance = new self(); 
		return self::$instance;
    }
	
	public static function init() 
	{ 		
		add_action( 'posts_clauses', array(USAM_Query::get_instance(), 'posts_clauses' ), 20, 2 );
		add_filter( 'posts_search', array(USAM_Query::get_instance(), 'search_by_site'), 20, 2 );
		add_filter( 'posts_join', array(USAM_Query::get_instance(), 'posts_join'), 20, 2 );
		add_filter( 'posts_results', array(USAM_Query::get_instance(), 'posts_results'), 20, 2 );
		add_filter( 'posts_fields', array(USAM_Query::get_instance(), 'fields_sql'), 8 , 2 );
	}
	
	public function fields_sql($sql, $wp_query = null)
	{
		if ( isset($wp_query->query_vars['fields']) )
		{
			switch( $wp_query->query_vars['fields'] ) 
			{
				case 'attribute_variant' :
					$sql = "v.id, v.attribute_id, v.code, v.value";
				break;				
			}
		}
		return $sql;
	}
	
	function posts_clauses( $clauses, $wp_query ) 
	{ 		
		global $wpdb;			
		if ( usam_is_multisite() && !is_main_site() )
			$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_posts_multisite')." AS multi ON ($wpdb->posts.ID = multi.multisite_post_id)";
		
		$primary_table = USAM_Query::get_primary_table();
		if ( isset($_REQUEST['f']) && empty($wp_query->query['pagename']) && (empty($wp_query->query['post_type']) || $wp_query->query['post_type'] == 'usam-product') && empty($wp_query->query['nopaging']) && (!isset($wp_query->query['posts_per_page']) || $wp_query->query['posts_per_page'] != 1) )
		{ 
			$select_filters = $_REQUEST['f']; 	
			if ( !is_array($select_filters) )
			{
				$select_filters = usam_url_array_decode($select_filters);				
			}				
			if ( is_array($select_filters) )
			{	
				update_meta_cache( 'term', array_keys($select_filters) );
				foreach ( $select_filters as $attribute_id => $value )
				{ 		
					if ( $value !== '' )
					{	
						if ( is_string($value) )
							$value = explode( '-', $value );	
						$clauses['join'] .= " INNER JOIN ".usam_get_table_db('product_filters')." AS f_{$attribute_id} ON ($wpdb->posts.ID = f_{$attribute_id}.product_id)"; 
						$type = usam_get_term_metadata($attribute_id, 'field_type');	
						if ( $type == 'O' ||  $type == 'N' )
						{									
							if ( !empty($value[0]) )
								$clauses['where'] .= " AND CAST(fv_{$attribute_id}.value AS SIGNED)>=".usam_string_to_float($value[0]);
							if ( !empty($value[1]) )
								$clauses['where'] .= " AND CAST(fv_{$attribute_id}.value AS SIGNED)<=".usam_string_to_float($value[1]);
							$clauses['join'] .= " INNER JOIN ".usam_get_table_db('product_attribute_options')." AS fv_{$attribute_id} ON (f_{$attribute_id}.filter_id = fv_{$attribute_id}.id)";
						}																		
						elseif ( $type == 'C' )
						{ 
							$clauses['where'] .= " AND fv_{$attribute_id}.value IN ('".implode("','",$value)."')";								
							$clauses['join'] .= " INNER JOIN ".usam_get_table_db('product_attribute_options')." AS fv_{$attribute_id} ON (f_{$attribute_id}.filter_id = fv_{$attribute_id}.id AND fv_{$attribute_id}.attribute_id={$attribute_id})";
						}
						else
						{									
							$clauses['where'] .= " AND f_{$attribute_id}.filter_id IN (".implode(',',array_map('intval', $value)).")";
						}
					}
				}
				$clauses['distinct'] = "DISTINCT";				
			}			
		}				
		if ( !empty($wp_query->query_vars['discount']) )
		{
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." AS dr ON ($primary_table.ID = dr.product_id)";
			$clauses['where'] .= " AND dr.discount_id IN (".implode(',',$wp_query->query_vars['discount']).")";
			$clauses['distinct'] = 'DISTINCT';
		}		
		if ( empty($wp_query->query_vars['price_meta_query']) )
		{
			$wp_query->query_vars['price_meta_query'] = [];
			if ( !empty($wp_query->query_vars['from_price']) )
			{					
				$type_price = !empty($wp_query->query_vars['type_price'])?$wp_query->query_vars['type_price']:usam_get_customer_price_code();
				$wp_query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => (float)$wp_query->query_vars['from_price'], 'compare' => '>=', 'type' => 'DECIMAL'];
				unset($wp_query->query_vars['from_price']);
			}		
			if ( !empty($wp_query->query_vars['to_price']) )
			{
				$type_price = !empty($wp_query->query_vars['type_price'])?$wp_query->query_vars['type_price']:usam_get_customer_price_code();
				$wp_query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => (float)$wp_query->query_vars['to_price'], 'compare' => '<=', 'type' => 'DECIMAL'];
				unset($wp_query->query_vars['to_price']);
			}
		}	
		if ( !empty($wp_query->query_vars['from_stock']) )
		{
			$wp_query->query_vars['stock_meta_query'][] = ['key' => 'stock', 'value' => (float)$wp_query->query_vars['from_stock'], 'compare' => '>=', 'type' => 'DECIMAL'];
			unset($wp_query->query_vars['from_stock']);
		}
		if ( !empty($wp_query->query_vars['to_stock']) )
		{
			$wp_query->query_vars['stock_meta_query'][] = ['key' => 'stock', 'value' => (float)$wp_query->query_vars['to_stock'], 'compare' => '<=', 'type' => 'DECIMAL'];	
			unset($wp_query->query_vars['to_stock']);		
		}
		if ( !empty($wp_query->query_vars['to_total_balance']) )
		{
			$wp_query->query_vars['stock_meta_query'][] = ['key' => 'total_balance', 'value' => (float)$wp_query->query_vars['to_total_balance'], 'compare' => '<=', 'type' => 'DECIMAL'];	
			unset($wp_query->query_vars['to_total_balance']);		
		}
		if ( !empty($wp_query->query_vars['from_total_balance']) )
		{
			$wp_query->query_vars['stock_meta_query'][] = ['key' => 'total_balance', 'value' => (float)$wp_query->query_vars['from_total_balance'], 'compare' => '>=', 'type' => 'DECIMAL'];
			unset($wp_query->query_vars['from_total_balance']);
		}				
		if ( !empty($wp_query->query_vars['s']) )
		{
			$clauses['distinct'] = "DISTINCT";
		}		
		if ( isset($wp_query->query_vars['associated_product']) )
		{						
			foreach( $wp_query->query_vars['associated_product'] as $item )
			{
				if ( !empty($item['list']) )
				{
					$clauses['join'] .= " INNER JOIN ".USAM_TABLE_ASSOCIATED_PRODUCTS." AS ".$item['list']." ON ($primary_table.ID = ".$item['list'].".associated_id)";
					$clauses['where'] .= " AND ".$item['list'].".list='".$item['list']."'";
					if ( !empty($item['product_id']) )
					{
						$ids = (array)$item['product_id'];
						$clauses['where'] .= " AND ".$item['list'].".product_id IN (".implode(',', $ids).")";
					}
				}
			}
			$clauses['groupby'] = '';
			$clauses['distinct'] = "DISTINCT";			
		} 
		if ( isset($wp_query->query_vars['purchased']) )
		{						
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_PRODUCTS_ORDER." AS p_order ON ($primary_table.ID = p_order.product_id)";
			$clauses['where'] .= " AND YEAR(p_order.date_insert) = YEAR(NOW())";
			$clauses['groupby'] = '';
			$clauses['distinct'] = "DISTINCT";
		}
		if ( isset($wp_query->query_vars['sets']) )
		{						
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_PRODUCTS_SETS." AS sets ON ($primary_table.ID = sets.product_id)";
			$clauses['where'] .= " AND sets.set_id IN (".implode(',', $wp_query->query_vars['sets']).")";
			$clauses['groupby'] = '';
			$clauses['distinct'] = "DISTINCT";
		}		
		if ( isset($wp_query->query_vars['leaders_sells_month']) )
		{						
			require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
			$ids = usam_get_products_order_query(['fields' => 'product_id', 'order' => 'DESC', 'groupby' => 'product_id', 'orderby' => 'amount_quantity', 'number' => 50, 'date_query' => [['after' => date( "Y-m-d H:i:s",strtotime("-30 day"))]]]);
			if ( $ids )
			{
				$results = $wpdb->get_results( "SELECT ID, post_parent FROM ".$wpdb->posts." WHERE ID IN (".implode(',', $ids).")" );
				$ids = [];
				foreach( $results as $result )
					$ids[] = $result->post_parent ? $result->post_parent : $result->ID;
				if ( $ids )
					$clauses['where'] .= " AND ID IN (".implode(',', $ids).")";
			}
			if ( empty($ids) )
				$clauses['where'] .= " AND ID=0";
		}			
		if ( isset($wp_query->query_vars['user_list']) )
		{		
			if ( is_string($wp_query->query_vars['user_list']) )
				$wp_query->query_vars['user_list'] = ['list' => $wp_query->query_vars['user_list']];
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_USER_POSTS." AS userlist ON ($primary_table.ID = userlist.product_id)";
			if ( isset($wp_query->query_vars['user_list']['list']) )
				$clauses['where'] .= " AND userlist.user_list='".$wp_query->query_vars['user_list']['list']."'";
			if ( isset($wp_query->query_vars['user_list']['contact_id']) )
				$clauses['where'] .= " AND userlist.contact_id='".$wp_query->query_vars['user_list']['contact_id']."'";
			$clauses['groupby'] = '';
			$clauses['distinct'] = "DISTINCT";
		}			
		if ( isset($wp_query->query_vars['in_stock']) )
		{					
			$select_products = get_option('usam_types_products_sold', array( 'product', 'services' ));
			if ( in_array('product', $select_products) || in_array('services', $select_products) )
			{// Работа с остатками только для товаров и услуг	
				$code = usam_get_customer_balance_code();		
				$clauses['join'] .= " INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON ($primary_table.ID = stock.product_id)";
				$clauses['where'] .= " AND stock.meta_key='$code'";
				if ( $wp_query->query_vars['in_stock'] )
				{// Исключить проданный товар из показа								
					$clauses['where'] .= " AND stock.meta_value>0";
				}			
				$clauses['groupby'] = '';
				$clauses['distinct'] = "DISTINCT";
			}
		} 
		if ( !empty($wp_query->query_vars['postmeta_query']) )
		{
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $wp_query->query_vars['postmeta_query'] );	
			if ( !empty( $meta_query->queries ) ) 
			{
				$c = $meta_query->get_sql( 'post', USAM_TABLE_POST_META, $primary_table, 'ID' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where']; 

				if ( $meta_query->has_or_relation() ) {
					$clauses['distinct'] = 'DISTINCT';
				}
			}			
		}
		if ( !empty($wp_query->query_vars['productmeta_query']) )
		{ 	
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $wp_query->query_vars['productmeta_query'] );	
			if ( !empty($meta_query->queries) ) 
			{
				$c = $meta_query->get_sql( 'product', USAM_TABLE_PRODUCT_META, $primary_table, 'ID' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where']; 

				if ( $meta_query->has_or_relation() ) {
					$clauses['distinct'] = 'DISTINCT';
				}
			}			
		}
		if ( !empty($wp_query->query_vars['attributes_query']) )
		{
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $wp_query->query_vars['attributes_query'] );	
			if ( !empty($meta_query->queries) ) 
			{	
				$c = $meta_query->get_sql( 'product', usam_get_table_db('product_attribute'), $wpdb->posts, 'ID' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where'];
				if ( $meta_query->has_or_relation() ) {
					$clauses['distinct'] = 'DISTINCT';
				}
			}
		}			
		if ( !empty($wp_query->query_vars['price_meta_query']) )
		{
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $wp_query->query_vars['price_meta_query'] );	
			if ( !empty($meta_query->queries) ) 
			{
				$c = $meta_query->get_sql( 'product', USAM_TABLE_PRODUCT_PRICE, $primary_table, 'ID' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where']; 

				if ( $meta_query->has_or_relation() ) {
					$clauses['distinct'] = 'DISTINCT';
				}				
			}
		}
		if ( !empty($wp_query->query_vars['stock_meta_query']) )
		{
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $wp_query->query_vars['stock_meta_query'] );	
			if ( !empty($meta_query->queries) ) 
			{
				$c = $meta_query->get_sql( 'product', USAM_TABLE_STOCK_BALANCES, $primary_table, 'ID' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where']; 
				if ( $meta_query->has_or_relation() ) {
					$clauses['distinct'] = 'DISTINCT';
				}
			}
		} 
		require_once( USAM_FILE_PATH . '/includes/query/conditions_query.php' );
		$conditions_query = new USAM_Conditions_Query();
		$clauses['where'] .= $conditions_query->get_sql_clauses( $wp_query->query_vars, ['title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'name' => 'post_name'] );
		
		if ( !empty($wp_query->query_vars['orderby']) )
		{ 				
			if ( $wp_query->query_vars['orderby'] == 'predictive' && !empty($wp_query->query_vars['s']) )
			{ 
				$title = esc_sql( $wpdb->esc_like( trim( usam_remove_emoji($wp_query->query_vars['s'])) ) );
				$clauses['orderby'] = "$wpdb->posts.post_title NOT LIKE '{$title}%' ASC";
			}
			else
			{ 
				if ( !is_array($wp_query->query_vars['orderby']) )
					$sorts = array($wp_query->query_vars['orderby'] => $wp_query->query_vars['order']);
				else
					$sorts = $wp_query->query_vars['orderby'];
				$sort = [];			
				foreach ( $sorts as $orderby => $order )
				{
					switch ( $orderby ) 
					{
						case 'category' :						
							if ( stripos($clauses['join'], "{$wpdb->prefix}term_relationships ON") === false )
							{
								$clauses['join'] .= " INNER JOIN {$wpdb->prefix}term_relationships ON ($wpdb->posts.ID = {$wpdb->prefix}term_relationships.object_id)";
							}							
							if ( stripos($clauses['join'], "{$wpdb->prefix}term_taxonomy ON") === false )
							{
								$clauses['join'] .= " INNER JOIN $wpdb->term_taxonomy ON ({$wpdb->prefix}term_relationships.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id AND {$wpdb->term_taxonomy}.taxonomy = 'usam-category')";
							}
							if ( stripos($clauses['join'], "{$wpdb->prefix}terms ON") === false )
							{
								$clauses['join'] .= " INNER JOIN {$wpdb->terms} ON ({$wpdb->terms}.term_id={$wpdb->term_taxonomy}.term_id)";
							}
							$sort[] = "{$wpdb->terms}.name ".$order;		
						break;						
						case 'price' :
							if ( !isset($wp_query->query_vars['type_price']) )
								$wp_query->query_vars['type_price'] = usam_get_customer_price_code();											
						
							if ( stripos($clauses['where'], USAM_TABLE_PRODUCT_PRICE.'.product_id') !== false )
							{
								$sort[] = USAM_TABLE_PRODUCT_PRICE.".meta_value ".$order;		
							}
							else
							{ 
								if ( stripos($clauses['join'], USAM_TABLE_PRODUCT_PRICE." AS price") === false )
								{
									$clauses['join'] .= " INNER JOIN ".USAM_TABLE_PRODUCT_PRICE." AS price ON ($primary_table.ID = price.product_id)";
									$clauses['where'] .= " AND price.meta_key='price_".$wp_query->query_vars['type_price']."'";
								}				
								$sort[] = "CAST(price.meta_value AS DECIMAL) ".$order;
							}	
						break;
						case 'percent' :			
							if ( !isset($wp_query->query_vars['type_price']) )
								$wp_query->query_vars['type_price'] = usam_get_customer_price_code();
							if ( stripos($clauses['join'], USAM_TABLE_PRODUCT_PRICE." AS price") === false )
							{
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCT_PRICE." AS price ON ($primary_table.ID = price.product_id)";
								$clauses['where'] .= " AND price.meta_key='price_".$wp_query->query_vars['type_price']."'";
							}
							if ( stripos($clauses['where'], 'old_price.meta_key') === false )
							{
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCT_PRICE." AS old_price ON ($primary_table.ID = old_price.product_id)";
								$clauses['where'] .= " AND old_price.meta_key='old_price_".$wp_query->query_vars['type_price']."'";
							}
							$sort[] = "old_price.meta_value/price.meta_value ".$order;
						break;						
						case 'sku' :
						case 'weight' :	
						case 'date_externalproduct' :
							if ( stripos($clauses['join'], "LEFT JOIN ".USAM_TABLE_PRODUCT_META." AS pm_$orderby") === false )
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCT_META." AS pm_$orderby ON ($primary_table.ID = pm_$orderby.product_id)";
							$sort[] = "pm_$orderby.meta_value ".$order;
						break;	
						case 'sticky' :						
							if ( stripos($clauses['join'], "$primary_table.ID = userlist.product_id AND userlist.user_list='".$orderby."'") === false )
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_USER_POSTS." AS userlist ON ($primary_table.ID = userlist.product_id AND userlist.user_list='".$orderby."')";
							$sort[] = "CAST(userlist.user_list AS SIGNED) ".$order;
						break;
						case 'comment' :
						case 'desired' :
						case 'compare' :						
						case 'basket' :
						case 'views' :	
						case 'rating' :	
							if ( stripos($clauses['join'], "LEFT JOIN ".USAM_TABLE_POST_META." AS postmeta ON") === false )
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_POST_META." AS postmeta ON ($primary_table.ID = postmeta.post_id AND postmeta.meta_key='".$orderby."')";
							$sort[] = "CAST(postmeta.meta_value AS SIGNED) ".$order;
						break;
						case 'total_balance' :			
						case 'stock' :			
							if ( stripos($clauses['join'], "INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON") === false )
							{
								$clauses['join'] .= " INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON ($primary_table.ID = stock.product_id)";
								$clauses['where'] .= " AND stock.meta_key='".$orderby."'";
							}
							$sort[] = "CAST(stock.meta_value AS DECIMAL) ".$order;
						break;	
						case 'purchased' :
							if ( stripos($clauses['join'], 'p_order.product_id') === false )
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCTS_ORDER." AS p_order ON ($primary_table.ID = p_order.product_id)";
							$sort[] = "p_order.date_insert ".$order; 
						break;		
						case 'user_list' :						
							if ( stripos($clauses['join'], 'userlist.product_id') === false )
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_USER_POSTS." AS userlist ON ($primary_table.ID = userlist.product_id)";
							$sort[] = "userlist.user_list ".$order;
						break;
						case 'date' :	
						case 'post_date' :						
							$sort[] = "$wpdb->posts.post_date ".$order;
						break;	
						case 'modified' :	
							$sort[] = "$wpdb->posts.post_modified ".$order;
						break;							
						case 'title' :	
						case 'post_title' :						
							$sort[] = "$wpdb->posts.post_title ".$order;
						break;	
						case 'status' :	
						case 'post_status' :						
							$sort[] = "$wpdb->posts.post_status ".$order;
						break;						
						case 'rand' :						
							$sort[] = "rand()";
						break;
						case 'attribute_value_num' :	
						case 'attribute_value' :						
							if ( isset($wp_query->query_vars['attribute_key']) )
							{
								if ( stripos($clauses['where'], 'attribute_value.meta_key') === false )
								{
									$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('product_attribute')." AS attribute_value ON ($wpdb->posts.ID = attribute_value.product_id)";
									$clauses['where'] .= " AND attribute_value.meta_key='".$wp_query->query_vars['attribute_key']."'";
								}
								if ( $orderby == 'attribute_value' )
									$sort[] = "attribute_value.meta_value ".$order;
								else
									$sort[] = " CAST(attribute_value.meta_value AS NUMERIC) ".$order;
							}
						break;					
						case 'postmeta_value_num' :
						case 'postmeta_value' :	
							if ( isset($wp_query->query_vars['postmeta_key']) )
							{
								if ( stripos($clauses['where'], 'order_postmeta.meta_key') === false )
								{
									$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_POST_META." AS order_postmeta ON ($primary_table.ID = order_postmeta.post_id)";
									$clauses['where'] .= " AND order_postmeta.meta_key='".$wp_query->query_vars['postmeta_key']."'";
								}
								if ( $orderby == 'attribute_value' )
									$sort[] = "order_postmeta.meta_value ".$order;
								else
									$sort[] = " CAST(order_postmeta.meta_value AS SIGNED) ".$order;
							}
						break;						
						case 'productmeta_value_num' :
						case 'productmeta_value' :	
							if ( isset($wp_query->query_vars['productmeta_key']) )
							{
								if ( stripos($clauses['where'], 'order_productmeta.meta_key') === false )
								{
									$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCT_META." AS order_productmeta ON ($primary_table.ID = order_productmeta.product_id)";
									$clauses['where'] .= " AND order_productmeta.meta_key='".$wp_query->query_vars['productmeta_key']."'";
								}
								if ( $orderby == 'productmeta_value' )
									$sort[] = "order_productmeta.meta_value ".$order;
								else
									$sort[] = " CAST(order_productmeta.meta_value AS NUMERIC) ".$order;
							}
						break;
						default:							
							if ( isset($wp_query->query_vars['attributes_query']) && isset($wp_query->query_vars['attributes_query'][$orderby]) )
							{
								$i = 0;
								foreach ( $wp_query->query_vars['attributes_query'] as $key => $value )
								{								
									if ( $key == 'relation' )
										continue;									
									if ( $key == $orderby )
										break;
									$i++;
								}
								$i = $i?$i:'';
								$sort[] = usam_get_table_db('product_attribute')."$i.meta_value ".$order;	
							}								
							elseif ( stripos($orderby, 'storage_') !== false )
							{	
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_STOCK_BALANCES." AS ".$orderby." ON ($primary_table.ID = ".$orderby.".product_id)";
								$clauses['where'] .= " AND ".$orderby.".meta_key='".$orderby."'";
								$sort[] = "CAST({$orderby}.meta_value AS DECIMAL) $order";
							}	
							elseif ( stripos($orderby, 'competitor_') !== false )
							{	
								$id = str_replace('competitor_', '', $orderby);
								$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_PRODUCTS_COMPETITORS." AS ".$orderby." ON ($primary_table.ID = ".$orderby.".product_id)";
								$clauses['where'] .= " AND ".$orderby.".site_id='".$id."'";
								$sort[] = "CAST({$orderby}.current_price AS DECIMAL) $order";
							}					
						break;						
					}		
				}
				if ( !empty($sort) )
				{
					$clauses['orderby'] = implode(", ",$sort);						
				} 
			} 
		}	
		if ( isset($wp_query->query_vars['fields']) && $wp_query->query_vars['fields'] == 'attribute_variant' )
		{			
			$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('product_filters')." AS f ON ($primary_table.ID = f.product_id)";
			$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('product_attribute_options')." AS v ON (f.filter_id = v.id)";		
			$clauses['orderby'] = "v.value ASC";		
			$clauses['distinct'] = "";
		}
		return $clauses;
	}
	
	public function posts_join( $sql, $wp_query ) 
	{	
		global $wpdb;		
		if ( !empty($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] == 'usam-product' )
		{
			if ( !empty($wp_query->query_vars['s']) )
			{	
				$s = esc_sql( $wpdb->esc_like( trim($wp_query->query_vars['s']) ) );			
				if ( !empty(self::$terms_ids) )
				{	
					$sql .= " LEFT JOIN $wpdb->term_relationships AS tr ON ($wpdb->posts.ID = tr.object_id)";
					$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";		
				}
				if ( self::$dictionary_search )
					$sql .= " LEFT JOIN ".usam_get_table_db('posts_search')." AS p_search ON ($wpdb->posts.ID = p_search.post_search_id)";	
				if ( self::$meta_sku )
					$sql .= " LEFT JOIN ". USAM_TABLE_PRODUCT_META." AS metasku ON ($wpdb->posts.ID = metasku.product_id AND metasku.meta_key='sku')";		
			}	
		}
		return $sql;	
	}
	
	public static function get_id_excludes() 
	{
		$exclude_products = get_option('usam_search_exclude_products', '');
		if (is_array($exclude_products))
			$exclude_products = implode(",", $exclude_products);
		return $exclude_products;
	}
	
	public static function get_product_id_by_sku( $s )
	{
		static $query = null, $result = null;
		if ( $result === null || $query !== $s )
			$result = (int)usam_get_product_id_by_sku( $s );
		$query = $s;		
		return $result;
	}
	
	public static function get_brand( $s )
	{
		static $query = null, $result = null;
		if ( $result === null || $query !== $s )
			$result = get_term_by('name', $s, 'usam-brands');
		$query = $s;
		return $result;
	}
		
	public static function get_category( $s )
	{
		static $query = null, $result = null;
		if ( $result === null || $query !== $s )
			$result = get_term_by( 'name', $s, 'usam-category' );
		$query = $s;
		return $result;
	}
	
	public static function get_primary_table( )
	{
		if ( usam_is_multisite() && !is_main_site() )
			return 'multi';
		else
		{
			global $wpdb;
			return $wpdb->posts;
		}
	}
	
	public function search_by_site( $search, $wp_query )
	{ 
		global $wpdb;	
		$q = $wp_query->query_vars;
		if ( empty($search) || !isset($wp_query->query_vars['s']) )
			return $search; // пропустить обработку - нет поиска в запросе			
		
		if ( !empty($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] == 'usam-product' )
		{		
		//	$id_excludes = USAM_Query::get_id_excludes();	
			
			$default = ['sku' => '=', 'barcode' => 1, 'post_content' => 0, 'post_excerpt' => 0];
			$search_parent = get_option('usam_search_product_property', []);			
			$search_parent = array_merge( $default, $search_parent );
			$primary_table = USAM_Query::get_primary_table();
			
			self::$terms_ids = [];			
			$search = '';		
			$s = esc_sql( $wpdb->esc_like( trim( usam_remove_emoji($wp_query->query_vars['s'])) ) );
			if (strripos($s, ' ') === false) 
			{		
				if ( $search_parent['sku'] === '=' )
				{
					$product_id = USAM_Query::get_product_id_by_sku( $s );
					if ( $product_id )			
						return " AND ($primary_table.ID=$product_id)";
				}				
				if ( $search_parent['barcode'] && is_numeric($s) )
				{
					$product_id = usam_get_product_id_by_meta( 'barcode', $s );
					if ( $product_id )		
						return " AND ($primary_table.ID=$product_id)";
				}
				if ( $search_parent['sku'] === 'like' )
				{										
					$search .= "metasku.meta_value LIKE LOWER ('%{$s}%') OR ";
					self::$meta_sku = true;
				}
			}
			$pattern = ['(', ')', '[', ']', '"', '\'', '\\']; 
			$rlike = str_replace($pattern, '', $s);	
			$search .= "$wpdb->posts.post_title LIKE LOWER ('{$s}%')";
			if ( $search_parent['post_content'] )
				$search .= " OR $wpdb->posts.post_content LIKE LOWER ('%{$s}%')";
			if ( $search_parent['post_excerpt'] )
				$search .= " OR $wpdb->posts.post_excerpt LIKE LOWER ('%{$s}%')";	
			if ( strripos($s, ' ') === false )
				$search .= " OR $wpdb->posts.post_title LIKE LOWER ('% {$s}%') OR $wpdb->posts.post_title RLIKE LOWER (' [(«\"]{$rlike}')";			
			$switcher = usam_switcher( $s );
			if ( $switcher != $s && !preg_match('/[+#!.,\?]/', $switcher) )
			{
				$rlike = str_replace($pattern, '', $switcher);	
				$search .= "OR $wpdb->posts.post_title LIKE LOWER ('{$switcher}%')";
				if ( strripos($s, ' ') === false )
					$search .= " OR $wpdb->posts.post_title LIKE LOWER ('% {$switcher}%') OR $wpdb->posts.post_title RLIKE LOWER (' [(«\"]{$rlike}')";
			}
			$search_terms = explode(' ', $s);			
			if ( !empty($search_terms) && count($search_terms) > 1 )
			{
				$variants = [];
				foreach	( $search_terms as $term ) 
				{		
					if ( strlen($term) > 3 )
					{
						$rlike = str_replace($pattern, '', $term);
						$variants[] = "($wpdb->posts.post_title LIKE LOWER ('{$term}%') OR $wpdb->posts.post_title LIKE LOWER ('% {$term}%') OR $wpdb->posts.post_title RLIKE LOWER (' [(«\"]{$rlike}'))";
					}
				}	
				if ( !empty($variants) )
					$search .= " OR ".implode(" AND ", $variants); 
			}
			$term = USAM_Query::get_brand( $s );
			if ( !empty($term) )
				self::$terms_ids[] = $term->term_id;
			elseif ( empty($_REQUEST['cat_id']) )
			{				
				$term = USAM_Query::get_category( $s );
				if ( !empty($term) )
					self::$terms_ids[] = $term->term_id;	
			}
			if ( !empty(self::$terms_ids) )
				$search .= " OR ( tt.term_id IN (".implode(',',self::$terms_ids).") )";
			else
			{
				$search .= " OR p_search.meta_value LIKE LOWER ('{$s}%')";
				self::$dictionary_search = true;
			} 
			if ( is_admin() && is_numeric($wp_query->query_vars['s']) )
				$search .= " OR $wpdb->posts.ID=".$wp_query->query_vars['s'];	
			
			$search = "AND ($search)";	
			if ( !empty($_REQUEST['cat_id']) )
			{	// Искать только в этой категории		
				$cat_id = absint($_REQUEST['cat_id']);
				self::$terms_ids[] = $cat_id ;	
				$search = " AND tt.term_id=$cat_id ".$search;					
			}	
		}
		return $search;
	}
	
	public function posts_results( $posts, $wp_query ) 
	{	
		global $wpdb;		
		$user_id = get_current_user_id();			
		if ( !empty($wp_query->query_vars['s']) && ( $user_id == 0 || usam_check_current_user_role('subscriber') ) )
		{ 
			require_once( USAM_FILE_PATH . '/includes/search/search_query.class.php' );
			remove_filter( 'posts_results', array(USAM_Query::get_instance(), 'posts_results'), 500, 2 );
			
			$s = esc_sql( $wpdb->esc_like( trim( usam_remove_emoji($wp_query->query_vars['s']) ) ) );	
			if ( mb_strlen($s) > 3 )
			{
				$contact_id = usam_get_contact_id( );	
				$number_results = count($posts);				
				$data = ['phrase' => $s, 'number_results' => $number_results, 'contact_id' => $contact_id, 'date_insert' => date( "Y-m-d H:i:s" )];			
				$today = getdate();			
				$searching_results = usam_get_searching_results(['contact' => $contact_id, 'year' => $today['year'], 'month' => $today['month'], 'day' => $today['mday'], 'hour' => $today['hours'], 'order' => 'DESC']);	
				$insert = true;									
				if ( !empty($searching_results) )
				{				
					foreach	( $searching_results as $searching_result ) 
					{
						if ( stripos( $s, $searching_result->phrase ) !== false )
						{
							$wpdb->update( USAM_TABLE_SEARCHING_RESULTS, $data, array('id' => $searching_result->id ), array( '%s', '%d', '%s' ) );	
							$insert = false;
							break;
						}		
						elseif ( stripos( $searching_result->phrase, $s) !== false )
						{							
							$insert = false;
							break;
						}							
					}
				}
				if ( $insert )
				{
					$wpdb->insert( USAM_TABLE_SEARCHING_RESULTS, $data, ['%s', '%d', '%d', '%s']);				
				}
			}			
		}
		$ids = [];
		foreach	( $posts as $post ) 
		{
			if ( isset($post->ID) )
				$ids[] = $post->ID;
			elseif ( is_numeric($post) )
				$ids[] = $post;
		}
		if ( $ids )
		{
			if ( isset($wp_query->query_vars['product_attribute_cache']) && $wp_query->query_vars['product_attribute_cache'] )
			{
				usam_update_cache( $ids, [usam_get_table_db('product_attribute') => 'product_attribute'], 'product_id' );
			}			
			if ( usam_is_multisite() && !is_main_site() )
				$ids = $wpdb->get_col("SELECT ID FROM ".usam_get_table_db('linking_posts_multisite')." WHERE multisite_post_id IN (".implode(",",$ids).")");	
			if ( $ids )
			{
				if( isset($wp_query->query_vars['product_meta_cache']) && $wp_query->query_vars['product_meta_cache'] )
				{ 
					usam_update_cache( $ids, [USAM_TABLE_PRODUCT_META => 'product_meta'], 'product_id' );
				}			
				if( isset($wp_query->query_vars['post_meta_cache']) && $wp_query->query_vars['post_meta_cache'] )
				{
					usam_update_cache( $ids, [USAM_TABLE_POST_META => 'post_meta'], 'post_id' );
				}			
				if( isset($wp_query->query_vars['stocks_cache']) && $wp_query->query_vars['stocks_cache'] )
				{			
					usam_update_cache( $ids, [USAM_TABLE_STOCK_BALANCES => 'product_stock'], 'product_id' );
				}	
				if( isset($wp_query->query_vars['prices_cache']) && $wp_query->query_vars['prices_cache'] )
				{
					usam_update_cache( $ids, [USAM_TABLE_PRODUCT_PRICE => 'product_price'], 'product_id' );
				}				
				if( isset($wp_query->query_vars['discount_cache']) && $wp_query->query_vars['discount_cache'] )
				{				
					$discounts = usam_cache_current_product_discount( $ids );
					$rule_ids = array();					
					foreach ( $discounts as $codes_price )
					{
						foreach ( $codes_price as $discount_ids )
							$rule_ids = array_merge($rule_ids, $discount_ids);
					}	
					$rule_ids = array_unique($rule_ids);
					usam_update_cache( $rule_ids, [USAM_TABLE_DISCOUNT_RULE_META => 'rule_meta'], 'rule_id' );	
				}				
				if( isset($wp_query->query_vars['product_images_cache']) && $wp_query->query_vars['product_images_cache'] )
				{
					$attachments = (array)get_posts(['post_type' => 'attachment', 'post_status' => 'all', 'post_parent__in' => $ids, 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => -1]);
					if ( !empty($attachments) )	
					{ 					
						$cache = array();
						foreach	( $attachments as $attachment ) 
						{
							$cache[$attachment->post_parent][] = $attachment;
						}			
						foreach	( $ids as $id ) 
						{
							if ( !isset($cache[$id]) )
								$cache[$id] = array();	
							wp_cache_set( $id, $cache[$id], 'usam_product_images' );
						}
					}		
				}					
				if( isset($wp_query->query_vars['user_list_cache']) && $wp_query->query_vars['user_list_cache'] )
				{
					require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
					$products = usam_get_user_posts(['product_id' => $ids]);
					$cache = array();
					foreach ( $products as $product ) 
					{
						$cache[$product->product_id][] = $product;				
					}		
					foreach ( $ids as $id )
					{
						if ( !isset($cache[$id]) )
							$cache[$id] = array();
						wp_cache_set( $id, $cache[$id], 'usam_user_product_list' );
					}				
				}
			}			
		}
		return $posts;
	}
}
?>