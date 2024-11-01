<?php
/**
 * AJAX запросы магазина
 */	
require_once( USAM_FILE_PATH .'/includes/ajax.php' ); 
class USAM_USAM_Processing_Requests_Ajax extends USAM_Callback
{	
	protected $sendback;
	protected $verify_nonce = false;
	protected $query = 'usam_ajax_action';	
	public function __construct() 
	{		
		add_action( 'init', array($this, 'handler_ajax'), 11 );
	}	
	
		
	function controller_file_upload()
	{ 		
		$results = ['status' => 'error', 'error_message' => __('Неизвестная ошибка','usam')];
		if ( !current_user_can('view_files') )
			return $results;
		if ( $this->_verify_nonce() )	
		{		
			if( isset($_FILES['upl']) && $_FILES['upl']['error'] == 0 )
				$results = usam_fileupload( $_FILES['upl'] );
			elseif( isset($_FILES['file']) && $_FILES['file']['error'] == 0 ) 
				$results = usam_fileupload( $_FILES['file'] );	
		}
		return $results;
	}
	
	function _verify_nonce( ) 
	{
		$option_work = get_option("usam_option_work","simple");				
		if ( $option_work == 'central' )
		{
			return true;
		}	
		$result = $this->verify_nonce( $this->action.'_nonce' );
		if ( is_wp_error( $result ) )			
			return true;
		
		return false;
	}	
	
	function controller_get_product_data( ) 
	{		
		if ( !empty($_REQUEST['product_id']) ) 
		{ 
			$product_id = (int)$_REQUEST['product_id'];
			$post = get_post( $product_id );
			if ( $post->post_parent )							
				$terms = get_the_terms( $post->post_parent, 'usam-category' );	
			else
				$terms = get_the_terms( $product_id, 'usam-category' );	
			
			$categories = array();
			if ( !empty($terms[0]) )		
			{
				$categories[] = $terms[0]->name;
				$ancestors = usam_get_ancestors( $terms[0]->term_id, 'usam-category' );
				foreach( $ancestors as $id )
				{
					$term = get_term( $id, 'usam-category' );
					$categories[] = $term->name;
				}
			}
			$product = ['price' => usam_get_product_price( $product_id ), 'old_price' => usam_get_product_old_price( $product_id ), 'title' => $post->post_title, 'brand' => usam_get_product_brand_name( $product_id ), 'category' => usam_get_product_category_name( $product_id ), 'categories' => $categories];
		}		
		return $product;
	}	
		
	private function get_basket_data( $cart_page = false )
	{
		$cart = USAM_CART::instance();	
		
		ob_start();				
		include( usam_get_template_file_path( 'widget-basket' ) );
		$output = ob_get_contents();
		ob_end_clean();
			
		$result = array();
		$result['widget'] = $output;
		
		$count = usam_get_number_products_basket();	
		$result['number_goods'] = $count;			
		$result['number_goods_message'] = sprintf( _n('%d товар', '%d товаров', $count, 'usam'), $count);
		
		$count = usam_get_number_sku_basket();
		$result['total_number_items_basket'] = $count;		
		$result['total_number_items_basket_message'] = sprintf( _n('%d товар', '%d товаров', $count, 'usam'), $count);		
		$result['basket_subtotal'] = usam_get_basket_subtotal();		
		$result['basket_total'] = usam_cart_total();		

		$result['basket_weight'] = usam_cart_weight_total();	
		if ( $cart_page )
		{
			ob_start();
			include_once( usam_get_template_file_path( 'content-page-basket' ) );
			$result['cart_page'] = ob_get_contents();	
			ob_end_clean();			
		}
		$result = apply_filters( 'usam_get_basket_data', $result );
		return $result;	
	}
	
	/**
	 * Функция обновления количества.
	 */
	protected function controller_remove_cart_item() 
	{
		$cart = usam_core_setup_cart();

		if ( isset($_REQUEST['key'] ) ) 
		{
			$key = (int)$_REQUEST['key'];			
			$cart->remove_products( $key, true );		
		}			
		ob_start();
		include_once( usam_get_template_file_path( 'widget-basket' ) );
		$output = ob_get_clean();		
		return $output;			
	}
	
	protected function controller_update_product_quantity_basket() 
	{
		$cart = usam_core_setup_cart();

		$return = array( 'result' => false );		
		if ( isset($_REQUEST['quantity']) ) 
			$quantity = usam_string_to_float($_REQUEST['quantity']);
		else
			$quantity = 1;
		
		$product_key = 0;
		if ( isset($_REQUEST['product_id']) )
		{
			$product_id = (int)$_REQUEST['product_id'];
			$product_id = usam_get_post_id_main_site( $product_id );
			$product_basket = usam_get_product_basket( $product_id );
			if ( isset($product_basket['id']) )
				$product_key = $product_basket['id'];			
		}
		elseif ( isset($_REQUEST['product_key']) ) 
			$product_key = (int)$_REQUEST['product_key'];	
				
		if ( $product_key ) 
		{		
			if ( $quantity == 0 )
				$result = $cart->remove_products( $product_key, true );
			else
			{
				$result = $cart->update_quantity( $product_key, $quantity, false );	
				$cart->recalculate();
			}
			$return = $this->get_basket_data( true );
			$return['result'] = $result;
		}	
		return $return;		
	}		
	
