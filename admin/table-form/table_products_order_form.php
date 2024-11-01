<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_products_form.php' );		
class USAM_Table_Products_Order_Form extends USAM_Table_Products_Form
{	
	public function section_products( )
	{			
		?>					
		<div class = 'edit_form'>					
			<?php
			if ( usam_check_type_product_sold('product') )
			{
				$documents = usam_get_shipping_documents_order( $this->data['id'] ); 
				if ( $documents )
				{
					$result = 0;
					$i = 0;
					foreach( $documents as $document )
					{
						if ( $document->storage )
						{					
							$products = usam_get_products_shipped_document( $document->id );
							$ids = [];
							foreach( $products as $product )
							{
								$ids[] = $product->product_id;
							}
							usam_update_cache( $ids, [USAM_TABLE_STOCK_BALANCES => 'product_stock'], 'product_id' );
							foreach( $products as $product )
							{
								$q = (int)usam_get_product_stock( $product->product_id, 'storage_'.$document->storage );
								if ( $q < $product->quantity )
									$result = 1;
								else
									$i++;
							}
						}
						else
						{
							$result = 2;
							break;
						}
					}
					if ( $result != 2 )
					{
						if ( count($documents) == 1 )
							$storage = usam_get_storage( $documents[0]->storage );	
						else
							$storage = ['owner' => ''];
						if ( !$storage['owner'] )
						{
							?>
							<div class ="edit_form__item">
								<div class ="edit_form__item_name"><?php _e( 'Статус товаров','usam'); ?>:</div>
								<div class ="edit_form__item_option order_product_status">						
									<?php if ( $result ) { ?>
										<?php if ( $i ) { ?>
											<span class="item_status item_status_notcomplete"><?php echo !empty($storage['title'])?sprintf(__('Отсутствуют на %s','usam'),'"'.$storage['title'].'"'):__('Отсутствуют','usam'); ?></span>
										<?php } else { ?>
											<span class="item_status item_status_attention"><?php echo !empty($storage['title'])?sprintf(__('Отсутствуют на %s','usam'),'"'.$storage['title'].'"'):__('Отсутствуют','usam'); ?></span>
									<?php } } else { ?>
										<span class="item_status item_status_valid"><?php echo !empty($storage['title'])?sprintf(__('В наличии на %s','usam'),'"'.$storage['title'].'"'):__('В наличии','usam'); ?></span>
									<?php } ?>
								</div>
							</div>
							<?php
						}
					}
				}
			}
			?>
			<div class ="edit_form__item">
				<div class="edit_form__item_name"><label><?php esc_html_e( 'Цены', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span v-if="!edit || !abilityChange">
						<span v-for="value in type_prices" v-if="value.code==data.type_price">
							<span v-html="value.title"></span>							
							<span class="item_status item_status_valid" v-if="value.code==data.contact_type_price"><?php _e('Персональная цена', 'usam'); ?></span>
						</span>		
					</span>
					<select v-else name="type_price" v-model="data.type_price">
						<option :value='value.code' v-for="value in type_prices" v-html="value.title"></option>
					</select>					
				</div>
			</div>	
			<?php if ( usam_check_bonuses_displayed( $this->data['totalprice'] ) ) { ?>
				<div class ="edit_form__item">
					<div class="edit_form__item_name"><?php esc_html_e( 'Сумма бонусов за покупку', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<?php $url = admin_url("admin.php?page=crm&tab=contacts&table=bonus_cards&form=view&form_name=bonus_card"); ?>
						<span v-if="Object.keys(bonus_card) && data.user_ID">
							<?php printf( __( '%s на карту %s, на которой %s бонусов', 'usam'), '<span class="item_status item_status_valid">+{{formatted_number(accruedBonuses)}}</span>', "<a :href='`$url&id=`+bonus_card.code'>{{bonus_card.code}}</a>", "<span class='item_status' :class='bonus_card.sum>0?`item_status_valid`:`item_status_attention`'>{{formatted_number(bonus_card.sum)}}</span>" ); ?>
						</span>
						<span v-if="!Object.keys(bonus_card) && data.user_ID" class="item_status item_status_valid">{{formatted_number(accruedBonuses)}}</span>
						<span v-if="data.user_ID==0" class="item_status_attention item_status"><?php _e( 'Личный кабинет не привязан к заказу', 'usam'); ?></span>
					</div>
				</div>
			<?php } ?>				
		</div>
		<?php		
		$discounts = [];
		foreach ( $this->data['discounts'] as $discount )
		{ 
			if ( !$discount->product_id )
				$discounts[] = $discount;			
		}					
		if ( !empty($discounts))
		{
		?>
		<div id="order_discount" class = 'order_discount'>
			<div class = 'order_discount_name'><?php esc_html_e( 'Акции на корзину', 'usam'); ?></div>
			<div class = 'order_discount_rules'>								
				<?php		
				$url = admin_url('admin.php?page=manage_discounts&tab=basket&form=edit&form_name=basket_discount');		
				foreach ( $discounts as $order_discount )
				{ 									
					?><a href='<?php echo add_query_arg(['id' => $order_discount->id], $url); ?>'><?php echo usam_get_discount_rule_name($order_discount, $this->data['type_price']); ?></a><?php
				}	
				?>
			</div>							
		</div>
		<?php
		}
		$columns = [
			'n'         => __('№', 'usam'),
			'title'     => __('Имя', 'usam'),			
		];				
		$columns['quantity'] = __('Кол-во', 'usam');
		$columns['old_price'] = __('Цена', 'usam');
		$columns['discount'] = __('Скидка', 'usam');
		$columns['price'] = __('Со скидкой', 'usam');
		$product_taxes = usam_get_order_product_taxes( $this->data['id'] );
		if ( !empty($product_taxes) ) 
		{ 						
			foreach ( $product_taxes as $id => $tax ) 
				$columns['tax_'.$tax->tax_id] = $tax->name;		
		}		
		$columns['total'] = __('Всего', 'usam');
		$columns['tools'] = '';
	
		$this->display($columns, 'order');	
	}	
	
	function currency_display( $price ) 
	{			
		return usam_get_formatted_price( $price, ['type_price' => $this->data['type_price'], 'wrap' => true]);
	}
	
	public function get_format_discount( $discount )
	{
		$p = '';
		if ( $discount > 0 && $this->data['order_basket'] )
		{
			$p = round( $discount/ $this->data['order_basket']*100, 2 );
			$p = " ( -$p% )";
		}
		echo $this->currency_display( $discount ).$p;	
	}	
	
	protected function display_body_table()
	{		
		?>
		<tr v-if="products.length" v-for="(product, k) in products">
			<td class="column-n">{{k+1}}</td>
			<td class="column-title">
				<div class="product_name_thumbnail">
					<div class="product_image image_container viewer_open" @click="viewer(k)">
						<img :src="product.small_image">
					</div>
					<div class="product_name">	
						<input size='4' type='text' :name="'products['+product.id+'][name]'" v-model="product.name" v-if="edit && abilityChange">
						<div v-else >	
							<p v-if="product.price==0"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></p>
							<a :href="product.url" v-html="product.name"></a>							
						</div>	
						<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
						<span v-if="product.product_day" class="item_status item_status_valid"><?php esc_html_e( 'Товар дня', 'usam'); ?></span>
					</div>
				</div>	
				<div class = 'order_discount product_order_discount' v-if="product.discounts.length">
					<div class = 'order_discount_name'><?php esc_html_e( 'Акции на товар', 'usam'); ?></div>
					<div class = 'order_discount_rules'>							
						<a :href='discount.url' v-for="discount in product.discounts" v-html="discount.name"></a>
					</div>								
				</div>	
				<div class = 'product_bonus' v-if="product.used_bonuses > 0">
					<strong><?php esc_html_e( 'Использованные бонусы', 'usam'); ?>:</strong>
					<span class = "item_status status_white" v-html="'-'+product.used_bonuses"></span>
				</div>
				<?php				
				if ( usam_check_bonuses_displayed( $this->data['totalprice'] ) )
				{
					?>			
					<div class = 'product_bonus' v-if="data.user_ID > 0">
						<strong><?php esc_html_e( 'Бонусы, которые будут начислены', 'usam'); ?>:</strong>								
						<input v-if="edit && abilityChange" size='4' type='text' :name="'products['+product.id+'][bonus]'" v-model="product.bonus">
						<span v-else class = "item_status item_status_valid" v-html="'+'+product.formatted_bonus"></span>
					</div>		
					<?php		
				}						
				?>				
			</td>			
			<?php $this->display_special_columns_table(); ?>
			<td class="column-quantity">				
				<div class = "quantity_product" v-if="edit && abilityChange">
					<input size='4' type='text' :name="'products['+product.id+'][quantity]'" v-model="product.quantity" @blur="recountProducts">	
					<div class = "quantity_product" v-if="product.units.length">
						<select :name="'products['+product.id+'][unit_measure]'" v-model="product.unit_measure" @blur="recountProducts">
							<option v-for="additional_unit in product.units" :value="additional_unit.code" v-html="additional_unit.in"></option>
						</select>
					</div>
				</div>
				<span v-else v-html="product.formatted_quantity"></span>							
			</td>
			<td class="column-edit_price">	
				<div class="discount_selection" v-if="edit && abilityChange">
					<input size='4' type='text' :name="'products['+product.id+'][old_price]'" v-model="product.old_price" @blur="recountProducts">
					<input size='4' type='hidden' :name="'products['+product.id+'][price]'" v-model="product.price">
					<input size='4' type='hidden' :name="'products['+product.id+'][product_id]'" v-model="product.product_id">
				</div>
				<div class="discount_selection" v-else v-html="product.old_price"></div>	
			</td>
			<td class="column-discount">	
				<div class="discount_selection" v-if="edit && abilityChange">
					<input size='4' type='text' :name="'products['+product.id+'][discount]'" v-model="product.discount" @blur="recountProducts">
					<select :name="'products['+product.id+'][type]'" v-model="product.type" @blur="recountProducts">
						<option value='p'>%</option>
						<option value='f'>-</option>
					</select>
				</div>
				<div v-else>
					<span v-html="product.formatted_discount"></span>
					<span v-if="product.type=='p'">%</span>
					<span v-else></span>
				</div>
			</td>
			<td class="column-discount_price"><span v-html="product.formatted_price"></span></td>
			<td class="column-product_tax" v-for="item in product.taxes">
				<span v-if="item.is_in_price" v-html="item.tax"></span>				
				<span class="item_status status_black" v-else v-html="'+'+item.tax"></span>
			</td>	
			<td class="column-total"><span v-html="product.formatted_total"></span></td>
			<td class="column-delete">					
				<a class="action_delete" v-if="edit && abilityChange" href="" @click="delElement($event, k)"></a>
			</td>	
		</tr>
		<?php		
	}
		
	protected function display_total_table()
	{	
		?>
		<tr v-if="taxtotal>0" class="usam_order_basket">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Сумма без налога', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="formatted_number(subtotal-taxtotal)"></th>
			<th></th>
		</tr>
		<tr v-if="taxtotal>0" v-for="tax in total_product_taxes"></td>
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class = "products_total_name" v-html="tax.name+':'"></th>
			<th class = "products_total_value">
				<span class="item_status status_black" v-html="'+'+formatted_number(tax.tax)"></span>
			</th>
			<th></th>
		</tr>
		<tr class="usam_order_basket" v-if="subtotal-totalprice!=0">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="formatted_subtotal"></th>
			<th></th>
		</tr>
		<tr v-if="discount>0">
			<td :colspan = 'table_columns.length-5'></td>
			<th class = "products_total_name" colspan='3'><?php esc_html_e( 'Скидка', 'usam'); ?>:</th>
			<th class = "products_total_value">
				<span class="item_status status_white" v-if="discount>0" v-html="'-'+formatted_discount"></span>
			</th>
			<th></th>
		</tr>			
		<tr v-if="data.used_bonuses>0 || edit && abilityChange && Object.keys(bonus_card)">
			<td :colspan = 'table_columns.length-5'>			
				<div class = 'edit_form' v-if="edit && abilityChange && Object.keys(bonus_card) && bonus_card.sum>0">							
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Списать бонусы с карты', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input size="20" type="number" v-model="bonuses" :max="bonus_card.sum" @input="bonuses=bonuses > bonus_card.sum?bonus_card.sum:bonuses" @blur="addBonuses(bonuses)">
						</div>
					</div>
				</div>
			</td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Используемые бонусы', 'usam'); ?>:</th>
			<th class = "products_total_value">
				<span class="item_status status_white" v-if="data.used_bonuses>0">-{{data.used_bonuses}}</span>
			</th>				
			<th></th>
		</tr>	
		<tr class="usam_order_final_basket" v-if="discount>0">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Стоимость с учетом скидки', 'usam'); ?>:</th>
			<th class = "products_total_value"  v-html="formatted_number(subtotal-discount, data.rounding)"></th>		
			<th></th>
		</tr>			
		<tr v-if="data.shipping>0">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Доставка', 'usam'); ?>:</th>
			<th class = "products_total_value"><span class="item_status status_black" v-if="data.shipping>0" v-html="'+'+data.shipping"></span></th>
			<th></th>
		</tr>
		<tr class ="products_total_amount">
			<td :colspan = 'table_columns.length-5'>
				<div class = 'edit_form' v-if="edit && abilityChange || data.coupon_name">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Купон', 'usam'); ?>:</div>
						<div class ="edit_form__item_option" v-if="edit && abilityChange">							
							<input size="20" type="text" v-model="data.coupon_name">
							<input size='4' type='hidden' :name="'_coupon_code_order'" v-model="data.coupon_name">
						</div>
						<div class ="edit_form__item_option" v-else>							
							<a :href='data.url'>{{data.coupon_name}}</a>{{data.coupon_effect_message}}
						</div>
					</div>
				</div>			
			</td>
			<th colspan='3' class='products_total_name'><?php esc_html_e( 'Итог', 'usam'); ?>:</th>
			<th class='products_total_value'><span v-html="formatted_totalprice"></span></th>
			<th></th>
		</tr>			
		<tr v-if="data.weight">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class='products_total_name'><?php esc_html_e( 'Вес заказа', 'usam'); ?>:</th>
			<th class='products_total_value'>{{data.weight}}</th>
			<th></th>
		</tr>		
		<tr v-if="data.volume">
			<td :colspan = 'table_columns.length-5'></td>
			<th colspan='3' class='products_total_name'><?php esc_html_e( 'Объем заказа', 'usam'); ?>:</th>
			<th class='products_total_value'>{{data.volume}}</th>
			<th></th>
		</tr>
		<?php
	}	
	
	public function select_product_buttons( )
	{	
		?>	
		<button v-if="!edit && abilityChange" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить товар', 'usam'); ?></button>
		<?php
		if( current_user_can( 'edit_order_contractor' ) )
		{
			?><span id="add_orders_spplier"><button v-if="!edit && abilityChange && orders_contractor!==null" type="button" class="button" @click="addOrdersSpplier"><?php _e( 'Сделать документы поставщику', 'usam'); ?></button></span><?php
		}
		?>
		<div v-if="edit && abilityChange" class="select_product__buttons">			
			<button type="button" class="button button-primary" @click="saveElement" v-if="!edit_form"><?php _e( 'Сохранить', 'usam'); ?></button>
			<button type="button" class="button" @click="edit=!edit" v-if="!edit_form"><?php _e( 'Отменить', 'usam'); ?></button>
			<?php 
			if ( usam_check_type_product_sold( 'product' ) && current_user_can('view_shipped') )
			{
				?>	
				<div class="add_items__storage">		
					<?php 					
					$shipping_documents = usam_get_shipping_documents_order( $this->data['id'] );					
					if ( !empty($shipping_documents) ) { ?>	
						<select v-model="change_shipping">
							<?php														
								foreach ( $shipping_documents as $document )		
								{										
									$storage = usam_get_storage( $document->storage ); 
									$storage_name = isset($storage['title'])?$storage['title']:'';
									?><option value="<?php echo $document->id; ?>"><?php printf( __('Отгрузка № %s со склада %s', 'usam'), $document->id, $storage_name ); ?></option><?php
								}									
							?>
						</select>
					<?php }  else { ?>	
						<select v-model="add_shipping">
							<?php	
								$storages = usam_get_storages();	
								foreach ( $storages as $storage )		
								{										
									?><option value="<?php echo $storage->id; ?>"><?php printf( __('Отгрузка со склада %s', 'usam'), $storage->title ); ?></option><?php
								}									
							?>
						</select>
					<?php } ?>	
				</div>
			<?php } ?>	
			<div class="add_items__payment">
				<select v-model="change_payment">
					<option v-for="payment in data.payments" v-if="payment.status==1" :value="payment.id"><?php printf( __('В добавить в оплату №%s', 'usam'), '{{payment.id}}' ); ?></option>
					<option value="0"><?php _e("Не добавлять в оплату","usam"); ?></option>
				</select>	
			</div>				
		</div>		
		<?php		
	}
}