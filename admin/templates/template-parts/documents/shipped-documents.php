<div id="shipped_documents" class="documents" v-if="shippeds!==null">
	<p class ="items_empty" v-if="!shippeds.length"><?php _e( 'Нет документов отгрузки', 'usam'); ?></p>
	<shipped-document v-for="(document, i) in shippeds" :key="document.id" v-if="document.status!='delete'" :doc="document" @change="document=$event" :delivery="delivery" :storages="storages" :products="products" :units="units" :delivery_problems="delivery_problems" :couriers="couriers" :user_columns="user_columns['shipped']" :statuses="statuses" inline-template>
		<div class="usam_document">
			<div class="usam_document-title-container">
				<div class="usam_document-title" v-if="data.id"><?php printf(__( 'Отгрузка %s от %s', 'usam'), '№ {{data.number}}', '{{localDate(data.date_insert,"'.get_option('date_format', 'Y/m/j').'")}}' ); ?></div>
				<div class="usam_document-title" v-else><?php _e( 'Новая отгрузка', 'usam'); ?></div>
				<div class="usam_document-action-block">					
					<?php
					if ( current_user_can( 'edit_shipped' ) )
					{
						?><div class="usam_document-action" @click="edit=!edit"><?php _e( 'Редактировать', 'usam'); ?></div><?php
					} 
					?>
					<div class="usam_document-action" @click="toggle=!toggle"><?php _e( 'Свернуть', 'usam'); ?></div>		
					<div class = "usam_menu">
						<div class="menu_name button"><?php _e( 'Действия', 'usam'); ?></div>
						<div class="menu_content menu_content_form">
							<div class="menu_items">
								<?php
								if ( current_user_can( 'edit_shipped' ) )
								{
									?>
									<a class='menu_items__item' @click="copyOrderOrder"><?php _e('Скопировать товары заказа', 'usam'); ?></a>
									<a class='menu_items__item' @click="recalculateShipped"><?php _e('Пересчитать стоимость', 'usam'); ?></a>
									<a class='menu_items__item' @click="createMove"><?php _e('Создать перемещение', 'usam'); ?></a>
									<a class='menu_items__item' @click="addReserve"><?php _e('Добавить в резерв', 'usam'); ?></a>
									<a class='menu_items__item' v-if="storagePickup.is_create_order" @click="addOrderTransportCompany"><?php _e('В транспортную компанию', 'usam'); ?></a>
									<?php
								}
								if ( current_user_can( 'delete_shipped' ) )
								{
									?><div class="menu_items__item menu_items__item_delete " @click="deleteShipped()"><?php _e( 'Удалить', 'usam'); ?></div><?php
								} 
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="usam_document_content" v-show="!toggle">
				<div class="usam_document_header">				
					<div class="usam_document__sidebar" v-if="data.id>0">			
						<?php		
						if ( current_user_can( 'print_shipped' ) )
						{
							$time = time();
							$printed_forms = usam_get_printed_forms_document('shipped');				
							foreach (['printed_form' => ['title' => __('Комплект документов','usam'), 'icon' => 'printer'], 'printed_form_to_pdf' => ['title' => 'Комплект документов в pdf', 'icon' => 'pdf']] as $key => $item )
							{
								?>
								<div class="usam_document__container">
									<div class="usam_document__container_title"><?php echo $item['title']; ?></div>
									<div class="edit_form">						
										<?php	
										foreach ( $printed_forms as $link )
										{					
											$url = usam_url_action($key, ['form' => $link['id'], 'id' => $this->id, 'time' => $time]);
											?>		
											<div class ="edit_form__item">
												<a class='usam_document__sidebar_action' :href="'<?php echo usam_url_action($key, ['form' => $link['id'], 'time' => $time]); ?>&id='+data.id" target="_blank"><?php echo usam_get_icon($item['icon']) ?><span><?php echo $link['title']; ?></span></a>	
											</div>
											<?php				
										}
										?>								
									</div>
								</div>
								<?php	
							}
						}
						?>								
						<div class="usam_document__container" v-if="data.track_id">
							<div class="usam_document__container_title"><?php _e( 'Сообщения клиенту', 'usam'); ?></div>
							<div class="edit_form">
								<div class ="edit_form__item">								
									<a class='usam_document__sidebar_action' @click="sendTracking(i)"><?php echo usam_get_icon('email')."<span>".__('Отправить трек-номер', 'usam')."</span>"; ?></a>
								</div>						
							</div>
						</div>
					</div>				
					<div class="usam_document__right">				
						<div class="usam_document__container">
							<div class="usam_document__container_title"><?php _e( 'Основное', 'usam'); ?></div>
							<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-main.php' ); ?>	
						</div>											
						<div class="usam_document__container" v-if="edit || data.courier">
							<div class="usam_document__container_title"><?php _e( 'Информация для курьера', 'usam'); ?></div>
							<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-courier.php' ); ?>						
						</div>		
						<div class="usam_document__container" v-if="edit || data.external_document || data.track_id">
							<div class="usam_document__container_title"><?php _e( 'Внешний документ', 'usam'); ?></div>
							<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/shipped/shipped-external-document.php' ); ?>						
						</div>			
					</div>				
				</div>
