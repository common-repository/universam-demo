<div class = 'edit_form'>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Логин покупателя', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">								
			<autocomplete v-if="edit_data" :selected="user.user_login" @change="data.user_id=$event.id; data.user_login=$event.user_login" :request="'users'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
			<div v-else>
				<a v-if="user.user_login" :href="'<?php echo admin_url("user-edit.php"); ?>?user_id='+data.user_id" v-html="user.user_login"></a>
				<div v-else><?php _e('заказ не привязан к личному кабинету','usam'); ?></div>
			</div>
		</div>
	</div>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Тип плательщика', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">
			<span v-if="!edit_data && payer.id==data.type_payer" v-for="payer in payers" v-html="payer.name"></span>
			<select v-model="data.type_payer" v-if="edit_data" name="type_payer">			
				<option v-for="payer in payers" :value="payer.id" v-html="payer.name"></option>
			</select>
		</div>
	</div>
</div>
<div class ="edit_form" v-for="group in propertyGroups" v-if="check_group(group.code) && group.type_payers.includes(data.type_payer)">
	<div class ="edit_form__title">{{group.name}}</div>
	<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code && property.field_type!='personal_data' && property.field_type!='agreement'" :class="{'edit_form__row_error':property.error}">
		<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
		<div class ="edit_form__item_option" v-if="edit_data">
			<?php include( USAM_FILE_PATH . '/admin/templates/template-parts/property.php' ); ?>
		</div>
		<div class ="edit_form__item_option" v-else-if="!edit_data">
			<?php include( USAM_FILE_PATH . '/admin/templates/template-parts/view-property.php' ); ?>
		</div>
	</div>
</div>						
<p v-if="edit_data && !edit">
	<button class="button button-primary" @click="save_customer"><?php _e( 'Сохранить', 'usam'); ?></button>
	<button class="button" @click="edit_data=!edit_data"><?php _e( 'Отменить', 'usam'); ?></button>
	<button class="button" @click="sidebar('buyers')"><?php _e('Сменить покупателя', 'usam'); ?></button>
</p>
<?php
add_action('usam_after_form',function() {
	require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-buyers.php' );
});
usam_vue_module('list-table');