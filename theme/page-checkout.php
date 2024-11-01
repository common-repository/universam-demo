<?php
// Описание: Страница корзины, оформления заказа и т. д.

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} 
get_header( 'shop' );

if ( usam_show_user_login_form() && function_exists('user_management') )
{	// Описание: Авторизация перед покупкой
	?>
	<div>	
		<h1 class="title" v-if="page=='basket'"><?php _e('Корзина', 'usam'); ?></h1>	
		<?php user_management(['register_form' => 'login', 'show_title' => false]); ?>
	</div>
	<?php 
}
else
{
	?>
	<div id="basket" v-cloak>
		<h1 class="title" v-if="page=='basket'"><?php _e('Корзина', 'usam'); ?></h1>
		<h1 class="title" v-if="page=='checkout'"><?php _e('Оформление заказа', 'usam');; ?></h1>
		<div class="page-content">
			<?php 	 
				do_action( 'usam_before_main_content' );
				usam_include_template_file('order-process');
				do_action( 'usam_after_main_content' );
			?>
		</div>
	</div>
	<?php 
}
get_footer( 'shop' ); ?>