<?php 
// Описание: Шаблон товара-подарка. Обычно используется в корзине
?>
<div class="product_grid "  v-for="(product, k) in gifts" :product_id="product.ID">
	<div class="product_grid__wrapper">				
		<div class="product_grid__substrate"></div>	
		<div class = "product_link">	
			<div class="product_grid__image"><img :src="product.small_image"></div>
			<div class="product_grid__swatches">				
				<span @click="quick_view(product.ID)"><?php usam_svg_icon('quick-view'); ?></span>
			</div>			
		</div>		
		<div class="product_grid__information">	
			<a class = "product_grid__text product_link" :href="product.url">
				<div class="product_grid__title" v-html="product.post_title"></div>									
			</a>
			<div class="product_grid__buttons">					
				<button class="button main-button add_gift_button" @click="add_gift(product.ID)"><?php _e('Выбрать', 'usam'); ?></button>
			</div>
		</div>			
	</div>
</div>