<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-parser.php' );
class USAM_Form_parser_supplier extends USAM_Form_parser
{	
	protected $site_type = 'supplier';
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0">'.__('Изменить сайт поставщика','usam').' &laquo;{{data.name}}&raquo;</span><span v-else>'.__('Добавить сайт поставщика','usam').'</span>';
	}
	
	function get_tags()
	{			
		return [
			'product_identification' => ['title' => __('Идентификация карточки товара','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'not_available' => ['title' => __('Не доступность товара','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'title' => ['title' => __('Название товара','usam'), 'plural' => false, 'tag' => 'h1', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'sku' => ['title' => __('Артикул','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'code' => ['title' => __('Внешний код','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],			
			'brand' => ['title' => __('Бренд','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],			
			'price' => ['title' => __('Цена','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'number', 'json' => 0, 'json_mass' => ''],			
			'thumbnail' => ['title' => __('Фотография миниатюра','usam'),'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'url', 'json' => 0, 'json_mass' => ''],
			'images' => ['title' => __('Фотографии','usam'), 'plural' => true, 'tag' => '', 'rules' => [], 'type' => 'url', 'json' => 0, 'json_mass' => ''],
			'category' => ['title' => __('Категория','usam'), 'plural' => true, 'tag' => ".breadcrumbs [itemprop='name']", 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],	
			'variations_name' => ['title' => __('Навание вариации','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'variations' => ['title' => __('Вариации','usam'), 'plural' => true, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'attribute_name' => ['title' => __('Название характеристики','usam'), 'plural' => true, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],			
			'attribute_value' => ['title' => __('Значение характеристики','usam'), 'plural' => true, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'similar' => ['title' => __('Аналоги','usam'), 'plural' => true, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'content' => ['title' => __('Описание товара','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
			'excerpt' => ['title' => __('Дополнительное описание','usam'), 'plural' => false, 'tag' => '', 'rules' => [], 'type' => 'string', 'json' => 0, 'json_mass' => ''],
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
					<select v-model='data.scheme'>
						<option value='https'>https</option>
						<option value='http'>http</option>
					</select>
				</div>
			</div>						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Отображение товаров', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select v-model='data.view_product'>
						<option value='card'><?php esc_html_e( 'Отдельные карточки товаров', 'usam'); ?></option>
						<option value='list'><?php esc_html_e( 'Товары списком на странице', 'usam'); ?></option>
					</select>
				</div>
			</div>												
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.type_price=$event.id" :lists="prices" :selected="data.type_price" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
					<p class="description"><?php _e( 'Укажите цену Вашего сайта, в которую будет загружаться цена с сайта поставщика', 'usam'); ?></p>
					<input type="hidden" name ="type_price" v-model="data.type_price">					
				</div>
			</div>			
			<div class ="edit_form__item">				
				<div class ="edit_form__item_name"><?php esc_html_e( 'Вариант импорта', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='type_import' v-model='data.type_import'>		
						<option value=''><?php _e( 'Обновлять или создавать'  , 'usam'); ?></option>							
						<option value='update'><?php _e( 'Только обновить'  , 'usam'); ?></option>
						<option value='images'><?php _e( 'Обновить если нет миниатюры', 'usam'); ?></option>
						<option value='description'><?php _e( 'Обновить если нет описания', 'usam'); ?></option>
						<option value='url'><?php _e( 'Обновить если еще не обновлялся с', 'usam'); ?> {{data.domain}}</option>
						<option value='insert'><?php _e( 'Только создать', 'usam'); ?></option>		
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Скорость обхода', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.bypass_speed">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Проверка наличия', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='existence_check' v-model="data.existence_check">
						<option value='url' v-if="data.view_product!=='list'"><?php esc_html_e( 'По ссылке', 'usam'); ?></option>
						<option value='sku' v-if="data.tags['sku'].tag!==''"><?php esc_html_e( 'По артикулу', 'usam'); ?></option>
						<option value='code' v-if="data.tags['code'].tag!==''"><?php esc_html_e( 'По внешнему коду', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item" v-if="advanced">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Использование прокси', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='proxy' v-model='data.proxy'>
						<option value='0'><?php esc_html_e( 'Нет', 'usam'); ?></option>
						<option value='1'><?php esc_html_e( 'Да', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item" v-if="advanced">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Перевод на указанный язык', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='translate' v-model='data.translate'>						
						<option value=''><?php _e( 'Не требует перевода'  , 'usam'); ?></option>	
						<?php							
						$translators = usam_get_translators();						
						foreach ( $translators as $translator ) 
						{
							?><option value='<?php echo $translator['id']; ?>'><?php esc_html_e( $translator['title'] ); ?></option><?php
						}											
						?>
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
		usam_add_box( 'usam_settings','<span class="titleButtons"><span>'. __('Параметры','usam').'</span><a @click="advanced=!advanced">'.__( 'Расширенные настройки', 'usam').'</a></span>', [$this, 'display_settings'] );
		usam_add_box( 'usam_tags', '<span class="titleButtons" v-if="data.id"><span>'.__('Основные теги','usam').'</span><span class="button" @click="sidebar(`tagtesting`)">'.__( 'Тестировать', 'usam').'</span></span>', [$this, 'display_form_tags']);
		?>		
		<usam-box :id="'usam_variations'" :title="'<?php _e( 'Настройка вариаций', 'usam'); ?>'" v-if="data.tags.variations.tag!==''">
			<template v-slot:body>
				<div class="edit_form">	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Добавлять в группу', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select v-model="data.parent_variation">
								<option value='0'><?php esc_html_e( 'Создавать новую', 'usam'); ?></option>
								<?php 																
								$variations = get_terms(['taxonomy' => 'usam-variation', 'parent' => 0, 'hide_empty' => false]);
								foreach ( $variations as $variation )
								{	
									?><option value='<?php echo $variation->term_id; ?>'><?php echo $variation->name; ?></option><?php 
								}
								?>	
							</select>
						</div>
					</div>
					<div class ="edit_form__item" v-for="(value, k) in blocks_variations">
						<div class ="edit_form__item_name"><label :for="'variations_'+k" v-html="value.title+':'"></label></div>
						<div class ="edit_form__item_option">
							<input :id="'variations_'+k" type="text" v-model="data.variations[k]">
						</div>
					</div>
				</div>
			</template>
		</usam-box>		
		<?php
		usam_add_box( 'usam_urls', __('Ссылки для загрузки товаров','usam'), array( $this, 'display_urls' ) );
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
		<usam-box :id="'usam_product_default_values'" :title="'<?php _e( 'Настройки значения по умолчанию', 'usam'); ?>'">
			<template v-slot:body>
				<div class='edit_form'>			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Статус товара' , 'usam'); ?>:</div>
						<div class ="edit_form__item_option">					
							<select id='option_status' v-model ="data.post_status">						
								<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
								<?php
								foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $key => $status ) 
								{										
									?><option value='<?php echo $key; ?>'><?php echo $status->label; ?></option><?php
								}
								?>
							</select>
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Поставщик товара' , 'usam'); ?>:</div>
						<div class ="edit_form__item_option">					
							<select id='option_contractor' v-model ="data.contractor">
								<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
								<?php			
								$companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor', 'orderby' => 'name']);
								foreach ( $companies as $company )
								{					
									?><option value="<?php echo $company->id; ?>"><?php echo $company->name; ?></option><?php
								}				
								?>
							</select>	
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Изменять остатки на складе', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">					
							<select name='store' v-model='data.store'>
								<option value="0"><?php esc_html_e( 'Не выбран', 'usam'); ?></option>
								<?php							
								$storages = usam_get_storages(['fields' => ['id', 'title'], 'shipping' => 1]);						
								foreach ( $storages as $store ) 
								{
									?><option value="<?php echo $store->id; ?>"><?php esc_html_e( $store->title ); ?></option><?php
								}											
								?>
							</select>
						</div>
					</div>				
				</div>
			</template>
		</usam-box>
		<usam-box :id="'usam_cookie'" :title="'<?php _e( 'Собственные заголовки', 'usam'); ?>'">
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