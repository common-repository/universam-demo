<?php 
require_once( USAM_FILE_PATH . '/includes/parser/spider.class.php' );
class USAM_Spider_Site_Partner extends USAM_Spider
{		
	protected function insert_product( $data )
	{	
		static $term_link_processing = null;	
		
		if ( !usam_is_license_type('ENTERPRISE') )	
			$data['title'] = 'Купите лицензию';			
		
		if ( !empty($data['title']) && $data['product_identification'] ) 
		{		
			global $wpdb;
			$data['price'] = isset($data['price']) ? usam_string_to_float($data['price']) : 0;			
			$competitor_product_id = 0;	
			if( empty(self::$parsing_site['existence_check']) || self::$parsing_site['existence_check'] === 'sku' )
			{
				if ( !empty($data['sku']) )
					$competitor_product_id = (int)$wpdb->get_var("SELECT id FROM `".USAM_TABLE_PRODUCTS_COMPETITORS."` WHERE site_id=".self::$id." AND sku='".$data['sku']."' LIMIT 1");
				else
					return false;
			}
			elseif( self::$parsing_site['existence_check'] === 'url' )
				$competitor_product_id = (int)$wpdb->get_var("SELECT id FROM `".USAM_TABLE_PRODUCTS_COMPETITORS."` WHERE site_id=".self::$id." AND url='".$data['url']."' LIMIT 1");		
				
			$type_import = usam_get_parsing_site_metadata( self::$id, 'type_import' );
			$status = isset($data['not_available']) && $data['not_available'] ?'not_available':'available';	
			if ( !$competitor_product_id )
			{				
				if ( !$type_import || $type_import == 'insert' )
				{
					if ( !empty($data['category']) )
					{					
						$term = get_term_by( 'name', $data['category'], 'usam-category' );
						if ( !empty($term) )
							$args = ['name' => $term->name, 'category_id' => $term->term_id];
						else
							$args = ['name' => $data['category'], 'category_id' => 0];	
					}
					else		
					{
						if ( $this->link_processing['category'] )
						{
							if ( $term_link_processing === null )
								$term_link_processing = get_term( $this->link_processing['category'], 'usam-category' );							
							$args = ['name' => $term_link_processing->name, 'category_id' => $term_link_processing->term_id];
						}
						else
							$args = [];	
					}					
					$category_id = usam_insert_competitor_category_product( $args );
					$insert = ['title' => $data['title'], 'competitor_category_id' => $category_id, 'url' => $data['url'], 'site_id' => self::$parsing_site['id'], 'current_price' => $data['price'], 'status' => $status];
					if ( !empty($data['thumbnail']) ) 
						$insert['thumbnail'] = $data['thumbnail'];
					if ( !empty($data['sku']) ) 
					{
						$insert['sku'] = $data['sku'];
						$insert['product_id'] = usam_get_product_id_by_sku( $data['sku'] );
						wp_cache_delete( "usam_product_attribute_sku-".$data['sku'] );
					}				
					$product_loading = usam_get_parsing_site_metadata( self::$id, 'product_loading' );				
					if ( $product_loading !== 'existing' || !empty($insert['product_id']) )
					{					
						$competitor_product_id = usam_insert_product_competitor( $insert );
						if ( $competitor_product_id )
							$this->products_added++;					
					}
				}
			}		
			else
			{
				if ( !$type_import || $type_import == 'update' )
				{
					$update = ['title' => $data['title'], 'current_price' => $data['price'], 'url' => $data['url'], 'status' => $status];
					if ( !empty($data['thumbnail']) ) 
						$update['thumbnail'] = $data['thumbnail'];					
					if ( !empty($data['sku']) ) 
					{
						$update['sku'] = $data['sku'];
						$update['product_id'] = usam_get_product_id_by_sku( $data['sku'] );
						wp_cache_delete( "usam_product_attribute_sku-".$data['sku'] );
					}						
					global $wpdb;
					$product_price = $wpdb->get_row("SELECT * FROM `".USAM_TABLE_COMPETITOR_PRODUCT_PRICE."` WHERE competitor_product_id=$competitor_product_id ORDER BY date_insert DESC LIMIT 1");
					if ( !empty($product_price) )
					{
						$update['old_price'] = $product_price->price;
						$update['old_price_date'] = $product_price->date_insert;
					}	
					if ( usam_update_product_competitor($competitor_product_id, $update) )	
						$this->products_update++;	
				}
			}			
			$product_price_id = usam_insert_competitor_product_price(['competitor_product_id' => $competitor_product_id, 'price' => $data['price']]);
			if ( $product_price_id )
				wp_cache_delete( $product_price_id, 'usam_competitor_product_price' );
			if ( $competitor_product_id )
				wp_cache_delete( $competitor_product_id, 'usam_product_competitor' );
		}
	}
}
?>