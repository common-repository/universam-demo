<?php
$columns = [
		'n'         => __('№', 'usam'),
		'title'     => __('Товары', 'usam'),
		'edit_price' => __('Цена', 'usam'),
		'quantity'   => __('Количество','usam'),	 		 
		'total'     => __('Всего', 'usam'),
		'tools'     => ''
	];
?>
<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="data.type" :loaded="$root.loaded" :edit="edit" :items="data.products" @change="data.products=$event" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>' :type_price="data.type_price">		
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
			<td class="column-edit_price">	
				<div class="discount_selection" v-if="edit">
					<input size='4' type='text' v-model="product.old_price" @blur="slotProps.recountProducts">
				</div>
				<div class="discount_selection" v-else v-html="product.old_price"></div>	
			</td>			
			<td class="column-quantity">
				<div class = "quantity_product" v-if="edit">
					<input size='4' type='text' v-model="product.quantity" @blur="slotProps.recountProducts">	
				</div>
				<span v-else v-html="product.quantity+' '+(Object.keys(units).length?units[product.unit_measure]:'')"></span>
			</td>
			<td class="column-total"><span v-html="slotProps.formatted_number( product.total )"></span></td>
			<td class="column-delete">					
				<a class="action_delete" href="" @click="delElement($event, k)" v-if="edit"></a>
			</td>	
		</tr>
	</template>	
</table-products>
<button v-if="!edit" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить товар', 'usam'); ?></button>
<div v-if="edit" class="select_product__buttons">			
	<button type="button" class="button button-primary" @click="saveProducts"><?php _e( 'Сохранить', 'usam'); ?></button>
	<button type="button" class="button" @click="edit=!edit"><?php _e( 'Отменить', 'usam'); ?></button>
</div>