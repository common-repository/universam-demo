<modal-panel ref="modalmanagers">
	<template v-slot:title><?php _e('Выбор менеджера', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show" :query="'employees'" :args="{add_fields:['foto','post'],user_id__not_in:0}">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Имя', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items">
					<td class="column_title">
						<a class='user_block'>	
							<div class='image_container usam_foto'><img :src='item.foto'></div>
							<div class='user_data'>
								<div class='user_name'>{{item.appeal}}<span class='customer_online' v-if='item.online'></span></div>
								<div class='user_post'>{{item.post}}</div>		
							</div>									
						</a>	
					</td>
					<td class="column_action">
						<button class="button" v-if="!sidebar_checks_elected(item)" @click="addUser(item, 'managers')"><?php _e( 'Выбрать', 'usam'); ?></button>
						<span class="item-selected" v-else><?php _e( 'Выбранно', 'usam'); ?></span>
					</td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>