	/**
	 * Рассчитывает скидку купона при вводе в корзине.
	 */
	protected function controller_apply_coupon( ) 
	{
		$cart = usam_core_setup_cart();		
		$output = array();
		$coupon = '';
		if ( !empty($_REQUEST['coupon_number']) )		
			$coupon = sanitize_title($_REQUEST['coupon_number']);
		
		$cart->set_properties(['coupon_name' => $coupon]);
		return $this->get_basket_data( true );
	}

	// Функция очистки корзины
	protected function controller_empty_cart() 
	{
		$cart = usam_core_setup_cart();
		$cart->empty_cart( );
		return $this->get_basket_data( true );	
	}
	
	//Потратить бонусы в корзине
	protected function controller_spend_bonuses( $id = '' ) 
	{
		$cart = usam_core_setup_cart();
		
		$user_id = get_current_user_id();	
		$bonus_card = usam_get_bonus_card( $user_id, 'user_id' );
		if ( !empty($bonus_card) && $bonus_card['status'] == 'active' && $bonus_card['sum'] > 0 )		
			$cart->set_properties( array( 'bonuses' => $bonus_card['sum'] ) );
		return $this->get_basket_data( true );	
	}

	/**
	 * Потратить бонусы в корзине
	 */
	protected function controller_return_bonuses(  ) 
	{
		$cart = usam_core_setup_cart();			
		$cart->set_properties(['bonuses' => 0]);
		return $this->get_basket_data( true );	
	}
	
