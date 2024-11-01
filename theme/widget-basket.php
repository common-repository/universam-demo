<?php 
// Описание: Виджет корзины. Написано с использованием Vue.
?>
<div class="usam_cart_loading" v-if="basket==null"><?php _e( 'Загрузка...', 'usam'); ?></div>
<div class="widget_basket_products" v-if="basket.products.length" :class="{'is-loading-basket':send}">
	<div class="widget_basket_product" v-for="(product, k) in basket.products">
		<div class="basket_product_image"><img :src="product.small_image" alt=""></div>
		<div class="widget_basket_product__right">			
			<div class="widget_basket_product__gift" v-if="product.gift"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></div>
			<div class="widget_basket_product__name"><a :href="product.url" v-html="product.name"></a><?php usam_svg_icon('close', ["@click" => "remove(k)", "class" => "widget_basket_product__close"]) ?></div>
			<div class="widget_basket_product__data" v-if="product.gift!=1">								
				<div class="widget_basket_product__price" v-html="product.price.currency"></div>
				<div class="usam_quantity">	
					<span @click="minus(k)" class="usam_quantity__minus" data-title = "<?php _e('Уменьшить количество', 'usam'); ?>">-</span>			
					<input type="number" v-model="product.quantity" class="quantity_update" autocomplete="off"/>
					<span @click="plus(k)" class="usam_quantity__plus" data-title = "<?php _e('Увеличить количество', 'usam'); ?>">+</span>
				 </div>	
			</div>	
		</div>				
	</div>	
</div>
<div class="view_form widget_basket_totalprice" v-if="basket.products.length">				
	<div class ="view_form__row">	
		<div class ="totalprice">	
			<div class ="totalprice__title"><?php _e('Стоимость', 'usam'); ?>:</div>	
			<div class ="totalprice__price" v-html="basket.subtotal.currency"></div>	
		</div>
	</div>	
	<div class ="view_form__row">	
		<a target="_parent" href="<?php echo usam_get_url_system_page('basket'); ?>" title="<?php _e('Оформить заказ', 'usam'); ?>" class="go_checkout"><?php _e('Оформить заказ', 'usam'); ?></a>
	</div>	
</div>
<div class="empty_page" v-if="basket.products.length===0">
	<div class="empty_page__icon"><?php usam_svg_icon('basket') ?></div>
	<div class="empty_page__title"><?php  _e('К сожалению, Ваша корзина пуста', 'usam'); ?></div>		
	<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть товары', 'usam'); ?></a>
</div>