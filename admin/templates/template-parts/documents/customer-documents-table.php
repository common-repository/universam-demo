<?php
$columns = [ 
	['id' => 'name', 'name' => __('Название', 'usam')],
	['id' => 'document_type', 'name' => __('Тип документа', 'usam')],	
	['id' => 'status', 'name' => __('Статус', 'usam')],
	['id' => 'sum', 'name' => __('Сумма', 'usam')],	
];
usam_vue_module('list-table');		
?>
<list-table ref="documents" :query="'documents'" :args="argsDocs" :columns='<?php echo json_encode( $columns ); ?>'>
	<template v-slot:tbody="slotProps">
		<tr v-for="(item, k) in slotProps.items">
			<td class="column-name">						
				<a v-if="item.name!==''" :href="'<?php echo add_query_arg(['form' => 'view']); ?>&form_name='+item.type+'&id='+item.id">{{item.name}}</a>	
				<div><a :href="'<?php echo add_query_arg(['form' => 'view']); ?>&form_name='+item.type+'&id='+item.id">№ {{item.number}}</a> <span class="document_date"><?php _e("от","usam"); ?> {{localDate(item.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</span></div>
			</td>
			<td class="column-document_type">{{item.document_type}}</td>			
			<td class="column-status">
				<span class='item_status status_customer' :style="'background:'+item.status_color+';color:'+item.status_text_color" v-html="item.status_name"></span>
				<div v-if="item.manager_id"><strong><?php _e('Ответственный','usam'); ?>:</strong> <a href="">{{item.manager.name}}</a></div>
				<div v-else class="no_manager_assigned"><?php _e('Нет ответственного','usam'); ?></div>
			</td>						
			<td class="column-sum">
				<a :href="'<?php echo usam_url_action('printed_form', ['time' => time()] ); ?>&form='+item.type+'&id='+item.id" target="_blank">{{item.totalprice}} <span v-html="item.currency"></span></a>
				<div v-if="item.closedate"><strong><?php _e('Срок', 'usam'); ?>: </strong>{{localDate(item.closedate,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</div>
			</td>					
		</tr>
	</template>
</list-table>