<?php
global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_USERS_BASKET, USAM_TABLE_BANNERS, USAM_TABLE_DISCOUNT_RULES, USAM_TABLE_DELIVERY_SERVICE) );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `group`" );
$wpdb->query( "ALTER TABLE `".usam_get_table_db('property_groups')."` DROP COLUMN `type_payer`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DELIVERY_SERVICE."` DROP COLUMN `setting`" );

delete_option('usam_gallery_image');
delete_option('usam_category_image');
delete_option('usam_brand_image');
delete_option('usam_product_discount');
$rules = usam_get_coupons_rules( );
foreach ( $rules as $key => $rule )
{
	$rule['rule_type'] = 'order';
	usam_edit_data( $rule, $rule['id'], 'usam_coupons_roles' );
}

$rules = usam_get_bonuses_rules( );
foreach ( $rules as $key => $rule )
{
	$rule['rule_type'] = $rule['type'];
	usam_edit_data( $rule, $rule['id'], 'usam_bonuses_rules' );
}

