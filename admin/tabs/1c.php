<?php
class USAM_Tab_1c extends USAM_Page_Tab
{
	protected  $display_save_button = true;	
	protected  $views = ['simple', 'table'];
	public function get_title_tab()
	{			
		return __('Обмен с 1С', 'usam');	
	}
	
	public function display()
	{		
		$statuses = ['' => __("Из 1С", "usam")];
		foreach ( get_post_stati(['show_in_admin_status_list' => true], 'objects') as $key => $status ) 
		{										
			$statuses[$key] = $status->label;
		}
		$options = array( 
			['type' => 'text', 'title' => __('Адрес сайта для 1С', 'usam'), 'html' => '<span class="js-copy-clipboard">'.home_url('api/1c').'</span>'],
			['key' => 'active', 'type' => 'checkbox', 'title' => __('Включить обмен с 1С', 'usam'), 'option' => '1c'],		
			['key' => 'log', 'type' => 'checkbox', 'title' => __('Записывать лог', 'usam'), 'option' => '1c'],			
		);		
		$this->display_table_row_option( $options );	
		?><h3><?php _e('Загрузка товаров из 1С', 'usam'); ?></h3><?php
		$options = array( 			
			['key' => 'variation', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Загружать варианты', 'usam'), 'option' => '1c'],
			['key' => 'title', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Название товара как в 1С', 'usam'), 'option' => '1c'],			
			['key' => 'body', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Добавлять контент из 1С', 'usam'), 'option' => '1c'],
			['key' => 'attributes', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Характеристики как в 1С', 'usam'), 'option' => '1c'],		
			['key' => 'excerpt', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Добавлять описание из 1С', 'usam'), 'option' => '1c'],
			['key' => 'attachments', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Добавлять картинки из 1С', 'usam'), 'option' => '1c'],
			['key' => 'categories', 'group' => 'product', 'type' => 'checkbox', 'title' => __('Импортировать категории из 1С', 'usam'), 'option' => '1c'],
			['key' => 'post_status', 'group' => 'product', 'type' => 'select', 'title' => __('Статус товара по умолчанию', 'usam'), 'option' => '1c', 'options' => $statuses],			
		);		
		$this->display_table_row_option( $options );	
		?><h3><?php _e('Выгрузка в 1С', 'usam'); ?></h3><?php
		$options = array( 			
			['key' => 'version_1c', 'type' => 'select', 'title' => __('Версия схемы', 'usam'), 'option' => '1c', 'options' => ['2.03' => '2.03', '2.07' => '2.07', '2.09' => '2.09']],
			['key' => 'schema_version', 'type' => 'checkbox', 'title' => __('Добавить версию схемы', 'usam'), 'option' => '1c'],
			['key' => 'upload_1c', 'group' => 'order', 'type' => 'checkbox', 'title' => __('Загружать заказы из 1С', 'usam'), 'option' => '1c'],
		);		
		$this->display_table_row_option( $options );		
	}
}