<div class="usam_document" v-for="(item, k) in accounts">
	<div class="usam_document-title-container">
		<div class="usam_document-title"><?php _e( 'Реквизит счета', 'usam'); ?> {{item.number}}</div>
		<div class="usam_document-action-block">					
			<div class="usam_document-action" @click="markDeleteAccount(k)"><?php _e( 'Удалить', 'usam'); ?></div>
			<div class="usam_document-action" @click="item.edit=!item.edit"><?php _e( 'Редактировать', 'usam'); ?></div>
			<div class="usam_document-action" @click="item.show=!item.show"><?php _e( 'Свернуть', 'usam'); ?></div>					
		</div>
	</div>			
	<div class="usam_document_content" v-show="item.show">
		<div class="usam_document__right">				
			<div class="usam_document__container">
				<div class="usam_document__container_title"><?php _e( 'Банк', 'usam'); ?></div>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Наименование банка', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.name"></span>
							<input :class="{'hide':!item.edit}" maxlength='255' size="255" type="text" v-model="item.name">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'БИК', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.bic"></span>
							<input :class="{'hide':!item.edit}" maxlength='9' size="9" type="text" v-model="item.bic">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Адрес банка', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span  v-show="!item.edit" class = "js-copy-clipboard" v-html="item.address"></span>
							<textarea :class="{'hide':!item.edit}" maxlength='255' size="255" type="text" v-model="item.address"></textarea>
						</div>
					</div>
					<div class ="edit_form__item" v-if="item.swift || item.edit">
						<div class ="edit_form__item_name"><?php esc_html_e( 'SWIFT', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.swift"></span>
							<input :class="{'hide':!item.edit}" size="11" maxlength='11' type="text" v-model="item.swift">
						</div>
					</div>
				</div>
			</div>			
			<div class="usam_document__container">
				<div class="usam_document__container_title"><?php _e( 'Счёт', 'usam'); ?></div>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Расчетный счёт', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.number"></span>
							<input :class="{'hide':!item.edit}" size="50" maxlength='50' type="text" v-model="item.number">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Кор. счёт', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.bank_ca"></span>
							<input :class="{'hide':!item.edit}" size="50" maxlength='50' type="text" v-model="item.bank_ca">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Валюта', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<span v-show="!item.edit" class = "js-copy-clipboard" v-html="item.currency_name"></span>
							<select-list @change="item.currency=$event.id; item.currency_name=$event.name" :lists="currencies" :selected="item.currency" :class="{'hide':!item.edit}"></select-list>
						</div>
					</div>				
				</div>
			</div>			
		</div>		
		<button type="button" v-show="item.edit && item.number" class="button button-primary" :disabled="request" @click="saveAccount(k)"><?php _e( 'Сохранить', 'usam'); ?></button>
	</div>	
</div>
<div class ="buttons">
	<button type="button" class="button" @click="addAccount"><?php _e( 'Добавить банковский счет', 'usam'); ?></button>	
	<button type="button" v-show="taggedAccounts.length" class="button button-primary" @click="saveAccounts"><?php _e( 'Сохранить', 'usam'); ?></button>	
</div>
<?php