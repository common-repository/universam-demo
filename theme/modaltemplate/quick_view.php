<?php 
$product_id = !empty($_REQUEST['product_id'])?sanitize_title($_REQUEST['product_id']):0;
$url = get_permalink($product_id); 
$category = wp_get_post_terms( $product_id, 'usam-category' );
?>
<div id="quick_view_<?php echo $product_id; ?>" class='quick_view modal fade hide' aria-hidden="true">
	<button type="button" class="quick_view__close" data-dismiss="modal" aria-hidden="true">×</button>
	<div class='modal-scroll'>
		<div class='quick_view__content'>
			<div class='quick_view__image image_container'><?php echo usam_product_thumbnail( $product_id, 'single', '', false ); ?></div>
			<div class='quick_view__product'>
				<a class='quick_view__rows quick_view__product_title'href="<?php echo $url; ?>"><?php echo get_the_title( $product_id ); ?></a>
				<div class='quick_view__rows quick_view__category_rating'>
					<?php echo usam_get_product_rating(  'quick_view__product_rating', $product_id, false ); ?>
					<?php if ( !empty($category[0]) ) { ?>		
						<a class='quick_view__product_category' href="<?php echo get_term_link( $category[0]->term_id, 'usam-category'); ?>"><?php echo usam_get_product_category_name( $product_id ); ?></a>
					<?php } ?>							
				</div>
				<div class='quick_view__rows quick_view__product_excerpt'><?php echo usam_limit_words( get_the_excerpt($product_id), 100 ); ?></div>	
				<div class='quick_view__rows quick_view__product_brand'><?php usam_get_product_brand_name( $product_id ); ?></div>
				<div class='quick_view__rows'>
					<div class='quick_view__product_sku_name'><?php _e("Артикул","usam"); ?>:</div>
					<div class='quick_view__product_sku'><?php echo usam_get_product_meta($product_id , 'sku' ); ?></div>
				</div>
				<div class='quick_view__rows quick_view__price_addtocart'>
					<div class='quick_view__product_price'><?php echo usam_get_product_price_currency( $product_id ); ?></div>
					<div class='quick_view__product_addtocart'><?php usam_addtocart_button( $product_id ); ?></div>
				</div>	
			</div>
		</div>
	</div>
</div>