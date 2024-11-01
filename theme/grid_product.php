<?php 
// Описание: Шаблон отображения товара сеткой
?>
<?php
global $post, $product_limit;			
$product_id = $post->ID;
$row = !empty($product_limit)?$product_limit:4; 
if( !usam_product_has_stock( $product_id ) )
	$class = "out_stock";
else
	$class = "in_stock";
?>			
<div class="product_grid column<?php echo $row; ?> js-product <?php echo $class; ?>" product_id="<?php echo $product_id ?>" itemscope itemprop="itemListElement" itemtype="http://schema.org/Product">
	<div class="product_grid__wrapper">				
		<div class="product_grid__substrate"></div>				
		<?php if( usam_is_page('wish-list') ) { ?>		
			<a href="" title="<?php _e('Убрать из списка', 'usam'); ?>" data-product_id="<?php echo $product_id; ?>" class = "delete js-product-desired"></a>
		<?php } ?>
		<a class = "product_link" href="<?php echo usam_product_url(); ?>" itemprop="url">	
			<div class="product_grid__image">
				<div class="image_container">
					<?php usam_product_thumbnail(); ?>
				</div>
			</div>
			<?php usam_label_product( $product_id ); ?>	
			<div class="product_grid__swatches">				
				<?php usam_svg_icon('quick-view', 'js-quick-view-open'); ?>
				<?php usam_product_rating( 'product_grid__rating', false, true ); ?>	
			</div>			
		</a>		
		<div class="product_grid__information">					
			<a class = "product_grid__text product_link" href="<?php echo usam_product_url(); ?>">
				<div class="product_grid__title" itemprop="name"><?php the_title(); ?></div>				
				<div class="prices" itemscope itemprop="offers" itemtype="http://schema.org/Offer">
					<span class="old_price"><?php usam_product_price_currency( true ); ?></span>
					<span class="price"><?php usam_product_price_currency( ); ?></span>
					<meta itemprop="price" content="<?php echo usam_get_product_price( $product_id ); ?>" />	
					<meta itemprop="priceCurrency" content="<?php echo usam_get_currency_price_by_code(); ?>" />	
					<?php
					if( usam_product_has_stock( $product_id ) )
					{						
						?><link itemprop="availability" href="http://schema.org/InStock"><?php
					}	
					?>					
				</div>						
			</a>
			<div class="fade_in_block">		
				<div class="product_grid__buttons">
					<?php
					if ( usam_chek_user_product_list('desired') ) 
					{ 
						$class = usam_checks_product_from_customer_list( 'desired' )?'list_selected':''; 
						usam_svg_icon('favorites', 'desired_product js-product-desired '.$class, ['title' => __('Добавить в избранное', 'usam')]);
					}
					if ( !usam_product_has_variations($product_id) ) 
					{ 
						usam_addtocart_button();
					} 
					else 
					{ 
						?><a class = "button" href="<?php echo usam_product_url(); ?>"><?php _e('Выбрать варианты', 'usam'); ?></a><?php 
					} 
					if ( usam_chek_user_product_list('compare') ) 
					{
						$class = usam_checks_product_from_customer_list( 'compare' )?'list_selected':'';
						usam_svg_icon('comparison', 'compare_product js-product-compare '.$class, ['title' => __('Добавить к сравнению', 'usam')]);
					}
					?>	
				</div>		
			</div>	
		</div>			
	</div>
</div>