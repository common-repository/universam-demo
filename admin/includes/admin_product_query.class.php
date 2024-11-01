<?php 
final class USAM_Admin_Product_Query
{			
	public static function get_filter( $query_vars = [] )
	{
		global $wpdb;	
		if( !usam_check_current_user_role('administrator') && (current_user_can('seller_company') || current_user_can('seller_contact')) )
			$query_vars['author'] = get_current_user_id();
		
		require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
		$f = new Filter_Processing();
		$type_price = usam_get_manager_type_price();		
		
		if ( !isset($query_vars['meta_query']) )
			$query_vars['meta_query'] = [];		
										
		$selected = $f->get_filter_value('ps');
		if ( $selected )
		{					
			switch ( $selected ) 
			{
				case 'image_yes':
					$query_vars['meta_query'][] = ['key' => '_thumbnail_id', 'value' => '', 'compare' => '!=', 'relation' => 'AND'];
				break;					
				case 'image_no':
					$query_vars['meta_query'][] = ['relation' => 'OR', ['key' => '_thumbnail_id', 'value' => '0', 'compare' => '='], ['key' => '_thumbnail_id', 'compare' => 'NOT EXISTS']];
				break;				
				case 'webspy_yes':
					$query_vars['productmeta_query'][] = ['key' => 'webspy_link', 'value' => '', 'compare' => '!='];
				break;	
				case 'webspy_no':					
					$query_vars['productmeta_query'][] = ['relation' => 'OR', ['key' => 'webspy_link', 'value' => '', 'compare' => '='], ['key' => 'webspy_link', 'compare' => 'NOT EXISTS']];
				break;
				case 'variant_prod':					
					$product = $wpdb->get_col( "SELECT DISTINCT post_parent FROM $wpdb->posts WHERE post_parent != '0' AND post_type = 'usam-product'" );
					$query_vars['post__in'] = $product;						
				break;								
			}
		}			
		$date_interval = $f->get_date_interval();
		if( !empty($date_interval['from']) )			
			$query_vars['date_query'][] = ['after' => date('Y-m-d H:i:s', $date_interval['from']), 'inclusive' => true];	
		if( !empty($date_interval['to']) )			
			$query_vars['date_query'][] = ['before' => date('Y-m-d H:i:s', $date_interval['to']), 'inclusive' => true];		

		if ( !isset($query_vars['productmeta_query']) )
			$query_vars['productmeta_query'] = [];
		$query_vars['productmeta_query'] = array_merge($query_vars['productmeta_query'], $f->get_string_for_query(['sku', 'code', 'webspy_link']));		
		$selected = $f->get_filter_value('contractors');
		if ( $selected )
			$query_vars['productmeta_query'][] = ['key' => 'contractor', 'value' => array_map('intval', (array)$selected), 'compare' => 'IN'];	
		
		$selected = $f->get_filter_value('platform_export');
		if ( $selected )
		{
			$platform = [];
			foreach ( (array)$selected as &$value ) 	
				$platform[] = sanitize_title($value).'_id';
			$query_vars['productmeta_query'][] = ['key' => $platform, 'compare_key' => 'IN'];
		}
		$selected = $f->get_filter_value('exchange_rules');
		if ( $selected )
		{
			$selected = array_map('intval', (array)$selected);
			foreach ( $selected as &$rule_id ) 				
				$rule_id = 'rule_'.$rule_id;				
			$query_vars['productmeta_query'][] = ['key' => $selected, 'compare_key' => 'IN'];
		}			
		$selected = $f->get_filter_value('parsing_sites');
		if ( $selected )
		{				
			$ids = array_map('intval', (array)$selected);	 
			if ( $ids )
			{
				require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');		
				$sites = usam_get_parsing_sites(['include' => $ids, 'fields' => 'domain']);
				foreach ( $sites as $site ) 
					$query_vars['productmeta_query'][] = ['key' => 'webspy_link', 'value' => '('.$site.')', 'compare' => 'RLIKE'];
			}
		}
		$selected = $f->get_filter_value('discount');
		if ( $selected )
			$query_vars['discount'] = array_map('intval', (array)$selected);
		$query_vars['productmeta_query'] = array_merge($query_vars['productmeta_query'], $f->get_digital_interval_for_query(['weight', 'barcode']));
		$query_vars['price_meta_query'] = $f->get_digital_interval_for_query(["price_".$type_price => 'price', "oldprice_".$type_price => 'oldprice']);
		$query_vars['postmeta_query'] = $f->get_digital_interval_for_query(['views', 'rating', 'comment', 'subscription', 'compare', 'desired', 'basket', 'purchased']);
			
		$columns_meta = [];			
		$terms = usam_get_product_attributes();
		foreach ( $terms as $term ) 
			$columns_meta[$term->slug] = 'attr_'.$term->term_id;
		$query_vars['attributes_query'] = $f->get_string_for_query( $columns_meta );
		
		$columns_meta = ['stock', 'total_balance'];
		$storages = usam_get_storages();
		foreach ( $storages as $storage ) 
		{
			$columns_meta[] = 'storage_'.$storage->id;
		}	
		$query_vars['stock_meta_query'] = $f->get_digital_interval_for_query($columns_meta);	
		$selected = $f->get_filter_value('orderby', isset($query_vars['orderby'])?$query_vars['orderby']:'' );
		if ( $selected )
		{
			$query_vars['orderby'] = sanitize_text_field($selected);			
			switch ( $query_vars['orderby'] ) 
			{	
				case 'thumbnail' :		
					$query_vars["meta_key"] = '_thumbnail_id';
					$query_vars["orderby"] = 'meta_value_num';
				break;				
				case 'price' :			
				case 'oldprice' :
					$query_vars['type_price'] = $type_price;
				break;		
				default:
					foreach ( $storages as $storage ) 
					{
						if ( 'storage_'.$storage->id == $query_vars['orderby'] )
						{
							$query_vars['orderby'] = 'storage_'.$storage->id;
							break;
						}
					}
				break;				
			}		
		}			
		if ( !isset($query_vars['conditions']) )
			$query_vars['conditions'] = [];
		$query_vars['conditions'] = array_merge($query_vars['conditions'], $f->get_string_for_query(['title', 'content', 'excerpt', 'name']));
		$query_vars['conditions'] = array_merge($query_vars['conditions'], $f->get_digital_interval_for_query(['id' => 'post_id']) );
					
		$selected = $f->get_filter_value('order', isset($query_vars['order'])?$query_vars['order']:'' );
		if ( $selected )
			$query_vars['order'] = sanitize_title($selected);		
		$selected = $f->get_filter_value('type');
		if ( $selected )	
			$query_vars["product_type"] = sanitize_title($selected);				
		$query_vars['tax_query'] = [];	
		foreach( ['category', 'brands', 'category_sale', 'catalog', 'selection', 'variation'] as $tax_slug )
		{						
			$selected = $f->get_filter_value($tax_slug);
			if ( $selected )					
				$query_vars["tax_query"][] = ['taxonomy' => 'usam-'.$tax_slug, 'field' => 'id', 'terms' => array_map('sanitize_title', (array)$selected), 'operator' => 'IN'];	
		}		
		$selected = $f->get_filter_value('post_author');
		if ( $selected )			
			$query_vars["author"] = absint($selected);		
		$selected = $f->get_filter_value('post_status');		
		if ( $selected )			
			$query_vars["post_status"] = sanitize_title($selected);	
		return $query_vars;
	}
}
?>