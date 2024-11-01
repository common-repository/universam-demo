<div class="form_toolbar_menus">				
	<div class="form_toolbar_menu form_menu">
		<div class="form_menu_name"><?php usam_system_svg_icon("layer"); ?><?php _e('Добавить слой', 'usam'); ?></div>
		<div class="form_submenu">
			<div class="form_submenu_wrap">
				<div class="add_layer form_submenu_name"><?php _e('Текст', 'usam');?></div>
				<div class="form_submenu">
					<div class="add_layer form_submenu_name" @click="addLayerH"><?php _e('Заголовок', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerContent"><?php _e('Текст', 'usam');?></div>
				</div>
			</div>
			<div class="form_submenu_wrap">
				<div class="add_layer form_submenu_name"><?php _e('Изображение', 'usam');?></div>
				<div class="form_submenu">
					<wp-media inline-template @change="addLayerImage">
						<div class="add_layer form_submenu_name" @click="addMedia"><?php _e('Медиафайлы', 'usam');?></div>
					</wp-media>						
				</div>
			</div>
			<div class="form_submenu_wrap">
				<div class="add_layer form_submenu_name"><?php _e('Товар', 'usam');?></div>
				<div class="form_submenu">
					<div class="add_layer form_submenu_name" @click="addLayerThumbnail('product-thumbnail')"><?php _e('Миниатюра', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerPrice('product-price')"><?php _e('Цена', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerOldPrice('product-oldprice')"><?php _e('Старая цена', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerProductTitle('product-title')"><?php _e('Название', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerDescription('product-description')"><?php _e('Описание', 'usam');?></div>		
					<div class="add_layer form_submenu_name" @click="addLayerAddtocart('product-addtocart')"><?php _e('Кнопка добавить в корзину', 'usam');?></div>						
				</div>
			</div>
			<div class="form_submenu_wrap">
				<div class="add_layer form_submenu_name"><?php _e('Товар дня', 'usam');?></div>
				<div class="form_submenu">
					<div class="add_layer form_submenu_name" @click="addLayerThumbnail('product-day-thumbnail')"><?php _e('Миниатюра', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerPrice('product-day-price')"><?php _e('Цена', 'usam');?></div>
					<div class="add_layer form_submenu_name" @click="addLayerOldPrice('product-day-oldprice')"><?php _e('Старая цена', 'usam');?></div>		
					<div class="add_layer form_submenu_name" @click="addLayerProductTitle('product-day-title')"><?php _e('Название', 'usam');?></div>	
					<div class="add_layer form_submenu_name" @click="addLayerDescription('product-day-description')"><?php _e('Описание', 'usam');?></div>	
					<div class="add_layer form_submenu_name" @click="addLayerAddtocart('product-day-addtocart')"><?php _e('Кнопка добавить в корзину', 'usam');?></div>	
				</div>
			</div>
			<div class="form_submenu_wrap">
				<div class="add_layer form_submenu_name" @click="sidebar('elements')"><?php _e('Элементы', 'usam');?></div>				
			</div>			
			<div class="add_layer form_submenu_name" @click="addLayerButton"><?php _e('Кнопка', 'usam');?></div>
			<div id="toolbar_add_layer_shape" style="display:none" class="add_layer form_submenu_name" data-type="shape"><?php _e('Shape', 'usam'); ?></div>					
		</div>
	</div>
</div>
<div class="form_toolbar_menus">				
	<div class="form_toolbar_menu form_menu devices_menu">
		<div class="form_menu_name">
			<span class="svg_icon"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+device"></use></svg></span>
			<span class="designation">ш</span><span class="designation_width designation_value">{{data.settings.size[device].width}}</span>
			<span class="designation designation_height">в</span><span class="designation_value">{{data.settings.size[device].height}}</span>
		</div>
		<div class="form_submenu">
			<div class="form_submenu_wrap" v-for="(name, d) in devicesLists">
				<div class="form_submenu_name" :class="{'active':device==d}"><span class="device_name" @click="devices[d] ? device=d : ''"><span class="svg_icon"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+d"></use></svg></span>{{name}}</span><selector v-if="d!=='computer'" v-model="devices[d]"/></div>
			</div>					
		</div>
	</div>
</div>
<div class="form_toolbar_menu form_menu">
	<div class="form_menu_name"><?php usam_system_svg_icon("undo_action"); ?></div>
	<div class="form_submenu">
		<div class="form_submenu_wrap">
			<div class="form_submenu_name" @click="undo" :class="{'disabled':!history.length}"><?php usam_system_svg_icon("arrow3", "return_action"); ?><?php _e('Назад', 'usam');?></div>
			<div class="form_submenu_name" @click="redo" :class="{'disabled':!history.length}"><?php usam_system_svg_icon("arrow3"); ?><?php _e('Вперед', 'usam');?></div>
		</div>						
	</div>
</div>	