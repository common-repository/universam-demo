<script type="x-template" id="form-table">
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
							<span v-html="column.name" v-else></span>
						</th>
					</tr>
					<tr v-if="columns_tools" class="table_products__columns_tools_row">
						
					</tr>
				</thead>
				<tbody>					
					<tr class = "items_empty" v-if="items.length==0">
						<td :colspan = 'tableColumns.length'><slot name="empty"><?php _e( 'Нет записей', 'usam'); ?></slot></td>
					</tr>
					<slot name="tbody" :tableColumns="tableColumns" :items="items" :user_columns="user_columns" :editTable="editTable" :delElement="delElement" v-else>
						<tr v-if="items.length" v-for="(item, k) in items">
							<td class="column-n">{{k+1}}</td>
							<slot name="tbodyrow" :tableColumns="tableColumns" :k="k" :item="item" :user_columns="user_columns" :editTable="editTable"></slot>
							<td class="column-delete">					
								<a class="action_delete" v-if="editTable" @click="delElement($event, k)"></a>
							</td>	
						</tr>
					</slot>
				</tbody>
				<tfoot>
					<slot name="tfoot" :tableColumns="tableColumns" :items="items" :user_columns="user_columns"></slot>
				</tfoot>
			</table>							
		</div>
		<slot name="tfotertools" :tableColumns="tableColumns" :items="items" :selectElement="selectElement">
			<div class="add_items" v-if="editTable">
				<div class="add_items__options">				
					<div class="add_items__add">	
						<slot name="tautocomplete" :tableColumns="tableColumns" :items="items" :user_columns="user_columns" :selectElement="selectElement"></slot>							
					</div>
				</div>					
			</div>
			<button v-if="!edit && !editTable" type="button" class="button" @click="editTable=!editTable">
				<span v-if="items.length"><?php _e( 'Изменить список', 'usam'); ?></span>
				<span v-else><?php _e( 'Добавить', 'usam'); ?></span>
			</button>
			<div v-if="!edit && editTable" class="select_product__buttons">			
				<button type="button" class="button button-primary" @click="$emit('save', items)"><?php _e( 'Сохранить', 'usam'); ?></button>
				<button type="button" class="button" @click="editTable=!editTable"><?php _e( 'Отменить', 'usam'); ?></button>
			</div>		
		</slot>
	</div>
</script>