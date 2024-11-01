<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_products_form.php' );		
class USAM_Table_Products_banner_Form extends USAM_Table_Products_Form
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
			<td class="column-inset">	
				<input size='4' type='text' v-model="product[device].inset" style="width:200px;">
			</td>	
			<td class="column-delete">					
				<a class="action_delete" href="" @click="delElement($event, k)"></a>
			</td>	
		</tr>
		<?php		
	}
}