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
	<div class ='view_form__item order_status_name' v-if="order.shipping.length == 1">
		<div class ='view_form__name'><?php _e( 'Способ получения', 'usam'); ?>:</div>
		<div class ='view_form__option' v-html="order.shipping[0].name"></div>
	</div>
	<div class ='view_form__item order_status_name' v-if="order.shipping.length == 1 && order.shipping[0].storage_pickup>0">
		<div class ='view_form__name'><?php _e( 'Офис получения', 'usam'); ?>:</div>
		<div class ='view_form__option'><span v-html="order.shipping[0].pickup.city+' '+order.shipping[0].pickup.address"></span> <span><?php _e( 'т.', 'usam'); ?> {{order.shipping[0].pickup.phone}}</span></div>
	</div>
	<div class ='view_form__item order_status_name' v-if="order.paid==2 || order.paid==0">	
		<div class ='view_form__name'><?php _e( 'Статус оплаты', 'usam'); ?>:</div>
		<div class ='view_form__option'>
			<span class="item_status order_footer__payment_status item_status_valid" v-if="order.paid==2">{{order.payment_status}}</span>
			<span class="item_status order_footer__payment_status item_status_attention" v-else-if="order.paid==0">{{order.payment_status}}</span>
		</div>	
	</div>
	<div class ='view_form__item order_status_name' v-if="order.paid && order.date_paid">	
		<div class ='view_form__name'><?php _e( 'Дата оплаты', 'usam'); ?>:</div>
		<div class ='view_form__option'>
			<span class="order_footer__payment_date">{{localDate(order.date_paid,'d.m.Y')}}</span>
		</div>	
	</div>			
</div>	
<div class='products_order_title'><?php _e('Купленные товары', 'usam'); ?></div>
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
<div class="totalprice_block">	
	<div class ="totalprice" v-for="tax in order.total_product_taxes">	
		<div class ="totalprice__title">{{tax.name}}:</div>	
		<div class ="totalprice__price" v-html="tax.tax"></div>	
	</div>
	<div class ="totalprice" v-if="order.discount>0">	
		<div class ="totalprice__title"><?php _e('Стоимость', 'usam'); ?>:</div>	
		<span class ="totalprice__price" v-html="order.price_without_discount_currency"></span>
	</div>
	<div class ="totalprice" v-if="order.discount>0">	
		<div class ="totalprice__title"><?php _e('Общая скидка', 'usam'); ?>:</div>	
		<div class ="totalprice__price"><span class="item_status status_white" v-html="'-'+order.discount_currency"></span></div>
	</div>
	<div class ="totalprice" v-if="order.discount>0">	
		<div class ="totalprice__title"><?php _e('Стоимость с учетом скидки', 'usam'); ?>:</div>	
		<span class ="totalprice__price" v-html="order.discounted_price_currency"></span>
	</div>
	<div class ="totalprice" v-if="order.shipping>0">	
		<div class ="totalprice__title"><?php _e('Доставка', 'usam'); ?>:</div>	
		<div class ="totalprice__price"><span class="item_status status_black" v-if="order.shipping>0" v-html="'+'+order.shipping"></span></div>
	</div>
	<div class ="totalprice">	
		<div class ="totalprice__title totalprice__important"><?php _e('Итог', 'usam'); ?>:</div>	
		<span class ="totalprice__price totalprice__important" v-html="order.totalprice_currency"></span>
	</div>		
</div>	
<div class='tbox'>
	<div class='user_profile__section_title'><?php _e( 'Данные покупателя', 'usam'); ?></div>	
	<div class='customer_details view_form' v-for="group in propertyGroups" v-if="check_group(group.code)">
		<div class ="view_form__title">{{group.name}}</div>
		<div class ="view_form__item" v-for="(property, k) in order.properties" v-if="property.group==group.code">	
			<div class ="view_form__name" v-html="property.name"></div>
			<div class ="view_form__option">
				<?php usam_include_template_file('view-property', 'template-parts'); ?>
			</div>
		</div>		
	</div>	
