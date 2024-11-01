<?php
// Описание: Избранное

?>
<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Избранное', 'usam'); ?></h1>
</div>
<div class='usam_desired_product usam_user_products'>
	<?php
	$product_ids = usam_get_product_ids_in_user_list( 'desired' );	
	if ( !empty($product_ids) )		
	{
		$products = usam_get_products(['post_status' => 'publish', 'post__in' => $product_ids, 'orderby' => 'post__in', 'update_post_term_cache' => false, 'post_parent' => 0]);
		$output = '';	
		foreach ( $products as $product )
		{		
			$price = usam_get_product_price_currency( $product->ID );
			$output .= "<div id='product-$product->ID' class='usam_product'>";
			$output .= "<a href='" . usam_product_url( $product->ID ) . "' class='preview_link'  rel='" . str_replace( " ", "_", $product->post_title ) . "'>";			
			$output .= usam_get_product_thumbnail( $product->ID, 'product-thumbnails', $product->post_title );	
			$output .= "<div class = 'product_title'>".$product->post_title."</div>";			
			if ( $price )
			{
				if( usam_is_product_discount( $product->ID ) )										
					$output .= '<div class = "price price_sale">'. $price .'</div>';				
				else 									
					$output .='<div class = "price">' .$price . '</div>';				
			}
			$output .= "</a>";
			$output .= "</div>";		
		}	
		echo $output;
	}
	?>				
</div>	