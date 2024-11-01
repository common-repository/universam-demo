<div class='affairs'>
	<div class='affair' v-for="(affair, k) in item.affairs">
		<a :href="'<?php echo add_query_arg(['form' => 'view']); ?>&form_name='+affair.type+'&id='+affair.id"><span>{{localDate(affair.date_insert,'d.m.Y')}}</span> - <span v-html="affair.title"></span></a>
	</div>
</div>
<div class="row-actions">
	<span class="row_action"><a @click="openEvent(item, 'meeting')"><?php _e( 'Встреча', 'usam'); ?></a></span>
	<span class="row_action"><a @click="openEvent(item, 'task')"><?php _e( 'Задача', 'usam'); ?></a></span>
	<span class="row_action"><a :href="'<?php echo admin_url('admin.php?page=feedback&tab=chat'); ?>&contact_id='+item.id"><?php _e( 'Чат', 'usam'); ?></a></span>
	<?php
	if( current_user_can('view_communication_data') )
	{
		?>
		<span class="row_action" v-if="item.emails.length"><a data-emails="item.emails.join(',')" :data-name="item.appeal" class="js-open-message-send"><?php _e( 'Сообщение', 'usam'); ?></a></span>
		<span class="row_action" v-if="item.phones.length"><a data-phones="item.phones.join(',')" :data-name="item.appeal" class="js-open-sms-send"><?php _e( 'SMS', 'usam'); ?></a></span>
		<span class="row_action" v-if="item.phones.length"><a data-phones="item.phones.join(',')" :data-name="item.appeal" class="js-communication-phone"><?php _e( 'Звонок', 'usam'); ?></a></span>	
		<?php
	}
	?>		
</div>