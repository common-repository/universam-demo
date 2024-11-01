<?php 
require_once(USAM_FILE_PATH.'/includes/basket/payment_gateways_query.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/delivery_services_query.class.php');
require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
$statuses = usam_get_object_statuses(['type' => ['order']]);
$delivery_service = usam_get_delivery_services();
$payment_gateways = usam_get_payment_gateways();	

$statusList = $xml->addChild('Реквизиты');	//ЗначенияРеквизитов
foreach ($statuses as $item)
{
	$statusElement = $statusList->addChild('Элемент');
	$statusElement->addChild('Ид', 'source');
	$statusElement->addChild('Название', 'Источник заказа ИД');
}
$statusList = $xml->addChild('Статусы');	
$statusList1 = $xml->addChild('Cтатусы');	
foreach ($statuses as $item)
{
	$statusElement = $statusList->addChild('Элемент');
	$statusElement1 = $statusList1->addChild('Элемент');
	$statusElement->addChild('Ид', $item->internalname);
	$statusElement->addChild('Название', esc_html($item->name));
	$statusElement1->addChild('Ид', $item->internalname);
	$statusElement1->addChild('Название', esc_html($item->name));
}
$paymentSystemsList = $xml->addChild('ПлатежныеСистемы');
foreach ($payment_gateways as $item) 
{
	$paymentSystemElement = $paymentSystemsList->addChild('Элемент');
	$paymentSystemElement->addChild('Ид', $item->id);
	$paymentSystemElement->addChild('Название', esc_html($item->name));
	$paymentSystemElement->addChild('ТипОплаты', '');
}
$shippingServicesList = $xml->addChild('СлужбыДоставки');
foreach ($delivery_service as $item)
{
	$shippingServiceElement = $shippingServicesList->addChild('Элемент');
	$shippingServiceElement->addChild('Ид', $item->id);
	$shippingServiceElement->addChild('Название', esc_html($item->name));
}