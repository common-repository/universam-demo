<?php
require_once( USAM_FILE_PATH . "/admin/interface-filters/products_interface_filters.class.php" );
?>
<selection-products @change="selectionProducts" :download="edit" inline-template>	
	<div class = 'selection_products'>
		<?php
		$interface_filters = new Products_Interface_Filters();					
		$interface_filters->display(); 
		?>	
		<div class = 'table_products_container'>
			<table class="usam_list_table table_products">
				<thead>
					<tr>
						<th class="manage-column column-title sortable" @click="sort('title')"><?php _e( 'Товары (работы, услуги)', 'usam'); ?></th>
						<th class="manage-column column-price sortable" @click="sort('price')"><?php _e( 'Цена', 'usam'); ?></th>
						<th class="manage-column column-stock sortable" @click="sort('stock')"><?php _e( 'Остаток', 'usam'); ?></th>
						<th class="manage-column column-storages" v-for="(column, id) in columns" :class="'column-'+id" v-html="column.name" :title="column.name"></th>
					</tr>					
				</thead>
				<tbody>
					<tr class = "items_empty" v-if="items.length==0"><td :colspan = 'columns.length'><?php _e( 'Нет товаров', 'usam'); ?></td></tr>
					<tr v-for="(product, k) in items">
						<td class="column-title">
							<div class="product_name_thumbnail">
								<div class="product_image image_container viewer_open" @click="viewer(k)">
									<img :src="product.small_image">
								</div>
								<div class="product_name">	
									<div class="product_name" v-html="product.post_title" @click="select(k)"></div>								
									<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span> <button @click="select(k)" type="button" class="button"><?php _e( 'Выбрать', 'usam'); ?></button></p>									
								</div>
							</div>								
						</td>
						<td class="column-price" @click="select(k)">	
							<span class="price" v-html="product.price_currency"></span>
						</td>
						<td class="column-stock" v-html="product.stock_units" @click="select(k)"></td>
						<td class="column-storages" v-for="(column, id) in columns" :class="'column-'+id" @click="select(k)">
							<span v-html="product.storages_data[id] !== undefined ? product.storages_data[id].stock_units : ''"></span>
						</td>
					</tr>
				</tbody>							
			</table>
			<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
		</div>
	</div>
</selection-products>