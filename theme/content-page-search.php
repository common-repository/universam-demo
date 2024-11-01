<?php
// Описание: Шаблон страницы "Поиска товаров"
?>
<div class = "product_list_columns">	
	<div class = "sidebar"><?php dynamic_sidebar('search'); ?></div>
	<div class = "search_page js-search-page">	
		<?php			
		global $wp_query;
		$row = 12;
		$search_keyword = !empty($wp_query->query_vars['keyword'])?$wp_query->query_vars['keyword']: '';	
		?>		
		<input id="search_keyword" type='text' value='<?php echo $search_keyword; ?>' class="search_page__keyword js_search_page_keyword option-input" data-row="<?php echo $row; ?>" autocomplete='off' placeholder="<?php _e('Введите строку для поиска','usam'); ?>">
		<h1 class="search_page__heading_title"><?php _e('Результаты поиска', 'usam'); ?><span class="search_page__search_phrase js_search_phrase"><?php echo $search_keyword; ?></span></h1>
		<?php	
		if ( get_option('usam_website_type', 'store' ) == 'crm' )
			$search_type = 'post';
		else
			$search_type = 'product';	
		$products = USAM_Search_Shortcodes::get_products(['posts_per_page' => $row, 's' => $search_keyword]);	
		?>
		<div id="catalog_list" class="search_results js-products">
			<?php
			if ( !empty($products) )
			{		
				ob_start();		
				include usam_get_template_file_path( 'search-'.$search_type );	
				echo ob_get_clean();
			} 
			else
				echo '<div class="nothing_found '.($search_keyword?'':'hide').'">'.__('Товаров в каталоге не найдено. Попробуйте использовать другую комбинацию фильтров.', 'usam').'</div>';
			?>
		</div>
		<div class="js-search-results-more-check"></div>
		<div class="search_page__more_result js-more-result"><?php _e('Загрузка результатов...', 'usam') ?></div>
		<div class="search_page__no_more_search_results js-no-more-search-results"><?php _e('Нет больше результатов для вывода', 'usam') ?></div>
	</div>
</div>
<?php	
wp_reset_query();