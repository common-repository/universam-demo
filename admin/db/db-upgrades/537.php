<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_FILES]);

 
$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='hide_visitors' WHERE `meta_key`='menu'");	
$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='field_type' WHERE `meta_key`='type_attribute'");	
