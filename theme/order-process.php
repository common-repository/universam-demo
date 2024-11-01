<?php
// Описание: Шаблон корзины и оформления заказа. Написана на Vue. 
?>	
<?php usam_include_template_file('loading', 'template-parts'); ?>
<?php usam_include_template_file('basket-empty', 'checkout'); ?>
<div v-else-if="basket!==null" :class="{'is-loading-basket':send}" class="basket_content">
	<div class='usam_message message_error' v-if="errors.length>0">
		<p class='validation-error' v-for="(error, k) in errors"><span><?php echo  __('Ошибка', 'usam').': '; ?></span>{{error}}</p>
	</div>
	<div v-show="page=='basket'">
		<?php do_action('usam_basket_before'); ?>
		<div class='products_basket'>
			<div class="product_row" v-for="(product, k) in basket.products">				
				<div class="product_row__image basket_product_image"><img :src="product.small_image" width="100" height="100"></div>
				<div class="product_row__content">	
					<div class="product_row__name">
						<div class="product_row__gift" v-if="product.gift"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></div>
						<a :href="product.url" v-html="product.name"></a>
						<div class="product_row__sku"><?php _e('Артикул', 'usam'); ?>: <span v-html="product.sku"></span></div>
						<div class="product_row__actions">
							<span class="product_row__action" @click="remove(k)"><?php _e('Удалить', 'usam'); ?></span>
						</div>
					</div>
					<div class="product_row__content_right">
						<div class="product_row__prices">							
							<div class="product_row__oldprice" v-if="product.discount.value && !product.gift" v-html="product.old_price.currency"></div>
							<div class="product_row__price" v-if="product.price.value && !product.gift" v-html="product.price.currency"></div>
							<div class="product_row__discont" v-if="product.discount.value && !product.gift"><?php _e('Скидка', 'usam'); ?>: <span v-html="product.discount.currency"></span></div>
						</div>							
						<div class="product_row__quantity_change usam_quantity" v-if="product.gift!=1">	
							<span @click="minus(k)" class="usam_quantity__minus" data-title = "<?php _e('Уменьшить количество', 'usam'); ?>">-</span>
							<input type="number" :value="product.quantity" class="quantity_update" autocomplete="off"/>
							<span @click="plus(k)" class="usam_quantity__plus" data-title = "<?php _e('Увеличить количество', 'usam'); ?>">+</span>
						 </div>
						<div class="product_row__sum"><span v-if="product.total.value && !product.gift" v-html="product.total.currency"></span></div>
					</div>
				</div>
				<?php usam_svg_icon('close', ["@click" => "remove(k)", "class" => "product_row__close"]); ?>
			</div>
		</div>
		<div class ="basket_info">
			<div class ="basket_info-block basket_empty_cart_block">				
				<input type="button" class="button" @click="clear" value="<?php _e('Очистить корзину', 'usam'); ?>"/>
			</div>	
			<div class ="basket_info-block basket_bonuses_block" v-if="basket.uses_bonuses">
				<div class = "bonuses_submit" v-if="customer.user_logged">
					<div class = "bonuses_submit" v-if="basket.allowed_spend_bonuses || selected.bonuses">
						<button v-if="selected.bonuses" class="button" @click="selected.bonuses=0"><?php _e('Вернуть', 'usam'); ?></button>
						<button v-else class="button" @click="selected.bonuses=basket.allowed_spend_bonuses"><?php _e('Потратить', 'usam'); ?></button>
					</div>
					<div class = "usam_customer_bonus">
						<span class = "title"><?php _e('Ваши бонусы', 'usam'); ?>:</span>
						<span class ="total_bonuses" v-html="customer.bonuses-selected.bonuses"></span>
					</div>
				</div>
				<div class = "bonuses_info" v-else><?php _e('Для использования бонусов необходимо', 'usam'); ?> <a href="<?php echo usam_get_url_system_page('login'); ?>" data-modal="login" class="usam_modal"><?php _e('авторизоваться', 'usam'); ?></a> <?php _e('или', 'usam'); ?> <a href="<?php echo usam_get_url_system_page('login'); ?>" data-modal="login" class="usam_modal"><?php _e('зарегистрироваться', 'usam'); ?></a> <?php _e('на сайте.', 'usam'); ?></div>					
			</div>
		</div>	
		<div class ="basket_info basket_results">	
			<div class ="basket_info-block coupon_basket" v-if="basket.uses_coupons">
				<div class="coupon_code">
					<input type="text" placeholder="<?php _e('Введите код купона', 'usam'); ?>" class="option-input" v-model="coupon"/>
					<div class='validation-error' v-if="basket.errors.coupon!==undefined" v-html="basket.errors.coupon"></div>
				</div>
				<button class="coupon_button button" @click="selected.coupon=coupon" :class="{'main-button':coupon!=''}" :disabled="coupon==''"><?php _e('Активировать', 'usam'); ?></button>
			</div>
			<div class ="basket_info-block basket_totalprice_block">			
				<div class="totalprice_block">	
					<div class ="totalprice" v-if="basket.subtotal.value!=basket.amount_no_delivery.value">	
						<div class ="totalprice__title"><?php _e('Стоимость корзины', 'usam'); ?>:</div>	
						<span class ="totalprice__price" v-html="basket.subtotal.currency"></span>
					</div>
					<div class ="totalprice" v-if="basket.discount.value">	
						<div class ="totalprice__title"><?php _e('Общая скидка', 'usam'); ?>:</div>	
						<div class ="totalprice__price" v-html="basket.discount.currency"></div>
					</div>	
					<div class ="totalprice" v-if="selected.bonuses">	
						<div class ="totalprice__title"><?php _e('Потраченные бонусы', 'usam'); ?>:</div>	
						<div class ="totalprice__price" v-html="selected.bonuses"></div>	
					</div>				
					<div class ="totalprice" v-if="basket.taxes" v-for="(tax, k) in basket.taxes">	
						<div class ="totalprice__title">{{tax.name}}:</div>	
						<div class ="totalprice__price" v-html="tax.tax"></div>	
					</div>	
					<div class ="totalprice">	
						<div class ="totalprice__title totalprice__important"><?php _e('Общая цена', 'usam'); ?>:</div>	
						<div class ="totalprice__price totalprice__important" v-html="basket.amount_no_delivery.currency"></div>	
					</div>
					<div class ="totalprice" v-if="basket.cost_paid.value">	
						<div class ="totalprice__title"><?php _e('Оплачено', 'usam'); ?>:</div>	
						<div class ="totalprice__price" v-html="basket.cost_paid.currency"></div>	
					</div>						
				</div>	
			</div>
		</div>
		<div class='usam_checkout_taskbar'>
			<button class='button main-button' @click="page='checkout'"><?php _e('Оформить заказ', 'usam'); ?></button>
		</div>		
		<div id="cross_sells" class = "cross_sell_list basket_products_list" v-if="cross_sells">
			<h3 class="prodtitles"><?php _e('К Вашим покупкам прекрасно подойдут:', 'usam'); usam_change_block( admin_url( "admin.php?page=marketing&tab=crosssell" ), __("Изменить правила подбора Cross-Selling", "usam") ); ?></h3>
			<div class='slides js-carousel-products' v-html="cross_sells"></div>
		</div>
		<div class = "basket_gifts_list basket_products_list" v-if="gifts.length">
			<h3 class="prodtitles"><?php _e('Выберете подарок:', 'usam'); ?></h3>
			<div class='slides js-carousel-products'>
				<?php usam_include_template_file('gifts', 'template-parts'); ?>
			</div>
		</div>
		<?php do_action('usam_basket_after'); ?>
	</div>
	<div v-show="page=='checkout'">
		<?php usam_include_template_file( 'checkout' ); ?>
	</div>
</div>