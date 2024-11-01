<?php
function usam_return_details( $action ) 
{
	global $wpdb;

	$count_sql = null;
	
	$post_statuses = ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'];		
	switch( $action ) 
	{
		case 'products':
			$post_type = 'usam-product';
			$count = wp_count_posts( $post_type );
		break;
		case 'product_variations':
			$post_type = 'usam-variation';
			$count = wp_count_posts( $post_type );
		break;
		case 'images':							
			$count = wp_count_posts( 'attachment' );			
		break;			
		case 'orders':	
			$count = usam_get_orders(['fields' => ['count'], 'number' => 1]);
		break;
		case 'coupons':
			$count_sql = "SELECT COUNT(`id`) FROM `" . USAM_TABLE_COUPON_CODES . "`";
		break;			
		// WordPress
		case 'posts':
			$post_type = 'post';
			$count = wp_count_posts( $post_type );
		break;	
		case 'links':
			$count_sql = "SELECT COUNT(`link_id`) FROM `" . $wpdb->prefix . "links`";
		break;
		case 'comments':
			$count = wp_count_comments();
		break;
		default:
			if ( taxonomy_exists($action) )
				$count = wp_count_terms( $action );
		break;
	}	
	if( isset($count ) || $count_sql ) 
	{
		if( isset($count ) ) 
		{
			if( is_object( $count ) ) 
			{
				$count_object = $count;
				$count = 0;
				foreach( $count_object as $key => $item )
					$count = $item + $count;
			}
			return $count;
		} 
		else 
		{
			$count = $wpdb->get_var( $count_sql );
		}
		return $count;
	} 
	else 
		return 0;		
}

function usam_vue_module( $handle )
{
	$module = new USAM_VUE_Modules();
	$module->registered( $handle );
}

function usam_get_form_customer_case( $customer_id, $customer_type ) 
{	
	if ( $customer_type == 'company' )
		$items = usam_get_customer_case( $customer_id, 'company' );
	else
		$items = usam_get_customer_case( $customer_id, 'contact' );
	
	$text = '';
	if ( !empty($items) )
	{
		$number_of_cases_displayed = 4;
		$i = 0;				
		$number_of_cases = count($items);
		foreach ( $items as $affair )
		{
			if ( $affair->status == 3 )
				continue;
			
			$i++;
			if ( $i > 1 )
				$text .= '<hr size="1" width="90%">';
			$text .= "<a href='".usam_get_event_url( $affair->id, $affair->type )."'>".usam_local_date($affair->start)." - $affair->title</a>";
			if ( $i == $number_of_cases_displayed && $number_of_cases > $number_of_cases_displayed )
			{
				$left = $number_of_cases - $number_of_cases_displayed;
				$text .= '<br><span class="are_still_cases">'.sprintf(__('еще осталось %s','usam'),$left).' ... </span>';
				break;
			}
		}			
	}
	else
		$text = __('Нет дел','usam');
	
	return '<div id="customer_case">'.$text.'</div>';
}

function usam_get_display_table_lists( $list, $display_table )
{		
	$_SERVER['REQUEST_URI'] = remove_query_arg( '_wp_http_referer', $_SERVER['REQUEST_URI'] );	
	
	$args = array( 'label' => __('Список','usam'), 'default' => 20, 'option' => $list.'_lists_table' );			
	add_screen_option( 'per_page', $args );		
	$args = array(
		'singular'  => $list.'_lists',    
		'plural'    => $list.'_lists', 
		'ajax'      => true,
		'screen'    => $list.'_lists_table',
	);					
	$filename = USAM_FILE_PATH ."/admin/includes/lists/{$list}_list_table.php"; 	
	if ( file_exists($filename) ) 	
	{
		require_once( $filename );
		$table = "USAM_{$list}_Table";
		$list_table = new $table( $args );	
		$filename = USAM_FILE_PATH ."/admin/includes/lists/display_table/{$display_table}_display_table.php";
		if ( file_exists($filename) ) 	
		{ 
			$list_table->prepare_items();	
			ob_start();	
			require_once( $filename );	
			$out = ob_get_contents();
			ob_end_clean();	
			echo $out;
		}
	}
}

function usam_get_table( $table, $args = [] )
{ 
	if ( $table )
	{	
		$name_class_table = 'USAM_List_Table_'.$table;
		if ( class_exists($name_class_table) )
			return new $name_class_table( $args );		
		
		$custom_tables = apply_filters( 'usam_register_custom_tables', [] );	
		if ( !empty($custom_tables[$table]) )
			$filename = $custom_tables[$table]['file'];
		else
			$filename = USAM_FILE_PATH .'/admin/list-table/'.$table.'_list_table.php'; 
		if ( file_exists($filename) )
		{
			require_once( $filename );						
			$name_class_table = 'USAM_List_Table_'.$table;
			return new $name_class_table( $args );	
		}
	}	
	return null;
}


function usam_update_page_sorting( $screen_id ) 
{
	if ( !empty($_REQUEST['page_sorting']) )
	{
		$page_sorting = sanitize_title($_REQUEST['page_sorting']);		
		$sort = get_user_option( 'usam_page_sorting' );	
		if ( empty($sort) ) 
			$sort = array();
		$sort[$screen_id] = $page_sorting;
		
		$user_id = get_current_user_id();
		update_user_option( $user_id, 'usam_page_sorting', $sort );			
	}
}

/*
 * Этот фильтр заменяет строку с контекстным переводом
 */
function usam_filter_gettex_with_context( $translation, $text, $context, $domain ) 
{
	if ( 'Taxonomy Parent' == $context && 'Parent' == $text && isset($_GET['taxonomy']) && 'usam-variation' == $_GET['taxonomy'] ) 
	{
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( 'Набор вариации', 'usam');
	}
	return $translation;
}
add_filter( 'gettext_with_context', 'usam_filter_gettex_with_context', 12, 4);

/*
function usam_filter_delete_text( $translation, $text, $domain ) 
{ 
	if ( 'Delete' == $text && isset($_REQUEST['post_id'] ) && isset($_REQUEST['parent_page'] ) ) {
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( 'Удалить', 'usam') ;
	}
	return $translation;
}
add_filter( 'gettext', 'usam_filter_delete_text', 12 , 3 );
*/

function usam_form_multipart_encoding() {
	echo ' enctype="multipart/form-data"';
}
add_action( 'post_edit_form_tag', 'usam_form_multipart_encoding' );
?>