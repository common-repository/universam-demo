<?php
/*
Шаблон вывода свойств, для просмотра. Используется, например, для вывода данных профиля в личном кабинете.
*/ 
?>
<div class ="autocomplete_property" v-if="property.field_type=='location'" v-html="property.search"></div>
<div class ="autocomplete_property" v-else-if="property.field_type=='company'" v-html="property.search"></div>
<div class ="autocomplete_property" v-else-if="property.field_type=='date'">{{localDate(property.value,'<?php echo get_option('date_format', 'Y/m/j') ?>')}}</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='select' || property.field_type=='radio' || property.field_type=='S' || property.field_type=='N' || property.field_type=='BUTTONS' || property.field_type=='AUTOCOMPLETE' || property.field_type=='COLOR' || property.field_type=='A'" v-html="Object.values(property.value).join(', ')"></div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='COLOR_SEVERAL' || property.field_type=='M'" v-html="Object.values(property.value).join(', ')"></div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='checkbox'">
	<span v-if="property.value.includes(option.id)" v-for="option in property.options"><span v-html="option.name"></span>, </span>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='none' || property.field_type=='one_checkbox' || property.field_type=='personal_data' || property.field_type=='agreement'"></div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='file'">
	<div class='usam_attachments'>	
		<div class='usam_attachments__file' v-if="typeof property.file !== typeof undefined">		
			<div class='attachment__file_data' v-html="property.file.title"></div>
		</div>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='files'">
	<div class='usam_attachments'>		
		<div class="usam_attachments__file" v-if="property.files.length" v-for="(file,i) in property.files">
			<div class='attachment__file_data' v-html="file.title"></div>
		</div>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='rating'">
	<div class="rating">
		<span v-for="n in [1,2,3,4,5]"><?php usam_svg_icon("star", "star"); ?></span>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.value!==null">
	<span v-if="typeof property.value === 'object'" v-html="property.value.join(', ')"></span>
	<span v-else v-html="property.value"></span>
</div>