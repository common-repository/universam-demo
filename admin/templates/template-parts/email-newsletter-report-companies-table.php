<?php
$columns = [ 
	['id' => 'subscriber', 'name' => __('Подписчик', 'usam')],
	['id' => 'type', 'name' => __('Тип компании', 'usam'), '' => ''],	
	['id' => 'status', 'name' => __('Статус', 'usam')],
	['id' => 'communication', 'name' => __('Email', 'usam')],	
	['id' => 'sent_at', 'name' => __('Отправлено', 'usam')],	
	['id' => 'opened_at', 'name' => __('Дата открытия', 'usam')],	
	['id' => 'clicked', 'name' => __('Нажатий', 'usam')],	
	['id' => 'unsub', 'name' => __('Отписался', 'usam')],	
	['id' => 'sending_status', 'name' => __('Статус отправки', 'usam')],	
];
usam_vue_module('list-table');		
?>
<list-table query="companies" :args="argsCompany" :columns='<?php echo json_encode( $columns ); ?>'>
	<template v-slot:tbody="slotProps">
		<tr v-for="(item, k) in slotProps.items">
			<td class="column-name">						
				<a :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'company']); ?>&id='+item.id">{{item.name}}</a>
			</td>
			<td class="column-type">{{item.company_type}}</td>			
			<td class="column-status">
				<span class='item_status status_customer' v-if="item.status_name!==''" :style="'background:'+item.status_color+';color:'+item.status_text_color" v-html="item.status_name"></span>
			</td>		
			<td class="column-communication">{{item.communication}}</td>
			<td class="column-communication"><span v-if="item.sent_at">{{localDate(item.sent_at,'<?php echo get_option('date_format', 'Y/m/j'); ?>  H:i')}}</span></td>		
			<td class="column-communication"><span v-if="item.opened_at">{{localDate(item.opened_at,'<?php echo get_option('date_format', 'Y/m/j'); ?>  H:i')}}</span></td>		
			<td class="column-clicked"><span v-if="item.clicked" class='item_status_valid item_status'>{{item.clicked}}</span></td>		
			<td class="column-unsub">
				<span v-if="item.unsub>0"><?php _e('Да','usam'); ?></span>
				<span v-else><?php _e('Нет','usam'); ?></span>
			</td>				
			<td class="column-sending_status"><span v-if="sending_statuses[item.sending_status]!== undefined">{{sending_statuses[item.sending_status]}}</span></td>					
		</tr>
	</template>
</list-table>