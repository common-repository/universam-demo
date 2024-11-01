<div class="form_settings__sections_options_name" v-if="slide.type=='vimeo' || slide.type=='youtube' || slide.type=='video'"><?php _e('Настройки видео', 'usam'); ?></div>
<div class="options" v-if="slide.type=='vimeo' || slide.type=='youtube' || slide.type=='video'">	
	<div class="options_row">
		<div class="options_name"><?php _e('Автозапуск', 'usam'); ?></div>
		<div class="options_item">
			<selector v-model="slide.settings.autoplay"></selector>
		</div>		
	</div>
	<div class="options_row">
		<div class="options_name"><?php _e('Включить звук', 'usam'); ?></div>
		<div class="options_item">
			<input type="checkbox" v-model="slide.settings.muted" class="option_input">
		</div>		
	</div>
	<div class="options_row">
		<div class="options_name"><?php _e('Качество', 'usam'); ?></div>
		<div class="options_item">
			<select-list @change="slide.settings.quality=$event.id" :lists="[{id:'', name:'auto'},{id:'240p', name:'240p'},{id:'360p', name:'360p'},{id:'540p', name:'540p'},{id:'720p', name:'720p'},{id:'1080p', name:'1080p'},{id:'2k', name:'2k'},{id:'4k', name:'4k'}]" :selected="slide.settings.quality"></select-list>
		</div>
	</div>	
	<div class="options_row" v-if="slide.type=='vimeo'">
		<div class="options_name"><?php _e('Кнопки управления', 'usam'); ?></div>
		<div class="options_item">
			<select-list @change="slide.settings.controls=$event.id" :lists="[{id:'', name:'<?php _e('Скрыть', 'usam'); ?>'},{id:1, name:'<?php _e('Показать', 'usam'); ?>'}]" :selected="slide.settings.controls"></select-list>
		</div>
	</div>	
</div>
<div class="form_settings__sections_options_name" v-if="slide.type=='vimeo' || slide.type=='youtube' || slide.type=='video'"><?php _e('Заглушка', 'usam'); ?></div>
<div class="options" v-if="slide.type=='vimeo' || slide.type=='youtube' || slide.type=='video'">							
	<div class="options_row">
		<div class="options_name image_container image_preview" height="100"><img v-if="slide.object_url" :src="slide.object_url"></div>
		<div class="options_item">
			<wp-media inline-template @change="addMedia">
				<div class="option_button" @click="addMedia"><?php _e('Медиафайлы', 'usam'); ?></div>
			</wp-media>	
			<a v-if="slide.object_url" @click="deleteMedia"><?php _e('Удалить', 'usam'); ?></a>	
		</div>					
	</div>				
</div>