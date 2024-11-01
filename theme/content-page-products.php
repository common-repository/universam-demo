<?php
// Описание: Шаблон для страницы "каталог" и категорий товаров

global $usam_query; 
$title = apply_filters( 'usam_the_title', get_the_title(), 'title' );
?>
<div class="catalog_head">			
	<?php do_action( 'usam_catalog_head' ); ?>	
	<div class="site-title">
		<h1 class ="title"><?php echo $title; ?></h1>
		<?php if ( !empty($usam_query) ) { ?>
			<span class ="number_products_title"><?php printf( _n('%s товар', '%s товаров', $usam_query->found_posts, 'usam'), $usam_query->found_posts); ?></span>	
		<?php } ?>		
	</div>		
	<?php 
	dynamic_sidebar('top-shop-tools'); 
	dynamic_sidebar('top-page-products'); 
	if ( !is_active_widget(0, 0, 'usam_filter_products') )
		usam_change_block( admin_url( "widgets.php" ), __("Добавить виджет фильтров", "usam") );
	?>	
</div>			
<div id="catalog_list">
	<?php usam_include_products_page_template(); ?>
</div>
<?php do_action( 'usam_catalog_footer' ); ?>	