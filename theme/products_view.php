<?php
// Описание: Промежуточный шаблон для страницы "каталог" и категорий товаров.
if ( usam_display_products() )
{	
	$view_type = usam_get_display_type();
	do_action( 'usam_products_view-before' );
	?>
	<div class="products_<?php echo $view_type; ?> js-products" itemscope itemtype="http://schema.org/ItemList">		
		<?php			
		if ( $view_type == 'list' )
			usam_cache_products_attributes( );
		while (usam_have_products()) :  			
			usam_the_product(); 			
			include( usam_get_template_file_path( $view_type.'_product' ) );
		endwhile; 	
		if( ! usam_product_count() )
		{
			?>
			<div class="empty_page" v-if="basket!==null && basket.products.length==0">
				<div class="empty_page__icon"><?php usam_svg_icon('search') ?></div>
				<div class="empty_page__title"><?php  _e('К сожалению, товары не найдены', 'usam'); ?></div>
				<div class="empty_page__description">
					<p><?php _e('Попробуйте изменить критерии поиска.', 'usam'); ?></p>
				</div>
			</div>
			<?php 
		}	
		?>		
	</div>
	<div class = "usam_navigation">
		<?php
		usam_product_info();	
		usam_pagination();
		?>
	</div>
	<?php usam_product_taxonomy_description();	?>	
<?php 
}
?>