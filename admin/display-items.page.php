<?php
/**
 * Функции для редактирования и добавления товаров на странице ТОВАРОВ
 */
require_once( USAM_FILE_PATH . '/admin/admin-products-list.php' );
class USAM_Display_Product_Page extends USAM_Admin_Products_List
{
	function load(  )
	{	
		add_filter( 'manage_edit-usam-product_sortable_columns', array(&$this, 'additional_sortable_column_names') ); // Какие колонки можно будет сортировать
		add_filter( 'manage_edit-usam-product_columns', array(&$this, 'additional_column_names'),11 );
		add_filter( 'manage_usam-product_posts_columns', array(&$this, 'additional_column_names'),11 );
		add_filter( 'posts_where', array(&$this, 'where_product_filter_description') ); // Фильтр описаний	
		add_filter( 'page_row_actions', array(&$this, 'action_product_in_row'), 10, 2 );		
	}
		
	function additional_column_names( $columns )
	{
		$columns = [];
		$columns['cb']            = '<input type="checkbox" />';	
		$columns['product_title'] = __('Название товара', 'usam');	
		$columns['status']        = __('Статус', 'usam');		
		$columns['price']         = __('Цена', 'usam');
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
			$columns['seller']          = __('Продавец', 'usam');
		else
			$columns['cats']          = __('Категория', 'usam');
		$terms = $this->get_admin_columns();
		if ( $terms )
		{
			foreach( $terms as $term )
			{
				$columns['attr_'.$term->slug] = $term->name;
			}
		}
		$columns['stock']         = '<span class="usam-dashicons-icon" title="' . __('Запас','usam') . '">'.__('Доступный запас','usam').'</span>';			
		$columns['featured']      = '<span class="usam-dashicons-icon" title="' . __('Избранное','usam') . '">'.__('Избранное','usam').'</span>';
		$columns['pdesc']         = '<span class = "usam-dashicons-icon" title="' . __('Наличие описания','usam') . '">'.__('Наличие описания' ).'</span>';
		if ( get_option('usam_show_product_rating', 1) ) 
			$columns['prating']       = '<span class = "usam-dashicons-icon" title="' . __('Рейтинг','usam') . '">'.__('Рейтинг','usam').'</span>';	
		$columns['date']          = __('Дата', 'usam');
	
		if ( isset($_GET['post_status']) && $_GET['post_status'] != 'all')
			unset($columns['status']);
		return $columns;
	}
					
	function additional_sortable_column_names( $columns )
	{
		$columns['product_title'] = 'thumbnail';
		$columns['status']        = 'post_status';	
		$columns['stock']         = 'stock';
		$columns['price']         = 'price';
		$columns['sku']           = 'sku';
		$columns['seller']        = 'seller_id';		
		$columns['code']          = 'code';		
		$columns['comment']       = 'comment';	
		$columns['views']         = 'views';
		$columns['prating']       = 'rating';	
		$columns['weight']        = 'weight';		
		$columns['author']        = 'post_author';	
		return $columns;
	}
	
	// Выбирает товары с описанием или без описания
	function where_product_filter_description( $where )
	{
		if ( !empty($_GET['d']) )
		{					
			switch ( $_GET['d'] ) 
			{			
				case 'excerpt_yes':	
					$where .= " AND wp_posts.post_excerpt != ''";	
				break;
				case 'excerpt_no':					
					$where .= " AND wp_posts.post_excerpt = ''";	
				break;
			}
		}			
		return $where;
	}
		
	//Действие над продуктами в строке
	function action_product_in_row( $actions, $post ) 
	{
		if ( $post->post_type != "usam-product" )
			return $actions;		
		if( current_user_can('edit_product', $post->ID) )		
		{	
			$url = admin_url( 'edit.php' );				
			if ( ($post->post_status == "publish" || $post->post_status == "draft") && $post->post_parent == 0 )
			{
				if ( ($post->post_status != "archive") )
					$actions['duplicate'] = '<a href="'.esc_url(usam_url_admin_action('duplicate_product', ['id' => $post->ID], $url)).'">' . esc_html_x( 'Дублировать', 'row-actions', 'usam') . '</a>';
			}
		}
		return $actions;
	}		
} 
?>