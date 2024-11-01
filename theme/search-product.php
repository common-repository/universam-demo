<?php
// Описание: Шаблон списка товаров на странице поиска
if (!empty($products))	
{		
	$search_keyword = preg_quote($search_keyword, '/');
	$search_key = explode(" ", $search_keyword );	
	array_unshift($search_key, $search_keyword);
	foreach ( $products as $product ) 
	{				
		if ( usam_product_has_stock($product->ID) )
			$class = 'in_stock';
		else					
			$class = 'not_available';				
				
		$title_output = preg_replace('/'.implode('|', $search_key) .'/iu', '<span class="search-excerpt">\0</span>', stripslashes($product->post_title) );	
		echo '
		<div id = "product-'.$product->ID.'" class="search_results__row '.$class.'">
			<div class="search_results__content_image">
				<a class="search_results__row_image image_container" href="'.usam_product_url( $product->ID ).'">'.usam_get_product_thumbnail( $product->ID ).'</a>
				<div class="search_results__row_content">
					<a href="'.usam_product_url( $product->ID ).'" class="search_results__row_name">'.$title_output.'</a>
					'.USAM_Search_Shortcodes::get_product_prices( $product->ID ).USAM_Search_Shortcodes::get_product_stock_title( $product->ID ).USAM_Search_Shortcodes::get_product_sku( $product->ID ).'
						<div class="search_results__row_description">'.usam_limit_words( strip_tags( USAM_Search_Shortcodes::strip_shortcodes( strip_shortcodes($product->post_excerpt) ) ),200,'...').'</div>
					'.USAM_Search_Shortcodes::get_product_categories( $product->ID ).USAM_Search_Shortcodes::get_product_tags( $product->ID ).'
				</div>				
			</div>			
		</div>';			
	}
}	