<?php 
// Подключает шаблон из папки плагина universam\theme\grid_product.php 
// Можно скопировать в папку с темой в папку magazine, чтобы было wp-content\themes\cuba\magazine\grid_product.php 
?>
<div class = 'usam_simple_list usam_products'>
	<?php
	if ( !empty($title_products_for_buyers) )
	{ ?>
		<h3 class='prodtitles'><span><?php echo $title_products_for_buyers ?></span></h3>
		<?php
	}
	?>
	<div class='products_grid'>
		<?php 
		while (usam_have_products()) :  	
			usam_the_product(); 			
			include( usam_get_template_file_path( 'grid_product' ) );
		endwhile; 
		?>	
	</div>
</div>	