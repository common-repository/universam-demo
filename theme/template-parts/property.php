<?php 
/* 
Описание: Шаблон добавления или редактирования свойств. Например в личном кабинете при редактировании профиля или при оформлении заказа
*/
?>
<div v-if="property.field_type=='location'" :class ="property.field_type+'_property'" class="property">
	<autocomplete :code="property.code" :selected="property.search" @change="property.value=$event.id" :request="'locations'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
</div>
<div v-else-if="property.field_type=='company'" :class ="property.field_type+'_property'" class="property">
	<autocomplete :code="property.code" :selected="property.value" @change="change_company" :request="'companies/search'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
</div>
<div v-else-if="property.field_type=='address' || property.field_type=='textarea'" :class ="property.field_type+'_property'" class="property">
	<textarea v-model="property.value" class="option-input" rows="3" cols="40" maxlength="1000"></textarea>
</div>
<div v-else-if="property.field_type=='click_show'" v-show="property.show" :class ="property.field_type+'_property'" class="property">
	<textarea v-model="property.value" class="option-input" rows="3" cols="40" maxlength="9000"></textarea>
</div>			
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='date'">
	<input type='text' class="option-input" placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.value" v-mask="'##.##.####'" autocomplete="off">
</div>	
<div class="property_selectlist" :class ="property.field_type+'_property'" v-else-if="property.field_type=='select' || property.field_type=='shops'">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value"></select-list>
	<?php usam_svg_icon("angle-down-solid"); ?>
</div>
<div class="property_radio"  :class ="property.field_type+'_property'" v-else-if="property.field_type=='radio'">
	<label v-for="(option,i) in property.options">
		<input type='radio' class="option-input radio" v-model="property.value" :value="option.id">
		<span v-html="option.name"></span>
	</label>
