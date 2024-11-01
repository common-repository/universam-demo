<?php
$columns = [
	'n'     => __('№', 'usam'),
	'title' => __('Товары', 'usam'),
	'price' => __('Цена', 'usam'),
	'delete'    => '',
];
?>
<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="list" :edit="true" :items="products" @change="products=$event" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>' :recalculate="false">
	<template v-slot:theadertools="slotProps" v-if="list=='crosssell'">
		<div class ="edit_form">
			<div class ="edit_form__item">
				<a target='_blank' href='<?php echo admin_url("options-general.php?page=marketing&tab=crosssell"); ?>' title='<?php _e('Редактировать правила перекрестных продаж','usam'); ?>'><?php _e('Создать правила','usam'); ?></a>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Последнее обновление', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option" v-if="data.increase_sales_time">{{localDate(data.increase_sales_time,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
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
						<a :href="product.url" v-html="product.post_title"></a>
						<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
						<input type="hidden" :name="list+'[]'" :value="product.product_id">
					</div>					
				</div>							
			</td>			
			<td class="column-price"><span v-html="slotProps.formatted_number(product.price)"></span></td>	
			<td class="column-delete">					
				<a class="action_delete" href="" @click="slotProps.delElement($event, k)"></a>
			</td>	
		</tr>
	</template>
</table-products>