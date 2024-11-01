<?php
// Описание: Шаблон страницы "Поиска записей"
if (!empty($products))	
{					
	$search_key = explode(" ",$search_keyword);		
	foreach ( $products as $product ) 
	{						
		$title_output = preg_replace('/'.implode('|', $search_key) .'/iu', '<span class="search-excerpt">\0</span>', stripslashes($product->post_title) );
		echo '
		<div id = "product-'.$product->ID.'" class="search_results__row">
			<div class="search_results__content_image">				
				<div class="search_results__row_content">
					<a href="'.usam_product_url( $product->ID ).'" class="search_results__row_name">'.$title_output.'</a>
					<div class="search_results__row_description">'.usam_limit_words( strip_tags( USAM_Search_Shortcodes::strip_shortcodes( strip_shortcodes($product->post_excerpt) ) ),200,'...').'</div>
				</div>				
			</div>
		</div>';			
	}
}	