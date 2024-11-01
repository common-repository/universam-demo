<?php
// Описание: Вывод данных в профиле пользователя
?>
<div class = 'profile__title'>
	<button v-if="tab!=='view'" @click="tab='view'" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
	<div class = 'profile__title_actions' v-if="tab==='change_password'">
		<h1 class="title"><?php _e('Изменить пароль', 'usam'); ?></h1>	
	</div>
	<div class = 'profile__title_actions' v-else-if="tab==='subscription'">
		<h1 class="title"><?php _e('Подписка на новости', 'usam'); ?></h1>	
	</div>
	<div class = 'profile__title_actions' v-else>
		<h1 class="title"><?php _e('Мой профиль', 'usam'); ?></h1>	
		<div class = 'profile__title_buttons'>
			<a class="button_change_password profile__title_button" @click="tab='change_password'"><?php _e( 'Изменить пароль', 'usam'); ?> →</a>
		</div>
	</div>
</div>
<?php usam_include_template_file('loading', 'template-parts'); ?>
<div v-if="tab=='view' && loaded">
	<div class ="view_form" v-for="(group, i) in propertyGroups" v-if="check_group(group.code)">
		<div class ="view_form__title" v-if="i>0">{{group.name}}</div>
		<div class ="view_form__item" v-for="(property, k) in properties" v-if="property.group==group.code && property.field_type!='personal_data' && property.field_type!='agreement'" :class="{'edit_form__row_error':property.error}">					
			<div class ="view_form__name" v-html="property.name"></div>
			<div class ="view_form__option">
				<?php usam_include_template_file('view-property', 'template-parts'); ?>
			</div>
		</div>			
	</div>
	<div class="view_form">		
		<div class="view_form__buttons">	
			<a @click="tab='edit'" class="button"><?php _e( 'Редактировать', 'usam'); ?></a>
			<a @click="tab='subscription'"><?php _e( 'Изменить подписку', 'usam'); ?></a>			
		</div>
	</div>
</div>
<div v-else-if="tab=='change_password' && loaded">	
	<div class='usam_message message_error' v-if="codeError=='pass_smalllength'">
		<div class='validation-error'><?php _e('Длина пароля должна быть больше 5 символов', 'usam'); ?></div>
	</div>
	<div class='usam_message message_error' v-if="codeError=='simple'">
		<div class='validation-error'><?php _e('Пароль слишком простой. Пароль должен содержать цифры и буквы', 'usam'); ?></div>
	</div>
	<div class='usam_message message_error' v-if="codeError=='pass_notequal'">
		<div class='validation-error'><?php _e('Пароли не равны', 'usam'); ?></div>
	</div>
	<div class="edit_form">
		<div class="edit_form__row">
			<div class ="edit_form__item">
				<div class ="edit_form__name"><?php _e( 'Новый пароль', 'usam') ?></div>
				<div class ="edit_form__option">
					<input type="text" v-model="pass" class="option-input"> 
				</div>
			</div>
		</div>
		<div class="edit_form__row">
			<div class ="edit_form__item">
				<div class ="edit_form__name"><?php _e( 'Повторите пароль','usam'); ?></div>
				<div class ="edit_form__option">
					<input type="text" v-model="pass2" class="option-input"> 
				</div>
			</div>
		</div>		
		<div class="edit_form__buttons">	
			<button class="button main-button" @click="change_pass" :disabled="pass == '' || pass2 == '' || send"><?php _e( 'Сохранить пароль', 'usam'); ?></button>
		</div>
	</div>
</div>
<div v-else-if="tab=='subscription' && loaded">	
	<div class="view_form" v-if="loaded">
		<div class="view_form__item" v-for="subscription in subscriptions">
			<label><input class="option-input" type="checkbox" v-model="subscription.subscribe"><span v-html="subscription.name"></span></label>
		</div>
	</div>
</div>
<div v-else-if="tab=='edit' && loaded">
	<div class ="edit_form" v-for="(group, i) in propertyGroups" v-if="check_group(group.code)">
		<div class ="edit_form__title" v-if="i>0">{{group.name}}</div>
		<div class ="edit_form__row edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code" :class="{'edit_form__row_error':property.error}">	
			<div :class ="'edit_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
			<div class ="edit_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
			<div class ="edit_form__option">
				<?php usam_include_template_file('property', 'template-parts'); ?>
			</div>
		</div>			
	</div>
	<div class ="edit_form">		
		<div class="edit_form__buttons" v-if="confirmAction!='deleteUser'">	
			<button class="button main-button" @click="save" :disabled="codeError" :class="{'is-loading':send}"><?php _e( 'Сохранить профиль', 'usam'); ?></button>
			<a class="profile__title_button" @click="confirmAction='deleteUser'"><?php _e( 'Удалить профиль', 'usam'); ?></a>			
		</div>		
		<div class="edit_form__title"v-if="confirmAction=='deleteUser'"><?php _e('Подтвердите удаление профиля', 'usam'); ?></div>
		<div class="edit_form__buttons" v-if="confirmAction=='deleteUser'">
			<a class="button main-button" @click="deleteProfile"><?php _e( 'Удалить профиль', 'usam'); ?></a>	
			<a class="button" @click="confirmAction=''"><?php _e( 'Отмена', 'usam'); ?></a>	
		</div>
	</div>
</div>