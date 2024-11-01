<modal-panel ref="modalsendsms" :backdrop="true">
	<template v-slot:title><?php _e('Написать SMS', 'usam'); ?></template>
	<template v-slot:body="modalProps">		
		<send-sms :phones="contact.phones" :object_id="object_id" :object_type="object_type" @add="addSMS" inline-template>
			<table class ='table_send_email'>
				<tr>
					<td class ='table_send_email__name'><?php _e('Кому', 'usam'); ?>:</td>
					<td>
						<select v-model="from" v-if="Object.keys(phones).length">			
							<option v-for="(display, email) in phones" :value='email'>{{display}}</option>
						</select>
						<input v-else type='text' v-model="from">
					</td>
				</tr>							
				<tr>
					<td colspan='2'>					
						<textarea rows="10" autocomplete="off" cols="40" v-model="message"></textarea>
					</td>
				</tr>				
				<tr>
					<td colspan='2' class="table_send_email__buttons">
						<button class="button button-primary" @click="send"><?php _e( 'Отправить', 'usam'); ?></button>	
						<a @click="$root.sidebar('modalsendsms')"><?php _e( 'Отменить', 'usam'); ?></a>
					</td>
				</tr>
			</table>
		</send-sms>
	</template>
</modal-panel>