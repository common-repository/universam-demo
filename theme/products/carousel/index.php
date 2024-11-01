<div class = 'usam_products carousel_products'>
	<?php
	if ( !empty($title_products_for_buyers) )
	{ ?>
		<h3 class='prodtitles'><span><?php echo $title_products_for_buyers ?></span></h3>
		<?php
	}
	?>
	<div class='slides js-carousel-products'>	
		<?php 
		while (usam_have_products()) :  	
			usam_the_product(); 			
			include( usam_get_template_file_path( 'grid_product' ) );
		endwhile; 
		?>	
	</div>
</div>	