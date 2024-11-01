<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-parser.php' );
class USAM_Form_parser_competitor extends USAM_Form_parser
{	
	protected $site_type = 'competitor';
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0">'.__('Изменить сайт конкурента','usam').' &laquo;{{data.name}}&raquo;</span><span v-else>'.__('Добавить сайт конкурента','usam').'</span>';
	}
	
	function get_tags()
	{			
		return [
			'product_identification' => ['title' => __('Идентификация карточки товара','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'title' => ['title' => __('Название товара','usam'), 'plural' => false, 'tag' => 'h1', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'category' => ['title' => __('Категория','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'brand' => ['title' => __('Бренд','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'sku' => ['title' => __('Артикул','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'not_available' => ['title' => __('Не доступность товара','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'price' => ['title' => __('Цена','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'number', 'json' => 0, 'json_mass' => ''],
			'thumbnail' => ['title' => __('Фотография миниатюра','usam'),'plural' => false, 'tag' => 'img', 'rules' => [], 'type' => 'url', 'json' => 0, 'json_mass' => ''],
		];
    }
			
	public function display_settings()
	{		
		?>	
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_link'><?php esc_html_e( 'Домен сайта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_link" type="text" v-model="data.domain">
					<p class="description"><?php _e( 'Можете указать ссылку подключаемого сайта', 'usam'); ?></p>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Схема', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='scheme' v-model="data.scheme">
						<option value='https'>https</option>
						<option value='http'>http</option>
					</select>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Скорость обхода', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="bypass_speed" v-model="data.bypass_speed">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Использование прокси', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='proxy' v-model="data.proxy">
						<option value='0'><?php esc_html_e( 'Нет', 'usam'); ?></option>
						<option value='1'><?php esc_html_e( 'Да', 'usam'); ?></option>
					</select>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Отображение товаров', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='view_product' v-model="data.view_product">
						<option value='card'><?php esc_html_e( 'Отдельные карточки товаров', 'usam'); ?></option>
						<option value='list'><?php esc_html_e( 'Товары списком на странице', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_price'><?php esc_html_e( 'Тип цены', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="hidden" name ="type_price" v-model="data.type_price">	
					<select-list @change="data.type_price=$event.id" :lists="prices" :selected="data.type_price" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
					<p class="description"><?php _e( 'Укажите цену Вашего сайта, в которую будет загружаться цена с сайта поставщика', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Вариант импорта', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='type_import' v-model='data.type_import'>						
						<option value=''><?php _e( 'Обновлять или создавать'  , 'usam'); ?></option>							
						<option value='update'><?php _e( 'Только обновить'  , 'usam'); ?></option>
						<option value='insert'><?php _e( 'Только создать', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Загрузка товара', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='product_loading' v-model='data.product_loading'>						
						<option value=''><?php _e( 'Импортировать все найденные товары', 'usam'); ?></option>							
						<option value='existing'><?php _e( 'Только те, которые есть на сайте', 'usam'); ?></option>
					</select>
				</div>
			</div>			
		</div>
      <?php
	}   
		
	function display_left()
	{
		?>
		<div id="titlediv">			
			<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Введите название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
		</div>					
		<?php
		usam_add_box( 'usam_settings','<span class="titleButtons"><span>'. __('Параметры','usam').'</span><span @click="sidebar=!sidebar">'.__( 'Расширенные настройки', 'usam').'</span></span>', [$this, 'display_settings'] );
		usam_add_box( 'usam_tags', '<span class="titleButtons" v-if="data.id"><span>'.__('Основные теги','usam').'</span><span class="button" @click="sidebar(`tagtesting`)">'.__( 'Тестировать', 'usam').'</span></span>', [$this, 'display_form_tags']);	
		usam_add_box( 'usam_urls', __('Ссылки для загрузки товаров','usam'), [$this, 'display_urls']);
		?>		
		<usam-box :id="'usam_excluded'">
			<template v-slot:title>
				<?php _e( 'Cсылки', 'usam'); ?><selector v-model="data.link_option" :items="[{id:0, name:'<?php _e( 'Исключить', 'usam') ?>'},{id:1, name:'<?php _e( 'Включить', 'usam') ?>'}]"></selector>
			</template>
			<template v-slot:body>
				<div class ="form_description">			
					<textarea v-model="data.excluded"></textarea>
				</div>
			</template>
		</usam-box>		
		<usam-box :id="'usam_headers'" :title="'<?php _e( 'Собственные заголовки', 'usam'); ?>'">
			<template v-slot:body>
				<div class ="form_description">		
					<textarea v-model="data.headers"></textarea>	
				</div>	
			</template>
		</usam-box>
		<usam-box :id="'usam_login'">
			<template v-slot:title>
				<?php _e( 'Авторизация', 'usam'); ?><selector v-model="data.authorization"></selector>
			</template>
			<template v-slot:body>
				<?php $this->display_login(); ?>
			</template>
		</usam-box>		
		<?php		
    }	
}
?>