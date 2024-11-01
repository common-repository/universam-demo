<?php 
// Описание: Шаблон баннера, на котором отображаются товары
?>
<span class='banner_sonar_point'></span>
<div class='banner_active_point'></div>
<div class='banner_point_product'>
	<div class='banner_point_product__image image_container'>
		<a href='<?php echo usam_product_url( $product['product_id'] ); ?>'><?php usam_product_thumbnail( $product['product_id'] ); ?></a>
	</div>
	<a class='banner_point_product__title' href='<?php echo usam_product_url( $product['product_id'] ); ?>'><?php echo get_the_title( $product['product_id'] ); ?></a>
	<div class='prices'>
		<span class="old_price"><?php echo usam_get_product_price_currency( $product['product_id'], true ); ?></span>
		<span class="price"><?php echo usam_get_product_price_currency( $product['product_id'] ); ?></span>
	</div>
	<div class='banner_point_product__content'>
		<div class='banner_point_product__desc'><?php echo usam_limit_words( get_the_excerpt( $product['product_id'] ), 150 ); ?></div>
	</div>
	<div class='banner_point_product__button'>
		<?php
		if ( !usam_product_has_variations( $product['product_id'] ) ) 
		{
			usam_addtocart_button( $product['product_id']  );
		} 
		else 
		{ 
			?><a class = "button" href="<?php echo usam_product_url( $product['product_id'] ); ?>"><?php _e('Выбрать варианты', 'usam'); ?></a><?php 
		}
		?>
	</div>
</div>