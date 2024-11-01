<div id="orders_contractor" class="documents">
	<p class ="items_empty" v-if="orders_contractor!==null && !orders_contractor.length"><?php _e( 'Нет документов отгрузки', 'usam'); ?></p>
	<order-contractor v-for="(document, i) in orders_contractor" :key="document.id" v-if="document.status!='delete'" :doc="document" @change="document=$event" :products="products" :units="units" :additionalcolumns="user_columns['order_contractor']" :statuses="statuses" inline-template>
		<div class="usam_document">
			<div class="usam_document-title-container">
				<div class="usam_document-title" v-if="data.id"><?php printf(__( 'Заказ поставщику %s от %s', 'usam'), '№ {{data.number}}', '{{localDate(data.date_insert,"'.get_option('date_format', 'Y/m/j').'")}}' ); ?></div>
				<div class="usam_document-title" v-else><?php _e( 'Новый заказ поставщику', 'usam'); ?></div>
				<div class="usam_document-action-block">					
					<?php
					if ( current_user_can( 'delete_order_contractor' ) )
					{
						?><div class="usam_document-action" @click="del()"><?php _e( 'Удалить', 'usam'); ?></div><?php
					}
					if ( current_user_can( 'edit_order_contractor' ) )
					{
						?><div class="usam_document-action" @click="edit=!edit"><?php _e( 'Редактировать', 'usam'); ?></div><?php
					} 
					?>
					<div class="usam_document-action" @click="toggle=!toggle"><?php _e( 'Свернуть', 'usam'); ?></div>		
				</div>
			</div>
			<div class="usam_document_content" v-show="!toggle">
				<div class="usam_document_header">				
					<div class="usam_document__sidebar" v-if="data.id>0">			
						<?php		
						if ( current_user_can( 'print_shipped' ) )
						{
							$time = time();
							$printed_forms = usam_get_printed_forms_document('order_contractor');				
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
						<?php 
						$actions = apply_filters( "usam_document_action", [], "order_contractor" ); 
						if ( $actions )
						{
							?>		
							<div class="usam_document__container">
								<div class="usam_document__container_title"><?php _e( 'Действия', 'usam'); ?></div>	
								<div class="edit_form">											
									<?php 									
									foreach ( $actions as $action )
									{					
										?>		
										<div class ="edit_form__item">
											<a class='usam_document__sidebar_action' @click="<?php echo $action['function']; ?>" <?php echo !empty($action['attr'])?$action['attr']:''; ?>><?php echo usam_get_icon($action['icon'])."<span>".$action['title']."</span>"; ?></a>
										</div>
										<?php				
									}
									?>
								</div>
							</div>	
						<?php 
						}
						?>								
					</div>						
					<div class="usam_document__right">				
						<div class="usam_document__container">
							<div class="usam_document__container_title"><?php _e( 'Основное', 'usam'); ?></div>
							<div class="edit_form">		
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Номер документа', 'usam'); ?>:</div>
									<div class ="edit_form__item_option" v-if="edit">
										<input type="text" v-model="data.number">
									</div>
									<div class ="edit_form__item_option" v-else>{{data.number}}</div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Расчетный счет', 'usam'); ?>:</div>
									<div class ="edit_form__item_option" v-if="edit">
										<select v-model='data.bank_account_id'>
											<option :value="v.id" v-html="v.name" v-for="v in $root.bank_accounts"></option>
										</select>	
									</div>
									<div class ="edit_form__item_option" v-else-if="typeof $root.bank_accounts[data.bank_account_id] !== typeof undefined"><a :href="$root.bank_accounts[data.bank_account_id].company_url">{{$root.bank_accounts[data.bank_account_id].bank_account_name}}</a></div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Поставщик', 'usam'); ?>:</div>
									<div class ="edit_form__item_option" v-if="edit">
										<select v-model='data.customer_id'>
											<option :value="v.id" v-html="v.name" v-for="v in $root.contractors"></option>
										</select>	
									</div>
									<div class ="edit_form__item_option" v-else-if="typeof $root.contractors[data.customer_id] !== typeof undefined"><a :href="$root.contractors[data.customer_id].url">{{$root.contractors[data.customer_id].name}}</a></div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
									<div class ="edit_form__item_option" v-if="edit">
										<select v-model='data.status'>
											<option v-for="status in statuses" v-if="status.type=='order_contractor' && (status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
										</select>	
									</div>
									<div class ="edit_form__item_option" v-else>
										<div class='item_status' :style="statusStyle(data, 'order_contractor')" v-html="statusName(data, 'order_contractor')"></div>
									</div>					
								</div>	
								<div class ="edit_form__item" v-if="data.note || edit">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Примечание', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<textarea rows="5" type="text" v-if="edit" v-model="data.note"></textarea>
										<span v-else v-html="data.note.replace(/\n/g,'<br>')"></span>
									</div>
								</div>									
							</div>
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
	$columns['quantity'] = __('Количество','usam');		
	$columns['order_quantity'] = __('В заказе','usam');
	$columns['price'] = __('Цена','usam');
	$columns['tools'] = '';
?>			
				<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'shipped'" :loaded="$root.loaded" :edit="edit" :user_columns="user_columns" :items="data.products" @change="data.products=$event" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>'>
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
							<td class="column-order_quantity" v-html="orderProduct(k, 'quantity')+' '+units[product.unit_measure]"></td>
							<td class="column-price" v-html="product.price"></td>
							<td class="column-delete">
								<a class="action_delete" href="" @click="slotProps.delElement($event, k)" v-if="edit && abilityChange"></a>
							</td>	
						</tr>
					</template>
					<template v-slot:tfoot="slotProps">
						<tr class="products_total_amount">
							<td :colspan = 'slotProps.tableColumns.length-4'></td>
							<th colspan='2' class = "products_total_name"><?php esc_html_e( 'Итог', 'usam'); ?>:</th>
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
								<tr v-for="(product, k) in products" v-if="product.contractor==data.customer_id || !product.contractor">
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
	</order-contractor>
</div>