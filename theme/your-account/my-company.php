<?php
// Описание: Мои компании
?>
<div class="view_company" v-if="tab=='company'">		
	<div class = 'profile__title'>		
		<button @click="tab='list'" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
		<h1 class="title" v-if="company.name" v-html="company.name"></h1>
	</div>
	<div class ="edit_form" v-for="group in propertyGroups" v-if="check_group(group.code)">
		<div class ="edit_form__title">{{group.name}}</div>
		<div class ="edit_form__row edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code" :class="{'edit_form__row_error':property.error}">	
			<div :class ="'edit_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
			<div class ="edit_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
			<div class ="edit_form__option">
				<?php usam_include_template_file('property', 'template-parts'); ?>
			</div>
		</div>		
	</div>	
	<div class="edit_form">
		<div class="edit_form__buttons">
			<button v-if="company_key!==null" class="button main-button" @click="edit" :disabled="send"><?php _e('Обновить компанию','usam'); ?></button>
			<button v-else class="button main-button" @click="add"><?php _e('Добавить компанию','usam'); ?></button>
		</div>
	</div>
</div>
<div v-else> 				
	<div v-if="tab=='new'">
		<div class = 'profile__title'>		
			<button @click="tab='list'" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
			<h1 class="title"><?php _e('Добавить компанию', 'usam'); ?></h1>
		</div>
		<div class="edit_form">
			<div class="edit_form__row">
				<div class="edit_form__item">
					<div class="edit_form__name"><?php _e("ИНН", "usam"); ?>*:</div>
					<div class="edit_form__option"><input v-model="inn" required type="text" maxlength="32" class="option-input"></div>
				</div>
			</div>
			<div class="edit_form__row">
				<div class="edit_form__item">
					<div class="edit_form__name"><?php _e("КПП", "usam"); ?>:</div>
					<div class="edit_form__option"><input v-model="ppc" type="text" maxlength="32" class="option-input"></div>
				</div>
			</div>
			<div class="edit_form__buttons">
				<button class="button main-button" @click="search"><?php _e('Найти','usam'); ?></button>
			</div>
		</div>
	</div>	
	<div class="my_companies" v-if="tab=='list'">
		<div class = 'profile__title'>
			<div class = 'profile__title_actions'>
				<h1 class="title"><?php _e( 'Мои компании', 'usam'); ?></h1>
				<div class = 'profile__title_buttons'>
					<button class="button profile__title_button" @click="tab='new'"><?php _e('Добавить компанию','usam'); ?></button>
				</div>
			</div>
		</div>
		<div v-for="(comp, k) in companies" class="company_list">
			<div class="company_list__content">
				<div class="company_list__name" @click="open(k)" v-html="comp.name"></div>
				<?php usam_svg_icon( 'close', ["@click" => "comp.delete=1", "v-if" => "!comp.delete"]); ?>
				<div class="company_list__delete" v-show="comp.delete">
					<button class="button main-button" @click="del(k)"><?php _e('Удалить компанию','usam'); ?></button>
					<button class="button" @click="comp.delete=0"><?php _e('Нет','usam'); ?></button>
				</div>
			</div>				
		</div>
		<div class="empty_page" v-if="companies.length==0 && loaded">
			<div class="empty_page__icon"><?php usam_svg_icon('search') ?></div>
			<div class="empty_page__title"><?php  _e('Ваши компании не найдены', 'usam'); ?></div>
			<div class="empty_page__description">
				<p><?php  _e('К сожалению, Вы еще не добавили ни одну компанию.', 'usam'); ?></p>
			</div>
		</div>
		<?php usam_include_template_file('loading', 'template-parts'); ?>
	</div>		
</div>