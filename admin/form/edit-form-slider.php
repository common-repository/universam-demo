<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Slider extends USAM_Edit_Form
{
	protected $vue = true;
	protected $JSON = true;
	public function get_data_tab()
	{ 		
		$default = ['id' => 0, 'name' => __('Новый слайдер', 'usam'), 'slides' => [], 'active' => 1, 'device' => '', 'type' => 'custom', 'template' => 'basic', 'settings' => ['devices' => ['computer' => 1, 'notebook' => 0, 'tablet' => 0, 'mobile' => 0], 'layouttype' => 'layout', 'size' => ['computer' => ['width' => '100%', 'height' => '500px'], 'notebook' => ['width' => '100%', 'height' => '400px'], 'tablet' => ['width' => '100%', 'height' => '300px'], 'mobile' => ['width' => '100%', 'height' => '200px']], 'show' => '', 'condition' => ['roles' => [], 'sales_area' => []], 'autoplay' => 1, 'autospeed' => 6000, 'loop' => false, 'button' => ['design' => '', 'position' => 'bottom center', 'orientation' => 'row', 'css' => ['width' => '10px', 'height' => '10px', 'border-radius' => '5px', 'margin' => '0 5px 5px 0', 'background-color' => '#ffffff', 'border-color' => '#ffffff', 'border-width' => '1px', 'border-style' => 'double', 'color' => '#AAAAB6', 'active-color' => '#AAAAB6', 'color-number' => '#AAAAB6', 'active-color-number' => '#AAAAB6'], 'show' => 1], 'margin' => '', 'padding' => '', 'z-index' => '', 'border-radius' => '', 'overflow' => '', 'area_size' => ['width' => '100%', 'height' => '500px'], 'classes' => '', 'custom_css' => '']];
		if ( $this->id )
		{
			$_slider = new USAM_Slider( $this->id );
			$this->data = $_slider->get_data();	
			if ( empty($this->data) )
				return false;
		}		
		$this->data = usam_format_data( $default, $this->data );	
		if ( $this->id )	
		{
			$this->data['slides'] = $_slider->get_slides();
			foreach ( $this->data['slides'] as &$slide )
			{ 
				if ( $slide->object_url == '' && $slide->object_id )
					$slide->object_url = wp_get_attachment_image_url( $slide->object_id, 'full' );
				$slide->start_date = $slide->start_date ? get_date_from_gmt( $slide->start_date, "Y-m-d" ):'';
				$slide->end_date = $slide->end_date ? get_date_from_gmt( $slide->end_date, "Y-m-d" ):'';
			}
			usort($this->data['slides'], function($a, $b){  return ($a->sort - $b->sort); });			
		}		
		$blocks = usam_register_sliders();
		$blocks[''] = __( 'Вручную', 'usam');		
        $roles = [['id' => 'notloggedin', 'name' => __('Не вошел в систему','usam')]];
		foreach (get_editable_roles() as $role => $info) 
            $roles[] = ['id' => $role, 'name' => translate_user_role( $info['name'] )];
		$this->js_args = [
			'tabSettings' => [
				'settings' => ['icons' => ['layout' => __('Макет', 'usam'), 'question' => __('Условия', 'usam')]], //, 'template' => __('Шаблон', 'usam')				
				'slide' => ['icons' => ['background' => __('Фон', 'usam'), 'animation' => __('Анимация', 'usam'), 'filter' => __('Фильтр', 'usam'), 'time' => __('Публикация', 'usam'), 'actions' => __('Действия', 'usam'), 'html' => __('Атрибуты', 'usam'), 'content' => __('Название', 'usam')]], 
				'navigation' => ['icons' => ['points' => __('Кнопки', 'usam')]],				
				'editor' => ['icons' => ['content' => __('Редактор', 'usam'), 'style' => __('Стиль', 'usam'), 'hover' => __('При наведении', 'usam'), 'size' => __('Размеры', 'usam'), 'shadow' => __('Тени', 'usam'), 'animation' => __('Анимация', 'usam'), 'actions' => __('Действия', 'usam'), 'html' => __('Атрибуты', 'usam')]]
			], 
			'elements' => ['triangle', 'play', 'play2', 'element'],	
			'devicesLists' => ['computer' => __('Компьютер', 'usam'), 'notebook' => __('Ноутбук', 'usam'), 'tablet' => __('Планшет', 'usam'), 'mobile' => __('Мобильный', 'usam')],
			'register_slider' => $blocks,
			'roles' => $roles,
		];		
	}		
		
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) { 
		return 'fullscreen_form constructor';
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
			<div class="form_toolbar_menus">
				<div class="form_toolbar_menu form_menu">
					<div class="form_menu_name"><?php usam_system_svg_icon("slides"); ?><?php _e('Слайд', 'usam');?></div>
					<div class="form_submenu">
						<div class="form_submenu_wrap">
							<div class="add_layer form_submenu_name" @click="addSlide"><?php _e('Добавить слайд', 'usam');?></div>						
						</div>						
					</div>
				</div>							
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
		require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slider_editor.php' );
		?>		
		<div class="control_panel" :class="{'show':panel}" ref="controlpanel">
			<div class="control_panel_header">
				<div class="editor_tool_tabs" @click="panel=true">
					<a class="editor_tool_tab" @click="toolTabs='slides'" :class="{'active':'slides'===toolTabs}"><?php usam_system_svg_icon("slides"); ?><?php _e('Слайды', 'usam'); ?></a>
					<a class="editor_tool_tab" @click="toolTabs='layers'" :class="{'active':'layers'===toolTabs}" v-if="slide.settings.layers.length"><?php usam_system_svg_icon("layer"); ?><?php _e('Слои', 'usam'); ?></a>
				</div>	
				<canvas id="time_linear_canvas" width="16380" height="35" :class="{'visibility':toolTabs=='layers'}"></canvas>
				<?php usam_system_svg_icon("close", ["v-if" => "panel", "@click" => "panel=false"]); ?>	
			</div>
			<sort-block v-if="toolTabs=='slides'" @change="sortSlides" :classes="'slide_selection'">
				<template v-slot:body="slotProps">
					<div class="slide_button" v-for="(slide, k) in data.slides" :class="{'active':slideActive==k}" @click="slideActive=k" draggable="true" @drop="slotProps.drop($event, k)" @dragover="slotProps.allowDrop($event, k)" @dragstart="slotProps.drag($event, k)" @dragend="slotProps.dragEnd($event, k)" @contextmenu="openMenuSlide($event, k)">
						<img :src="slide.object_url" alt="" v-if="slide.object_url">
						<?php usam_system_svg_icon("minus", ['@click' => 'delSlides(k)', "v-if" => "data.slides.length>1"]); ?>
					</div>
				</template>
			</sort-block>
			<div ref="menu-slide" class="menu_content menu_content_left">			
				<div class="menu_items">	
					<div class="menu_items__item" @click="duplicateSlide($event, menuSlide)"><?php _e('Дублировать','usam'); ?></div>						
					<div class="menu_items__item" @click="delSlides(menuSlide)"><?php _e('Удалить','usam'); ?></div>
				</div>			
			</div>
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/layers-editor.php' ); ?>
		</div>		
		<div class="form_settings_sidebar form_settings_scroll">
			<div class="form_settings_sidebar__tabs">
				<a @click="tab='settings'" :class="{'active':tab=='settings'}"><?php _e('Настройки', 'usam'); ?></a>
				<a @click="tab='slide'" :class="{'active':tab=='slide'}"><?php _e('Слайд', 'usam'); ?></a>
				<a @click="tab='navigation'" :class="{'active':tab=='navigation'}"><?php _e('Навигация', 'usam'); ?></a>
				<a @click="tab='editor'" :class="{'active':tab=='editor'}"><?php _e('Редактор', 'usam'); ?></a>
			</div>
			<div class="form_settings" v-if="tab=='settings'">
				<div class="form_settings__name"><?php _e('Настройки', 'usam'); ?></div>
				<?php $this->display_icon(); ?>
				<div class="form_settings__sections_options" v-if="section[tab]=='layout'">
					<div class="form_settings__sections_options_name"><?php _e('Размер', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Показать', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="data.settings.layouttype=$event.id" :lists="[{id:'fullscreenwidth', name:'<?php _e('Во весь экран по ширине', 'usam'); ?>'},{id:'fullscreenheight', name:'<?php _e('Во весь экран по высоте', 'usam'); ?>'},{id:'fullscreen', name:'<?php _e('Во весь экран', 'usam'); ?>'},{id:'image', name:'<?php _e('По фото', 'usam'); ?>'},{id:'layout', name:'<?php _e('По макету', 'usam'); ?>'},{id:'css', name:'<?php _e('Из файла css', 'usam'); ?>'}]" :selected="data.settings.layouttype"></select-list>
							</div>		
						</div>		
						<div class="options_row" v-if="data.settings.layouttype!='fullscreen' && data.settings.layouttype!='css'">
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
					<div class="form_settings__sections_options_name"><?php _e('Публикация', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Опубликовать', 'usam'); ?></div>
							<div class="options_item">
								<input type="checkbox" v-model="active" class="option_input">
							</div>		
						</div>					
					</div>	
					<div class="form_settings__sections_options_name"><?php _e('Переключение', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Автопереключение', 'usam'); ?></div>
							<div class="options_item">
								<input type="checkbox" v-model="data.settings.autoplay" class="option_input">
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Задержка', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.autospeed" class="option_input">
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('По кругу', 'usam'); ?></div>
							<div class="options_item">
								<input type="checkbox" v-model="data.settings.loop" class="option_input">
							</div>		
						</div>						
					</div>						
				</div>			
				<div class="form_settings__sections_options" v-if="section[tab]=='question'">
					<div class="form_settings__sections_options_name"><?php _e('Отражение', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<select-list @change="data.settings.show=$event.id" :lists="register_slider" :selected="data.settings.show"></select-list>	
						</div>										
					</div>					
					<div class="form_settings__sections_options_name"><?php _e('Условия отображения', 'usam'); ?></div>
					<div class="options">
						<div class="options_row"><?php _e('Устройства', 'usam'); ?></div>	
						<div class="options_row">
							<select-list @change="data.device=$event.id" :lists="[{id:'', name:'<?php _e('Всех устройствах', 'usam'); ?>'},{id:'mobile', name:'<?php _e('На мобильных', 'usam'); ?>'},{id:'desktop', name:'<?php _e('На компьютерах', 'usam'); ?>'}]" :selected="data.device"></select-list>
						</div>
						<div class="options_row"><?php _e('Регионы', 'usam'); ?></div>	
						<div class="options_row">
							<select-list @change="data.settings.condition.sales_area=$event.id" :lists="regions" :multiple="1" :selected="data.settings.condition.sales_area"></select-list>
						</div>						
						<div class="options_row"><?php _e('Роли', 'usam'); ?></div>
						<div class="options_row"><select-list @change="data.settings.condition.roles=$event.id" :lists="roles" :multiple="1" :selected="data.settings.condition.roles"></select-list></div>	
					</div>
				</div>				
			</div>
			<div class="form_settings" v-else-if="tab=='navigation'">
				<div class="form_settings__name"><?php _e('Навигация', 'usam'); ?></div>
				<?php $this->display_icon(); ?>
				<div class="form_settings__sections_options" v-if="section[tab]=='points'">
					<div class="form_settings__sections_options_name"><?php _e('Включить', 'usam'); ?><selector v-model="data.settings.button.show"></selector></div>
					<div class="options">						
						<div class="options_row">
							<div class="options_name"><?php _e('Внешний вид', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="data.settings.button.design=$event.id" :lists="[{id:'', name:'<?php _e('Точки', 'usam'); ?>'},{id:'indicator', name:'<?php _e('Индикатор', 'usam'); ?>'},{id:'description', name:'<?php _e('Описание', 'usam'); ?>'}]" :selected="data.settings.button.design"></select-list>
							</div>
						</div>
						<div class="options_row" v-if="data.settings.button.design==''">
							<div class="options_name"><?php _e('Выравнивание', 'usam'); ?></div>
							<div class="options_item">
								<select-position @change="data.settings.button.position=$event" :selected="data.settings.button.position"></select-position>
							</div>		
						</div>	
						<div class="options_row" v-if="data.settings.button.design==''">
							<div class="options_name"><?php _e('Ориентация', 'usam'); ?></div>
							<div class="options_item options_item_radios">
								<label :class="{'active':data.settings.button.orientation=='row'}"><input type="radio" v-model="data.settings.button.orientation" value="row" class="option_input"><?php _e('Горизонтальное', 'usam'); ?></label>
								<label :class="{'active':data.settings.button.orientation=='column'}"><input type="radio" v-model="data.settings.button.orientation" value="column" class="option_input"><?php _e('Вертикальное', 'usam'); ?></label>
							</div>
						</div>							
					</div>						
					<div class="form_settings__sections_options_name" v-if="data.settings.button.design=='indicator'"><?php _e('Стиль', 'usam'); ?></div>
					<div class="options" v-if="data.settings.button.design=='indicator'">							
						<div class="options_row">
							<div class="options_name"><?php _e('Отступы', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css.margin" class="option_input">
							</div>
						</div>		
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет номера', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['color-number']=$event" :value="data.settings.button.css['color-number']"></color-picker>
							</div>
						</div>							
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['color']=$event" :value="data.settings.button.css['color']"></color-picker>
							</div>
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет текста, когда активно', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['active-color']=$event" :value="data.settings.button.css['active-color']"></color-picker>
							</div>
						</div>						
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет индикатора', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['border-color']=$event" :value="data.settings.button.css['border-color']"></color-picker>
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет, когда активна кнопка', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['active-color-number']=$event" :value="data.settings.button.css['active-color-number']"></color-picker>
							</div>
						</div>	
					</div>	
					<div class="form_settings__sections_options_name" v-if="data.settings.button.design==''"><?php _e('Стиль', 'usam'); ?></div>
					<div class="options" v-if="data.settings.button.design==''">	
						<div class="options_row">
							<div class="options_name"><?php _e('Ширина', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css.width" class="option_input">
							</div>
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Высота', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css.height" class="option_input">
							</div>
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Отступы', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css.margin" class="option_input">
							</div>
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Радиус', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css['border-radius']" class="option_input">
							</div>
						</div>	
						<div class="options_row">
							<div class="options_name"><?php _e('Толщина рамки', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="data.settings.button.css['border-width']" class="option_input">
							</div>
						</div>	
						<div class="options_row">							
							<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['background-color']=$event" :value="data.settings.button.css['background-color']"></color-picker>
							</div>		
						</div>
						<div class="options_row">
							<div class="options_name"><?php _e('Цвет рамки', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="data.settings.button.css['border-color']=$event" :value="data.settings.button.css['border-color']"></color-picker>
							</div>		
						</div>						
					</div>						
				</div>
			</div>	
			<div class="form_settings" v-else-if="tab=='slide'">
				<div class="form_settings__name"><?php _e('Слайд', 'usam'); ?></div>
				<?php $this->display_icon(); ?>
				<div class="form_settings__sections_options" v-if="section[tab]=='background'">
					<div class="form_settings__sections_options_name"><?php _e('Источник', 'usam'); ?></div>
					<div class="options">
						<div class="options_row">
							<div class="options_name"><?php _e('Тип', 'usam'); ?></div>
							<div class="options_item">
								<select-list @change="slide.type=$event.id; slide.object_url=''; slide.object_id=0;" :lists="[{id:'trans', name:'<?php _e('Прозрачный', 'usam'); ?>'},{id:'image', name:'<?php _e('Изображение', 'usam'); ?>'},{id:'externalimage', name:'<?php _e('Внешнее изображение', 'usam'); ?>'},{id:'colored', name:'<?php _e('Цветной', 'usam'); ?>'},{id:'video', name:'<?php _e('Видео', 'usam'); ?>'},{id:'vimeo', name:'<?php _e('Vimeo', 'usam'); ?>'},{id:'youtube', name:'<?php _e('Youtube', 'usam'); ?>'}]" :selected="slide.type"></select-list>
							</div>
						</div>						
						<div class="options_row" v-show="slide.type=='image'">
							<div class="options_name"></div>
							<div class="options_item">
								<wp-media inline-template @change="addMedia">
									<div class="option_button" @click="addMedia"><?php _e('Медиафайлы', 'usam'); ?></div>
								</wp-media>	
								<a v-if="slide.object_url" @click="deleteMedia"><?php _e('Удалить', 'usam'); ?></a>	
							</div>
						</div>					
						<div class="options_row" v-if="slide.type=='externalimage'">
							<div class="options_name"><?php _e('Ссылка на изображение', 'usam'); ?></div>
							<div class="options_item">
								<input type="text" v-model="slide.object_url">
							</div>		
						</div>
						<div class="options_row" v-show="slide.type!='trans'">
							<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
							<div class="options_item">
								<color-picker @input="slide.settings['background-color']=$event" :value="slide.settings['background-color']"></color-picker>
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
					</div>
					<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-video-editor.php' ); ?>
					<div v-if="slide.object_url || data.type=='product_day_image'">
						<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-image-editor.php' ); ?>
					</div>					
				</div>					
				<div class="form_settings__sections_options" v-if="section[tab]=='time'">
					<div class="form_settings__sections_options_name">{{tabSettings.slide.icons[section[tab]]}}</div>
					<div class="options">						
						<div class="options_row">
							<div class="options_name"><?php _e('Показать', 'usam'); ?></div>
							<div class="options_item options_item_line">
								<v-date-picker v-model="slide.start_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
									<template v-slot="{ inputValue, inputEvents }"><input type="text" class="date_picker" :value="inputValue" v-on="inputEvents"/></template>
								</v-date-picker>
								<span> - </span>
								<v-date-picker v-model="slide.end_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
									<template v-slot="{ inputValue, inputEvents }"><input type="text" class="date_picker" :value="inputValue" v-on="inputEvents"/></template>
								</v-date-picker>
							</div>		
						</div>					
					</div>				
				</div>						
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/slide-tools.php' ); ?>
			</div>
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/layer-editor.php' ); ?>
		</div>			
		<?php
		require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-elements.php' );
	}
}
?>