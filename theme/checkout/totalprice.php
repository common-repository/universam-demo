<div class="totalprice_block">			
	<div class ="totalprice">	
		<div class ="totalprice__title"><?php _e('Товаров', 'usam'); ?>:</div>	
		<div class ="totalprice__price">{{basket.products.length}}</div>
	</div>
	<div class ="totalprice">	
		<div class ="totalprice__title"><?php _e('Стоимость', 'usam'); ?>:</div>	
		<div class ="totalprice__price" v-html="basket.subtotal.currency"></div>
	</div>
	<div class ="totalprice" v-if="basket.discount.value>0">
		<div class ="totalprice__title"><?php _e('Скидка', 'usam'); ?>:</div>	
		<div class ="totalprice__price" v-html="basket.discount.currency"></div>	
	</div>
	<div class ="totalprice" v-if="basket.bonuses>0">	
		<div class ="totalprice__title"><?php _e('Бонусы', 'usam'); ?>:</div>	
		<div class ="totalprice__price" v-html="basket.bonuses"></div>	
	</div>	
	<div class ="totalprice" v-if="!basket.virtual_products">
		<div class ="totalprice__title"><?php _e('Доставка', 'usam'); ?>:</div>	
		<div class ="totalprice__price" v-html="basket.shipping.currency"></div>
	</div>
	<div class ="totalprice" v-if="basket.taxes" v-for="(tax, k) in basket.taxes">	
		<div class ="totalprice__title">{{tax.name}}:</div>	
		<div class ="totalprice__price" v-html="tax.tax"></div>	
	</div>	
	<div class ="totalprice">	
		<div class ="totalprice__title totalprice__important"><?php _e('Итог', 'usam'); ?>:</div>	
		<div class ="totalprice__price totalprice__important" v-html="basket.total.currency"></div>	
	</div>		
</div>	
<div class="view_form totalprice_block" v-if="basket!==null && basket.cost_paid.value>0">		
	<div class ="view_form__title"><?php _e('Оплатить заказ', 'usam'); ?></div>
	<div class ="totalprice">	
		<div class ="totalprice__title"><?php _e('Оплачено', 'usam'); ?>:</div>	
		<div class ="totalprice__price" v-html="basket.cost_paid.currency"></div>	
	</div>
	<div class ="totalprice">	
		<div class ="totalprice__title totalprice__important"><?php _e('К оплате', 'usam'); ?>:</div>	
		<div class ="totalprice__price totalprice__important" v-html="basket.cost_unpaid.currency"></div>	
	</div>
</div>	