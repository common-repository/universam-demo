<?php
// Описание: Шаблон страницы "Сравнение товаров"
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
if( usam_product_count() )
{
	global $post;
	$products = [];	
	$categories_products = [];		
	?>
	<site-slider :items='categories' :classes="'compare_product_categories'" v-show="categories.length>1">
		<template v-slot:body="sliderProps">
			<div v-for="(term, i) in sliderProps.items" class="compare_product_categories__item" @click="category=i" :class="{'active':category==i}" v-html="term.name+' ('+term.products.length+')'"></div>
		</template>
	</site-slider>
	<div class = "compare_products" v-if="categories[category]">
		<site-slider ref="slider" :items='categories[category].products' @changeposition="translate=$event" :touch="false" :amount="1" :type="'transform'" @change="numberSlide=$event" :number="numberSlide" :classes="'compare_products__fixed js-compare-products-fixed'">
		<template v-slot:body="sliderProps">
			<?php usam_svg_icon("chevron_left", ['class' => 'compare_product__nav compare_product_prev', '@click' => 'sliderProps.prev', 'v-if' => '!sliderProps.scrollStart']); ?>
			<?php usam_svg_icon("chevron_right", ['class' => 'compare_product__nav compare_product_next', '@click' => 'sliderProps.next', 'v-if' => '!sliderProps.scrollEnd']); ?>
			<div class="compare_products__fixed_slider slider-slides">
				<?php
				while (usam_have_products()) 
				{  
					usam_the_product();				
					$terms = get_the_terms( $post->ID, 'usam-category' );
					$categories = [];
					foreach ( $terms as $term )
					{
						$categories[] = $term->term_id;																
						$categories_products[$term->term_id][] = $post->ID;					
					}
					$products[] = ['product_id' => $post->ID, 'post_title' => $post->post_title, 'categories' => $categories];
					?>
					<div class="compare_products__column compare_products__fixed_item js-product" product_id="<?php echo $post->ID ?>" v-if="sliderProps.items.includes(<?php echo $post->ID ?>)">		
						<div class="compare_products__fixed_image_title">
							<?php usam_svg_icon('close', 'compare_products__fixed_delete', ['title' => __('Убрать из списка', 'usam'), '@click' => 'del('.$post->ID.')']); ?>
							<div class="compare_products__fixed_image compare_products__image">
								<a href="<?php echo usam_product_url(); ?>"><?php echo usam_get_product_thumbnail( $post->ID, 'product-thumbnails' ); ?></a>
							</div>
							<a class="compare_products__fixed_product_title" href="<?php echo usam_product_url(); ?>"><?php echo $post->post_title; ?></a>
						</div>	
						<div class="compare_products__fixed_buttons">							
							<div class="compare_products__fixed_product_price"><?php echo usam_get_product_price_currency( $post->ID ); ?></div>
							<?php usam_product_variations( $post->ID ); ?>					
							<?php usam_addtocart_button( $post->ID ); ?>	
						</div>								
						<?php	
							$additional_units = usam_get_product_property( $post->ID, 'additional_units' );
							if ( !empty($additional_units) )
							{
								?>
								<div class="compare_products__units">	
									<?php usam_field_product_number(); ?>
									<?php usam_selection_product_units( $post->ID ); ?>
								</div>
								<?php
							}
						?>
					</div>						
					<?php
				}					
				?>
			</div>
		</template>
		</site-slider>
		<site-slider ref="slider" :items='categories[category].products' :mouse="false" :touch="false" :amount="1" :type="'transform'" @change="numberSlide=$event" :number="numberSlide" :classes="'compare_products__header'">
		<template v-slot:body="sliderProps">
			<?php usam_svg_icon("chevron_left", ['class' => 'compare_product__nav compare_product_prev', '@click' => 'sliderProps.prev', 'v-if' => '!sliderProps.scrollStart']); ?>
			<?php usam_svg_icon("chevron_right", ['class' => 'compare_product__nav compare_product_next', '@click' => 'sliderProps.next', 'v-if' => '!sliderProps.scrollEnd']); ?>
			<div class="compare_products__header_slider slider-slides">
				<?php					
				while (usam_have_products()) 
				{  
					usam_the_product(); 			
					$aggregate_reviews = usam_get_aggregate_reviews( $post->ID );
					?>
					<div class="compare_products__column compare_products__header_item js-product" product_id="<?php echo $post->ID ?>" v-if="sliderProps.items.includes(<?php echo $post->ID ?>)">
						<div class="header_comparison">							
							<div class="compare_products__image">
								<a href="<?php echo usam_product_url(); ?>"><?php echo usam_get_product_thumbnail( $post->ID, 'product-thumbnails' ); ?></a>
								<?php usam_svg_icon('close', ['title' => __('Убрать из списка', 'usam'), '@click' => 'del('.$post->ID.')']); ?>
							</div>
							<a class="compare_products__header_product_title" href="<?php echo usam_product_url(); ?>"><?php echo $post->post_title; ?></a>
							<?php usam_product_rating( 'average_vote compare_products__rating' ); ?>
							<div class='compare_products__header_review'>
								<div class="review_rating"><?php echo usam_get_post_meta( $post->ID, 'rating' ); ?></div>
								<?php echo usam_get_post_meta( $post->ID, 'rating_count' )."&nbsp;".__('отзывов', 'usam') ?>
							</div>					
							<div class="compare_products__header_product_price"><?php echo usam_get_product_price_currency( $post->ID ); ?></div>
							<?php usam_product_variations( $post->ID ); ?>					
							<?php usam_addtocart_button( $post->ID ); ?>		
							<?php	
								$additional_units = usam_get_product_property( $post->ID, 'additional_units' );
								if ( !empty($additional_units) )
								{
									?>
									<div class="compare_products__units">	
										<?php usam_field_product_number(); ?>
										<?php usam_selection_product_units( $post->ID ); ?>
									</div>
									<?php
								}
							?>
						</div>	
					</div>						
					<?php
				}					
				?>
			</div>	
		</template>
		</site-slider>		
		<div id="compare_products_lists" class="compare_products__lists">
			<div class = "group_options" v-for="group in groups" :class="{'active':!group.hide}">		
				<div class="group_options__name" @click="group.hide=!group.hide">
					<span v-html="group.name"></span>
					<?php usam_svg_icon("angle-down-solid"); ?>
				</div>
				<div class = "attribute_options" v-for="attribute in product_attributes[categories[category].term_id]" v-show="!group.hide" v-if="group.term_id==attribute.parent">
					<div class = "attribute_options__item">			
						<div class="attribute_options__item_name" v-html="attribute.name"></div>
						<div class="attribute_options__item_option" :style="{transform: 'translateX(-'+translate + 'px)'}">
							<div class="attribute_options__item_option_value compare_products__column" v-for="value in attribute.values" v-html="value.value"></div>
						</div>									
					</div>	
				</div>
			</div>	
		</div>
	</div>
	<?php
	add_action('wp_footer', function() use($products, $categories_products) {
		$product_attributes = [];
		$products_ids = [];
		foreach ( $products as $product )		
		{				
			$products_ids[] = $product['product_id'];
			$attributes = usam_get_product_attributes_comparison( $product['product_id'] );	
			$terms = get_the_terms( $product['product_id'], 'usam-category' );
			foreach ( $terms as $term )	
			{
				foreach ( $attributes as $attribute )	
				{
					if ( !isset($product_attributes[$term->term_id]) )
						$product_attributes[$term->term_id] = [];
					if ( isset($product_attributes[$term->term_id][$attribute['term_id']]) )
						$product_attributes[$term->term_id][$attribute['term_id']]['values'][] = ['product_id' => $product['product_id'], 'value' => implode(', ', $attribute['value'])];
					else
						$product_attributes[$term->term_id][$attribute['term_id']] = ['name' => $attribute['name'], 'parent' => $attribute['parent'], 'values' => [['product_id' => $product['product_id'], 'value' => implode(', ', $attribute['value'])]]];
				}
			}
		}
		$terms = wp_get_object_terms( $products_ids, 'usam-category', ['orderby' => 'name', 'update_term_meta_cache' => false]);
		foreach ( $terms as &$term )	
			$term->products = $categories_products[$term->term_id];
		?> 
		<script>			
			var products=<?php echo json_encode( $products ); ?>;
			var categories=<?php echo json_encode( $terms ); ?>;
			var product_attributes = <?php echo json_encode( $product_attributes ); ?>;
			var attributes = <?php echo json_encode( usam_get_product_attributes() ); ?>;
		</script>	
		<?php
	});
}
?>
<div class="empty_page" :class="{'hide':products.length>0}">
	<div class="empty_page__icon"><?php usam_svg_icon('comparison') ?></div>
	<div class="empty_page__title"><?php  _e('Нет товаров для сравнения', 'usam'); ?></div>
	<div class="empty_page__description">
		<p><?php  _e('У вас пока нет товаров в списке сравнения.', 'usam'); ?></p>
		<p><?php  _e('На нашем каталоге вы найдете много интересных товаров.', 'usam'); ?></p>
	</div>
	<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть наши товары', 'usam'); ?></a>
</div>
<?php