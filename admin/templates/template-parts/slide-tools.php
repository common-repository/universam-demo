<div class="form_settings__sections_options" v-if="section[tab]=='filter'">
	<div class="form_settings__sections_options_name"><?php _e('Фильтр фона', 'usam'); ?></div>
	<div class="options">
		<div class="options_row">
			<div class="options_name"><?php _e('Фильтр', 'usam'); ?></div>
			<div class="options_item">
				<select-list @change="slide.settings.filter=$event.id" :lists="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'1977', name:'1977'}, {id:'aden', name:'Aden'}, {id:'brooklyn', name:'Brooklyn'}, {id:'clarendon', name:'Clarendon'}, {id:'earlybird', name:'Earlybird'}, {id:'gingham', name:'Gingham'}, {id:'hudson', name:'Hudson'}, {id:'inkwell', name:'Inkwell'}, {id:'lark', name:'Lark'}, {id:'lofi', name:'Lo-Fi'}, {id:'mayfair', name:'Mayfair'}, {id:'moon', name:'Moon'}, {id:'nashville', name:'Nashville'}, {id:'perpetua', name:'Perpetua'}, {id:'reyes', name:'Reyes'}, {id:'rise', name:'Rise'}, {id:'slumber', name:'Slumber'}, {id:'toaster', name:'Toaster'}, {id:'walden', name:'Walden'}, {id:'willow', name:'Willow'}, {id:'xpro2', name:'X-pro II'}]" :selected="slide.settings.filter"></select-list>
			</div>		
		</div>		
		<div class="options_row" v-if="slide.settings.filter">
			<div class="options_name"><?php _e('Прозрачность', 'usam'); ?></div>
			<div class="options_item">
				<input type="text" v-model="slide.settings.filter_opacity" class="option_input">
				<range-slider :min="0" :max="100" :value="slide.settings.filter_opacity*100" @change="slide.settings.filter_opacity=$event/100"/>
			</div>		
		</div>							
	</div>
</div>
<div class="form_settings__sections_options" v-if="section[tab]=='animation'">
	<div class="form_settings__sections_options_name"><?php _e('Анимация фона', 'usam'); ?></div>
	<div class="options">
		<div class="options_row">
			<div class="options_name"><?php _e('Готовые эффекты', 'usam'); ?></div>
			<div class="options_item">
				<select-list @change="slide.settings.effect=$event.id" :lists="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'zoom', name:'<?php _e('Увеличение', 'usam'); ?>'}, {id:'move', name:'<?php _e('Движение', 'usam'); ?>'}, {id:'moveall', name:'<?php _e('Движение во все стороны', 'usam'); ?>'}]" :selected="slide.settings.effect"></select-list>
			</div>		
		</div>								
	</div>
</div>	
<div class="form_settings__sections_options" v-if="section[tab]=='html'">		
	<div class="form_settings__sections_options_name"><?php _e('Атрибуты', 'usam'); ?></div>
	<div class="options">							
		<div class="options_row"><?php _e('Классы', 'usam'); ?></div>
		<div class="options_row">
			<input type="text" v-model="slide.settings.classes" class="option_input">
		</div>		
		<div class="options_row">CSS</div>
		<div class="options_row">
			<textarea v-model="slide.settings.custom_css"></textarea>	
		</div>		
	</div>											
</div>	
<div class="form_settings__sections_options" v-if="section[tab]=='content'">		
	<div class="form_settings__sections_options_name"><?php _e('Описание слайда', 'usam'); ?></div>
	<div class="options">							
		<div class="options_row"><?php _e('Название', 'usam'); ?></div>
		<div class="options_row">
			<input type="text" v-model="slide.title" class="option_input">
		</div>			
		<div class="options_row"><?php _e('Описание', 'usam'); ?></div>
		<div class="options_row"><textarea v-model="slide.settings.description"></textarea></div>			
	</div>
</div>		
<div class="form_settings__sections_options" v-if="section[tab]=='actions'">
	<div class="form_settings__sections_options_name"><?php _e('Действия', 'usam'); ?></div>
	<div class="options">							
		<div class="options_row">
			<div class="options_name"><?php _e('Тип действия', 'usam'); ?></div>
			<div class="options_item">
				<select-list @change="slide.settings.actions.type=$event.id" :lists="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'link', name:'<?php _e('Ссылка', 'usam'); ?>'},{id:'webform', name:'<?php _e('Веб-форма', 'usam'); ?>'},{id:'modal', name:'<?php _e('Модальное окно', 'usam'); ?>'}]" :selected="slide.settings.actions.type"></select-list>
			</div>		
		</div>					
		<div class="options_row" v-if="slide.settings.actions.type=='link'">
			<div class="options_name"><?php _e('Ссылка', 'usam'); ?></div>
			<div class="options_item">
				<input type="text" v-model="slide.settings.actions.value" class="option_input">
			</div>		
		</div>	
		<div class="options_row" v-if="slide.settings.actions.type=='modal'">
			<div class="options_name"><?php _e('Код модального окна', 'usam'); ?></div>
			<div class="options_item">
				<input type="text" v-model="slide.settings.actions.value" class="option_input">
			</div>		
		</div>
		<div id="webforms" class="options_row" v-if="slide.settings.actions.type=='webform'">
			<div class="options_name"><?php _e('Веб-форма', 'usam'); ?></div>
			<div class="options_item">
				<select-list @change="slide.settings.actions.value=$event.id" :lists="webforms" :selected="slide.settings.actions.value"></select-list>
			</div>		
		</div>	
	</div>				
</div>						