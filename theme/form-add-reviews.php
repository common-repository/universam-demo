<?php
// Описание: Шаблон формы добавления "Отзывов клиентов"
?>
<div class="add_item_form add_new_review_form">
	<div id = "webform_<?php echo $this->options['goto_show_button']; ?>" class = "edit_form webform js-webform" v-cloak>
		<div v-if="!message_result">
			<div class='edit_form__add_title'><?php _e('Напишите свой отзыв', 'usam'); ?></div>		
			<div class ="edit_form__item edit_form__row" v-for="(property, k) in properties">			
				<div :class ="'edit_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a v-html="property.name"></a></div>
				<div class ="edit_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data'">{{property.name}} <span v-if="property.mandatory">*</span></div>
				<div class ="edit_form__option">
					<?php usam_include_template_file( 'property', 'template-parts' ); ?>
				</div>
			</div>
			<div class="edit_form__buttons">		
				<input class ="button main-button" type="submit" @click="send_form" :disabled="send" value="<?php _e('Оставить свой отзыв','usam'); ?>">
			</div>
		</div>
		<div class="message_result" v-html="message_result" v-else></div>
	</div>			
</div>	