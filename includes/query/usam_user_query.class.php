<?php
/**
 * Основные запрос в базу данных для шаблона
 */ 
new USAM_User_Query();
final class USAM_User_Query
{	
	public function __construct() 
	{		
		add_action( 'pre_user_query', array($this, 'pre_user_query'), 8 );
	}
	
	function pre_user_query( $t )
	{	
		global $wpdb;
		if( isset($t->query_vars['accounts']) )
		{		
			$t->query_from .= " LEFT JOIN ".USAM_TABLE_CUSTOMER_ACCOUNTS." AS accounts ON ($wpdb->users.ID = accounts.user_id)";
			if( $t->query_vars['accounts'] == false )
				$t->query_where .= ' AND accounts.user_id IS NULL';			
		}
		if( isset($t->query_vars['cards']) )
		{		
			$t->query_from .= " LEFT JOIN ".USAM_TABLE_BONUS_CARDS." AS cards ON ($wpdb->users.ID = cards.user_id)";
			if( $t->query_vars['cards'] == false )
				$t->query_where .= ' AND cards.user_id IS NULL';			
		}
	}
}
?>