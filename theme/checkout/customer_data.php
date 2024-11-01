<?php //Данные клиента ?>
<?php usam_change_block( admin_url( "admin.php?page=orders&tab=orders&view=table&table=order_properties" ), __("Добавить или изменить свойства заказа", "usam") ); ?>
<div class ="view_form" v-for="group in propertyGroups" v-if="check_group(group.code)">
	<div class ="view_form__title">{{group.name}}</div>
	<div class ="view_form__row" v-for="(property, k) in properties" v-if="property.group==group.code" :class="{'view_form__row_error':property.error}">	
		<div :class ="'view_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
		<div class ="view_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
		<div class ="view_form__option">
			<?php usam_include_template_file( 'property', 'template-parts'); ?>
		</div>
	</div>	
	<div class ="view_form__title" v-if="basket.agreements.length>0"><?php _e('Лицензионные соглашения', 'usam'); ?></div>
	<div class ="view_form__row" v-if="basket.agreements.length>0">		
		<p><?php _e('Пожалуйста, ознакомьтесь с текстом лицензионного соглашения и поставьте галочку, если вы согласны с ним', 'usam'); ?>:</p>
	</div>				
	<div class ="view_form__row" v-if="basket.agreements.length>0" v-for="(agreement, k) in basket.agreements">			
		<input class="option-input" type='checkbox' :value='agreement.ID' v-model='license'/>
		<a class='license_agreement' @click="modal(agreement.ID)">{{agreement.post_title}}</a><span class="asterix">*</span>	
		<p class='validation-error' v-if="errors_codes.license.includes(agreement.ID)"><?php _e('Пожалуйста, примите условия лицензионного соглашения, в противном случае мы не можем обработать ваш заказ.', 'usam'); ?></p>
		<teleport to="body">
			<modal-window :ref="'modal'+agreement.ID" :backdrop="true">
				<template v-slot:title><?php _e('Условия лицензионного соглашения','usam'); ?></template>
				<template v-slot:body>
					<div class ="property_agreement modal-scroll" v-html="agreement.post_content"></div>
				</template>
				<template v-slot:buttons>
					<button class="button main-button" @click="modal(agreement.ID); license.push(agreement.ID);"><?php _e('Согласен', 'usam'); ?></button>			
				</template>
			</modal-window>
		</teleport>
	</div>
</div>