</div>
<div class="property_selectlist" :class ="property.field_type+'_property'" v-else-if="property.field_type=='checkbox'">
	<select-list @change="property.value=$event.id" :lists="property.options" multiple='1' :selected="property.value"></select-list>
	<?php usam_svg_icon("angle-down-solid"); ?>
	<input type='hidden' class="option-input" :name="'fields['+property.code+'][]'" v-for="(v,i) in property.value" v-model="property.value[i]">
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='none'"></div>	
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='one_checkbox'">
	<label><input type='checkbox' class="option-input" v-model="property.value"/>{{property.name}} <span v-if="property.mandatory">*</span></label>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='personal_data'">
	<input type='checkbox' class="option-input" v-model="property.value"/><a @click="modal(property.id)" v-html="property.name"></a><span v-if="property.mandatory">*</span>
	<teleport to="body">
		<modal-window :ref="'modal'+property.id" :backdrop="true">
			<template v-slot:title><div class ="property_name_agreement"><?php _e('Согласие на обработку персональных данных','usam'); ?></div></template>
			<template v-slot:body>
				<div class ="property_agreement modal-scroll">
					<?php echo wpautop( wp_kses_post( get_option( 'usam_consent_processing_personal_data' ) ) ) ?>
				</div>
			</template>
			<template v-slot:buttons>
				<button class="button main-button" @click="property.value=1; modal(property.id)"><?php _e('Согласен', 'usam'); ?></button>			
			</template>
		</modal-window>
	</teleport>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='agreement'">
	<input type='checkbox' class="option-input" v-model="property.value"/><a @click="modal(property.id)" v-html="property.name"></a><span v-if="property.mandatory">*</span>
	<teleport to="body">
		<modal-window :ref="'modal'+property.id" :backdrop="true">
			<template v-slot:title><div class ="property_name_agreement" v-html="property.name_agreement"></div></template>
			<template v-slot:body>
				<div class ="property_agreement modal-scroll" v-html="property.agreement"></div>
			</template>
			<template v-slot:buttons>
				<button class="button main-button" @click="property.value=1; modal(property.id)"><?php _e('Согласен', 'usam'); ?></button>			
			</template>
		</modal-window>
	</teleport>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='button'">
	<a :href='property.url' class='button'>{{property.button_name}}</a>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='postcode'">
	<input type='text' class="option-input" v-model="property.value"/>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='file'">
	<div class='usam_attachments' @drop="fileDrop($event, k)" @dragover="aDrop">		
		<div class='usam_attachments__file' v-if="typeof property.file !== typeof undefined">
			<a class='usam_attachments__file_delete delete' @click="fileDelete(k, 0)"></a>							
			<div class='attachment_icon'>	
				<img v-show="property.file.icon !== undefined" :src='property.file.icon'/>				
				<progress-circle v-show="property.file.load" :percent="property.file.percent"></progress-circle>
			</div>
			<div class='attachment__file_data'>
				<div class='filename' v-html="property.file.title"></div>
				<div v-if="property.file.error" class='attachment__file_data__filesize' :class="{'loading_error':property.file.error}">{{property.file.error}}</div>
			</div>
		</div>
		<div class ='attachments__placeholder' @click="fileAttach">
			<div v-if="property.file==undefined">
				<div class="attachments__placeholder__text"><?php esc_html_e( 'Перетащите или нажмите, чтобы прикрепить файл', 'usam'); ?></div>
				<div class="attachments__placeholder__select"><?php usam_svg_icon("attachment"); ?><?php esc_html_e( 'Выбрать файл', 'usam'); ?></div>
			</div>
		</div>
		<input type='file' @change="fileChange($event, k)">
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='files'">
	<div class='usam_attachments' @drop="fileDrop($event, k)" @dragover="aDrop">			
		<div class="usam_attachments__file" v-if="property.files.length" v-for="(file,i) in property.files">
			<a class='usam_attachments__file_delete delete' @click="fileDelete(k,i)"></a>
			<div class='attachment_icon'>	
				<img v-show="file.icon !== undefined" :src='file.icon'/>				
				<progress-circle v-show="file.load" :percent="file.percent"></progress-circle>
			</div>
			<div class='attachment__file_data'>
				<div class='filename'>{{file.title}}</div>				
				<div v-if="file.error" class='filename' :class="{'loading_error':file.error}">{{file.error}}</div>
			</div>
		</div>
		<div class ='attachments__placeholder' @click="fileAttach">
			<div v-if="property.files.length==0">
				<div class="attachments__placeholder__text"><?php esc_html_e( 'Перетащите или нажмите, чтобы прикрепить файлы', 'usam'); ?></div>
				<div class="attachments__placeholder__select"><?php usam_svg_icon("attachment"); ?><?php esc_html_e( 'Выбрать файлы', 'usam'); ?></div>
			</div>
		</div>
		<input type='file' @change="fileChange($event, k)" multiple>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='rating'">
	<div class="rating">
		<?php usam_svg_icon("star", ['class' => 'star', 'v-for' => 'n in [1,2,3,4,5]', '@click' => 'property.value=n', '@mouseover' => 'property.hover=n', ':class' =>'{"rating__selected":property.value>=n,"rating__hover":property.hover>=n}', '@mouseleave' => 'property.hover=0']); ?>
	</div>
</div>
<div :class="[property.verification?'verification':'', property.error?'verification_error':'', property.field_type+'_property']" class="property" v-else>
	<input type='text' class="option-input" v-model="property.value" v-mask="property.mask">
</div>
<div class='validation-error' v-if="property.error=='simple'"><?php _e('Пароль слишком простой. Пароль должен содержать цифры и буквы', 'usam'); ?></div>
<div class='validation-error' v-else-if="property.error=='pass_smalllength'"><?php _e('Длина пароля должна быть больше 5 символов', 'usam'); ?></div>
<div class='validation-error' v-else-if="property.error"><?php _e('Пожалуйста, введите', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>
<div class='validation-error' v-else-if="property.error && property.value===''"><?php _e('Пожалуйста, введите', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>
<div class='validation-error' v-else-if="property.error"><?php _e('Проверьте', 'usam'); ?> <span class="usam_error_msg_field_name" v-html="property.name"></span></div>