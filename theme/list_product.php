<?php 
// Описание: Шаблон отображения товара списком
?>
<?php
global $post;			
$product_id = $post->ID;	
?>			
<div class="products_list__item js-product" product_id="<?php echo $product_id ?>" itemscope itemprop="itemListElement" itemtype="http://schema.org/Product"> 				
	<div class="products_list__item_image">
		<a itemprop="url" href="<?php echo usam_product_url(); ?>" class="image_container"><?php usam_product_thumbnail( ); ?></a>
		<?php usam_label_product();  ?>
	</div>				
	<div  class="products_list__item_content">		
		<div  class="products_table__item_description">					
			<div class="products_list__item_title">
				<a href="<?php echo usam_product_url(); ?>" itemprop="name"><?php the_title(); ?></a>
				<?php usam_product_rating( 'products_list__item_rating' ); ?>
			</div>			
			<div class = "product_content"><?php echo usam_display_product_attributes( $product_id, false, true, 4 ); ?></div>										
		</div>
		<div  class="products_list__item_parameters">										
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
			<?php if ( is_active_sidebar( 'list-product' ) ) : ?>
				<div class="widget-row widgets-list-product">
					<?php dynamic_sidebar('list-product'); ?>
				</div>
			<?php endif; ?>
			<?php
			if(  usam_product_has_stock() ) 
				usam_product_variations();	
			?>				
			<div class="products_list__item_addtocart_button">	
				<?php usam_include_template_file('property-quantity', 'template-parts'); ?>						
				<?php usam_addtocart_button( ); ?>
			</div>						
		</div>
	</div>
</div>			
<?php 