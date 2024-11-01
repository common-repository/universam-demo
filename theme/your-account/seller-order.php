<?php
// Описание: Выводит подробные сведения выбранного заказа
?>
<div class = 'profile__title'>
	<button @click="key=null" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
	<h1 class="title"><?php _e('Заказ','usam'); ?><span class ='document_id'>№ {{order.number}}</span></h1>
</div>	
<div class ="view_form">
	<div class ='view_form__item order_status_name'>	
		<div class ='view_form__name'><?php _e( 'Дата заказа', 'usam'); ?>:</div>	
		<div class ='view_form__option'>{{localDate(order.date_insert,'d.m.Y')}}</div>	
	</div>
	<div class ='view_form__item order_status_name'>	
		<div class ='view_form__name'><?php _e( 'Статус заказа', 'usam'); ?>:</div>
		<div class ='view_form__option'><span class='item_status order_status_name' :style="'background:'+order.status_color+';color:'+order.status_text_color" v-html="order.status_name"></span></div>	
	</div>
	<div class ='view_form__item order_status_name'>
		<div class ='view_form__name'><?php _e( 'Способ получения', 'usam'); ?>:</div>
		<div class ='view_form__option' v-html="order.name"></div>
	</div>
	<div class ='view_form__item order_status_name' v-if="order.storage_pickup>0">
		<div class ='view_form__name'><?php _e( 'Офис получения', 'usam'); ?>:</div>
		<div class ='view_form__option'><span v-html="order.pickup.city+' '+order.pickup.address"></span> <span><?php _e( 'т.', 'usam'); ?> {{order.pickup.phone}}</span></div>
	</div>		
</div>		
<div class='products_order'>
	<div class="product_row" v-for="(product, k) in order.products">				
		<div class="product_row__image basket_product_image"><img :src="product.small_image" width="100" height="100"></div>
		<div class="product_row__content">	
			<div class="product_row__name">
				<div class="product_row__gift" v-if="product.gift"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></div>
				<a :href="product.url" v-html="product.name"></a>
				<div class="product_row__sku"><?php _e('Артикул', 'usam'); ?>: <span v-html="product.sku"></span></div>
			</div>
			<div class="product_row__content_right">
				<div class="product_row__prices">							
					<div class="product_row__price" v-html="product.price_currency"></div>
					<div class="product_row__quantity" v-html="product.quantity_unit_measure"></div>
				</div>
				<div class="product_row__sum" v-if="product.total>0 && !product.gift" v-html="product.total_currency"></div>
			</div>
		</div>
	</div> 
</div>
<div class="view_form totalprice_block">	
	<div class ="view_form__row" v-for="tax in order.total_product_taxes">	
		<div class ="totalprice">	
			<div class ="totalprice__title">{{tax.name}}:</div>	
			<div class ="totalprice__price" v-html="tax.tax"></div>	
		</div>
	</div>
	<div class ="view_form__row" v-if="order.price_without_discount">	
		<div class ="totalprice">	
			<div class ="totalprice__title"><?php _e('Стоимость', 'usam'); ?>:</div>	
			<span class ="totalprice__price" v-html="order.price_without_discount_currency"></span>
		</div>
	</div>	
	<div class ="view_form__row" v-if="order.discount>0">	
		<div class ="totalprice">	
			<div class ="totalprice__title"><?php _e('Общая скидка', 'usam'); ?>:</div>	
			<div class ="totalprice__price"><span class="item_status status_white" v-html="'-'+order.discount_currency"></span></div>
		</div>
	</div>
	<div class ="view_form__row" v-if="order.discount>0">	
		<div class ="totalprice">	
			<div class ="totalprice__title"><?php _e('Стоимость с учетом скидки', 'usam'); ?>:</div>	
			<span class ="totalprice__price" v-html="order.discounted_price_currency"></span>
		</div>
	</div>
	<div class ="view_form__row" v-if="order.shipping>0">	
		<div class ="totalprice">	
			<div class ="totalprice__title"><?php _e('Доставка', 'usam'); ?>:</div>	
			<div class ="totalprice__price"><span class="item_status status_black" v-if="order.shipping>0" v-html="'+'+order.shipping"></span></div>
		</div>
	</div>
	<div class ="view_form__row" v-if="order.discount>0">	
		<div class ="totalprice">	
			<div class ="totalprice__title totalprice__important"><?php _e('Итог', 'usam'); ?>:</div>	
			<span class ="totalprice__price totalprice__important" v-html="order.totalprice_currency"></span>
		</div>
	</div>				
</div>