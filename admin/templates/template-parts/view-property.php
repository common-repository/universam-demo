<div class ="autocomplete_property js-copy-clipboard" v-if="property.field_type=='location'" v-html="property.search"></div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='date'">{{localDate(property.value,'<?php echo get_option('date_format', 'Y/m/j') ?>')}}</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='select' || property.field_type=='radio' || property.field_type=='shops'">
	<div v-if="option.id==property.value" v-for="option in property.options" v-html="option.name"></div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='checkbox'">
	<span v-if="property.value.includes(option.id)" v-for="option in property.options"><span v-html="option.name"></span>, </span>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='none' || property.field_type=='one_checkbox' || property.field_type=='personal_data' || property.field_type=='agreement'">
	<span v-if="property.value"><?php _e('Да', 'usam'); ?></span>
	<span v-else><?php _e('Нет', 'usam'); ?></span>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='file'">
	<div class='usam_attachments'>	
		<div class='usam_attachments__file' v-if="typeof property.file !== typeof undefined">		
			<a class='attachment_icon' download :href='property.file.url' title ='<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>' target='_blank'><img :src="property.file.icon"/></a>
			<div class='attachment__file_data'>
				<div class='filename'><a download :href='property.file.url' title ='<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>' target='_blank' v-html="property.file.title"></a></div>
				<div class='attachment__file_data__filesize'>{{property.file.size}}</div>
			</div>
		</div>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='files'">
	<div class='usam_attachments'>
		<div class="usam_attachments__file" v-if="property.files.length" v-for="(file,i) in property.files">
			<a class='attachment_icon' download :href='file.url' title ='<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>' target='_blank'><img :src="file.icon"/></a>
			<div class='attachment__file_data'>
				<div class='filename'><a download :href='file.url' title ='<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>' target='_blank' v-html="file.title"></a></div>
				<div class='attachment__file_data__filesize'>{{file.size}}</div>
			</div>
		</div>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='rating'">
	<div class="rating">
		<span v-for="n in [1,2,3,4,5]" class="star" :class="{'rating__selected':property.value>=n,'rating__hover':property.hover>=n}"></span>
	</div>
</div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='email'"><span class="pointer" @click="openEmail">{{property.value}}</span> <span class="validation-error" v-if="property.reason" v-html="property.communication_error+'! <?php _e('Не возможно связаться с клиентом.', 'usam'); ?>'"></span></div>
<div :class ="property.field_type+'_property'" v-else-if="property.field_type=='phone' || property.field_type=='mobile_phone'"><span class="pointer" <?php echo apply_filters( 'usam_possibility_to_call', false )? '@click="call(property)"' : ""; ?> >{{property.value}}</span> <span class="validation-error" v-if="property.reason" v-html="property.communication_error+'! <?php _e('Не возможно связаться с клиентом.', 'usam'); ?>'"></span></div>
<span v-else-if="property.mask!=''" class="js-copy-clipboard" :class ="property.field_type+'_property'">{{property.value | VMask(property.mask)}}</span>
<a v-else-if="property.value.startsWith('https://') || property.value.startsWith('http://')" :href="property.value" target='_blank' rel="noopener">{{property.value}}</a>
<span class="js-copy-clipboard" :class ="property.field_type+'_property'" v-else>{{property.value}}</span>