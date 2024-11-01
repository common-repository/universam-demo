<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';

USAM_Install::create_or_update_tables([USAM_TABLE_WEBFORMS]); 

delete_option( 'usam_terms_and_conditions');

$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDER."` DROP COLUMN `template`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `order_type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `bypass_speed`" );

$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENT_META." SET meta_key='external_document_date' WHERE `meta_key`='date_external_document'");


$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP INDEX `shipping`" );

require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
$webforms = usam_get_webforms();
$results = [];
foreach ( $webforms as $webform ) 
{
	$results[$webform->id] = [];
	
	$results[$webform->id]['result_message'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='result_message'") );	
	$results[$webform->id]['modal_button_name'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='modal_button_name'") );	
	$results[$webform->id]['button_name'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='button_name'") );	
	$results[$webform->id]['description'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='description'") );	
	
	$results[$webform->id]['buttonCSS'] = ['text-align' => 'center', 'width' => '', 'height' => '', 'background-color' => '', 'color' => '', 'font-size' => '', 'font-weight' => '', 'line-height' => '', 'border-color' => '', 'border-style' => '', 'border-width' => '', 'border-radius' => '5px', 'text-decoration' => '', 'padding' => '', 'text-transform' => ''];
	$background = maybe_unserialize($wpdb->get_var("SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='button_color'") );
	$results[$webform->id]['buttonCSS']['background-color'] = !empty($background)?trim($background):'';
	$color = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='button_text_color'") );
	$results[$webform->id]['buttonCSS']['color'] = !empty($color)?trim($color):'';
	$results[$webform->id]['fields'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='fields'") );
	$results[$webform->id]['payment_gateway'] = maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$usam_base_prefix}webform_meta WHERE webform_id={$webform->id} AND meta_key='payment_gateway'") );
}

foreach( $results as $id => $settings ) 
{
	usam_update_webform( $id, ['settings' => $settings]);
}

$wpdb->query("DROP TABLE {$usam_base_prefix}banner_meta"); 
$wpdb->query("DROP TABLE {$usam_base_prefix}webform_meta"); 