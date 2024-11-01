<?php
// Описание: Шаблон страницы содержащие списки товаров. Например, категории, новинки и т.д.
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
get_header( 'shop' );
usam_output_breadcrumbs();

do_action( 'usam_before_main_content' ); 
	?>
	<div class="product_list_columns">		
		<div id="primary" class="usam_product_display">
			<?php usam_get_content( ); ?>
		</div>		
		<div class = "sidebar"><?php dynamic_sidebar('product'); ?></div>
	</div>
	<?php 
do_action( 'usam_after_main_content' );
get_footer( 'shop' );