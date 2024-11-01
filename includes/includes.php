<?php
require_once(USAM_FILE_PATH.'/includes/misc.functions.php');
require_once(USAM_FILE_PATH.'/includes/license.class.php');
require_once(USAM_FILE_PATH.'/includes/technical/technical.functions.php');
require_once(USAM_FILE_PATH.'/includes/technical/support_message.class.php');

require_once(USAM_FILE_PATH.'/includes/exchange/API/request_processing.php');
require_once(USAM_FILE_PATH.'/includes/exchange/API/register_rest_route.php');
require_once(USAM_FILE_PATH.'/includes/exchange/API/document_rest_route.php');
require_once(USAM_FILE_PATH.'/includes/exchange/API/product_rest_route.php');
require_once(USAM_FILE_PATH.'/includes/exchange/API/crm_rest_route.php');
require_once(USAM_FILE_PATH.'/includes/exchange/API/seo_rest_route.php');
require_once(USAM_FILE_PATH.'/includes/exchange/showcase-handler.php');
require_once(USAM_FILE_PATH.'/includes/multisite/multisite.php');
if( usam_is_multisite() )
	require_once(USAM_FILE_PATH.'/includes/multisite/multisite_handler.class.php');
require_once(USAM_FILE_PATH.'/includes/constants.php');
require_once(USAM_FILE_PATH.'/includes/event_handling.class.php');
require_once(USAM_FILE_PATH.'/includes/query/usam-db.php');
require_once(USAM_FILE_PATH.'/includes/query/usam_query.class.php');	
require_once(USAM_FILE_PATH.'/includes/query/usam_user_query.class.php');	
require_once(USAM_FILE_PATH.'/includes/post.php');

//CRM
require_once(USAM_FILE_PATH.'/includes/crm/counters.php');
require_once(USAM_FILE_PATH.'/includes/crm/object_status.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/property.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/properties_query.class.php');		
require_once(USAM_FILE_PATH.'/includes/crm/property_group.class.php');	
require_once(USAM_FILE_PATH.'/includes/crm/property_groups_query.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/crm.helpers.php');
require_once(USAM_FILE_PATH.'/includes/crm/event.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/events_query.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/contact.php');
require_once(USAM_FILE_PATH.'/includes/crm/contacts_query.class.php');	
require_once(USAM_FILE_PATH.'/includes/analytics/page_viewed.class.php');
require_once(USAM_FILE_PATH.'/includes/analytics/visit.class.php');
require_once(USAM_FILE_PATH.'/includes/analytics/visits_query.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/company.php');
require_once(USAM_FILE_PATH.'/includes/crm/companies_query.class.php');	
require_once(USAM_FILE_PATH.'/includes/crm/notification.class.php');
require_once(USAM_FILE_PATH.'/includes/customer/customer.php');
require_once(USAM_FILE_PATH.'/includes/customer/bonus.class.php');
require_once(USAM_FILE_PATH.'/includes/customer/bonus_card.class.php');
require_once(USAM_FILE_PATH.'/includes/customer/customer_incentives.class.php');

require_once(USAM_FILE_PATH.'/includes/files/file.class.php');
require_once(USAM_FILE_PATH.'/includes/files/files_query.class.php');
require_once(USAM_FILE_PATH.'/includes/files/folder.class.php');
require_once(USAM_FILE_PATH.'/includes/files/folders_query.class.php');
require_once(USAM_FILE_PATH.'/includes/files/file.php');

require_once(USAM_FILE_PATH.'/includes/post-types.class.php');
require_once(USAM_FILE_PATH.'/includes/query/query.php');
require_once(USAM_FILE_PATH.'/includes/system_page.php');	
require_once(USAM_FILE_PATH.'/includes/crm/communication_errors_query.class.php');
require_once(USAM_FILE_PATH.'/includes/deprecated.php');
require_once(USAM_FILE_PATH.'/includes/admin_bar.class.php');
require_once(USAM_FILE_PATH.'/includes/autocomplete_forms.class.php');

require_once(USAM_FILE_PATH.'/includes/seo/seo.php');
require_once(USAM_FILE_PATH.'/includes/analytics/advertising_campaign.php');

