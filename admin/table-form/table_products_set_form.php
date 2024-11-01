<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_products_form.php' );		
class USAM_Table_Products_set_Form extends USAM_Table_Products_Form
{
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
						<span v-html="product.post_title"></span>
						<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
					</div>							
				</div>				
			</td>
			<td class="column-quantity">	
				<input size='4' type='text' :name="'products['+product.id+'][quantity]'" v-model="product.quantity">
			</td>				
			<td class="column-status">	
				<input type='checkbox' :name="'products['+product.id+'][status]'" v-model="product.status">
				<input type='hidden' :name="'products['+product.id+'][product_id]'" v-model="product.product_id">
			</td>	
			<td class="column-category">
				<select :name="'products['+product.id+'][category_id]'" v-model="product.category_id">
					<option :value='category.term_id' v-for="category in product.category" v-html="category.name"></option>
				</select>
			</td>			
			<td class="column-delete">					
				<a class="action_delete" href="" @click="delElement($event, k)"></a>
			</td>	
		</tr>
		<?php		
	}
}