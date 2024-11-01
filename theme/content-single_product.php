<?php
/*
Описание: Шаблон просмотра товара
*/ 
$product_id = $post->ID;
$product_has_stock = usam_product_has_stock();
$product_link = get_permalink( $product_id );
?>
<div id = "product-<?php echo $product_id; ?>" class="single_product js-product" product_id="<?php echo $product_id; ?>" itemscope itemtype="http://schema.org/Product">
	<?php
	if ( post_password_required() )
	{
		echo get_the_password_form();
		?>
		</div>
		<?php
		return;
	}
	do_action('usam_single_product_before', $product_id);
	?>
	<div class="single_product__header">
		<?php
		usam_single_image();
		if( usam_is_product_discount() )
		{
			?><div class="single_percent_action label_percent_action"><?php echo '-'.usam_get_percent_product_discount( $product_id )."%"; ?></div><?php
		}
		?>		
		<div class="single_product__main">
			<div class="single_labels">
				<?php
					$code_price = usam_get_customer_price_code();
					$discounts = usam_get_current_product_discount( $product_id );
					if ( !empty($discounts[$code_price]) )
					{ 
						foreach( $discounts[$code_price] as $discount_id ) 
						{			
							$label_name = usam_get_discount_rule_metadata($discount_id, 'label_name');
							$label_color = usam_get_discount_rule_metadata($discount_id, 'label_color');
							if( $label_name )
							{
								$style = $label_color?"background-color:{$label_color}":$label_color;
								?><div class="single_label_action" style="<?php echo $style; ?>"><?php echo esc_html($label_name); ?></div><?php 
							}
						}
					}
				?>
			</div>
			<h1 itemprop="name" class="name"><?php the_title(); ?></h1>	
			<div class="single_product__columns">				
				<div class="single_product__rows">					
					<div class="single_product__row single_product__sku_rating">
						<div class="single_product__sku">
							<span class ="single_product__text"><?php _e('Артикул:', 'usam'); ?></span>
							<span class="js-sku"><?php echo usam_get_product_meta($product_id , 'sku' ); ?></span>
							<meta itemprop="sku" content="<?php echo usam_get_product_meta($product_id , 'sku' ); ?>" />
							<meta itemprop="gtin" content="<?php echo usam_get_product_meta($product_id , 'barcode' ); ?>" />
						</div>
						<?php usam_product_rating( 'average_vote' ); ?>
					</div>
					<div class="single_product__row prices" itemprop="offers" itemscope="" itemtype="http://schema.org/Offer">
						<span class="old_price js-oldprice"><?php usam_product_price_currency( true ); ?></span>
						<span class="price js-price" price="<?php echo usam_get_product_price( $product_id ); ?>"><?php usam_product_price_currency(); ?></span>
						<meta itemprop="price" content="<?php echo usam_get_product_price( $product_id ); ?>" />	
						<meta itemprop="priceCurrency" content="<?php echo usam_get_currency_price_by_code(); ?>" />	
						<link itemprop="url" href="<?php echo get_permalink( $product_id ); ?>" />				
						<?php
						if( $product_has_stock )
						{						
							?><link itemprop="availability" href="http://schema.org/InStock"><?php
						}
						?>
					</div>
					<?php
					usam_product_variations( );
					if( $product_has_stock )
					{						
						if( usam_hide_addtocart_button() )
						{							
							if( usam_is_product_under_order() ) 
							{ 		
								?>
								<div class="single_product__addtocart">
									<?php echo usam_get_webform_link( get_site_option('usam_under_order_button'), 'main-button under_order_button button' ); ?>
								</div>
								<?php 
								
							}
							else
							{							
								$products = usam_get_products(['associated_product' => [['list' => 'options', 'product_id' => $product_id]], 'post_status' => 'publish']);	//Опции товара
								if ( $products )
								{
									?>
									<div class="single_product__options">
										<?php
										foreach( $products as $product )
										{
											?><div class="single_product__options_product">
												<input type="checkbox" value="1" class="option-input js-option" price="<?php echo usam_get_product_price( $product->ID ); ?>" product_id="<?php echo $product->ID; ?>">										
												<span class="single_product__options_name"><?php echo $product->post_title; ?></span>
												<span class="single_product__options_price">+<?php echo usam_get_product_price_currency( $product->ID ); ?></span>
											</div><?php						
										}
										?>
									</div>
								<?php } ?>
								<div class="single_product__addtocart <?php echo usam_has_additional_units($product_id)?'quantity_additional_units':''; ?>">
									<?php usam_include_template_file('property-quantity', 'template-parts'); ?>
									<div class="single_product__buttons">
										<?php usam_addtocart_button( $product_id, __('В корзину', 'usam')); ?>
										<?php if ( is_active_sidebar( 'single-product-buttons' ) ) : ?>					
											<div class="widget-column widgets-single-product-buttons">
												<?php dynamic_sidebar('single-product-buttons'); ?>
											</div>
										<?php endif; ?>
									</div>								
								</div>
								<?php
							}
						}
					}
					else
					{						
						if ( is_active_sidebar( 'single-product-sold' ) ) { 
							?><div class="widget-column widgets-single-product-buttons"><?php dynamic_sidebar('single-product-sold'); ?></div><?php 
						} 
						else 
						{ 
							?><div class="single_product__row single_product__soldout"><?php _e('Этот товар продан', 'usam'); ?></div><?php
						}
						if( usam_chek_user_product_list('subscription') ) 
						{ 
							usam_include_template_file('product-subscription', 'template-parts');
						}					
					}
					if ( is_active_sidebar( 'widgets-single-product' ) ) : ?>					
						<div class="widget-column widgets-single-product">
							<?php dynamic_sidebar('widgets-single-product'); ?>
						</div>
					<?php endif; ?>
					<div class = "single_product__row single_product__buttons">
						<?php if ( usam_chek_user_product_list('desired') ) { ?>
							<?php $class = usam_checks_product_from_customer_list( 'desired' )?'list_selected':''; ?>
							<?php usam_svg_icon('favorites', 'desired_product js-product-desired '.$class, ['title' => __('Добавить в избранное', 'usam')]); ?>
						<?php } ?>	
						<?php if ( usam_chek_user_product_list('compare') ) { ?>						
							<?php $class = usam_checks_product_from_customer_list( 'compare' )?'list_selected':''; ?>
							<?php usam_svg_icon('comparison', 'compare_product js-product-compare '.$class, ['title' => __('Добавить к сравнению', 'usam')]); ?>
						<?php } ?>	
					</div>
					<?php
					$bonus = usam_get_client_product_bonuses();
					if ( $bonus )
					{
						?><div class = "single_product__row single_product__bonus"><?php printf( __('%s бонусных баллов','usam'), "<span class='single_product__bonus_value'>$bonus</span>"); ?></div><?php
					}
					if ( is_active_sidebar( 'single-product-2' ) ) : ?>					
						<div class="widget-column widgets-single-product-2">
							<?php dynamic_sidebar('single-product-2'); ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="purchase_terms single_product__rows">
					<div class="single_product__brand" itemprop="brand" itemtype="http://schema.org/Brand" itemscope>
						<?php
						$brand = usam_product_brand( $product_id );
						if ( !empty($brand) )
						{
							?><meta itemprop="name" content="<?php echo $brand->name; ?>" /><?php
							$attachment_id = (int)get_term_meta($brand->term_id, 'thumbnail', true);
							if ( !empty($attachment_id) )
							{
								?>
								<a class="brand_image_link" title ='<?php printf(__('Посмотреть все товары бренда %s','usam'),$brand->name); ?>' href='<?php echo get_term_link( $brand->term_id, 'usam-brands' ); ?>'><?php usam_term_image($brand->term_id, 'full', ['alt' => $brand->name]) ?></a><?php 
							}
							else
							{
								?><a class='brand_text_link' title ='<?php _e('Посмотреть все товары бренда','usam'); ?>' href='<?php echo get_term_link( $brand->term_id, 'usam-brands' ); ?>'><?php echo $brand->name; ?></a><?php
							}
						} ?>
					</div>
					<?php usam_theme_banners(['banner_location' => 'purchase_terms']); ?>
				</div>
			</div>
		</div>
	</div>
	<!------------------------------------------------------------------------------------------------------------------------------>
	<div class = "product_footer_box">
		<?php 
		$tabs = usam_get_product_tabs(); 
		if( $tabs )
		{
			?>
			<div class="parameters_products">
				<div class="parameters_products__tabs usam_tabs">	
					<div class="header_tab product_tabs_header">	
						<?php
						foreach( $tabs as $k => $tab ) 
						{
							?><a href='#product_tab-<?php echo $tab->id; ?>' id="product_tab-<?php echo $tab->id; ?>" class="tab <?php echo !$k?'current':''; ?> usam_menu-<?php echo $tab->id; ?>"><h2><?php echo $tab->name; ?></h2></a><?php 
						} 
						?>
					</div>		
					<div class="countent_tabs">	
					<?php
						foreach( $tabs as $k => $tab ) 
						{
							?>
							<div id = "product_tab-<?php echo $tab->id; ?>" class = "tab product_tab <?php echo !$k?'current':''; ?>">
								<?php echo usam_get_product_tab_template( $tab ); ?>
							</div>
							<?php 
						} 
						?>
					</div>
				</div>
			</div>
			<?php 
		}		
		do_action('usam_single_product_after', $product_id);
		?>
	</div>
	<?php do_action( 'usam_product_addons', $product_id ); ?>
