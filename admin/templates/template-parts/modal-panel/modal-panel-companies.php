<modal-panel ref="modalcompanies">
	<template v-slot:title><?php _e('Выбор компаний', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" :query="'companies'" :args="args_companies">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items" @click="addCompany(item); sidebar('companies')">
					<td class="column_title">
						<a class='user_block'>	
							<div class='image_container usam_foto'><img :src='item.logo'></div>
							<div class='user_name' v-html="item.name"></div>							
						</a>	
					</td>
					<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>