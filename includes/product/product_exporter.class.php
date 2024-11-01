<?php
/**
 * Экспорт товаров
 */
require_once( USAM_FILE_PATH . '/includes/exchange/exporter.class.php');
class USAM_Product_Exporter extends USAM_Exporter
{	
	protected $product_attributes = [];
	public function get_args( ) 
	{
		$args = ['post_status' => 'publish', 'orderby' => $this->rule['orderby'], 'order' => $this->rule['order'], 'paged' => $this->paged, 'posts_per_page' => $this->number, 'tax_query' => []];
		$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');		
		foreach ( $taxonomies as $tax )
		{		
			if ( !empty($this->rule[$tax->name]) )
				$args['tax_query'][] = ['taxonomy' => $tax->name, 'field' => 'id', 'terms' => $this->rule[$tax->name], 'operator' => 'IN'];
		}
		if ( !empty($this->rule['contractor']) )
			$args['productmeta_query'] = [['key' => 'contractor', 'value' => $this->rule['contractor'], 'compare' => 'IN']];
		if ( !empty($this->rule['from_day']) )
			$args['date_query']['after'] = $this->rule['from_day']." day ago";
		if ( !empty($this->rule['to_day']) )
			$args['date_query']['before'] = $this->rule['to_day']." day ago";
		if ( !empty($this->rule['from_price']) )
		{			
			$args['from_price'] = $this->rule['from_price'];
			$args['type_price'] = $this->rule['type_price'];
		}
		if ( !empty($this->rule['to_price']) )
		{			
			$args['to_price'] = $this->rule['to_price'];
			$args['type_price'] = $this->rule['type_price'];
		}
		if ( !empty($this->rule['from_stock']) )
			$args['from_stock'] = $this->rule['from_stock'];
		if ( !empty($this->rule['to_stock']) )
			$args['to_stock'] = $this->rule['to_stock'];
		if ( !empty($this->rule['from_total_balance']) )
			$args['from_total_balance'] = $this->rule['from_total_balance'];
		if ( !empty($this->rule['to_total_balance']) )
			$args['to_total_balance'] = $this->rule['to_total_balance'];
		if ( !empty($this->rule['from_views']) )
			$args['from_views'] = $this->rule['from_views'];
		if ( !empty($this->rule['to_views']) )
			$args['to_views'] = $this->rule['to_views'];		
		return $args;
	}
	
	public function get_total( ) 
	{
		$args = $this->get_args();	
		return usam_get_total_products( $args );	
	}

	protected function get_data( $param = [] ) 
	{			
		return $this->get_products_data( $param );
	}
	
	protected function get_products_data( $param = [] ) 
	{			
		$args = $this->get_args();
		$args = array_merge($args, $param);		
		$args['product_attribute_cache'] = false;
		foreach($this->rule['columns'] as $column => $value)
		{
			if ( stripos($column, 'attribute_') !== false)
				$args['product_attribute_cache'] = true;
			elseif ( stripos($column, 'storage_') !== false)
				$args['stocks_cache'] = true;
			elseif( stripos($column, 'price_') !== false)
				$args['prices_cache'] = true;
		}
		if ( $args['product_attribute_cache'] )
			$this->product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-product_attributes', 'term_meta_cache' => true]);
		$products = usam_get_products( $args, isset($this->rule['columns']['thumbnail']) );			
		$output	= [];	
		if ( !empty($products) )
		{
			foreach ( $products as $product )
			{			
				$output[] = $this->get_column( $product );		
				usam_clean_product_cache( $product->ID );				
			}		
		}			
		return $output;
	}
		
