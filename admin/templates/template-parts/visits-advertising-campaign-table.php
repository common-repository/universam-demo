<?php
$columns = [ 
	['id' => 'date', 'name' => __('Дата', 'usam')],
	['id' => 'contact', 'name' => __('Контакт', 'usam'), '' => ''],	
	['id' => 'time', 'name' => __('Время на сайте', 'usam')],
	['id' => 'views', 'name' => __('Просмотров', 'usam')],	
	['id' => 'referer', 'name' => __('Переход с сайта', 'usam')],	
	['id' => 'device', 'name' => __('Тип устройства', 'usam')],	
	['id' => 'ip', 'name' => 'IP'],	
];			
usam_vue_module('list-table');
?>
<list-table v-if="data.id" query="visits" :args="{add_fields:[`contact`,`long2ip`],meta_query:[{key:'campaign_id', value:data.id, compare:'='}]}" :columns='<?php echo json_encode( $columns ); ?>'>
	<template v-slot:tbody="slotProps">
		<tr v-for="(item, k) in slotProps.items">
			<td class="column-date">{{localDate(item.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?>  H:i')}}</td>
			<td class="column-contact">
				<a class='user_block'>	
					<div class='image_container usam_foto'><img :src='item.contact.foto'></div>
					<div class='user_name' v-html="item.contact.appeal"></div>							
				</a>	
			</td>
			<td class="column-time">{{item.time}}</td>	
			<td class="column-views">{{item.views}}</td>
			<td class="column-views">{{item.referer}}</td>
			<td class="column-device"><span v-if="item.device_name=='mobile'"><?php _e('Мобильные','usam'); ?></span><span v-else><?php _e('ПК','usam'); ?></span></td>
			<td class="column-ip">{{item.long2ip}}</td>
		</tr>
	</template>
</list-table>