<?php
	$columns = [
		'n'         => __('№', 'usam'),
		'title'     => __('Товары', 'usam')
	];	
	$columns['quantity'] = __('В отгрузке','usam');		
	$columns['order_quantity'] = __('В заказе','usam');
	$columns['reserve'] = __('Резерв','usam');
	$columns['storage'] = __('На складе','usam');
	$columns['price'] = __('Цена','usam');
	$columns['tools'] = '';
?>			
				<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'shipped'" :loaded="$root.loaded" :edit="edit" :additionalcolumns="user_columns" :items="data.products" @change="data.products=$event" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>'>
					<template v-slot:tbody="slotProps">
						<tr v-if="slotProps.products.length" v-for="(product, k) in slotProps.products">
							<td class="column-n">{{k+1}}</td>
							<td class="column-title">
								<div class="product_name_thumbnail">
									<div class="product_image image_container viewer_open" @click="slotProps.viewer(k)">
										<img :src="product.small_image">
									</div>
									<div class="product_name">	
										<input size='4' type='text' v-model="product.name" v-if="edit && abilityChange">
										<div v-else >											
											<a :href="product.url" v-html="product.name"></a>							
										</div>
										<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
									</div>							
								</div>
							</td>
							<td :class="'column-'+column" v-for="column in slotProps.user_columns"><span v-html="product[column]"></span></td>
							<td class="column-quantity">
								<div class = "quantity_product" v-if="edit && abilityChange">
									<input size='4' type='text' v-model="product.quantity">
								</div>
								<span v-else v-html="product.quantity+' '+units[product.unit_measure]"></span>
							</td>
							<td class="column-order_quantity" v-html="orderProduct(k, 'quantity')+' '+units[product.unit_measure]" v-if="data.order_id>0"></td>
							<td class="column-reserve">
								<div class = "reserve_product" v-if="edit && abilityChange">
									<input size='4' type='text' v-model="product.reserve">	
								</div>
								<span v-else v-html="product.reserve+' '+units[product.unit_measure]"></span>
							</td>			
							<td class="column-storage" v-html="product.storage"></td>
							<td class="column-price" v-html="product.price"></td>
							<td class="column-delete">
								<a class="action_delete" href="" @click="slotProps.delElement($event, k)" v-if="edit && abilityChange"></a>
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
					<template v-slot:tfotertools="slotProps" v-if="edit && products.length">
						<h4><?php _e( 'Товары заказа', 'usam'); ?></h4>
						<table class="usam_list_table table_products selection_products">
							<thead>
								<tr>
									<th scope="col" class="manage-column" :class="'column-'+column.id" v-for="column in [{id:'title', name:'<?php _e('Товары', 'usam'); ?>'},{id:'quantity', name:'<?php _e('Количество', 'usam'); ?>'}]">
										<span v-html="column.name"></span>
									</th>
								</tr>
							</thead>		
							<tbody>		
								<tr v-for="(product, k) in products">
									<td class="column-title">
										<div class="product_name_thumbnail">
											<div class="product_image image_container viewer_open" @click="slotProps.viewer(k)">
												<img :src="product.small_image">
											</div>
											<div class="product_name">
												<a v-html="product.name" @click="addProduct(k)"></a>	
												<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span><button @click="addProduct(k)" type="button" class="button select_button"><?php _e( 'Добавить в документ', 'usam'); ?></button></p>
											</div>
										</div>				
									</td>						
									<td class="column-quantity" v-html="product.quantity+' '+units[product.unit_measure]" @click="addProduct(k)"></td>
								</tr>
							</tbody>
						</table>
					</template>
				</table-products>	
			</div>
		</div>
	</shipped-document>
</div>
<?php 
add_action('usam_after_form',function() {
	?>
	<modal-panel ref="modalstorages">
		<template v-slot:title><?php _e('Выбор склада', 'usam'); ?></template>
		<template v-slot:body="modalProps">
			<list-table v-if="modalProps.show" :load="modalProps.show" query="storages" :args="{add_fields:['phone','schedule','city','address'], issuing:1, owner:sidebardata.owner}">
				<template v-slot:thead>
					<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
					<th></th>	
				</template>
				<template v-slot:tbody="slotProps">
					<tr v-for="(item, k) in slotProps.items" @click="selectStorage(item); sidebar('storages')">
						<td class="column_title">
							<div class="object">
								<div class="object_title" v-html="item.title"></div>
								<div class="object_description" v-html="item.city+' '+item.address"></div>
							</div>	
						</td>
						<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
					</tr>
				</template>
			</list-table>
		</template>
	</modal-panel>
<?php 
});
usam_vue_module('list-table');
?>