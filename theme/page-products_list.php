<?php
// Описание: Шаблон страницы "Каталог"
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
get_header( 'shop' );
usam_output_breadcrumbs();
do_action( 'usam_before_main_content' );
?>	
<div class="product_list_columns">
	<div id="primary" class="usam_product_display">		
		<?php usam_load_template("content-page-products"); ?>			
	</div>
	<div class = "sidebar"><?php dynamic_sidebar('product'); ?></div>
</div>
<?php
do_action( 'usam_after_main_content' ); 
get_footer( 'shop' );