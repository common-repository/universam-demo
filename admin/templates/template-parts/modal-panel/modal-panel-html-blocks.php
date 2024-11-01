<modal-panel ref="modalblocks">
	<template v-slot:title><?php _e('Выбор HTML блока', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<list-table :load="modalProps.show">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Название блока', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in registerBlocks">
					<td class="column_title">
						<div class='user_data'>
							<div class='user_name'>{{item.html_name}}</div>
							<div class='user_post'>{{item.template}}</div>		
						</div>	
					</td>
					<td class="column_action">
						<button class="button" @click="addBlock(item, 'blocks')"><?php _e( 'Выбрать', 'usam'); ?></button>
					</td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>