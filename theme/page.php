<?php
// Описание: Страница корзины, оформления заказа и т. д.

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} 
get_header( 'shop' );
usam_output_breadcrumbs();
$title = apply_filters( 'usam_the_title', get_the_title(), 'title' );
if ( $title )
{
	?><h1 class="title"><?php echo $title; ?></h1><?php 
} ?>
<?php 	 
	do_action( 'usam_before_main_content' );
	usam_get_content( );
	do_action( 'usam_after_main_content' );
?>
<?php
get_footer( 'shop' );