	private function get_column( $product ) 
	{ 
		static $taxonomies = null;
		if ( $taxonomies === null )
			$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');
		
		$results = [];		
		$prices = usam_get_prices(['type' => 'all']);
		foreach ( $product as $key => $value )	
		{
			if ( isset($this->rule['columns'][$key]) )
			{
				if ( $key == 'post_excerpt' || $key == 'post_content' )
				{
					$value = nl2br($value);
					$value = str_replace(["\r\n","\r","\n"],"",$value);		
				}	
				$results[$key] = $value;
			}
			elseif ( $key == 'ID' &&  isset($this->rule['columns']['product_id']) )
				$results['product_id'] = $value;
		}		
		if ( isset($this->rule['columns']['url']) )
			$results['url'] = usam_product_url( $product->ID );		
		
		$columns_meta = ['sku', 'virtual','code','weight','barcode','under_order', 'webspy_link', 'avito_id'];
		foreach ( $columns_meta as $key )	
		{
			if ( isset($this->rule['columns'][$key]) )
				$results[$key] = usam_get_product_meta($product->ID, $key );
		}	
		$columns_meta = array('views','rating','rating_count');
		foreach ( $columns_meta as $key )	
		{
			if ( isset($this->rule['columns'][$key]) )
				$results[$key] = usam_get_post_meta($product->ID, $key );
		}		
		$columns_meta = array('total_balance','stock');
		foreach ( $columns_meta as $key )	
		{
			if ( isset($this->rule['columns'][$key]) )
			{		
				$results[$key] = usam_product_remaining_stock($product->ID, $key);
			}
		}
		if ( isset($this->rule['columns']['weight_unit']) )
		{
			$results['weight_unit'] = get_option('usam_weight_unit','');			
		}	
		if ( isset($this->rule['columns']['reserve']) )
			$results['reserve'] = usam_string_to_float(usam_get_product_stock($product->ID, 'total_balance')) - usam_string_to_float(usam_get_product_stock($product->ID, 'stock'));
		if ( isset($this->rule['columns']['price']) )
			$results['price'] = usam_get_product_price($product->ID, $this->rule['type_price'] );
		if ( isset($this->rule['columns']['old_price']) )
			$results['old_price'] = usam_get_product_old_price($product->ID, $this->rule['type_price'] );
		if ( isset($this->rule['columns']['underprice']) )
		{
			$id_main_site = usam_get_post_id_main_site( $product->ID );
			$results['underprice'] = usam_get_product_metaprice($id_main_site, 'underprice_'.$this->rule['type_price'] );		
		}
		if ( isset($this->rule['columns']['discont']) )
		{
			$price = usam_get_product_price( $product->ID, $this->rule['type_price'] );	
			$old_price = usam_get_product_old_price( $product->ID, $this->rule['type_price'] );
			if ( $old_price )
				$discount = $old_price - $price;	
			else
				$discount = 0;
			$results['discont'] = $discount;
		}
		if ( isset($this->rule['columns']['product_type']) )
		{
			$results['product_type'] = usam_get_product_type( $product->ID );
		}		
		foreach ( $taxonomies as $tax )
		{
			$name = str_replace('usam-','',$tax->name);			
			if ( isset($this->rule['columns'][$name]) )
			{
				$lists = wp_get_post_terms($product->ID, $tax->name, ["fields" => "names"]);
				$results[$name] = !empty($lists)?implode("|",$lists):'';
			}
			if ( !empty($this->rule['columns'][$name.'_slug']) )
			{
				$terms = wp_get_post_terms($product->ID, $tax->name, ["fields" => "all"]);	
				$lists = [];
				foreach ( $terms as $term )
					$lists[] = $term->slug;				
				$results[$name.'_slug'] = !empty($lists)?implode("|",$lists):'';
			}
		}			
		if ( isset($this->rule['columns']['variations']) )
		{
			$list = [];			
			$args = ['post_type' => 'usam-product', 'post_parent' => $product->ID, 'post_status' => ['draft', 'pending', 'publish'], 'numberposts' => -1, 'order' => "ASC",'fields' => 'ids'];
			$ids = usam_get_products( $args );
			$terms = wp_get_object_terms( $ids, 'usam-variation', ['fields' => 'all_with_object_id']);
			if ( !empty($terms) )
			{ 			
				$variations = [];
				$parent_terms = [];
				foreach ($terms as $term)
				{				
					$ancestors = usam_get_ancestors( $term->term_id, 'usam-variation' );	
					if ( empty($ancestors) )
						continue;
					$parent_term = get_term_by( 'id', $ancestors[0], 'usam-variation' );				
					$parent_terms[$parent_term->term_id] = $parent_term->name;	
					$variations[$parent_term->term_id][] = $term->name."@".$term->object_id;				
				}	
				foreach ($parent_terms as $id => $name )
				{
					$list[] = $name.":".implode("|",$variations[$id]);
				}							
			}
			$results['variations'] = !empty($list)?implode("=",$list):'';
		}		
		if ( isset($this->rule['columns']['thumbnail']) )
			$results['thumbnail'] = usam_get_product_thumbnail_src($product->ID, 'full' );	
			
		if ( isset($this->rule['columns']['exel_image']) )
		{
			$post_thumbnail_id = get_post_thumbnail_id( $product->ID );
			$results['exel_image'] = '';
			if ( $post_thumbnail_id )
			{
				$old_metadata = image_get_intermediate_size($post_thumbnail_id, 'small-product-thumbnail' );
				$uploads = wp_upload_dir();
				if ( isset($old_metadata['path']) )
				{		
					if ( file_exists($uploads['basedir']."/".$old_metadata['path']) )
						$results['exel_image'] = $uploads['basedir'].'/'.$old_metadata['path'];		
				}
			}
		}
		if ( isset($this->rule['columns']['images']) )
		{
			$images = usam_get_product_images( $product->ID );
			$post_thumbnail_id = get_post_thumbnail_id( $product->ID );
			$export_images = array();
			foreach ($images as $image)
			{						
				if ( $post_thumbnail_id == $image->ID && isset($this->rule['columns']['thumbnail']) )
					continue;
				
				$small = wp_get_attachment_image_src($image->ID, 'full');	
				if ( !empty($small[0]) )
					$export_images[] = $small[0];
			}				
			$results['images'] = implode( '|', $export_images );
		}		
		$storages = usam_get_storages();					
		foreach ( $storages as $storage )
		{	
			if ( isset($this->rule['columns']['storage_'.$storage->code]) )
				$results['storage_'.$storage->code] = usam_get_product_stock($product->ID, 'storage_'.$storage->id );
		}	
		foreach ( $prices as $type_price )
		{			
			if ( isset($this->rule['columns']['price_'.$type_price['code']]) )				
				$results['price_'.$type_price['code']] = usam_get_product_price($product->ID, $type_price['code'] );
		}	
		if ( $this->product_attributes )
		{
			foreach($this->product_attributes as $term)
			{
				if ( $term->parent != 0 )
				{				
					if ( isset($this->rule['columns']['attribute_'.$term->term_id]) )
						$results['attribute_'.$term->term_id] = implode($this->rule['splitting_array'],usam_get_product_attribute_display($product->ID, $term->slug, false));
				}			
			}
		}
		if ( isset($this->rule['columns']['box_length']) )
			$results['box_length'] = usam_get_product_meta($product->ID, 'length' );
		if ( isset($this->rule['columns']['box_width']) )
			$results['box_width'] = usam_get_product_meta($product->ID, 'width' );
		if ( isset($this->rule['columns']['box_height']) )
			$results['box_height'] = usam_get_product_meta($product->ID, 'height' );		
			
		if ( isset($this->rule['columns']['unit_measure']) )
			$results['unit_measure'] = usam_get_product_meta($product->ID, 'unit_measure' );
		if ( isset($this->rule['columns']['unit']) )
			$results['unit'] = usam_get_product_meta($product->ID, 'unit' );		
		return $results;
	}
}
?>