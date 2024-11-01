<div class ="object_property" v-if="property.field_type=='DESCRIPTION'">
	<textarea v-model="property.value" rows="3" cols="40" maxlength="9000" :name="'attributes['+property[`slug`]+']'"></textarea>
</div>
<div class ="object_property" v-else-if="property.field_type=='D'">			
	<input type='text' placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.value" v-mask="'##.##.####'" :name="'attributes['+property[`slug`]+']'" autocomplete="off"/>
</div>	
<div class="object_property attribute_selectlist" v-else-if="property.field_type=='S' || property.field_type=='N' || property.field_type=='COLOR' || property.field_type=='A'">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value" :search="1"></select-list>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<div class="object_property attribute_buttons" v-else-if="property.field_type=='BUTTONS'">
	<selector v-model="property.value" :items="property.options"></selector>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<div class="object_property attribute_buttons" v-else-if="property.field_type=='PRICES'">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value" :search="1"></select-list>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<div class="object_property attribute_autocomplete" v-else-if="property.field_type=='AUTOCOMPLETE'">
	<autocomplete :code="k" :selected="property.search" @change="property.value = $event.id; property.search=$event.name" :request="'attribute_values'" :query="{attribute_id:property.term_id}"></autocomplete>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<div class="object_property attribute_checklist checkblock" v-else-if="property.field_type=='COLOR_SEVERAL' || property.field_type=='M'">
	<div><input type="hidden" :name="'attributes['+property[`slug`]+'][]'" :value="id" v-for="(id, k) in property.value"></div>
	<check-list :lists='property.options' @change="property.value=$event" :color="property.field_type=='COLOR_SEVERAL'"/>	
</div>
<div class ="object_property" v-else-if="property.field_type=='C'">
	<input type='checkbox' v-model="property.value" :name="'attributes['+property[`slug`]+']'"/>
</div>
<div class='object_property usam_attachments' v-else-if="property.field_type=='file'">
	<attachment v-model="property.value" :attachment="property.file" :args="{'property':property.id,'type':'property'}"></attachment>
</div>
<div class="object_property" v-else-if="property.field_type=='F'">
	<wp-media v-model="property.value" :file="property.file"></wp-media>
	<input type="hidden" :value="property.value" :name="'attributes['+property[`slug`]+']'"/>
</div>
<div class ="object_property" v-else-if="property[`slug`] == 'brand' || property[`slug`] == 'contractor'" v-html="property.value"></div>
<div class ="object_property" v-else>
	<autocomplete :selected="property.value" v-on:keydown="property.value=$event.value" @change="property.value=$event.name" :request="'products/attributes'" :query="{slug:property[`slug`]}" :none="'<?php _e('Подобные данные не найдены','usam'); ?>'"></autocomplete>
	<input type="hidden" v-model="property.value" :name="'attributes['+property[`slug`]+']'">
</div>
<div class="message_error usam_message" :class="{'hide-animation':!property.error}"><?php _e('Пожалуйста, введите', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>