</div>
<div class="full-gallery js-full-gallery">	
	<div class="full-gallery__topbar">
		<div class="full-gallery__topbar_title"><?php the_title(); ?></div>
		<div class="full-gallery__topbar_buttons">
			<?php usam_svg_icon("search", "js-zoom"); ?>
			<?php usam_svg_icon("fullscreen", "js-fullscreen"); ?>
			<?php usam_svg_icon("fullscreen-exit", "js-fullscreen hide"); ?>
			<?php usam_svg_icon("close", "full-gallery__topbar_buttons_close js-full-gallery-close"); ?>	
		</div>		
	</div>
	<div class="full-gallery__image slides js-full-gallery-slides">			
		<?php
		$attachments = usam_get_product_images( $product_id );	
		foreach ($attachments as $attachment)
		{						
			$image = wp_get_attachment_image_src($attachment->ID, 'full');		
			 ?>
			<div class='full-gallery__image_zoom js-full-gallery-slide-zomm'>
				<img id='image-<?php echo $attachment->ID; ?>' src='<?php echo $image[0]; ?>' alt='<?php echo $attachment->post_title; ?>' width='<?php echo $image[1]; ?>' height='<?php echo $image[2]; ?>' class='full-gallery__image_img'/>
			</div>
			<?php
		}				
		?>
	</div>
	<div class='full-gallery__small_image slides js-full-gallery-small'>	
		<?php
		$attachments = usam_get_product_images( $product_id );	
		foreach ($attachments as $attachment)
		{						
			$image = wp_get_attachment_image_src($attachment->ID, 'small-product-thumbnail');		
			echo "<img id='thumbnail_small-".$attachment->ID."' src='".$image[0]."' alt='".$attachment->post_title."' width='".$image[1]."' height='".$image[2]."'/>";
		}				
		?>
	</div>	
</div>
<?php
add_action('wp_footer', function() use( $post ) {
	$post->images = [];
	$attachments = usam_get_product_images( $post->ID );	
	foreach ($attachments as $attachment)
	{						
		$small = wp_get_attachment_image_src($attachment->ID, 'small-product-thumbnail');	
		$full = wp_get_attachment_image_src($attachment->ID, 'full');
		$post->images[] = ['small' => $small[0], 'full' => $full[0], 'alt' => $attachment->post_title, 'id' => $attachment->ID];
	}	
	?> 
	<script>			
		var product=<?php echo json_encode( $post ); ?>;
	</script>	
	<?php
	include_once( usam_get_template_file_path( 'media-viewer', 'template-parts' ) );
});
?>