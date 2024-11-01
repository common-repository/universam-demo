<?php
// Описание: Шаблон страницы Результаты покупки

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} 
get_header( 'shop' );
?>
<div class="page-content">
	<?php 	
	do_action( 'usam_before_main_content' );
	
	echo usam_get_transaction_theme( ); 

	do_action( 'usam_after_main_content' );
	?>
</div>
<?php 
get_footer( 'shop' );