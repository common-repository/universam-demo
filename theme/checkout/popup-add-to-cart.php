<?php 
// Описание: Шаблон окно добавления в корзину
?>
<div id="popup_addtocart" class="popup_addtocart" v-show="show" :style="{left: left+ 'px', top: top + 'px' }" v-cloak>
	<div class = "popup_addtocart__title"><?php _e('Товар добавлен в корзину', 'usam'); ?></div>
	<div class = "popup_addtocart__product">	
		<div class = "popup_addtocart__image">
			<img :src="product.image" alt="">
		</div>
		<div class = "popup_addtocart__content">
			<div class = "popup_addtocart__product_name" v-html="product.name"></div>
			<div class = "popup_addtocart__quantity_price">
				<div class = "popup_addtocart__quantity" v-html="product.quantity_unit_measure"></div>
				<div class = "price popup_addtocart__price" v-if="product.price" v-html="product.price.currency"></div>
			</div>
		</div>
	</div>
	<div class = "popup_addtocart__buttons">	
		<a href="<?php echo usam_get_url_system_page( 'basket' ); ?>" class="button main-button button_buy"><?php _e('Перейти в корзину', 'usam'); ?></a>
		<button @click='show=0' class="button-v3"><?php _e('Продолжить покупки', 'usam'); ?></button>
	</div>
</div>
<?php 