<?php 
// Name: Товары на фото

if ( !empty($block['options']['id']) )
{
	$banners = usam_get_theme_banners(['ids' => [$block['options']['id']], 'number' => 1]);			
	
	if ( $banners )
	{
		if( !empty($block['name']) )
		{ 
			?><<?php echo $block['options']['tag_name'] ?> class='html_block__name'><?php echo $block['name'] ?></<?php echo $block['options']['tag_name'] ?>><?php
		}
		if( !empty($block['options']['description']) )
		{ 
			?><div class='html_block__description'><?php echo $block['options']['description'] ?></div><?php
		}
		?>
		<div class="html_block___photo">
			<?php 			
			foreach( $banners as $banner ) 
			{ 
				echo usam_get_theme_banner( (array)$banner );
			}
			?>
			<div class="html_block___photo__right">			
				<div class="products_photo slides">
					<?php					
					if ( !empty($banners[0]['settings']['products']) )
					{
						foreach( $banners[0]['settings']['products'] as $product )
						{
							?>
							<div class='product_photo'>
								<div class='product_photo__image'>
									<a href='<?php echo usam_product_url( $product['product_id'] ); ?>'><?php usam_product_thumbnail( $product['product_id'] ); ?></a>
								</div>
								<a class='product_photo__title' href='<?php echo usam_product_url( $product['product_id'] ); ?>'><?php echo get_the_title( $product['product_id'] ); ?></a>
								<?php $product_categories = get_the_terms( $product['product_id'] , 'usam-category' ); ?>
								<?php if ( !empty($product_categories) ) { ?>
								<?php $category = current($product_categories); ?>
									<a class="product_photo__category" href="<?php echo get_term_link($category->term_id, 'usam-category'); ?>" title="Товары в категории <?php echo $category->name; ?>"><?php echo $category->name; ?></a>
								<?php } ?>							
								<div class='prices'>
									<span class="old_price"><?php echo usam_get_product_price_currency( $product['product_id'], true ); ?></span>
									<span class="price"><?php echo usam_get_product_price_currency( $product['product_id'] ); ?></span>
								</div>
								<div class='product_photo__button'>
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
							<?php						
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php						
	}
}
add_action('wp_footer', function() use($block) {	
	?> 
	<script>			
		if ( jQuery(".products_photo").length )
			jQuery('.products_photo').owlCarousel({autoplay:false, autoWidth:false, loop:false, items:1, nav:true, dots:true });
	</script>	
	<style>
	#html_block_<?php echo $block['id'] ?> .owl-stage{gap:<?php echo $block['content_style']['gap'] ?>}
	</style>
	<?php
}, 100);
?>


