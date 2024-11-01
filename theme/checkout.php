<?php
// Шаблон оформление заказа. Оформление написано с использованием Vue. 
?>
<div id = "checkout" class="columns_block">		
	<div class="checkout__details">				
		<?php						
		if( !is_user_logged_in() && get_option('usam_registration_upon_purchase') == 'suggest' )
			usam_include_template_file('suggest_login', 'checkout');	
		usam_include_template_file('type_payer', 'checkout'); //Выбор типа платильщиков
		usam_include_template_file('payer_company', 'checkout'); //Выбор компании при оформлении заказа если добавлена в личном кабинете
		?>
		<div class ="checkout_details_block">
			<?php usam_include_template_file('customer_data', 'checkout'); //Данные клиента	?>
		</div>
		<?php usam_include_template_file('shipping_methods', 'checkout');//Выбор способа доставки ?>	
		<?php usam_include_template_file('payment_methods', 'checkout'); //Выбор способа оплаты ?>		
	</div>
	<div class="checkout__total">	
		<div class="ordering_banners">
			<?php usam_theme_banners(['banner_location' => 'ordering']); // вывести баннеры, если есть для оформления заказа ?>
		</div>	
		<div class="checkout-payment-block">
			<div class="view_form">			
				<div class ="view_form__title"><?php _e('Посмотреть стоимость заказа', 'usam'); ?></div>
			</div>
			<?php usam_include_template_file( 'products', 'checkout' ); ?>
			<?php usam_include_template_file('totalprice', 'checkout'); ?>								
			<div class='usam_checkout_taskbar' v-if="basket.payment_methods.length!=0">
				<button class='button main-button checkout-button' @click="buy" :class="{'is-loading':send}" :disabled="codeError"><?php _e('Покупка', 'usam'); ?></button>
			</div>							
		</div>											
	</div>	
</div>