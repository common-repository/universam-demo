<list-table :query="'change_history'" :args="changeHistoryArgs" :filter="false">
	<template v-slot:thead>
		<th class="column_title"><?php _e( 'Дата', 'usam'); ?></th>
		<th class="column_title"><?php _e( 'Пользователь', 'usam'); ?></th>	
		<th class="column_type"><?php _e( 'Тип события', 'usam'); ?></th>	
		<th class="column_description"><?php _e( 'Что изменилось', 'usam'); ?></th>
	</template>
	<template v-slot:tbody="slotProps">
		<tr v-for="(item, k) in slotProps.items">
			<td class="column_date">
				{{localDate(item.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}
			</td>
			<td class="column_title">
				<a class='user_block'>	
					<div class='image_container usam_foto'><img :src='item.user.foto'></div>
					<div class='user_name' v-html="item.user.appeal"></div>							
				</a>	
			</td>
			<td class="column_type" v-html="item.name_type"></td>
			<td class="column_description" v-html="item.name_description"></td>
		</tr>
	</template>
</list-table>
<?php
usam_vue_module('list-table');