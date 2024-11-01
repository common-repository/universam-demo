<modal-panel ref="modalbuyers">
	<template v-slot:title><?php _e('Выбор клиента', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" :query="typePayer=='company'?'companies':'contacts'" :args="typePayer=='company'?{add_fields:['logo'], orderby:'name', order:'ASC'}:{add_fields:['foto'], order:'ASC'}">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items" @click="selectBuyer(item); sidebar('buyers')">
					<td class="column_title">
						<a class='user_block' v-if="typePayer=='company'">	
							<div class='image_container usam_foto'><img :src='item.logo'></div>
							<div class='user_name' v-html="item.name"></div>							
						</a>
						<a class='user_block' v-else>	
							<div class='image_container usam_foto'><img :src='item.foto'></div>
							<div class='user_name' v-html="item.appeal"></div>							
						</a>							
					</td>
					<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>