<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_products_form.php' );		
class USAM_Table_Products_gift_Form extends USAM_Table_Products_Form
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
						<a :href="product.url" v-html="product.post_title"></a>
						<p class="product_sku"><?php esc_html_e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
					</div>							
				</div>		
			</td>		
			<td class="column-delete">					
				<a class="action_delete" href="" @click="delElement($event, k)"></a>
				<input type="hidden" name="products[]" :value="product.ID" />
			</td>	
		</tr>
		<?php		
	}	
}