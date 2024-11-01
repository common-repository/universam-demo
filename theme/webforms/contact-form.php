<?php
// Name: Форма обратной связи
?>
<div class = "webform">
	<div v-if="!message_result">
		<div class="description" v-html="data.description" v-if="data.description"></div>
		<div class ="view_form">	
			<div class ="view_form__row" v-for="(property, k) in properties">
				<div :class ="'view_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
				<div class ="view_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
				<?php usam_include_template_file('property', 'template-parts'); ?>
			</div>
		</div>
		<div class="modal__buttons">
			<button type="button" class="button main-button" @click="send_form" :disabled="send" v-html="data.button_name"></button>
		</div>
	</div>
	<div class="message_result" v-html="message_result" v-else></div>
</div>
<?php		