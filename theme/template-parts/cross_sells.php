<?php
/*
Описание: Шаблон вывода cross sells. Обычно используется в корзине
*/ 
if( have_posts() )
{
	while (usam_have_products()) :  	
		usam_the_product(); 			
		include( usam_get_template_file_path( 'grid_product' ) );
	endwhile; 
}		
?>