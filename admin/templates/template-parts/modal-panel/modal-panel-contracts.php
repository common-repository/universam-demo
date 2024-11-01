<modal-panel ref="modalcontracts">
	<template v-slot:title><?php _e('Выбор договора', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" :query="'contracts'" :args="args_contracts">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
				<th class="column_date"><?php _e( 'Дата', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items" @click="selectContract(item); sidebar('contracts')">
					<td class="column_title">						
						<div class='item_name' v-html="item.name"></div>	
						<strong class="document_id" v-html="'№ '+item.number"></strong>
					</td>
					<td class="column_date">{{slotProps.localDate(item.date_insert,'d.m.Y')}}</td>
					<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>