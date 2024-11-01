<?php
// Описание: Авторизация перед покупкой
?>
<div class="view_form login_buttons_block">
	<div class ="view_form__title"><?php _e('Войти или зарегистрироваться', 'usam'); ?></div>
	<?php
	if ( is_active_sidebar( 'checkout-suggest-login' ) ) : ?>					
		<div class="widget-column widgets-checkout-suggest-login">
			<?php dynamic_sidebar('checkout-suggest-login'); ?>
		</div>
	<?php endif; ?>
	<div class="checkout__login_buttons">
		<button class="button checkout__login_button usam_modal" data-modal="login"><?php _e('Войти', 'usam'); ?></button>
		<button class="button checkout___register_button usam_modal" data-modal="login"><?php _e('Зарегистрироваться', 'usam'); ?></button>
	</div>
</div>