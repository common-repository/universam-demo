<div class ="edit_form__item">
	<div class ="edit_form__item_name"><?php _e( 'Отображение','usam'); ?>:</div>
	<div class ="edit_form__item_option">
		<select v-model="data.appeal">					
			<option :value="lastname_f_p"  v-if="data.firstname!== ''" v-html="lastname_f_p"></option>
			<option :value="data.lastname+' '+data.firstname+' '+data.patronymic"  v-if="data.lastname!== '' && data.firstname!== '' && data.patronymic!== ''" v-html="data.lastname+' '+data.firstname+' '+data.patronymic"></option>
			<option :value="data.firstname+' '+data.patronymic" v-if="data.patronymic!== '' && data.firstname!== ''" v-html="data.firstname+' '+data.patronymic"></option>
			<option :value="data.lastname+' '+data.firstname" v-if="data.lastname!== '' && data.firstname!== ''" v-html="data.lastname+' '+data.firstname"></option>
			<option :value="data.firstname" v-if="data.firstname!== ''" v-html="data.firstname"></option>
			<option :value="data.lastname" v-if="data.lastname!== ''" v-html="data.lastname"></option>
		</select>												
	</div>	
</div>
<div class ="edit_form__item">
	<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
	<div class ="edit_form__item_option">
		<select v-model='data.status'>
			<option v-for="status in statuses" v-if="status.internalname == data.status || status.visibility" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
		</select>
	</div>			
</div>
<div class ="edit_form__item">
	<div class ="edit_form__item_name"><?php _e( 'Пользователь','usam'); ?>:</div>
	<div class ="edit_form__item_option">
		<autocomplete :selected="user.user_login" @change="user_change" :request="'users'"></autocomplete>
		<input type="hidden" name="user_id" v-model="data.user_id">
	</div>
</div>	
<div class ="edit_form__item">
	<div class ="edit_form__item_name"><?php _e( 'Пол','usam'); ?>:</div>
	<div class ="edit_form__item_option">
	<select v-model="data.sex">
		<option value=""><?php _e('Не известно','usam'); ?></option>
		<option value="m"><?php _e('Мужской','usam'); ?></option>
		<option value="f"><?php _e('Женский','usam'); ?></option>
	</select>	
	</div>
</div>
<div class ="edit_form__item">
	<div class ="edit_form__item_name"><?php esc_html_e( 'Дата рождения', 'usam'); ?>:</div>
	<div class ="edit_form__item_option">
		<date-picker v-model="data.birthday"/>
	</div>
</div>	