<?php		
require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );	
require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_banner extends USAM_Edit_Form
{
	protected $vue = true;	
	protected $JSON = true;	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить баннер','usam');
		else
			$title = __('Добавить баннер', 'usam');	
		return $title;
	}
	
	protected function form_class( )
	{ 
		return 'constructor';
	}
	
	protected function get_data_tab()
	{	
		$default = ['id' => 0,  'name' => '', 'status' => 'active', 'actuation_time' => 0, 'object_id' => 0, 'object_url' => '', 'type' => 'image', 'sort' => 10, 'start_date' => '', 'end_date' => '', 'device' => '', 'locations' => [], 'settings' => ['devices' => ['computer' => 1, 'notebook' => 0, 'tablet' => 0, 'mobile' => 0], 'html' => '', 'video' => '', 'layouttype' => 'layout', 'size' => ['computer' => ['width' => '100%', 'height' => '300px'], 'notebook' => ['width' => '100%', 'height' => '300px'], 'tablet' => ['width' => '100%', 'height' => '300px'], 'mobile' => ['width' => '100%', 'height' => '200px']], 'layers' => [], 'background-color' => '', 'css' => ['background-position' => 'center center', 'background-repeat' => 'no-repeat', 'background-size' => 'contain', 'background-attachment' => 'scroll'], 'margin' => '0', 'overflow' => '', 'products' => '', 'actions' => ['type' => '', 'value' => ''], 'filter' => '', 'filter_opacity' => '', 'effect' => '', 'classes' => '', 'custom_css' => '', 'area_size' => ['width' => '500px', 'height' => '300px'], 'object_size' => ['width' => '', 'height' => ''], 'autoplay' => 1, 'muted' => 0, 'quality' => '', 'video_id' => '', 'video_mp4' => '', 'video_webm' => '']];							
		if ( $this->id !== null )
		{
			$this->data = usam_get_banner( $this->id );		
			if ( empty($this->data) )
				return false;					
			$this->data['locations'] = usam_get_banner_location( $this->id );
		}		
		if ( empty($this->data['type']) )
			$this->data['type'] = 'image';		
		$this->data = usam_format_data( $default, $this->data );	
		$this->data['settings']['devices'] = array_map('intval', (array)$this->data['settings']['devices']);
		$statuses = [];
		foreach ( usam_get_banner_statuses() as $key => $name ) 
			$statuses[] = ['id' => $key, 'name' => $name];
		$this->js_args = [
			'tabSettings' => [
				'settings' => ['icons' => ['content' => __('Название', 'usam'), 'layout' => __('Макет', 'usam')]],
				'slide' => ['icons' => ['background' => __('Фон', 'usam'), 'animation' => __('Анимация', 'usam'), 'filter' => __('Фильтр', 'usam'), 'time' => __('Публикация', 'usam'), 'actions' => __('Действия', 'usam'), 'html' => __('Атрибуты', 'usam')]], 
				'display' => ['icons' => ['place' => __('Расположение', 'usam'), 'question' => __('Условия', 'usam')]],
				'editor' => ['icons' => ['content' => __('Редактор', 'usam'), 'style' => __('Стиль', 'usam'), 'hover' => __('При наведении', 'usam'), 'size' => __('Размеры', 'usam'), 'shadow' => __('Тени', 'usam'), 'animation' => __('Анимация', 'usam'), 'actions' => __('Действия', 'usam'), 'html' => __('Атрибуты', 'usam')]]
			], 
			'statuses' => $statuses,
			'register' => usam_register_banners(),
			'types' => ['image' => __('Картинка', 'usam'), 'externalimage' => __('Внешние изображение', 'usam'), 'colored' => __('Цветной', 'usam'), 'product_day_image' => __('Миниатюра товара дня', 'usam'), 'html' => __('HTML код или текст', 'usam'), 'video' => __('Видео', 'usam'), 'vimeo' => 'Vimeo', 'youtube' => 'Youtube', 'products' => __('Товары на картинке', 'usam'), 'shops' => __('Забрать в магазине', 'usam')],
			'devicesLists' => ['computer' => __('Компьютер', 'usam'), 'notebook' => __('Ноутбук', 'usam'), 'tablet' => __('Планшет', 'usam'), 'mobile' => __('Мобильный', 'usam')],
		];
	}	
		
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();	
		wp_enqueue_script( 'v-color' );
		wp_enqueue_style( 'usam-admin-silder' );			
		wp_enqueue_style( 'usam-silder-filter' );
	}
	
	protected function display_toolbar()
    {
		?>
		<div class="tab_title form_toolbar">			
			<span class="form_title_go_back">
				<a href="<?php echo $this->get_url_go_back(); ?>" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
				<span class="form_title edit_name_form">
					<span class='edit_name_form__dispaly' @click='openEditName' v-show='!editName'>{{data.name}}</span>
					<input ref="formname" type='text' v-model='data.name' v-show="editName">				
				</span>
			</span>
			<div class="form_toolbar_tools" v-if="data.type!='html' && data.type!='shops'">
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-editor-menu.php' ); ?>
			</div>			
			<?php $this->display_navigation(); ?>
			<div class="action_buttons">
				<?php $this->toolbar_buttons();	?>			
			</div>			
		</div>
		<?php		
	}
	
	protected function toolbar_buttons( ) 
	{						
		$this->display_toolbar_buttons();
		$this->main_actions_button();
	}
	
	public function display_form( ) 
	{		
		?>
		<div class ="description_tinymce type_editor" v-show="data.type=='html' || data.type=='shops'">		
			<?php
			wp_editor( $this->data['settings']['html'], "description_tinymce", [
					'textarea_name' => 'html',
					'media_buttons'=> false,
					'textarea_rows' => 20,	
					'wpautop' => 0,								
					'tinymce' => ['theme_advanced_buttons3' => 'invoicefields,checkoutformfields']
				]
			); 
			?>
		</div>
		<div class ="type_editor" v-show="data.type=='shops'">		
			<?php
			$storages = usam_get_storages(['fields' => 'count', 'issuing' => 1, 'number' => 1]);		
			if ( $storages )
				$content = "<p>".sprintf(_n('Забрать в %s магазине', 'Забрать в %s магазинах',$storages,'usam'),$storages)."</p>{{data.settings.html}}";
			else
				$content = "<p>".__("Временно отсутствует в магазинах", "usam")."</p>";
			echo $content;
			?>
		</div>
		<div v-show="data.type=='video' || data.type=='externalimage' || data.type=='image' || data.type=='colored' || data.type=='product_day_image' || data.type=='products' || data.type=='vimeo' || data.type=='youtube'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slider_editor.php' ); ?>
		</div>
		<div class="control_panel" :class="{'show':panel}" v-show="(data.type=='externalimage' || data.type=='colored' || data.type=='product_day_image' || data.type=='image') && data.settings.layers.length || data.type=='products'">
			<div class="control_panel_header">
				<div class="editor_tool_tabs" @click="panel=true">
					<a class="editor_tool_tab" @click="toolTabs='products'" v-if="data.type=='products'" :class="{'active':'products'===toolTabs}"><?php usam_system_svg_icon("slides"); ?><?php _e('Товары', 'usam'); ?></a>					
					<a class="editor_tool_tab" @click="toolTabs='layers'" :class="{'active':'layers'===toolTabs}" v-if="data.settings.layers.length"><?php usam_system_svg_icon("layer"); ?><?php _e('Слои', 'usam'); ?></a>
				</div>	
				<canvas id="time_linear_canvas" width="16380" height="35" :class="{'visibility':toolTabs=='layers'}"></canvas>
				<div id="hovertime" style="transform: translate(0px, 0px); display: none; left: 880px;">
					<div class="timebox"><span class="ctm">00</span>:<span class="cts">08</span>:<span class="ctms">80</span></div><div class="timebox_marker"></div>
				</div>
				<?php usam_system_svg_icon("close", ["v-if" => "panel", "@click" => "panel=false"]); ?>	
			</div>		
			<div class ="description_tinymce" v-if="data.type=='products' && toolTabs=='products'">		
			<?php
				$columns = [
					'n'         => __('№', 'usam'),
					'title'     => __('Товары', 'usam'),
					'inset'     => __('Отступы', 'usam'),
					'delete'    => '',
				];				
				$this->table_products_add_button($columns, 'banner');
			?>
			</div>
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/layers-editor.php' ); ?>
		</div>			
		<div class="form_settings_sidebar form_settings_scroll">
			<div class="form_settings_sidebar__tabs">
				<a @click="tab='settings'" :class="{'active':tab=='settings'}"><?php _e('Настройки', 'usam'); ?></a>
				<a @click="tab='slide'" :class="{'active':tab=='slide'}"><?php _e('Слайд', 'usam'); ?></a>
				<a @click="tab='display'" :class="{'active':tab=='display'}"><?php _e('Отображение', 'usam'); ?></a>
				<a @click="tab='editor'" :class="{'active':tab=='editor'}"><?php _e('Редактор', 'usam'); ?></a>
			</div>
			<div class="form_settings" v-if="tab=='settings'">
				<div class="form_settings__name"><?php _e('Настройки', 'usam'); ?></div>
				<?php $this->display_icon(); ?>				
				<div class="form_settings__sections_options" v-if="section[tab]=='content'">
					<div class="form_settings__sections_options_name"><?php _e('Описание баннера', 'usam'); ?></div>
					<div class="options">
						<div class="options_row"><?php _e('Название', 'usam'); ?></div>
						<div class="options_row">
							<input type="text" v-model="data.name" class="width100">
						</div>
						<div class="options_row" v-if="data.type !== 'html'"><?php _e('Описание', 'usam'); ?></div>
						<div class="options_row" v-if="data.type !== 'html'">
							<textarea v-model="data.settings.html"></textarea>	
						</div>
					</div>							
				</div>							
				<div class="form_settings__sections_options" v-if="section[tab]=='layout'">						
					<div class="form_settings__sections_options_name"><?php _e('Размер макета', 'usam'); ?></div>
					<div class="options">							
						<div class="options_row">
							<div class="options_name"><?php _e('Показать', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="data.settings.layouttype=$event.id" :lists="[{id:'fullscreenwidth', name:'<?php _e('Во весь экран по ширине', 'usam'); ?>'},{id:'fullscreenheight', name:'<?php _e('Во весь экран по высоте', 'usam'); ?>'},{id:'fullscreen', name:'<?php _e('Во весь экран', 'usam'); ?>'},{id:'image', name:'<?php _e('По фото', 'usam'); ?>'},{id:'layout', name:'<?php _e('По макету', 'usam'); ?>'},{id:'css', name:'<?php _e('Из файла css', 'usam'); ?>'}]" :selected="data.settings.layouttype"></select-list>
							</div>		
						</div>
						<div class="options_row" v-if="data.settings.layouttype!='css'">
							<div class="options_name"><?php _e('Размер', 'usam'); ?></div>
							<div class="options_item options_item_line options_item_size">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenwidth'">ш</span>
								<input type="text" v-model="data.settings.size.computer.width" class="option_input" v-if="data.settings.layouttype!='fullscreenwidth'">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenheight'">в</span>
								<input type="text" v-model="data.settings.size.computer.height" class="option_input" v-if="data.settings.layouttype!='fullscreenheight'">
							</div>	
						</div>
						<div class="options_row" v-if="data.settings.layouttype!='fullscreen' && data.settings.layouttype!='css'">
							<div class="options_name"><?php _e('Размер 1023 - 778', 'usam'); ?></div>
							<div class="options_item options_item_line options_item_size">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenwidth'">ш</span>
								<input type="text" v-model="data.settings.size.notebook.width" class="option_input" v-if="data.settings.layouttype!='fullscreenwidth'">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenheight'">в</span>
								<input type="text" v-model="data.settings.size.notebook.height" v-if="data.settings.layouttype!='fullscreenheight'" class="option_input">
							</div>		
						</div>
						<div class="options_row" v-if="data.settings.layouttype!='fullscreen' && data.settings.layouttype!='css'">
							<div class="options_name"><?php _e('Размер 777 - 480', 'usam'); ?></div>
							<div class="options_item options_item_line options_item_size">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenwidth'">ш</span>
								<input type="text" v-model="data.settings.size.tablet.width" class="option_input" v-if="data.settings.layouttype!='fullscreenwidth'">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenheight'">в</span>
								<input type="text" v-model="data.settings.size.tablet.height" class="option_input" v-if="data.settings.layouttype!='fullscreenheight'">
							</div>	
						</div>		
						<div class="options_row" v-if="data.settings.layouttype!='fullscreen' && data.settings.layouttype!='css'">
							<div class="options_name"><?php _e('Размер < 480', 'usam'); ?></div>
							<div class="options_item options_item_line options_item_size">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenwidth'">ш</span>
								<input type="text" v-model="data.settings.size.mobile.width" class="option_input" v-if="data.settings.layouttype!='fullscreenwidth'">
								<span class="designation" v-if="data.settings.layouttype!='fullscreenheight'">в</span>
								<input type="text" v-model="data.settings.size.mobile.height" class="option_input" v-if="data.settings.layouttype!='fullscreenheight'">
							</div>	
						</div>						
					</div>
					<div class="form_settings__sections_options_name"><?php _e('Размер рабочей области', 'usam'); ?></div>
					<div class="options">							
						<div class="options_row">
							<div class="options_item options_item_line options_item_size">
								<span class="designation">ш</span>
								<input type="text" v-model="data.settings.area_size.width" class="option_input">
								<span class="designation">в</span>
								<input type="text" v-model="data.settings.area_size.height" class="option_input">
							</div>		
						</div>									
					</div>		
					<div class="form_settings__sections_options_name"><?php _e('Контейнер', 'usam'); ?></div>
					<div class="options">							
						<div class="options_row">
							<div class="options_name"><?php _e('Внешние отступы', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.margin" class="option_input">
							</div>		
						</div>								
						<div class="options_row">
							<div class="options_name"><?php _e('Внутренние отступы', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.padding" class="option_input">
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Позиционирование', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings['z-index']" class="option_input">
							</div>		
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Углы', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings['border-radius']" class="option_input">
							</div>		
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Скрыть то что выходит за границы', 'usam'); ?></div>
							<div class="options_item">
								<selector v-model="data.settings.overflow" :items="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'hidden', name:'<?php _e('Да', 'usam'); ?>'}]"></selector>
							</div>
						</div>	
					</div>						
				</div>					
			</div>			
			<div class="form_settings" v-if="tab=='slide'">
				<div class="form_settings__name"><?php _e('Слайд', 'usam'); ?></div>
				<?php $this->display_icon(); ?>				
				<div class="form_settings__sections_options" v-if="section[tab]=='background'">
					<div class="form_settings__sections_options_name"><?php _e('Фон', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Тип баннера', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="data.type=$event.id" :lists="types" :selected="data.type"></select-list>
							</div>		
						</div>
						<div class="options_row" v-show="slide.type=='vimeo' || slide.type=='youtube'">
							<div class="options_name"><?php _e('ID видео', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="slide.settings.video_id">
							</div>
						</div>		
						<div class="options_row" v-show="slide.type=='video'">
							<div class="options_name"><?php _e('Ссылка в mp4', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="slide.settings.video_mp4">
							</div>		
						</div>		
						<div class="options_row" v-show="slide.type=='video'">
							<div class="options_name"><?php _e('Ссылка в webm', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="slide.settings.video_webm">
							</div>		
						</div>
						<div class="options_row" v-show="data.type=='image' || data.type=='products'">
							<div class="options_name"></div>
							<div class="options_item">
								<wp-media inline-template @change="addMedia">
									<div class="option_button" @click="addMedia"><?php _e('Медиафайлы', 'usam'); ?></div>
								</wp-media>	
								<a v-if="slide.object_url" @click="deleteMedia"><?php _e('Удалить', 'usam'); ?></a>	
							</div>
						</div>													
						<div class="options_row" v-if="data.type=='externalimage'">
							<div class="options_name"><?php _e('Ссылка на изображение', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.object_url">
							</div>		
						</div>
						<div class="options_row" v-show="data.type!='trans'">
							<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings[`background-color`]=$event" :value="data.settings[`background-color`]"></color-picker>
							</div>		
						</div>												
					</div>					
					<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-video-editor.php' ); ?>		
					<div v-if="data.object_url || data.type=='product_day_image'">	
						<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-image-editor.php' ); ?>
					</div>								
				</div>
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-tools.php' ); ?>	
				<div class="form_settings__sections_options" v-if="section[tab]=='time'">				
					<div class="form_settings__sections_options_name"><?php _e('Публикация', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Опубликовать', 'usam'); ?></div>
							<div class="options_item">
								<selector v-model="data.status" :items="statuses"/>
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Показать', 'usam'); ?></div>
							<div class="options_item options_item_line">
								<v-date-picker v-model="data.start_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
									<template v-slot="{ inputValue, inputEvents }"><input type="text" class="date_picker" :value="inputValue" v-on="inputEvents"/></template>
								</v-date-picker>
								<span> - </span>
								<v-date-picker v-model="data.end_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
									<template v-slot="{ inputValue, inputEvents }"><input type="text" class="date_picker" :value="inputValue" v-on="inputEvents"/></template>
								</v-date-picker>
							</div>		
						</div>							
					</div>						
				</div>					
			</div>
			<div class="form_settings" v-else-if="tab=='display'">
				<div class="form_settings__name"><?php _e('Отображение', 'usam'); ?></div>
				<?php $this->display_icon(); ?>
				<div class="form_settings__sections_options" v-if="section[tab]=='place'">
					<div class="form_settings__sections_options_name"><?php _e('Расположение в шаблоне', 'usam'); ?> ({{data.locations.length}})</div>
					<div class="options">
						<div class="options_row">
							<check-list :lists='register' :selected='data.locations' @change="data.locations=$event"/>	
						</div>		
					</div>					
				</div>				
				<div class="form_settings__sections_options" v-if="section[tab]=='question'">					
					<div class="form_settings__sections_options_name"><?php _e('Условия отображения', 'usam'); ?></div>
					<div class="options">							
						<div class="options_row">
							<div class="options_name"><?php _e('Отображать на', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="data.device=$event.id" :lists="[{id:'', name:'<?php _e('Всех устройствах', 'usam'); ?>'},{id:'mobile', name:'<?php _e('Мобильных', 'usam'); ?>'},{id:'desktop', name:'<?php _e('Компьютерах', 'usam'); ?>'}]" :selected="data.device"></select-list>
							</div>		
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Сортировка', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.sort">
							</div>		
						</div>		
					</div>
					<div class="form_settings__sections_options_name" v-if="data.type=='image'"><?php _e('Авто показ', 'usam'); ?><selector v-model="data.actuation_time"/></div>
					<div class="options" v-if="data.type=='image' && data.actuation_time">						
						<div class="options_row">
							<?php esc_html_e('Через сколько секунд показывать?', 'usam'); ?>
						</div>
						<div class="options_row">
							<div class="options_item">
								<input type="text" v-model="data.actuation_time">
							</div>		
						</div>
					</div>
				</div>												
			</div>						
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/layer-editor.php' ); ?>
		</div>
		<?php
		require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-elements.php' );	
	}
}
?>