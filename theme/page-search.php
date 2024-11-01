<?php
// Описание: Шаблон страницы поиска

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} 
get_header( 'shop' );
?>
<?php 
	usam_output_breadcrumbs(); 
	do_action( 'usam_before_main_content' );
	usam_get_content( ); 
	do_action( 'usam_after_main_content' );
?>
<?php
get_footer( 'shop' );