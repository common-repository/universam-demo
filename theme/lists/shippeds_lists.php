<?php usam_include_template_file('list-empty', 'template-parts'); ?>
<div class = 'orders lists' :class="{'list_loading':request}">
	<div class = 'order' v-for="(item, k) in items">
		<div class="list_header">
			<div class="list_header__title" @click="key=k">
				<span class="list_header__title_number"><?php _e('Заказ','usam'); ?> №{{item.number}}</span>
				<span class="list_header__title_date"><?php _e('от','usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</span>
			</div>		
			<span class='item_status list_header__status_name' :style="'background:'+item.status_color+';color:'+item.status_text_color" v-html="item.status_name"></span>
		</div>
		<div class="order__content">
			<div class="order__product" v-for="(product,i) in item.products" v-if="i<3">
				<div class="order__product_image image_container">
					<img :src='product.small_image'>
				</div>
				<div class="order__product_content">
					<div class="order__product_left">
						<div class="order__product_name"><a :href="product.url" v-html="product.name"></a></div>
						<div class="order__product_sku"><span class="order__product_sku_title"><?php _e('Артикул','usam'); ?>:</span><span v-html="product.sku"></span></div>
					</div>
					<div class="order__product_right">
						<div class="order__product_price" v-html="product.price_currency"></div>
					</div>
				</div>			
			</div>
			<div class="order__content">
				<div class="order__category_price">
					<span class ="order__price" v-html="item.price_currency"></span>
				</div>				
			</div>
		</div>
		<div class="order_footer">
			<div class="order_footer__row">
				<div class="order_footer__title">
					<span class="item_status order_footer__payment_status item_status_valid" v-if="item.paid==2">{{item.payment_status}}</span>
					<span class="item_status order_footer__payment_status item_status_attention" v-else-if="item.paid==0">{{item.payment_status}}</span>
				</div>	
				<div class="order_footer__total"  @click="key=k">
					<div class="order_footer__number_products"><?php _e('Товаров','usam'); ?>: {{item.products.length}}</div>
					<div class="order_footer__totalprice"><?php _e('Сумма','usam'); ?>: <span v-html="item.totalprice_currency"></span></div>
				</div>
			</div>		
		</div>
	</div>
	<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
</div>
<?php