require_once(USAM_FILE_PATH.'/includes/change_history.class.php');
require_once(USAM_FILE_PATH.'/includes/system_processes.class.php');
require_once(USAM_FILE_PATH.'/includes/compare.functions.php');
require_once(USAM_FILE_PATH.'/includes/option_data.php');
require_once(USAM_FILE_PATH.'/includes/theme/customize.class.php');
//--------------//
if( isset($_REQUEST['usam_ajax_action']) || ( defined('DOING_AJAX') && DOING_AJAX ))
{		
	if(!is_admin())
		require_once(USAM_FILE_PATH.'/includes/processing_requests_ajax.php');	
}
elseif(isset($_REQUEST['usam_action']))
{
	if(!is_admin())
		require_once(USAM_FILE_PATH.'/includes/processing_requests.php');	
}

require_once(USAM_FILE_PATH.'/includes/block/blocks.class.php');

//Товар
require_once( USAM_FILE_PATH . '/includes/product/product-filters.class.php'  );
require_once(USAM_FILE_PATH.'/includes/product/product.php');
require_once(USAM_FILE_PATH.'/includes/product/product-counters.php');
require_once(USAM_FILE_PATH.'/includes/product/product_attribute_values_query.class.php');
require_once(USAM_FILE_PATH.'/includes/product/product-template.php');
require_once(USAM_FILE_PATH.'/includes/product/product.class.php');
require_once(USAM_FILE_PATH.'/includes/parser/product_competitor.class.php');
require_once(USAM_FILE_PATH.'/includes/product/product_download_status.class.php');
require_once(USAM_FILE_PATH.'/includes/product/product_day.class.php');
include_once(USAM_FILE_PATH.'/includes/product/discount_rule.php');
include_once(USAM_FILE_PATH.'/includes/product/discount_rules_query.class.php');

require_once(USAM_FILE_PATH.'/includes/basket/cart.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/checkout.php');
require_once(USAM_FILE_PATH.'/includes/basket/coupons.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/creating_coupons.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/merchant.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/delivery_service.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/payment_gateways_query.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/delivery_services_query.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/payment_gateway.php');
require_once(USAM_FILE_PATH.'/includes/basket/shipping.class.php');
require_once(USAM_FILE_PATH.'/includes/basket/taxes.class.php');

require_once(USAM_FILE_PATH.'/includes/theme/banner.class.php');
require_once(USAM_FILE_PATH.'/includes/template.php');
require_once(USAM_FILE_PATH.'/includes/theme.php');
require_once(USAM_FILE_PATH.'/includes/media/media_gallery.class.php');
require_once(USAM_FILE_PATH.'/includes/media/media.php');

require_once(USAM_FILE_PATH.'/includes/printing-form.php');
require_once(USAM_FILE_PATH.'/includes/shop_requisites.php');
require_once(USAM_FILE_PATH.'/includes/screen.php');
require_once(USAM_FILE_PATH.'/includes/tracker.class.php');	

//Обратная связь
require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialog.class.php');
require_once(USAM_FILE_PATH.'/includes/feedback/chat.class.php');
require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews.class.php');
require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_theme.class.php');
require_once(USAM_FILE_PATH.'/includes/feedback/sms.class.php');
require_once(USAM_FILE_PATH."/includes/feedback/sms_gateway.class.php");
require_once(USAM_FILE_PATH.'/includes/feedback/social_network_profile.class.php');
require_once(USAM_FILE_PATH.'/includes/feedback/social_network_profiles_query.class.php');

//Рассылка
require_once(USAM_FILE_PATH.'/includes/mailings/sending_messages.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/newsletter.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/subscriber-list.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/email-styling.php');
require_once(USAM_FILE_PATH.'/includes/mailings/pop3.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/email.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/mailboxes_query.class.php');
require_once(USAM_FILE_PATH.'/includes/mailings/email_query.class.php');
require_once(USAM_FILE_PATH."/includes/mailings/mailbox.class.php");

require_once(USAM_FILE_PATH.'/includes/taxonomy/category.php');
require_once(USAM_FILE_PATH.'/includes/taxonomy/category_sale.php');
require_once(USAM_FILE_PATH.'/includes/taxonomy/taxonomy.php');
require_once(USAM_FILE_PATH.'/includes/taxonomy/variations.class.php');

