<?php
/**
 * Class USAM_Help_Tab
 */
new USAM_Help_Tab();	
class USAM_Help_Tab
{	
	function __construct( )
	{	
	/**
	 * Загрузка помощи
	 */	
		add_action( 'load-edit.php'              , array($this, 'add_help_tabs') );
		add_action( 'load-post.php'              , array($this, 'add_help_tabs') );
		add_action( 'load-post-new.php'          , array($this, 'add_help_tabs') );
		add_action( 'load-edit-tags.php'         , array($this, 'add_help_tabs') );
	}		
	
	/**
	 * Эта функция добавляет контекстную справку для всех экранов. Поддерживает также $screen->add_help_tab().
	 */
	function add_help_tabs()
	{
		$tabs = array(						
			// Главная страница списка товаров (edit.php?post_type=usam-product)
			'edit-usam-product' => array(
				'title' => _x( 'Категории товаров', 'contextual help tab', 'usam'),
				'links' => array(
					'document/managing-products'   => _x( 'Добавление и управление товарами'   , 'contextual help link', 'usam'),
				),
			),
			// Страница добавления и редактирования товаров
			'usam-product' => array(
				'title' => _x( 'Обзор', 'contextual help tab', 'usam'),
				'links' => array(
					'document/managing-products'   => _x( 'Добавление и управление товарами'   , 'contextual help link', 'usam'),
				//	'resource/video-adding-products/' => _x( 'Видео: Добавление продуктов', 'contextual help link', 'usam'),
				),
			),
			// Страница меток товаров
			'edit-product_tag' => array(
				'title' => _x( 'Метки товаров', 'contextual help tab', 'usam'),
				'links' =>array(
					'resource/video-product-tags/' => _x( 'Видео: Метки товаров', 'contextual help link', 'usam'),
				),
			),
			// Страница категорий товаров
			'edit-usam-category' => array(
				'title' => _x( 'Категории товаров', 'contextual help tab', 'usam'),
				'links' => array(
					'document/managing-products'   => _x( 'Категории товаров'   , 'contextual help link', 'usam'),
				),
			),
			// Страница вариаций товаров
			'edit-usam-variation' => array(
				'title' => _x( 'Вариации товаров', 'contextual help tab', 'usam'),
				'links' => array(
					'document/variable-product'   => _x( 'Вариативный товар'   , 'contextual help link', 'usam'),
				),
			),			
		);
		$screen = get_current_screen();
		if ( array_key_exists( $screen->id, $tabs ) ) 
		{
			$tab = $tabs[$screen->id];
			$content = '<p><strong>' . __('Дополнительная информация', 'usam') . '</strong></p>';
			$links = array();		
			foreach( $tab['links'] as $link => $link_title ) 
			{
				$link = 'http://docs.wp-universam.ru/' . $link;
				$links[] = '<a target="_blank" href="' . esc_url( $link ) . '" rel="noopener">' . esc_html( $link_title ) . '</a>';
			}
			$content .= '<p>' . implode( '<br />', $links ) . '</p>';
			$screen->add_help_tab( array( 'id' => $screen->id . '_help', 'title' => $tab['title'], 'content' => $content, ) );
		}
		$this->help_sidebar();
	}	
	
	function set_help_tabs( $page, $tab, $help_tabs ) 
	{			
		$screen = get_current_screen();
		foreach( $help_tabs as $help_tab => $title ) 
		{
			$help_file = USAM_FILE_PATH ."/admin/help/$page/$tab/$help_tab.php";	
			if ( file_exists($help_file) )	
			{
				ob_start();				
				require_once( $help_file );			
				$content = ob_get_clean();		
				$screen->add_help_tab( array( 'id' => $screen->id . '_help_'.$help_tab, 'title' => $title, 'content' => $content ) );				
			}				
		} 
		$this->help_sidebar();
	}
	
	function help_sidebar( ) 
	{
		$screen = get_current_screen();
		$screen->set_help_sidebar(
			'<p><strong>' . __('Больше информации:', 'usam') . '</strong></p>' .
			'<p><a href="' . 'http://docs.wp-universam.ru' . '" target="_blank" rel="noopener">' . __('Документация', 'usam') . '</a></p>' .	
			'<p><a href="' . 'http://wp-universam.ru/products/' . '" target="_blank" rel="noopener">' . __('Больше возможностей', 'usam') . '</a></p>'.		
			'<p><a href="' . 'http://wp-universam.ru/capabilities/' . '" target="_blank" rel="noopener">' . __('О Универасам', 'usam') . '</a></p>'
		);		
	}
}
?>