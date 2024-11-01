<div class ="autocomplete_property object_property" v-if="property.field_type=='location'">
	<autocomplete :code="property.code" :selected="property.search" @change="property.value=$event.id" :request="'locations'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
	<input type='hidden' class="option-input" :name="'fields['+property.code+']'" v-model="property.value">
</div>
<div class ="autocomplete_property object_property" v-else-if="property.field_type=='company'">	
	<autocomplete :code="property.code" :selected="property.value" @change="change_company" :request="'companies/search'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
	<input type='hidden' class="option-input" :name="'fields['+property.code+']'" v-model="property.value">
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='address' || property.field_type=='textarea' || property.field_type=='click_show'">
	<textarea v-model="property.value" :name="'fields['+property.code+']'" class="option-input" rows="3" cols="40" maxlength="9000"></textarea>
</div>		
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='date'">
	<input type='text' class="option-input" :name="'fields['+property.code+']'" placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.value" v-mask="'##.##.####'" autocomplete="off"/>
</div>	
<div class="object_property property_selectlist" :class ="property.field_type+'_property'" v-else-if="property.field_type=='select' || property.field_type=='radio' || property.field_type=='shops'">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value"></select-list>
	<input type='hidden' class="option-input" :name="'fields['+property.code+']'" v-model="property.value">
</div>
<div class="object_property property_selectlist" :class ="property.field_type+'_property'" v-else-if="property.field_type=='checkbox'">
	<select-list @change="property.value=$event.id" :lists="property.options" multiple='1' :selected="property.value"></select-list>
	<input type='hidden' class="option-input" :name="'fields['+property.code+'][]'" v-for="(v,i) in property.value" v-model="property.value[i]">
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='none' || property.field_type=='personal_data' || property.field_type=='agreement'">
	<input type='checkbox' class="option-input" v-model="property.value"/>
</div>	
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='one_checkbox'">
	<input type='hidden' class="option-input" :name="'fields['+property.code+']'" value='0'/>
	<input type='checkbox' class="option-input" :name="'fields['+property.code+']'" v-model="property.value" value='1'>
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='button'">
	<a :href='property.url' class='button'>{{property.button_name}}</a>
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='postcode'">
	<input type='text' class="option-input" :name="'fields['+property.code+']'" v-model="property.value"/>
</div>
<div class='object_property images' :class ="property.field_type+'_property'" v-else-if="property.field_type=='file'">
	<attachment v-model="property.value" :attachment="property.file" :args="{'property':property.id,'type':'property'}"></attachment>
</div>
<div class='object_property usam_attachments images' :class ="property.field_type+'_property'" v-else-if="property.field_type=='files'" @drop="fileDrop($event, k)" @dragover="aDrop">
	<div class="usam_attachments__file" v-if="property.files.length" v-for="(file,i) in property.files">
		<input type='hidden' class="option-input" :name="'fields['+property.code+'][]'" v-model="file.id"/>
		<a class='usam_attachments__file_delete delete' @click="fileDelete(k,i)"></a>
		<div class='attachment_icon'>	
			<img v-show="file.icon !== undefined" :src='file.icon'/>				
			<progress-circle v-show="file.load" :percent="file.percent"></progress-circle>
		</div>
		<div class='attachment__file_data'>
			<div class='filename'>{{file.title}}</div>				
			<div v-if="file.error" class='attachment__file_data__error' :class="{'loading_error':file.error}">{{file.error}}</div>
			<div v-else class='attachment__file_data__filesize'><a download :href="file.url" title ="<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>" target='_blank' v-show="file.percent!== undefined"><?php _e('Скачать','usam'); ?></a>{{file.size}}</div>
		</div>
	</div>		
	<div class ='attachments__placeholder' @click="fileAttach">
		<div v-if="property.files.length==0">
			<div class="attachments__placeholder__text"><?php esc_html_e( 'Перетащите или нажмите, чтобы прикрепить файлы', 'usam'); ?></div>
			<div class="attachments__placeholder__select"><span class="dashicons dashicons-paperclip"></span><?php esc_html_e( 'Выбрать файлы', 'usam'); ?></div>
		</div>
	</div>
	<input type='file' @change="fileChange($event, k)" multiple>
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else-if="property.field_type=='rating'">
	<div class="rating">
		<span v-for="n in [1,2,3,4,5]" @click="property.value=n" @mouseover="property.hover=n" class="star" :class="{'rating__selected':property.value>=n,'rating__hover':property.hover>=n}" @mouseleave="property.hover=0"></span>
		<input type='hidden' class="option-input" :name="'fields['+property.code+']'" v-model="property.value"/>
	</div>
</div>
<div class ="object_property" :class ="property.field_type+'_property'" v-else>
	<input type='text' class="option-input" :name="'fields['+property.code+']'" v-model="property.value" v-mask="property.mask"/>	
</div>
<div class="message_error usam_message" :class="{'hide-animation':!property.error}"><?php _e('Пожалуйста, введите', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>