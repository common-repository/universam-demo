<?php
global $wp_roles, $wpdb;

$vk_api = get_option('usam_vk_api');
if ( !empty($vk_api['api_id']) )
{
	$vk_api['client_id'] = $vk_api['api_id'];
	unset($vk_api['api_id']);
	update_option('usam_vk_api', $vk_api);
}