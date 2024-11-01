<?php
class USAM_Tab_nuke extends USAM_Page_Tab
{	
	protected $views = ['simple'];
	public function get_title_tab()
	{
		return __('Удаление элементов сайта', 'usam');
	}
	
	public function display_tables() 
	{
		$data = ['products' => __('Товары', 'usam'), 'product_variations' => __('Вариации товаров', 'usam'), 'orders' => __('Заказы', 'usam'), 'coupons' => __('Купоны', 'usam')];
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']], 'objects');
		foreach ( $taxonomies as $taxonomy => $value )
		{
			$data[$taxonomy] = $value->label;
		}
		?>	
		<div class ="edit_form">
			<?php 
			foreach ( $data as $key => $title )
			{ 
				$count = usam_return_details( $key );
				?>				
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for="delete_<?php echo $key; ?>"><?php echo $title; ?></label></div>
					<div class ="edit_form__item_option">
						<input type="checkbox" id="delete_<?php echo $key; ?>" v-model="actions" value="delete_<?php echo $key; ?>"<?php echo disabled($count, 0); ?> > (<?php echo $count; ?>)
					</div>
				</div>
			<?php } ?>
		</div>
		<input type="submit" @click="del" class="button button-primary" value="<?php _e( 'Удалить', 'usam'); ?>">
		<?php
	}	
	
	public function display_remove_wordpress_data() 
	{
		$data = ['posts' => __( 'Записи', 'usam'), 'images' => __( 'Изображения', 'usam'), 'category' => __( 'Категории', 'usam'), 'post_tag' => __( 'Метки', 'usam'), 'links' => __( 'Ссылки', 'usam'), 'comments' => __( 'Комментарии', 'usam')];
		?>		
		<div class ="edit_form">
			<?php 
			foreach ( $data as $key => $title ) 
			{ 
				$count = usam_return_details( $key );
				?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for="delete_<?php echo $key; ?>"><?php echo $title; ?></label></div>
					<div class ="edit_form__item_option">
						<input type="checkbox" id="delete_<?php echo $key; ?>" v-model="actions" value="delete_<?php echo $key; ?>"<?php echo disabled($count, 0); ?> > (<?php echo $count; ?>)
					</div>
				</div>
			<?php } ?>
		</div>
		<input type="submit" @click="del" class="button button-primary" value="<?php _e( 'Удалить', 'usam'); ?>">
		<?php
	}
	
	public function display_directory() 
	{		
		$directory = ['location_type' => __('Типы местоположений', 'usam'), 'properties_groups' => __('Группы свойств', 'usam'), 'properties' => __('Все свойства', 'usam'), 'webform_properties' => __( 'Только свойства веб-форм', 'usam'), 'order_properties' => __('Только свойства заказов', 'usam'), 'crm_properties' => __('Только свойства контактов и компаний', 'usam'), 'currency' => __('Валюты', 'usam'), 'country' => __('Страны', 'usam'), 'object_status' => __('Статусы объектов', 'usam'), 'search_engine_regions' => __('Регионы для поисковых систем', 'usam'), 'units_measure' => __('Единицы измерения', 'usam')];		
		?>		
		<div class ="edit_form">
			<?php foreach ( $directory as $key => $title ) { ?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="directory_<?php echo $key; ?>"><?php echo $title; ?></label></div>
				<div class ="edit_form__item_option">
					<input id ="directory_<?php echo $key; ?>" type="checkbox" v-model="directory" value="<?php echo $key; ?>">
				</div>
			</div>
			<?php } ?>
		</div>
		<input type="submit" @click="perform_action" class="button button-primary" value="<?php _e('Очистить и добавить значения по умолчанию', 'usam'); ?>">
		<?php
	}
		
	public function display() 
	{				
		usam_add_box( 'usam_tables', __('Очистить таблицы от данных Универсама','usam'), array( $this, 'display_tables' ) );	
		usam_add_box( 'usam_remove_wordpress_data', __('Очистить таблицы WordPress','usam'), array( $this, 'display_remove_wordpress_data' ) );
		usam_add_box( 'usam_directory', __('Справочники','usam'), array( $this, 'display_directory' ) );			
	}
}