<script type="x-template" id="table-products">
	<div v-show="loaded" class="select_product">
		<div class = 'product_table_tools'>				
			<slot name="theadertools"></slot>
		</div>
		<div class = 'table_products_container'>
			<table class="usam_list_table table_products">
				<thead>
					<tr>
						<th scope="col" class="manage-column" :class="'column-'+column.id" v-for="column in tableColumns">
							<span v-if="column.id=='tools'" class="dashicons dashicons-list-view open_columns_tools" @click="columns_tools=!columns_tools"></span>
							<span v-else-if="column.id=='discount'">{{column.name}} <input v-if="edit" size="4" type="text" v-model="product_discounts" @blur="changeDiscount(product_discounts)"></span>
							<span v-else-if="column.id=='old_price'">{{column.name}} <input v-if="edit" size="4" type="text" v-model="add_sum" @blur="chargePrice(add_sum)"></span>
							<span v-html="column.name" v-else></span>
						</th>
					</tr>
					<tr v-if="columns_tools" class="table_products__columns_tools_row">
						<td :colspan = 'tableColumns.length'>		
							<div class="table_products__columns_tools">						
								<label v-for="(name, c) in column_names">
									<input type="checkbox" v-model="user_columns" :value="c">{{name}}
								</label>
							</div>
						</td>
					</tr>
				</thead>
				<tbody>					
					<tr class = "items_empty" v-if="products.length==0">
						<td :colspan = 'tableColumns.length'><slot name="empty"><?php _e( 'Нет товаров', 'usam'); ?></slot></td>
					</tr>
					<slot name="tbody" :tableColumns="tableColumns" :products="products" :user_columns="user_columns" :viewer="viewer" :addBonuses="addBonuses" :recountProducts="recountProducts" :formatted_number="formatted_number" :delElement="delElement" v-else></slot>
				</tbody>
				<tfoot>
					<slot name="tfoot" :tableColumns="tableColumns" :products="products" :user_columns="user_columns" :formatted_number="formatted_number" :totalprice="totalprice" :subtotal="subtotal" :discount="discount" :taxtotal="taxtotal"></slot>
				</tfoot>
			</table>							
		</div>
		<slot name="tfotertools" :tableColumns="tableColumns" :products="products">	
			<button v-if="!edit && show_button" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить товар', 'usam'); ?></button>
			<div class="add_items" v-show="edit">
				<div class="add_items__options">				
					<button type="button" class="button open_table" @click="tableShow=!tableShow"><span class="dashicons dashicons-list-view"></span></button>
					<div class="add_items__add" v-if="!tableShow">	
						<autocomplete @change="selectElement" :selected="searchItem" :query="{status:['publish','draft']}" :request="'products'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
					</div>
					<button v-if="!tableShow" @click="addElement" type="button" class="button"><?php _e('Добавить', 'usam'); ?></button>
					<button v-if="!tableShow" @click="importData=!importData" type="button" class="button import_data"><?php _e( 'Импорт товаров', 'usam'); ?></button>
					<button v-if="products.length" @click="deleteElements" type="button" class="button"><?php _e( 'Очистить', 'usam'); ?></button>
				</div>	
				<div class="add_items_table" v-show="tableShow">	
					<?php require ( USAM_FILE_PATH.'/admin/templates/vue-templates/table-selection-products.php' ); ?>
				</div>				
			</div>
			<div class='product_importer' v-show="importData">		
				<?php 
				require_once( USAM_FILE_PATH . '/admin/includes/product/product_form_importer.php' );
				$progress_form = new USAM_Product_Form_Importer();
				$progress_form->display();
				?>
			</div>			
		</slot>
	</div>
</script>