	function controller_update_language()
	{
		$language = sanitize_title($_REQUEST['language']);
		setcookie('usamlang', $language, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}

	function controller_get_modal()
	{				
		$template = sanitize_text_field($_REQUEST['modal']);
		ob_start();					
		usam_include_template_file( $template, 'modaltemplate' );
		return ob_get_clean();
	}

	//********************************************************* Категории товаров ****************************************************************//
	
	
	// функция выводит продукты в соответствии с запрошенными сортировками
	function controller_get_products()
	{	
		global $wp_query;	
		$output = '';
		if ( $this->_verify_nonce() )
		{			
			$query_vars = !empty($_REQUEST['query'])?$_REQUEST['query']:array();	
			if (isset($query_vars['in_stock']) && $query_vars['in_stock'] == 'false')
				$query_vars['in_stock'] = false;
						
			if ( isset($_REQUEST['row']) ) 
				$query_vars['posts_per_page'] = absint( $_REQUEST['row'] );							
			if ( !empty($_REQUEST['s']) )
				$query_vars['s'] = trim(stripslashes(strip_tags($_REQUEST['s']))); 
			elseif ( !empty($_REQUEST['search']) )
				$query_vars['s'] = trim(stripslashes(strip_tags($_REQUEST['search']))); 			
			if ( !empty($_REQUEST['paged']) )  
				$query_vars['paged'] = absint( $_REQUEST['paged'] );					
			if ( isset($query_vars['pagename']) )
			{
				$page = $query_vars['pagename'];
				unset($query_vars['pagename']);				
			}
			else
				$page = '';
			if ( !empty($query_vars['tabs']) )
			{
				$contact_id = usam_get_contact_id();
				if ( $query_vars['tabs'] == 'my-desired' )
					$query_vars['user_list'] = ['list' => 'desired', 'contact_id' => $contact_id];	
			}			
			if(!empty($_REQUEST['number']) && is_numeric($_REQUEST['number']) && $_REQUEST['number'] <= 400 )
			{					
				$number = absint($_REQUEST['number']);
				$cookie_key = 'number_products';
				setcookie( $cookie_key, $number, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				$_COOKIE[$cookie_key] = $number;
				$query_vars['posts_per_page'] = $number;
			}
			if ( isset($_REQUEST['favorite_shop']) )
			{
				$contact_id = usam_get_contact_id();
				$favorite_shop = absint($_REQUEST['favorite_shop']);
				usam_update_contact_metadata($contact_id, 'favorite_shop', $favorite_shop);	
			}
			if ( isset($_REQUEST['individual_price']) )
			{
				$company_id = (int)$_REQUEST['individual_price'];
				$contact_id = usam_get_contact_id();	
				usam_update_contact_metadata( $contact_id, 'checkout_company_id', $company_id );	
			}	
			$append = isset($_REQUEST['append']) ? (int)$_REQUEST['append']:0;		
			usam_update_view();						
			add_filter( 'pre_get_posts', ['USAM_Theme_Query', 'change_product_request'], 11 );	
			$count = 0;
			if ( !empty($query_vars['s']) || $page == 'search' )
			{			 
				$products = USAM_Search_Shortcodes::get_products( $query_vars );
				$count = (int)$wp_query->found_posts;
				if ( !empty($products) )
				{
					if ( get_option('usam_website_type', 'store' ) == 'crm' )
						$search_type = 'post';
					else
						$search_type = 'product';	
					$search_keyword = stripslashes( strip_tags( trim($query_vars['s']) ) );
					ob_start();	
					include usam_get_template_file_path( 'search-'.$search_type );	
					$output = ob_get_clean();
				}				
			}
			elseif( !empty($query_vars) )
			{							
				$query_vars['post_type'] = 'usam-product';				
				if ( !empty($_REQUEST['rating']) )
				{ 
					$rating = (array)$_REQUEST['rating'];
					$query_vars['postmeta_query'][] = ['key' => 'rating', 'value' => $rating, 'compare' => 'IN'];
				}
				ob_start();			
				$wp_query = new WP_Query( $query_vars );
				$count = (int)$wp_query->found_posts;
				$view_type = usam_get_display_type();
				if ( $append )
				{
					while (usam_have_products()) :  			
						usam_the_product(); 			
						include( usam_get_template_file_path( $view_type.'_product' ) );
					endwhile; 	
				}
				else
				{
					$templates = ["{$view_type}_view", "products_view"];
					foreach ($templates as $template)
					{
						if ( usam_load_template( $template ) )
							break;
					}						
				}
				$output = ob_get_clean();
			}	
			remove_filter( 'pre_get_posts', ['USAM_Theme_Query', 'change_product_request'], 11 );
		}		
		return ['products' => $output, 'count' => $count];
	}
	
//********************************************************* Товар ****************************************************************//		
			
	// Изменить подписку на странице подписок
	public function controller_update_subscribe()
	{					
		if ( !empty($_POST['list_id']) && !empty($_POST['communication']) )
		{		
			$list_id = absint($_REQUEST['list_id']);	
			$communication = sanitize_text_field($_REQUEST['communication']);
			$status = !empty($_REQUEST['status']) && $_REQUEST['status'] == 1?1:2;	
			$type = is_email($communication)?'email':'phone';
			usam_set_subscriber_lists(['communication' => $communication, 'status' => $status, 'id' => $list_id, 'type' => $type]);
			usam_update_mailing_statuses(['include' => [$list_id]]);	
		} 
	}		
			
	function controller_set_banner() 
	{			
		if ( $this->_verify_nonce() )		
		{
			$id = absint($_REQUEST['id']);
			$banner = usam_get_banner( $id );
			if ( !empty($banner) )
			{
				$update = [];
				if ( $banner['type'] == 'html' )
					$update['settings']['html'] = stripslashes($_REQUEST['html']);
				else
				{
					$update['object_id'] = absint($_REQUEST['attachment_id']);
					$update['object_url'] = absint($_REQUEST['url']);
				}
				$result = usam_update_banner( $id, $update );
			}
		}
	}
	
	function controller_get_list_table()
	{			
		if ( $this->_verify_nonce() )		
		{
			$table = sanitize_title($_REQUEST['table']).'_list_table';	
			$file_path = usam_get_template_file_path( $table, 'list-table' );
			if ( $file_path )
			{
				include( $file_path );			
				$name_class_table = 'USAM_'.$table;
				$list_table = $wp_list_table = new $name_class_table(['singular'  => $table, 'plural' => $table]);				
				return $list_table->ajax_response();
			}			
		}				
	}
		
	function controller_get_products_group()
	{
		$title = sanitize_text_field(stripslashes($_POST['title']));
		$query = sanitize_title($_POST['query']);	
		$product_id = !empty($_POST['product_id'])?usam_get_post_id_main_site( absint($_POST['product_id']) ):0;	
		$number = absint($_POST['number']);
		$template = sanitize_title($_POST['template']);
		$post_meta_cache = isset($_POST['post_meta_cache'])?(bool)$_POST['post_meta_cache']:false;
		
		ob_start();
		usam_change_block( admin_url( "admin.php?page=interface&tab=product_view#".$query ), __("Изменить этот блок", "usam") );	
		new USAM_Display_Product_Groups(['title' => $title, 'query' => $query, 'template' => $template, 'product_id' => $product_id, 'limit' => $number, 'post_meta_cache' => $post_meta_cache]);	
		$output = ob_get_clean();	
		return $output;	
	}
	
	function controller_close_cookie_notice()
	{
		setcookie('cookienotice', 1, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}	
}
new USAM_USAM_Processing_Requests_Ajax();
?>