<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_WEBFORMS) );

$message_completed = '<h2>Спасибо за Ваш заказ в интернет-магазине!</h2>
					&nbsp;
Ваш номер заказа: <strong>№ %order_id%</strong>
&nbsp;
<span style="font-size: 18pt; color: #ff0000;">Прочтите письмо, которое мы Вам отправили на %customer_email%!</span>
&nbsp;
[if order_paid=2 {<span style="font-size: 18pt; color: #99cc00;"><strong>Заказ оплачен</strong></span>}]

Обработка заказа занимает от 1 до 5 рабочих дней.
Зарегистрированные пользователи могут просматривать информацию о готовности заказа в личном кабинете.

&nbsp;

Выбранный способ получения: %shipping_method_name%
[if shipping_method_name=Самовывоз {<strong>Забрать из магазина %storage_address%</strong>
График работы %storage_schedule%
Телефон %storage_phone%

Как только менеджер обработает заказ и товар будет доставлен в указанный магазин, он вам отправит смс и письмо на электронную почту.}]
Об изменениях статуса заказа Вы будете получать письмо на эл. почту, указанную при оформление заказа.

Вы заказали эти товары:

%product_list%
Итоговая стоимость: %total_price_currency%.';

$message_fail = '<h2>Оплата заказа завершилось ошибкой.</h2>
К сожалению ваш заказ № %order_id% не был оплачен.
Возможно на карте не достаточно средств. Повторите оплату или свяжитесь с нами.
Нажмите <a href="'.usam_get_url_system_page('checkout').'">здесь</a>, чтобы вернуться к странице оформления заказа.';

$gateways = usam_get_payment_gateways( array( 'active' => 'all' ) );
foreach( (array)$gateways as $key => $gateway )
{
	if ( !usam_get_payment_gateway_metadata( $gateway->id, 'message_fail' ) )
	{
		usam_update_payment_gateway_metadata($gateway->id, 'message_fail', $message_fail);		
	}
	if ( !usam_get_payment_gateway_metadata( $gateway->id, 'message_completed' ) )
	{
		usam_update_payment_gateway_metadata($gateway->id, 'message_completed', $message_completed);		
	}
	$setting = maybe_unserialize($gateway->setting);			
	if ( !empty($setting['gateway_option']) )
	{
		foreach( $setting['gateway_option'] as $k => $v )
		{		
			usam_update_payment_gateway_metadata($gateway->id, $k, $v );
		}		
	}
	if ( !empty($setting['condition']) )
	{		
		foreach( $setting['condition'] as $k => $vv )
		{		
			if ( !empty($vv) && is_array($vv) )
			{
				foreach( $vv as $v )
					usam_add_payment_gateway_metadata($gateway->id, $k, $v );
			}
		}		
	}
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_GATEWAY."` DROP COLUMN `setting`" );