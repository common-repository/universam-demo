<?php
$columns = [
	'n'         => __('№', 'usam'),
	'title'     => __('Товары (работы, услуги)', 'usam'),
	'price' => __('Цена', 'usam'),
	'discount'  => __('Скидка', 'usam'),
	'discount_price' => __('Цена со скидкой', 'usam'),		
	'quantity'   => __('Количество','usam'),	 		 
	'total'     => __('Всего', 'usam'),
	'tools'     => ''
];		
?>
<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'lead'" :loaded="$root.loaded" :edit="edit" :items="data.products" @change="data.products=$event" :taxes="data.product_taxes" :type_price="data.type_price" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>'>	
	<template v-slot:theadertools="slotProps">
		<div class = 'edit_form'>	
			<div class ="edit_form__item">
				<div class="edit_form__item_name"><?php esc_html_e( 'Цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<span v-if="!edit">
						<span v-for="value in type_prices" v-if="value.code==data.type_price">
							<span v-html="value.title"></span>							
							<span class="item_status item_status_valid" v-if="value.code==data.contact_type_price"><?php _e('Персональная цена', 'usam'); ?></span>
						</span>		
					</span>
					<select v-else v-model="data.type_price">
						<option :value='value.code' v-for="value in type_prices" v-html="value.title"></option>
					</select>					
				</div>
			</div>
		</div>	
	</template>	
	<template v-slot:tbody="slotProps">
		<tr v-if="slotProps.products.length" v-for="(product, k) in slotProps.products">
			<td class="column-n">{{k+1}}</td>
			<td class="column-title">
				<div class="product_name_thumbnail">
					<div class="product_image image_container viewer_open" @click="slotProps.viewer(k)">
						<img :src="product.small_image">
					</div>
					<div class="product_name">	
						<input size='4' type='text' v-model="product.name" v-if="edit">
						<div v-else >	
							<p v-if="product.price==0"><span class="label_product_gift"><?php _e('Подарок', 'usam'); ?></span></p>
							<a :href="product.url" v-html="product.name"></a>							
						</div>
						<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
					</div>					
				</div>	
				<div class = 'order_discount product_order_discount' v-if="product.discounts!==undefined && product.discounts.length">
					<div class = 'order_discount_name'><?php esc_html_e( 'Акции на товар', 'usam'); ?></div>
					<div class = 'order_discount_rules'>							
						<a :href="'<?php admin_url('admin.php?page=manage_discounts&tab=discount&form=edit&form_name=product_discount'); ?>&id='+discount.rule_id" v-for="discount in product.discounts" v-html="discount.name"></a>
					</div>								
				</div>				
			</td>			
			<td :class="'column-'+column" v-for="column in slotProps.user_columns"><span v-html="product[column]"></span></td>
			<td class="column-edit_price">	
				<div class="discount_selection" v-if="edit">
					<input size='4' type='text' v-model="product.old_price" @blur="slotProps.recountProducts">
				</div>
				<div class="discount_selection" v-else v-html="product.old_price"></div>	
			</td>
			<td class="column-discount">	
				<div class="discount_selection" v-if="edit">
					<input size='4' type='text' v-model="product.discount" @blur="slotProps.recountProducts">
					<select v-model="product.discount_type" @blur="slotProps.recountProducts">
						<option value='p'>%</option>
						<option value='f'>-</option>
					</select>
				</div>
				<div v-else>
					<span v-html="slotProps.formatted_number(product.discount, 2)"></span>
					<span v-if="product.type=='p'">%</span>
					<span v-else></span>
				</div>
			</td>
			<td class="column-discount_price"><span v-html="slotProps.formatted_number(product.price)"></span></td>			
			<td class="column-quantity">
				<div class = "quantity_product" v-if="edit">
					<input size='4' type='text' v-model="product.quantity" @blur="slotProps.recountProducts">	
				</div>
				<span v-else v-html="product.quantity+' '+(Object.keys(units).length?units[product.unit_measure]:'')"></span>
			</td>
			<td class="column-total"><span v-html="slotProps.formatted_number( product.total )"></span></td>
			<td class="column-delete">					
				<a class="action_delete" href="" @click="slotProps.delElement($event, k)" v-if="edit"></a>
			</td>	
		</tr>
	</template>
	<template v-slot:tfoot="slotProps">
		<tr v-if="slotProps.taxtotal>0" class="usam_order_basket">
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Сумма без налога', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="slotProps.formatted_number(slotProps.subtotal-slotProps.taxtotal)"></th>
			<th></th>
		</tr>
		<tr v-if="slotProps.taxtotal>0" v-for="tax in slotProps.total_product_taxes"></td>
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name" v-html="tax.name">:</th>
			<th class = "products_total_value">
				<span class="item_status status_black" v-html="'+'+slotProps.formatted_number(tax.tax)"></span>
			</th>
			<th></th>
		</tr>
		<tr class="cart_total" v-if="slotProps.discount>0">
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="slotProps.formatted_number( slotProps.subtotal )"></th>		
			<th></th>
		</tr>			
		<tr v-if="slotProps.discount>0">
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Скидка', 'usam'); ?>:</th>
			<th class = "products_total_value"><span class="item_status status_white" v-html="'-'+slotProps.formatted_number( slotProps.discount )"></span></th>	
			<th></th>
		</tr>			
		<tr class="products_total_amount">
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Итог', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="slotProps.formatted_number( slotProps.totalprice )"></th>
			<th></th>
		</tr>
	</template>
</table-products>