<div class="form_settings__sections_options_name"><?php _e('Настройки отображения', 'usam'); ?></div>
<div class="options">	
	<div class="options_row">
		<div class="options_name"><?php _e('Повторение', 'usam'); ?></div>
		<div class="options_item">
			<select-list @change="slide.settings.css['background-repeat']=$event.id" :lists="[{id:'no-repeat', name:'<?php _e('Не повторять', 'usam'); ?>'},{id:'repeat', name:'<?php _e('Повторять', 'usam'); ?>'},{id:'repeat-x', name:'<?php _e('Повторить по горизонтали', 'usam'); ?>'},{id:'repeat-y', name:'<?php _e('Повторить по вертикали', 'usam'); ?>'}]" :selected="slide.settings.css['background-repeat']"></select-list>
		</div>		
	</div>
	<div class="options_row">
		<div class="options_name"><?php _e('Масштабирование', 'usam'); ?></div>
		<div class="options_item">
			<select-list @change="slide.settings.css['background-size']=$event.id" :lists="[{id:'cover', name:'<?php _e('Вписать в блок', 'usam'); ?>'},{id:'contain', name:'<?php _e('Вместить', 'usam'); ?>'},{id:'percent', name:'<?php _e('Процент', 'usam'); ?>'},{id:'auto', name:'<?php _e('Автоматически', 'usam'); ?>'}]" :selected="slide.settings.css['background-size']"></select-list>
		</div>		
	</div>	
	<div class="options_row">
		<div class="options_name"><?php _e('Прокрутка', 'usam'); ?></div>
		<div class="options_item">
			<select-list @change="slide.settings.css['background-attachment']=$event.id" :lists="[{id:'local', name:'<?php _e('По умолчанию', 'usam'); ?>'},{id:'scroll', name:'<?php _e('Скрол', 'usam'); ?>'},{id:'fixed', name:'<?php _e('Фиксирован', 'usam'); ?>'}]" :selected="slide.settings.css['background-attachment']"></select-list>
		</div>		
	</div>							
	<div class="options_row" v-if="slide.settings['background-size']=='percent'">
		<div class="options_name"><?php _e('Масштаб', 'usam'); ?></div>
		<div class="options_item">
			<input type="text" v-model="slide.settings.percent">
		</div>		
	</div>	
	<div class="options_row">
		<div class="options_name"><?php _e('Позиция', 'usam'); ?></div>
		<div class="options_item">
			<select-position @change="slide.settings.css['background-position']=$event" :selected="slide.settings.css['background-position']"></select-position>
		</div>		
	</div>
	<div class="options_row">
		<div class="options_name"><?php _e('Радиус', 'usam'); ?></div>
		<div class="options_item">
			<input type="text" v-model="slide.settings.css['border-radius']">
		</div>		
	</div>
</div>				