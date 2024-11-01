<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

get_header();
// Подключает блоки, шаблоны которых здесь template-parts\home-blocks
// Регистрация блоков в файле functions.php функция _register_home_blocks

if ( is_active_sidebar( 'top-page-home' ) ) :			
	dynamic_sidebar('top-page-home');									
 endif;	 
 
usam_home_blocks();

get_footer();

?>