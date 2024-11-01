<?php
// Описание: Шаблон пустой корзины
?>
<div class="empty_page" v-if="basket!==null && basket.products.length==0">
	<div class="empty_page__icon"><?php usam_svg_icon('basket') ?></div>
	<div class="empty_page__title"><?php  _e('К сожалению, Ваша корзина пуста', 'usam'); ?></div>
	<div class="empty_page__description">
		<p><?php  _e('У вас пока нет товаров в корзине.', 'usam'); ?></p>
		<p><?php  _e('На нашем каталоге вы найдете много интересных товаров.', 'usam'); ?></p>
	</div>
	<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть наши товары', 'usam'); ?></a>
</div>