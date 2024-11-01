<?php
/*
Описание: Шаблон страницы "Избранные товары"
*/ 
if( usam_product_count() )
{			
	?>
	<div class="products_grid js-products" itemscope itemtype="http://schema.org/ItemList">	
		<?php
		global $product_limit;
		$product_limit = 4;
		while (usam_have_products()) :  
			usam_the_product(); 					
			include( usam_get_template_file_path( 'grid_product' ) );
		endwhile; 	
		?>
	</div>
	<div class = "usam_navigation">
		<?php
		usam_product_info();	
		usam_pagination();
		?>
	</div>
	<?php
	$class = 'hide';
}
else
	$class = '';
?>	
<div class="empty_page <?php echo $class; ?>">
	<div class="empty_page__icon"><?php usam_svg_icon('favorites') ?></div>
	<div class="empty_page__title"><?php  _e('Нет товаров в избранном', 'usam'); ?></div>
	<div class="empty_page__description">
		<p><?php  _e('На нашем каталоге вы найдете много интересных товаров.', 'usam'); ?></p>
	</div>
	<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть наши товары', 'usam'); ?></a>
</div>