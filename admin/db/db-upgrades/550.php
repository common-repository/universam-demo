<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';

$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` CHANGE COLUMN `interval_from` `start_date` datetime NULL" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` CHANGE COLUMN `interval_to` `end_date` datetime NULL" );


USAM_Install::create_or_update_tables([USAM_TABLE_SUBSCRIPTION_PRODUCTS, USAM_TABLE_SUBSCRIPTION_META, USAM_TABLE_SLIDES]); 

$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDER."` DROP COLUMN `settings`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDER."` CHANGE COLUMN `setting` `settings` longtext NOT NULL DEFAULT ''" );

$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_SLIDER );
include_once(USAM_FILE_PATH.'/includes/theme/slider.php');
$settings = ['layouttype' => 'layout', 'size' => ['computer' => ['width' => '100%', 'height' => '500px'], 'notebook' => ['width' => '100%', 'height' => '400px'], 'tablet' => ['width' => '100%', 'height' => '300px'], 'mobile' => ['width' => '100%', 'height' => '200px']], 'show' => '', 'condition' => ['roles' => [], 'sales_area' => []], 'autospeed' => 6000, 'autoplay' => 1, 'button' => ['position' => 'bottom center', 'orientation' => 'row', 'css' => ['width' => '10px', 'height' => '10px', 'border-radius' => '5px', 'margin' => '0 5px 5px 0', 'background-color' => '#ffffff', 'border-color' => '#ffffff', 'border-width' => '1px', 'border-style' => 'double'], 'show' => 1]];
foreach( $results as $result )	
{	
	$result = (array)$result;
	if ( !empty($result['settings']) )
		$result['settings'] = array_merge( $settings, maybe_unserialize($result['settings']) );
	else
		$result['settings'] = $settings;
	usam_update_slider( $result['id'], $result );	
}

$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_SLIDES );
$settings = ['background-size' => 'contain', 'background-position' => 'center center', 'repeat' => 'no-repeat', 'filter' => '', 'filter_opacity' => 1, 'effect' => '', 'background-color' => '#ddf1ef', 'layers' => []];
foreach( $results as $result )	
{	
	if ( $result->object_id && empty($result->object_url) )
	{
		$src = wp_get_attachment_image_src( $result->object_id, 'full' );	
		if ( !empty($src[0]) )
			$result->object_url = $src[0];		
		$result = (array)$result;	
		if ( empty($result['settings']) )
			$result['settings'] = [];	
		else
			$result['settings'] = maybe_unserialize($result['settings']);
		$result['settings'] = array_merge( $settings, maybe_unserialize($result['settings']) );		
		$result['type'] = 'image';
		if ( !empty($result['html']) && empty($result['settings']['html']) )
			$result['settings']['html'] = $result['html'];

		$result['settings'] = maybe_serialize($result['settings']);		
		$wpdb->update( USAM_TABLE_SLIDES, $result, ['id' => $result['id']] );
	}
}


	
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` DROP COLUMN `fon`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` DROP COLUMN `description`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` DROP COLUMN `type`" );

$wpdb->query("DROP TABLE {$usam_base_prefix}products_banner");


$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` DROP COLUMN `html`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` DROP COLUMN `language`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` CHANGE COLUMN `image_id` `object_id` bigint(20) unsigned NOT NULL DEFAULT '0'" );



