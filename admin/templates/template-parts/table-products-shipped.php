<?php
	$columns = [
		'n'         => __('№', 'usam'),
		'title'     => __('Товары', 'usam')
	];	
	$columns['quantity'] = __('В отгрузке','usam');		
	$columns['reserve'] = __('Резерв','usam');
	$columns['storage'] = __('На складе','usam');
	$columns['price'] = __('Цена','usam');
	$columns['tools'] = '';
?>
<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'shipped'" :loaded="$root.loaded" :edit="edit" :show_button="show_button" :items="data.products" @change="data.products=$event" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>' :query="query">
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
							<a :href="product.url" v-html="product.name"></a>							
						</div>
						<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
					</div>							
				</div>
			</td>
			<td :class="'column-'+column" v-for="column in slotProps.user_columns"><span v-html="product[column]"></span></td>
			<td class="column-quantity">
				<div class = "quantity_product" v-if="edit">
					<input size='4' type='text' v-model="product.quantity">
				</div>
				<span v-else v-html="product.quantity+' '+units[product.unit_measure]"></span>
			</td>
			<td class="column-reserve">
				<input size='4' type='text' v-model="product.reserve" v-if="edit">	
				<span v-else v-html="product.reserve+' '+units[product.unit_measure]"></span>
			</td>			
			<td class="column-storage" v-html="product.storage"></td>
			<td class="column-price">
				<input type='text' v-model="product.price" v-if="edit">	
				<span v-else v-html="product.price"></span>
			</td>			
			<td class="column-delete">
				<a class="action_delete" href="" @click="slotProps.delElement($event, k)" v-if="edit"></a>
			</td>	
		</tr>
	</template>
	<template v-slot:tfoot="slotProps">
		<tr class="products_total_amount">
			<td :colspan = 'slotProps.tableColumns.length-5'></td>
			<th colspan='3' class = "products_total_name"><?php esc_html_e( 'Итог', 'usam'); ?>:</th>
			<th class = "products_total_value" v-html="slotProps.formatted_number( slotProps.totalprice )"></th>
			<th></th>
		</tr>	
	</template>	
</table-products>