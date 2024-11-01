<?php
$columns = [
	'n'         => __('№', 'usam'),
	'title'     => __('Товары', 'usam'),
	'discount'  => __('Скидка', 'usam'),
	'status'    => __('Статус', 'usam'),
	'delete'    => '',
];
?>
<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'product_day'" :loaded="$root.loaded" :items="data.products" @change="formattingProduct" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>'>		
	<template v-slot:tbody="slotProps">
		<tr v-if="slotProps.products.length" v-for="(product, k) in slotProps.products" @dragover="allowDrop($event, k)" @dragstart="drag($event, k)" @dragend="dragEnd($event, k)">
			<td class="column-n">{{k+1}}</td>
			<td class="column-title" draggable="true">
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
			<td class="column-discount">	
				<div class="discount_selection">
					<input size='4' type='text' v-model="product.discount">
					<input type='hidden' v-model="product.product_id">
					<select v-model="product.dtype">
						<option value='p'>%</option>
						<option value='f'>-</option>
					</select>
				</div>
			</td>
			<td class="column-status">	
				<span class='item_status_notcomplete item_status' v-if="product.status==0"><?php _e('Ожидание', 'usam'); ?></span>
				<span class='item_status_valid item_status' v-else-if="product.status==1"><?php _e('Установлен', 'usam'); ?></span>
				<span class='status_blocked item_status' v-else><?php _e('Снят', 'usam'); ?></span>
			</td>		
			<td class="column-delete">					
				<a class="action_delete" href="" @click="delElement($event, k)"></a>
			</td>
		</tr>
	</template>
	<template v-slot:tfoot="slotProps"></template>
</table-products>