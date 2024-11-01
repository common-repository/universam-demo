<?php
// Name: Форма обратной связи с группой
// load_group:yes
?>
<div class = "webform">
	<div v-if="!message_result">
		<div class="description" v-html="data.description" v-if="data.description"></div>
		<div class ="webform_steps" v-if="main_groups.length!=1">
			<div class ="webform_step" v-for="(group, g) in main_groups" v-html="group.name" :class="{'active':step==g}"></div>
		</div>
		<div class ="view_form active" v-for="(group, g) in main_groups" v-if="step==g">
			<div class ="view_form__row" v-for="(property, k) in properties" v-if="property.group==group.code">			
				<div :class ="'view_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
				<div class ="view_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
				<div class ="view_form__option">
					<?php usam_include_template_file('property', 'template-parts'); ?>
				</div>
			</div>
			<div v-for="group2 in propertyGroups" v-if="group2.parent_id==group.id">
				<div class ="view_form__title" v-html="group2.name" v-if="propertyGroups.length>1"></div>
				<div class ="view_form__row" v-for="(property, k) in properties" v-if="property.group==group2.code">			
					<div :class ="'view_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
					<div class ="view_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
					<div class ="view_form__option">
						<?php usam_include_template_file('property', 'template-parts'); ?>
					</div>
				</div>
			</div>
			<div class="modal__buttons">
				<a href="" @click="prev" v-if="g">&laquo; <?php _e("назад","usam"); ?></a>
				<a href="" @click="next" v-if="g<main_groups.length-1"><?php _e("следующий шаг","usam"); ?> &raquo;</a>
				<button type="button" class="button main-button" v-if="g==main_groups.length-1" @click="send_form" :disabled="send" v-html="data.button_name"></button>
			</div>
		</div>		
	</div>	
	<div class="message_result" v-html="message_result" v-else></div>
</div>
<?php		