require_once(USAM_FILE_PATH.'/includes/processing.functions.php');
require_once(USAM_FILE_PATH.'/includes/formatting.functions.php');
require_once(USAM_FILE_PATH.'/includes/verification.php');
require_once(USAM_FILE_PATH.'/includes/sending_notification.class.php');
require_once(USAM_FILE_PATH.'/includes/shortcode.class.php');	
require_once(USAM_FILE_PATH.'/includes/directory/country.class.php');
require_once(USAM_FILE_PATH.'/includes/directory/location.php');
require_once(USAM_FILE_PATH.'/includes/directory/location.class.php');
require_once(USAM_FILE_PATH.'/includes/directory/locations_query.class.php');
require_once(USAM_FILE_PATH.'/includes/directory/currency.helpers.php');
require_once(USAM_FILE_PATH.'/includes/directory/currency.class.php');
require_once(USAM_FILE_PATH.'/includes/ftp.class.php');
require_once(USAM_FILE_PATH.'/includes/store.class.php');
require_once(USAM_FILE_PATH.'/includes/stores_query.class.php');

require_once(USAM_FILE_PATH.'/includes/personnel/personnel.php');
require_once(USAM_FILE_PATH.'/includes/personnel/manager_notification.class.php');
require_once(USAM_FILE_PATH.'/includes/parser/parsing_site.class.php');
require_once(USAM_FILE_PATH.'/includes/cron.php');
include_once(USAM_FILE_PATH.'/includes/theme/slider.php');
require_once(USAM_FILE_PATH.'/includes/theme/walker_nav_menu.php');
require_once(USAM_FILE_PATH.'/includes/theme/walker_icon_menu.php');
require_once(USAM_FILE_PATH.'/includes/assets.class.php');	
require_once(USAM_FILE_PATH.'/includes/exchange/universam_service_api.class.php');
require_once(USAM_FILE_PATH.'/includes/exchange/integration_services_query.class.php');
require_once(USAM_FILE_PATH.'/includes/exchange/integration_service.class.php');
require_once(USAM_FILE_PATH.'/includes/exchange/data_exchange.class.php');
require_once(USAM_FILE_PATH.'/includes/exchange/exchange_events.class.php');
require_once(USAM_FILE_PATH.'/includes/exchange/export-import.functions.php');

//Заказ
require_once(USAM_FILE_PATH.'/includes/document/document.class.php');
require_once(USAM_FILE_PATH.'/includes/document/order.class.php');
require_once(USAM_FILE_PATH.'/includes/document/order_product_tax.class.php');
require_once(USAM_FILE_PATH.'/includes/document/orders_query.class.php');
require_once(USAM_FILE_PATH.'/includes/document/payment.class.php');
require_once(USAM_FILE_PATH.'/includes/document/order.helpers.php');
require_once(USAM_FILE_PATH.'/includes/document/order_status_change.class.php');
require_once(USAM_FILE_PATH.'/includes/document/order-notification.class.php');
require_once(USAM_FILE_PATH.'/includes/document/shipped_document.class.php');
require_once(USAM_FILE_PATH.'/includes/document/order_shortcode.class.php');
require_once(USAM_FILE_PATH.'/includes/document/document-processing.class.php');

require_once(USAM_FILE_PATH.'/includes/feedback/vkontakte.class.php');
require_once(USAM_FILE_PATH.'/includes/search/class-search-shortcodes.php');
include_once(USAM_FILE_PATH.'/includes/widgets.php');
if( get_option('usam_website_type','store')=='marketplace' )
{
	require_once(USAM_FILE_PATH.'/includes/marketplace/seller.class.php');
	require_once(USAM_FILE_PATH.'/includes/marketplace/marketplace_commission.class.php');
	require_once(USAM_FILE_PATH.'/includes/marketplace/calculation_marketplace_commission.class.php');
	require_once(USAM_FILE_PATH.'/includes/marketplace/seller-filters.class.php');
}
require_once( USAM_FILE_PATH . '/includes/automation/triggers_query.class.php' );
if ( get_option( 'usam_db_version', false ) )
	require_once( USAM_FILE_PATH . '/includes/automation/trigger_processing.class.php' );
?>