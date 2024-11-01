<modal-panel ref="modalstorages">
	<template v-slot:title><?php _e('Выбор склада', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" query="storages" :args="storages_args">
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