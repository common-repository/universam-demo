<?php
//Список товаров
?>
<div class="widget_basket_products" v-if="basket.products.length">
	<div class="widget_basket_product" v-for="(product, k) in basket.products">
		<div class="basket_product_image"><img :src="product.small_image" alt=""></div>
		<div class="widget_basket_product__right">
			<div class="widget_basket_product__gift" v-if="product.gift"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></div>
			<a class="widget_basket_product__name" :href="product.url" v-html="product.name"></a>
			<div class="widget_basket_product__data" v-if="product.gift!=1">								
				<div class="widget_basket_product__price" v-html="product.price.currency"></div>
				<div class="widget_basket_product__quantity">{{product.quantity_unit_measure}}</div>
			</div>	
		</div>				
	</div>	
</div>
<?php 