</div>
<div class='tbox' v-if="order.shipping.length>1">
	<div class='user_profile__section_title'><?php _e('Получение товара', 'usam'); ?></div>
	<div class='shipping_documents' v-for="document in order.shipping">
		<div class ="view_form">
			<div class ='view_form__item order_status_name'>	
				<div class ='view_form__name'><?php _e( 'Номер', 'usam'); ?>:</div>	
				<div class ='view_form__option' v-html="document.number"></div>	
			</div>
			<div class ='view_form__item order_status_name'>	
				<div class ='view_form__name'><?php _e( 'Способ получения', 'usam'); ?>:</div>	
				<div class ='view_form__option' v-html="document.name"></div>	
			</div>
			<div class ='view_form__item order_status_name'>	
				<div class ='view_form__name'><?php _e( 'Статус отгрузки', 'usam'); ?>:</div>
				<div class ='view_form__option'><span class='item_status order_status_name' :style="'background:'+order.status_color+';color:'+order.status_text_color" v-html="document.status_name"></span></div>	
			</div>		
			<div class ='view_form__item order_status_name' v-if="document.storage_pickup>0">
				<div class ='view_form__name'><?php _e( 'Офис получения', 'usam'); ?>:</div>
				<div class ='view_form__option'><span v-html="document.pickup.city+' '+document.pickup.address"></span> <span><?php _e( 'т.', 'usam'); ?> {{document.pickup.phone}}</span></div>
			</div>			
			<div class ='view_form__item order_status_name' v-if="document.date_delivery">	
				<div class ='view_form__name' v-if="document.storage_pickup"><?php _e( 'Время готовности', 'usam'); ?>:</div>
				<div class ='view_form__name' v-else><?php _e( 'Время доставки', 'usam'); ?>:</div>
				<div class ='view_form__option'>
					<span class="order_footer__payment_date">{{localDate(document.date_delivery,'d.m.Y  H:i')}}</span>
				</div>	
			</div>	
			<div class ='view_form__item order_status_name' v-if="document.date_delivery">	
				<div class ='view_form__name'><?php _e( 'Отслеживание отправления', 'usam'); ?>:</div>
				<div class ='view_form__option'>
					<a :href="'<?php echo usam_get_url_system_page('tracking'); ?>?track_id='+document.track_id" class='tracking' title="<?php _e('Посмотреть историю отправления', 'usam'); ?>" v-html="document.track_id"></a>
				</div>	
			</div>			
		</div>	
		<div class='products_order'>
			<div class="product_row" v-for="(product, k) in document.products">				
				<div class="product_row__image basket_product_image"><img :src="product.small_image" width="100" height="100"></div>
				<div class="product_row__content">	
					<div class="product_row__name">
						<a :href="product.url" v-html="product.name"></a>
						<div class="product_row__sku"><?php _e('Артикул', 'usam'); ?>: <span v-html="product.sku"></span></div>
					</div>
					<div class="product_row__content_right">
						<div class="product_row__prices">							
							<div class="product_row__price" v-html="product.price_currency"></div>
							<div class="product_row__quantity" v-html="product.quantity_unit_measure"></div>
						</div>			
					</div>
				</div>
			</div> 
		</div>
	</div>
</div>
<div class='tbox' v-if="order.payments.length>1">
	<div class='user_profile__section_title'><?php _e('История оплаты', 'usam'); ?></div>
	<div class='payments'>
		<div class="payment_row" v-for="document in order.payments">				
			<div class="payment_row__name" v-html="document.name"></div>
			<div class="payment_row__content">
				<div class="payment_row__date">{{localDate(document.date_insert,'d.m.Y  H:i')}}</div>
				<div class="payment_row__status"><span class='item_status order_status_name' :style="'background:'+order.status_color+';color:'+order.status_text_color" v-html="document.status_name"></span></div>
				<div class="payment_row__sum" v-html="document.sum"></div>		
			</div>
		</div>
	</div>
</div>