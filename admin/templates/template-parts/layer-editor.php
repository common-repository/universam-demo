<div class="form_settings" v-else-if="tab=='editor'">
	<div class="form_settings__name"><?php _e('Редактор слоев', 'usam'); ?></div>				
	<div class="form_settings__info form_settings__sections" v-if="layerActive === null">
		<?php usam_system_svg_icon("layer"); ?><?php _e('Выберете или добавьте слой', 'usam'); ?>
	</div>
	<div class="form_settings__block" v-else>
		<?php $this->display_icon(); ?>
		<div class="form_settings__sections_options" v-show="section[tab]=='content'">
			<div class="form_settings__sections_options_name"><?php _e('Содержимое', 'usam'); ?></div>
			<div class="options">							
				<div class="options_row" v-if="layer.type=='header' || layer.type=='button' || layer.type=='text' || layer.type=='product-addtocart'"><?php _e('Тест', 'usam'); ?></div>
				<div class="options_row" v-if="layer.type=='header' || layer.type=='button' || layer.type=='text'">
					<textarea v-model="layer.content" @input="layer[device].css.height='auto';layer[device].css.width='auto';"></textarea>	
				</div>
				<div class="options_row" v-if="layer.type=='product-day-addtocart' || layer.type=='product-addtocart'">
					<input type="text" v-model="layer.content" @input="layer[device].css.height='auto';layer[device].css.width='auto';"></textarea>	
				</div>
				<div class="options_row" v-if="layer.type.includes('product-day-')"><?php _e('Данные будут взяты из текущего товара дня', 'usam'); ?></div>				
				<div class="options_row" v-if="layer.type.includes('product-')"><?php _e('Товар', 'usam'); ?></div>
				<div class="options_row" v-if="layer.type.includes('product-')">
					<autocomplete @change="selectProduct" :selected="layer.product_name" :query="{status:['publish','draft'], add_fields:['price_currency','full_image','description']}" :request="'products'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
				</div>
			</div>
		</div>					
		<div class="form_settings__sections_options" v-show="section[tab]=='animation'">
			<div class="form_settings__sections_options_name">{{tabSettings.editor.icons[section[tab]]}}</div>
			<div class="options">
				<div class="options_row">
					<div class="options_name"><?php _e('Эффект', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer.animation_in=$event.id" :lists="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'transform', name:'<?php _e('Трансформация', 'usam'); ?>'},,{id:'right', name:'<?php _e('Слайд справа', 'usam'); ?>'},{id:'left', name:'<?php _e('Слайд слева', 'usam'); ?>'},{id:'top', name:'<?php _e('Слайд сверху', 'usam'); ?>'},{id:'bottom', name:'<?php _e('Слайд снизу', 'usam'); ?>'}]" :selected="layer.animation_in"></select-list>
					</div>
				</div>
			</div>
			<div class="form_settings__sections_options_name"><?php _e('Основы', 'usam'); ?></div>
			<div class="options">
				<div class="options_row">
					<div class="options_name"><?php _e('Продолжительность', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.duration" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Ожидание перед запуском', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.delay" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Скорость изменения', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer.easing=$event.id" :lists="[{id:'ease-in', name:'ease-in'},{id:'ease-out', name:'ease-out'},{id:'ease-in-out', name:'ease-in-out'},{id:'linear', name:'linear'},{id:'step-start', name:'step-start'},{id:'step-end', name:'step-end'}]" :selected="layer.easing"></select-list>
					</div>		
				</div>
			</div>					
			<div class="form_settings__sections_options_name"><?php _e('Расширенный', 'usam'); ?></div>
			<div class="options">
				<div class="options_row">
					<div class="options_name"><?php _e('Прозрачность', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.opacity" class="option_input">
					</div>
				</div>		
				<div class="options_row">
					<div class="options_name"><?php _e('Переместить по X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.x" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Переместить по Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.y" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Переместить по Z', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.z" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Масштабирование по X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.scalex" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Масштабирование по Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.scaley" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Скос по X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.skewx" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Скос по Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.skewy" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Вращение по X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.rotatex" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Вращение по Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.rotatey" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Вращение по Z', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.rotatez" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Координата X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.originx" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Координата Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.originy" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Координата Z', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.transform.originz" class="option_input">
					</div>
				</div>								
			</div>						
		</div>					
		<div class="form_settings__sections_options" v-show="section[tab]=='style'">
			<div v-if="layer.type!=='image'">	
				<div class="form_settings__sections_options_name"><?php _e('Стиль', 'usam'); ?></div>
				<div class="options" v-if="layer.type=='element'">						
					<div class="options_row">
						<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
						<div class="options_item">
							<color-picker @input="layer[device].css.color=$event" :value="layer[device].css.color"></color-picker>
						</div>		
					</div>
				</div>
				<div class="options" v-else>						
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Курсор', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css.cursor=$event.id" :lists="[{id:'default', name:'<?php _e('По умолчанию', 'usam'); ?>'}, {id:'inherit', name:'<?php _e('Наследуется', 'usam'); ?>'}, {id:'pointer', name:'pointer'}]" :selected="layer[device].css.cursor"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Шрифт', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['font-family']=$event.id" :lists="[{id:'inherit', name:'<?php _e('Наследуется', 'usam'); ?>'}]" :selected="layer[device].css['font-family']"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Размер текста', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer[device].css['font-size']" class="option_input">
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Толщина текста', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['font-weight']=$event.id" :lists="[{id:'400', name:'400'},{id:'500', name:'500'},{id:'600', name:'600'},{id:'700', name:'700'}]" :selected="layer[device].css['font-weight']"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Начертание', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['font-style']=$event.id" :lists="[{id:'normal', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'italic', name:'<?php _e('Курсивное', 'usam'); ?>'},{id:'oblique', name:'<?php _e('Наклонное', 'usam'); ?>'}]" :selected="layer[device].css['font-style']"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Оформление', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['text-decoration']=$event.id" :lists="[{id:'none', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'line-through', name:'<?php _e('Перечеркнутый', 'usam'); ?>'},{id:'blink', name:'<?php _e('Мигающий текст', 'usam'); ?>'},{id:'overline', name:'<?php _e('Линия над текстом', 'usam'); ?>'},{id:'underline', name:'<?php _e('Подчеркнутый', 'usam'); ?>'}]" :selected="layer[device].css['text-decoration']"></select-list>
						</div>		
					</div>
					<div class="options_row">
						<div class="options_name"><?php _e('Междустрочный интервал', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer[device].css['line-height']" class="option_input">
						</div>		
					</div>
					<div class="options_row">
						<div class="options_name"><?php _e('Межбуквенное расстояние', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer[device].css['letter-spacing']" class="option_input">
						</div>		
					</div>					
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Стиль текста', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['text-transform']=$event.id" :lists="[{id:'none', name:'None'},{id:'uppercase', name:'<?php _e('Верхний регистр', 'usam'); ?>'},{id:'lowercase', name:'<?php _e('Нижний регистр', 'usam'); ?>'},{id:'capitalize', name:'<?php _e('Первый символ заглавным', 'usam'); ?>'}]" :selected="layer[device].css['text-transform']"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Выравнивание', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['text-align']=$event.id" :lists="[{id:'left', name:'<?php _e('Влево', 'usam'); ?>'},{id:'center', name:'<?php _e('Центр', 'usam'); ?>'},{id:'right', name:'<?php _e('Вправо', 'usam'); ?>'}]" :selected="layer[device].css['text-align']"></select-list>
						</div>		
					</div>
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
						<div class="options_item">
							<color-picker @input="layer[device].css.color=$event" :value="layer[device].css.color"></color-picker>
						</div>		
					</div>					
					<div class="options_row">
						<div class="options_name"><?php _e('Скрывает или показывает элемент', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].css['visibility']=$event.id" :lists="[{id:'hidden', name:'<?php _e('Элемент не виден', 'usam'); ?>'},{id:'visible', name:'<?php _e('Значение по умолчанию', 'usam'); ?>'}]" :selected="layer[device].css['visibility']"></select-list>
						</div>		
					</div>			
				</div>						
			</div>			
			<div class="form_settings__sections_options_name" v-if="layer.type!='element'" ><?php _e('Рамка', 'usam'); ?><selector v-model="layer[device].css['border-width']" :items="[{id:'0', name:'<?php _e('Нет', 'usam'); ?>'},{id:'1px', name:'<?php _e('Да', 'usam'); ?>'}]"></selector></div>
			<div class="options" v-if="layer[device].css['border-width'] && layer.type!='element'">	
				<div class="options_row">
					<div class="options_name"><?php _e('Цвет рамки', 'usam'); ?></div>
					<div class="options_item">
						<color-picker @input="layer[device].css['border-color']=$event" :value="layer[device].css['border-color']"></color-picker>
					</div>		
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Стиль рамки', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer[device].css['border-style']=$event.id" :lists="[{id:'none', name:'None'},{id:'solid', name:'Solid'},{id:'dashed', name:'Dashed'},{id:'dotted', name:'Dotted'},{id:'double', name:'Double'}]" :selected="layer[device].css['border-style']"></select-list>
					</div>		
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Толщина', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].css['border-width']" class="option_input">					
					</div>		
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Радиус', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].css['border-radius']" class="option_input">					
					</div>		
				</div>							
			</div>				
			<div class="form_settings__sections_options_name" v-if="layer.type!='element'" ><?php _e('Фон', 'usam'); ?></div>
			<div class="options" v-if="layer.type!='element'">	
				<div class="options_row">
					<div class="options_name"><div class="view_image" :style="'background-image:'+layer[device].css['background-image']"></div></div>
					<div class="options_item">
						<wp-media inline-template @change="layer[device].css['background-image']='url('+$event.url+')'">
							<div class="option_button" @click="addMedia"><?php _e('Медиафайлы', 'usam'); ?></div>
						</wp-media>	
						<a v-if="layer[device].css['background-image']" @click="layer[device].css['background-image']=''"><?php _e('Удалить', 'usam'); ?></a>	
					</div>
				</div>				
				<div class="options_row">
					<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
					<div class="options_item">
						<color-picker @input="layer[device].css['background-color']=$event" :value="layer[device].css['background-color']"></color-picker>
					</div>		
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Повторение', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer[device].css['background-repeat']=$event.id" :lists="[{id:'no-repeat', name:'<?php _e('Не повторять', 'usam'); ?>'},{id:'repeat', name:'<?php _e('Повторять', 'usam'); ?>'},{id:'repeat-x', name:'<?php _e('Повторить по горизонтали', 'usam'); ?>'},{id:'repeat-y', name:'<?php _e('Повторить по вертикали', 'usam'); ?>'}]" :selected="layer[device].css['background-repeat']"></select-list>
					</div>		
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Масштабирование', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer[device].css['background-size']=$event.id" :lists="[{id:'cover', name:'<?php _e('Вписать в блок', 'usam'); ?>'},{id:'contain', name:'<?php _e('Вместить', 'usam'); ?>'},{id:'percent', name:'<?php _e('Процент', 'usam'); ?>'},{id:'auto', name:'<?php _e('Автоматически', 'usam'); ?>'}]" :selected="layer[device].css['background-size']"></select-list>
					</div>		
				</div>	
				<div class="options_row" v-if="layer[device].css['background-size']=='percent'">
					<div class="options_name"><?php _e('Масштаб', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].css.percent">
					</div>		
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Прокрутка', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer[device].css['background-attachment']=$event.id" :lists="[{id:'local', name:'<?php _e('По умолчанию', 'usam'); ?>'},{id:'scroll', name:'<?php _e('Скрол', 'usam'); ?>'},{id:'fixed', name:'<?php _e('Фиксирован', 'usam'); ?>'}]" :selected="layer[device].css['background-attachment']"></select-list>
					</div>		
				</div>				
				<div class="options_row">
					<div class="options_name"><?php _e('Позиция', 'usam'); ?></div>
					<div class="options_item">
						<select-position @change="layer[device].css['background-position']=$event" :selected="layer[device].css['background-position']"></select-position> <input type="text" v-model="layer[device].css['background-position']">
					</div>		
				</div>				
			</div>							
		</div>
		<div class="form_settings__sections_options" v-show="section[tab]=='hover'">
			<div v-if="layer.type!=='image'">
				<div class="form_settings__sections_options_name"><?php _e('Стиль', 'usam'); ?><selector v-model="layer.hover_active"></selector></div>
				<div class="options" v-if="layer.type=='element' && layer.hover_active">						
					<div class="options_row">
						<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
						<div class="options_item">
							<color-picker @input="layer[device].hover.color=$event" :value="layer[device].hover.color"></color-picker>
						</div>		
					</div>
				</div>
				<div class="options" v-else-if="layer.hover_active">		
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Оформление', 'usam'); ?></div>
						<div class="options_item">
							<select-list @change="layer[device].hover['text-decoration']=$event.id" :lists="[{id:'none', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'line-through', name:'<?php _e('Перечеркнутый', 'usam'); ?>'},{id:'blink', name:'<?php _e('Мигающий текст', 'usam'); ?>'},{id:'overline', name:'<?php _e('Линия над текстом', 'usam'); ?>'},{id:'underline', name:'<?php _e('Подчеркнутый', 'usam'); ?>'}]" :selected="layer[device].hover['text-decoration']"></select-list>
						</div>		
					</div>						
					<div class="options_row" v-if="layer.type!='group'">
						<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
						<div class="options_item">
							<color-picker @input="layer[device].hover.color=$event" :value="layer[device].hover.color"></color-picker>
						</div>		
					</div>						
				</div>
				<div class="options_row" v-show="layer.hover_active">
					<div class="options_name"><?php _e('Цвет рамки', 'usam'); ?></div>
					<div class="options_item">
						<color-picker @input="layer[device].hover['border-color']=$event" :value="layer[device].hover['border-color']"></color-picker>
					</div>		
				</div>		
			</div>				
			<div class="form_settings__sections_options_name" v-show="layer.type!='element' && layer.hover_active"><?php _e('Фон', 'usam'); ?></div>
			<div class="options" v-if="layer.type!='element' && layer.hover_active">	
				<div class="options_row">					
					<div class="options_name"><div class="view_image" :style="'background-image:'+layer[device].hover['background-image']"></div></div>
					<div class="options_item">
						<wp-media inline-template @change="layer[device].hover['background-image']='url('+$event.url+')'">
							<div class="option_button" @click="addMedia"><?php _e('Медиафайлы', 'usam'); ?></div>
						</wp-media>	
						<a v-if="layer[device].hover['background-image']" @click="layer[device].hover['background-image']=''"><?php _e('Удалить', 'usam'); ?></a>	
					</div>	
				</div>				
				<div class="options_row">
					<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
					<div class="options_item">
						<color-picker @input="layer[device].hover['background-color']=$event" :value="layer[device].hover['background-color']"></color-picker>
					</div>		
				</div>								
				<div class="options_row">
					<div class="options_name"><?php _e('Позиция', 'usam'); ?></div>
					<div class="options_item">
						<select-position @change="layer[device].hover['background-position']=$event" :selected="layer[device].hover['background-position']"></select-position> <input type="text" v-model="layer[device].hover['background-position']">
					</div>		
				</div>				
			</div>							
		</div>		
		<div class="form_settings__sections_options" v-if="section[tab]=='size'">		
			<div class="form_settings__sections_options_name"><?php _e('Размеры', 'usam'); ?></div>
			<div class="options">							
				<div class="options_row">
					<div class="options_name options_name_icon"><?php usam_system_svg_icon("lock", ["@click" => "layer.sizelock=!layer.sizelock", ':class' => '[layer.sizelock?`selected`:``]']); ?><?php _e('Размер', 'usam'); ?></div>
					<div class="options_item options_item_line options_item_size">
						<span class="designation">ш</span>
						<input type="text" v-model="layer[device].css.width" class="option_input">
						<span class="designation">в</span>
						<input type="text" v-model="layer[device].css.height" class="option_input">
					</div>		
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Внешние отступы', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].css.margin" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Внутренние отступы', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].css.padding" class="option_input">
					</div>
				</div>													
			</div>
			<div class="form_settings__sections_options_name"><?php _e('Расположение', 'usam'); ?></div>
			<div class="options">					
				<div class="options_row">
					<div class="options_name"></div>
					<div class="options_item">
						<select-position @change="positionLayer" :selected="layer[device].inset"></select-position>
					</div>		
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Позиция', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].inset" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Трансформация', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer[device].transform" class="option_input">
					</div>
				</div>								
			</div>	
		</div>
		<div class="form_settings__sections_options" v-if="section[tab]=='html'">		
			<div class="form_settings__sections_options_name"><?php _e('Атрибуты', 'usam'); ?></div>
			<div class="options">							
				<div class="options_row">
					<div class="options_name"><?php _e('Классы', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.classes" class="option_input">
					</div>		
				</div>									
			</div>											
		</div>		
		<div class="form_settings__sections_options" v-if="section[tab]=='actions'">
			<div class="form_settings__sections_options_name"><?php _e('Действия', 'usam'); ?></div>
			<div class="options">							
				<div class="options_row">
					<div class="options_name"><?php _e('Тип действия', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer.actions.type=$event.id" :lists="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'link', name:'<?php _e('Ссылка', 'usam'); ?>'},{id:'webform', name:'<?php _e('Веб-форма', 'usam'); ?>'},{id:'modal', name:'<?php _e('Модальное окно', 'usam'); ?>'}]" :selected="layer.actions.type"></select-list>
					</div>
				</div>
				<div class="options_row" v-if="layer.actions.type=='link'">
					<div class="options_name"><?php _e('Ссылка', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.actions.value" class="option_input">
					</div>		
				</div>	
				<div class="options_row" v-if="layer.actions.type=='modal' && data.type!='products'">
					<div class="options_name"><?php _e('Код модального окна', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.actions.value" class="option_input">
					</div>		
				</div>
				<div id="webforms" class="options_row" v-if="layer.actions.type=='webform'">
					<div class="options_name"><?php _e('Веб-форма', 'usam'); ?></div>
					<div class="options_item">
						<select-list @change="layer.actions.value=$event.id" :lists="webforms" :selected="layer.actions.value"></select-list>
					</div>		
				</div>	
			</div>				
		</div>				
		<div class="form_settings__sections_options" v-if="section[tab]=='shadow'">				
			<div class="form_settings__sections_options_name"><?php _e('Тень блока', 'usam'); ?><selector v-model="layer.boxShadow.active" :items="[{id:0, name:'<?php _e('Нет', 'usam'); ?>'},{id:1, name:'<?php _e('Да', 'usam'); ?>'}]"></selector></div>
			<div class="options" v-if="layer.boxShadow.active">
				<div class="options_row">
					<div class="options_name"><?php _e('Сдвиг по X', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.boxShadow.x" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Сдвиг по Y', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.boxShadow.y" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Радиус размытия', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.boxShadow.radius" class="option_input">
					</div>
				</div>	
				<div class="options_row">
					<div class="options_name"><?php _e('Растяжение', 'usam'); ?></div>
					<div class="options_item">
						<input type="text" v-model="layer.boxShadow.spread" class="option_input">
					</div>
				</div>
				<div class="options_row">
					<div class="options_name"><?php _e('Цвет', 'usam'); ?></div>
					<div class="options_item">
						<color-picker @input="layer.boxShadow.color=$event" :value="layer.boxShadow.color"></color-picker>
					</div>		
				</div>
			</div>
			<div v-if="layer.type!='image'">
				<div class="form_settings__sections_options_name"><?php _e('Тень текста', 'usam'); ?><selector v-model="layer.textShadow.active"></selector></div>
				<div class="options" v-if="layer.textShadow.active">
					<div class="options_row">
						<div class="options_name"><?php _e('Сдвиг по X', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer.textShadow.x" class="option_input">
						</div>
					</div>	
					<div class="options_row">
						<div class="options_name"><?php _e('Сдвиг по Y', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer.textShadow.y" class="option_input">
						</div>
					</div>
					<div class="options_row">
						<div class="options_name"><?php _e('Радиус размытия', 'usam'); ?></div>
						<div class="options_item">
							<input type="text" v-model="layer.textShadow.radius" class="option_input">
						</div>
					</div>
					<div class="options_row">
						<div class="options_name"><?php _e('Цвет', 'usam'); ?></div>
						<div class="options_item">
							<color-picker @input="layer.textShadow.color=$event" :value="layer.textShadow.color"></color-picker>
						</div>		
					</div>
				</div>
			</div>
		</div>
	</div>
</div>