<script type="x-template" id="list-table">
	<div class ="list_table_box">
		<div class ="filter_table" v-show="filter">
			<slot name="filter"><filter-search :placeholder="'<?php _e('Поиск', 'usam'); ?>'" @change="search=$event"></filter-search></slot>
		</div>
		<table class="usam_list_table" :class="{'loading':loading}">
			<thead>
				<tr>
					<slot name="thead">
						<th scope="col" class="manage-column" :class="'column-'+column.id" v-for="column in columns">
							<span v-if="column.id=='tools'" class="dashicons dashicons-list-view open_columns_tools" @click="columns_tools=!columns_tools"></span>					
							<span v-html="column.name" v-else></span>
						</th>
					</slot>
				</tr>
			</thead>
			<tbody>
				<slot name="tbody" :items="items" :localDate="localDate"></slot>
			</tbody>
		</table>
		<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
	</div>
</script>
<?php usam_vue_module('paginated-list'); ?>