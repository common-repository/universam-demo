<div id="payment_documents" class="documents" v-if="payments!==null">
	<p class ="items_empty" v-if="!payments.length"><?php _e( 'Нет документов оплаты', 'usam'); ?></p>
	<div class="usam_document" v-for="(document, i) in payments" v-if="document.status!='delete'">
		<div class="usam_document-title-container">
			<div class="usam_document-title" v-if="document.id"><?php printf(__( 'Оплата %s от %s', 'usam'), '№ {{document.number}}', '{{localDate(document.date_insert,"'.get_option('date_format', 'Y/m/j').'")}}' ); ?></div>
			<div class="usam_document-title" v-else><?php _e( 'Новая оплата', 'usam'); ?></div>
			<div class="usam_document-action-block">					
				<?php
				if ( current_user_can( 'delete_payment' ) )
				{
					?><div class="usam_document-action" @click="deletePayment(i)"><?php _e( 'Удалить', 'usam'); ?></div><?php
				}
				if ( current_user_can( 'edit_payment' ) )
				{
					?><div class="usam_document-action" @click="document.edit=!document.edit"><?php _e( 'Редактировать', 'usam'); ?></div><?php
				} 
				?>
				<div class="usam_document-action" @click="document.toggle=!document.toggle"><?php _e( 'Свернуть', 'usam'); ?></div>		
			</div>
		</div>
		<div class="usam_document_content" v-show="!document.toggle">
			<div class="usam_document__right">				
				<div class="usam_document__container">
					<div class="usam_document__container_title"><?php _e( 'Документ', 'usam'); ?></div>
					<div class="edit_form">
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Номер документа', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<input type="text" v-model="document.number">
							</div>
							<div class ="edit_form__item_option" v-else>{{document.number}}</div>
						</div>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Расчетный счет', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<select v-model='document.bank_account_id'>
									<option :value="account.id" v-html="account.name" v-for="account in bank_accounts"></option>
								</select>	
							</div>
							<div class ="edit_form__item_option" v-else-if="typeof bank_accounts[document.bank_account_id] !== typeof undefined"><a :href="bank_accounts[document.bank_account_id].company_url">{{bank_accounts[document.bank_account_id].bank_account_name}}</a></div>
						</div>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Варианты оплаты', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<select v-model='document.payment_type'>
									<option :value="type" v-html="name" v-for="(name, type) in payment_types"></option>
								</select>	
							</div>
							<div class ="edit_form__item_option" v-else>{{payment_types[document.payment_type]}}</div>
						</div>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Способ оплаты', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<select v-model='document.gateway_id'>
									<option :value="gateway.id" v-html="gateway.name" v-for="gateway in payment_gateways"></option>
								</select>	
							</div>
							<div class ="edit_form__item_option" v-else>{{document.name}}</div>
						</div>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Стоимость', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<input type="text" v-model="document.sum">
							</div>
							<div class ="edit_form__item_option" v-else>{{document.sum}}</div>
						</div>							
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<select v-model='document.status'>
									<option v-for="status in statuses" v-if="status.type=='payment' && (status.internalname == document.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
								</select>	
							</div>
							<div class ="edit_form__item_option" v-else>
								<div class='item_status' :style="statusStyle(document, 'payment')" v-html="statusName(document, 'payment')"></div>
							</div>					
						</div>
						<div class ="edit_form__item" v-if="document.transactid">
							<div class ="edit_form__item_name"><?php _e( 'Номер транзакции', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<input type="text" v-model="document.transactid">
							</div>
							<div class ="edit_form__item_option" v-else>{{document.transactid}}</div>
						</div>
						<div class ="edit_form__item" v-if="document.date_payed">
							<div class ="edit_form__item_name"><?php _e( 'Дата оплаты', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">								
								<v-date-picker v-model="document.date_payed" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
									<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents"/></template>
								</v-date-picker>
							</div>
							<div class ="edit_form__item_option" v-else>{{localDate(document.date_payed,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
						</div>
					</div>
				</div>	
				<div class="usam_document__container" v-if="document.external_document || document.edit">
					<div class="usam_document__container_title"><?php _e( 'Внешний документ', 'usam'); ?></div>
					<div class="edit_form">						
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php _e( 'Номер документа', 'usam'); ?>:</div>
							<div class ="edit_form__item_option" v-if="document.edit">
								<input type="text" v-model="document.external_document">
							</div>
							<div class ="edit_form__item_option" v-else>{{document.external_document}}</div>
						</div>
					</div>
				</div>				
			</div>				
		</div>	
	</div>
</div>