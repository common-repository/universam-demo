<?php
// Описание: Шаблон личного кабинета пользователя

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} 
get_header( 'shop' );
	do_action( 'usam_before_main_content' );
	usam_get_content( ); 
	do_action( 'usam_after_main_content' );
get_footer( 'shop' );