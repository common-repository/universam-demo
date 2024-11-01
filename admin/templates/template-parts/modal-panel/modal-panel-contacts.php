<modal-panel ref="modalcontacts">
	<template v-slot:title><?php _e('Выбор контактов', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" :query="'contacts'" :args="args_contacts">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Имя', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items">
					<td class="column_title">
						<a class='user_block'>	
							<div class='image_container usam_foto'><img :src='item.foto'></div>
							<div class='user_name' v-html="item.appeal"></div>							
						</a>	
					</td>
					<td class="column_action">
						<button class="button" v-if="!sidebar_checks_elected(item)" @click="addContacts(item); sidebar('contacts')"><?php _e( 'Выбрать', 'usam'); ?></button>
						<span class="item-selected" v-else><?php _e( 'Выбранно', 'usam'); ?></span>
					</td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>