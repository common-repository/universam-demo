<?php
// Описание: Страница "Наборы товаров"
?>
<div id="sets" class="sets" v-if="sets[tab]" v-cloak>	
	<div class="set">
		<div class="set__list">
			<div class="set__header" v-for="(set, k) in sets" @click="tab=k" :class="{'set_selected':tab==k}">
				<div class="set__header_image" v-if="set.image"><img :src="set.image"></div>
				<div class="set__header_name" v-html="set.name"></div>
				<div class="set__header_count"><?php esc_html_e('Товаров', 'usam'); ?>: {{set.number_products}}</div>
				<div class="set__header_sum" v-html="set.totalprice.currency"></div>
			</div>
		</div>
		<div class='set__products'>
			<div class='set__category' v-for="(category, i) in sets[tab].categories">	
				<div class="set__category_name">
					<span v-html="category.name"></span>
					<?php usam_svg_icon('alert-circle-outline', ['@click' => 'category.show_description = !category.show_description', 'v-if' => 'category.description', 'class' => 'set__category_info']); ?>
				</div>
				<div class="set__category_description" v-html="category.description" v-if="category.show_description"></div>
				<div class='set__products_list'>
					<div class='set__product' v-for="(product, j) in category.products" :class="{'set_product_not_selected':!product.status}" v-if="product.status || category.all">
						<div class='set__product_left'>
							<input type='checkbox' v-model="product.status" class="option-input">
							<div class='set__product_image' @click="quick_view(product.ID)"><img :src="product.small_image"></div>
						</div>
						<div class='set__product_right'>							
							<div class='set__product_content'>
								<div class='set__product_name' @click="quick_view(product.ID)" v-html="product.post_title"></div>
								<div class='set__product_price'>
									<div class="prices" itemscope itemprop="offers" itemtype="http://schema.org/Offer">
										<span class="price_text"><?php esc_html_e('Цена', 'usam'); ?>:</span>
										<span v-if="product.old_price.value" class="old_price" v-html="product.old_price.currency"></span>
										<span class="price" v-html="product.price.currency"></span>
									</div>
								</div>									
							</div>
							<div class='set__product_edit' v-if="product.status">
								<div class="usam_quantity">	
									<span value="-" @click="minus(i,j)" class="usam_quantity__minus">-</span>
									<input type="number" :value="product.quantity" class="quantity_update" autocomplete="off"/>
									<span value="+" @click="plus(i,j)" class="usam_quantity__plus">+</span>
								 </div>
								<div class='set__product_total' v-html="product.total.currency"></div>					 
							</div>	
						</div>						
					</div>
				</div>
				<div class='set__product_more' v-if="category.hidden && !category.all" @click="category.all=1"><?php printf( __('Ещё %s вариантов', 'usam'), '{{category.hidden}}'); ?></div>
				<div class='set__product_more' v-if="category.hidden && category.all" @click="category.all=0"><?php printf( __('Скрыть %s вариантов', 'usam'), '{{category.hidden}}'); ?></div>	
			</div>
		</div>
	</div>
	<div class="sidebar_set"> 
		<div class="checkout-payment-block"> 
			<div class="view_form totalprice_block">	
				<div class ="view_form__title" v-html="sets[tab].purchase_name"></div>
				<div class ="view_form__row">	
					<div class ="totalprice">	
						<div class ="totalprice__title"><?php _e('Стоимость', 'usam'); ?>:</div>	
						<span class ="totalprice__price" v-html="sets[tab].totalprice.currency"></span>
					</div>
				</div>
				<div class ="view_form__row">	
					<div class ="totalprice">	
						<div class ="totalprice__title"><?php _e('Товаров', 'usam'); ?>:</div>	
						<div class ="totalprice__price">{{sets[tab].number_products}}</div>
					</div>
				</div>	
				<div class ="view_form__row">	
					<a href="<?php echo usam_get_url_system_page('checkout'); ?>" @click="go_checkout" class="button main-button"><?php _e('Оформить заказ', 'usam'); ?></a>
				</div>			
			</div>
		</div>
	</div>
</div>
<?php
global $post, $wp_query; 
if ( isset($wp_query->query['id']) )
	$query['page_id'] = $wp_query->query['id'];
else
{	
	$query['page_id'] = [ $post->ID ];
	if ( usam_is_system_page( 'reviews' ) )
		$query['page_id'][] = 0;
}
$customer_reviews = new USAM_Customer_Reviews_Theme(); 
echo $customer_reviews->show_button_reviews_form( 'top' );
echo $customer_reviews->show_reviews_form( 'top' );	
echo $customer_reviews->output_reviews_show( $query );
echo $customer_reviews->show_button_reviews_form( 'bottom' );
echo $customer_reviews->show_reviews_form( 'bottom' );
echo $customer_reviews->aggregate_footer(); 
?>