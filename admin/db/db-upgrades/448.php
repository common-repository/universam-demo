<?php
global $wpdb;
$wpdb->query( "RENAME TABLE {$wpdb->prefix}usam_competitor_products_data TO ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE );
USAM_Install::create_or_update_tables( array(USAM_TABLE_PARSING_SITES, USAM_TABLE_COMPETITOR_PRODUCT_PRICE) );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPETITOR_PRODUCT_PRICE."` DROP COLUMN `storage`" );


