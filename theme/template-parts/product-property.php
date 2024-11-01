<?php 
/* 
Использование: Обычно используется для маркетплейса
Описание:      Шаблон добавления или редактирования свойств товара
*/
?>
<div v-if="property.field_type=='DESCRIPTION'">
	<textarea v-model="property.value" rows="3" cols="40" maxlength="9000" class="option-input"></textarea>
</div>
<div v-else-if="property.field_type=='D'">			
	<input type='text' placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.value" v-mask="'##.##.####'" autocomplete="off" class="option-input">
</div>	
<div v-else-if="property.field_type=='S' || property.field_type=='N' || property.field_type=='COLOR' || property.field_type=='A'" class="attribute_selectlist">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value" :search="1"></select-list>
</div>
<div v-else-if="property.field_type=='BUTTONS'" class="attribute_buttons">
	<selector v-model="property.value" :items="property.options"></selector>
</div>
<div class="object_property attribute_autocomplete" v-else-if="property.field_type=='AUTOCOMPLETE'">
	<autocomplete :code="k" :selected="property.search" @change="property.value = $event.id; property.search=$event.name" :request="'attribute_values'" :query="{attribute_id:property.term_id}"></autocomplete>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<check-list :lists='property.options' @change="property.value=$event" :color="property.field_type=='COLOR_SEVERAL'" v-else-if="property.field_type=='COLOR_SEVERAL' || property.field_type=='M'"/>
<div v-else-if="property.field_type=='C'">
	<input type='checkbox' class="option-input" v-model="property.value">
</div>	
<attachment v-model="property.value" :attachment="property.file" :args="{'property':property.id,'type':'property'}" v-else-if="property.field_type=='file'"></attachment>
<div v-else-if="property.field_type=='F'">
	<wp-media v-model="property.value" :file="property.file"></wp-media>
</div>
<div v-else>
	<div v-if="property[`slug`] == 'brand' || property[`slug`] == 'contractor'" v-html="property.value"></div>
	<div v-else>	
		<input type='text' v-model="property.value" class="option-input">	
	</div>
</div>
<div class='validation-error' v-if="property.error"><?php _e('Пожалуйста, введите', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>