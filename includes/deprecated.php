<?php
/**
 * Устаревшие функции 
*/


function usam_get_webform_fields( $webform_id, $placeholder = false  ) 
{	
	
}

// Возвращает товары в сравнении
function usam_get_products_compare( )
{	
	$product_ids = usam_get_product_ids_in_user_list( 'compare' );	
	return $product_ids;
}

// Возвращает товары в избранном
function usam_get_products_desired( )
{
	$product_ids = usam_get_product_ids_in_user_list( 'desired' );	
	return $product_ids;
}	

function usam_get_cart_items_subtotal()
{
	return usam_get_basket_subtotal();
}

function usam_webform_link($code, $class = '', $id = '')
{
	echo usam_get_webform_link( $code, $class );
}

function usam_cart_item_count()
{	
	return usam_get_basket_number_items();
}

function usam_select_product_variation()
{
	usam_include_template_file( 'product-variation' );
}

/**
 * Функция цены товара
 */
function usam_the_product_price( $old_price = false, $product_id = 0 ) 
{
	return usam_get_product_price_currency( $product_id, $old_price );
}

/**
* Получить сcылку на термин
*/
function usam_get_term_link( $term_id, $taxonomy ) 
{ 
	return get_term_link($term_id, $taxonomy );
}

function usam_get_text_div( $str, $len )
{	
	return usam_limit_words( $str, $len ) ;
}

function usam_product_existing_rating( $vote_total = false, $no_grey = true ) 
{
	
}

function usam_get_product_existing_rating( $product_id = null, $vote_total = true, $no_grey = false ) 
{
	
}


/**
 * Получить постоянную ссылку на товар
 */
function usam_the_product_permalink( $product_id = null ) 
{	
	return get_permalink( $product_id );	
}


function usam_feedback_link( $code, $class = '', $echo = true ) 
{		
	$webform = usam_get_webform( $code, 'code' );	
	if ( !empty($webform['active']) )
	{
		$link = usam_get_webform_link( $code, $class );
		if ( $echo )
			echo $link;
		else
			return $link;
	}
	return '';
}

function usam_product_has_multicurrency( ) 
{	
	return false;
}

/**
* Вернуть налог корзины
*/
function usam_cart_tax( $forDisplay = true)
{
	return 0;
}

// Вывести отзывы
function usam_reviews( $echo = true )
{	
	$customer_reviews = new USAM_Customer_Reviews_Theme();
	$content = "<div class='product_reviews'>";
	$content .= $customer_reviews->show_button_reviews_form( 'top' );
	$content .= $customer_reviews->show_reviews_form( 'top' );	
	$content .= $customer_reviews->output_reviews_show( $query );
	$content .= $customer_reviews->show_button_reviews_form( 'bottom' );
	$content .= $customer_reviews->show_reviews_form( 'bottom' );
	$content .= $customer_reviews->aggregate_footer(); 
	$content .="</div>";
	if ( $echo )
		echo $content;
	return $content;
}


//Сумма всех бонусов
function usam_get_total_bonus_account( $user_id = 0 )
{
	return usam_get_available_user_bonuses( $user_id );
}

//Получить все записи бонусов
function usam_get_bonus_account( $user_id = '' )
{
	if (  $user_id == '')
		$user_id =  get_current_user_id();
	require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
	
	$bonus_card = usam_get_bonus_card( $user_id, 'user_id');
	if ( empty($bonus_card['code']) )
		return array();	
	return usam_get_bonuses( array( 'code' => $bonus_card['code'] ) );
}


function usam_start_dialog(  )
{
	
}

function usam_chat_button(  )
{
	
}



/**
 * В случае, если пользователь не вошел в систему, создать клиенту куки с уникальным ID в паре с переходным в базе данных.
 */
function usam_create_customer_id()
{
	$expire = USAM_CUSTOMER_DATA_EXPIRATION; 
	$id = '_' . wp_generate_password(); // make sure the ID is a string
	$data = $id . $expire;
	$hash = hash_hmac( 'md5', $data, wp_hash( $data ) );
	// store ID, expire and hash to validate later
	$cookie = $id . '|' . $expire . '|' . $hash;

	setcookie( USAM_CUSTOMER_COOKIE, $cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE[USAM_CUSTOMER_COOKIE] = $cookie;
	return $id;
}

/**
 * Убедитесь, что куки клиента не будет нарушены.
 * @return Возвращает смешанный идентификатор клиента.
 */
function usam_validate_customer_cookie() 
{
	$cookie = $_COOKIE[USAM_CUSTOMER_COOKIE];
	list( $id, $expire, $hash ) = explode( '|', $cookie );
	$data = $id . $expire;
	$hmac = hash_hmac( 'md5', $data, wp_hash( $data ) );
	if ( $hmac != $hash )
		return false;
	return $id;
}

/**
 * Слияние анонимных данных клиента (хранится в переходных) с данными счета мета, когда клиент входит в систему
 */
function _usam_merge_customer_data()
{
	$account_id = get_current_user_id();
	$cookie_id = usam_validate_customer_cookie();	
	
	if ( ! $cookie_id )
		return;

	$cookie_data = get_transient( "usam_customer_meta_{$cookie_id}" );
	if ( ! is_array( $cookie_data ) || empty( $cookie_data ) )
		return;
	
	foreach ( $cookie_data as $key => $value )
		usam_add_customer_meta( $key, $value, $account_id );	

	delete_transient( "usam_customer_meta_{$cookie_id}" );
	setcookie( USAM_CUSTOMER_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	unset( $_COOKIE[USAM_CUSTOMER_COOKIE] );
}

/**
 * Получить текущий ID клиента.
 * Если пользователь вошел в систему, возвращает идентификатор пользователя. В противном случае возвращает идентификатор, связанный с куки клиента.
 * Если $mode установлен в «создать», создаст идентификатор клиента, если он еще не был создан.
 */
function usam_get_current_customer_id( $mode = '' )
{ 
	if ( !defined('USAM_CUSTOMER_COOKIE')  )
		return false;
	
	if ( is_user_logged_in() && isset($_COOKIE[USAM_CUSTOMER_COOKIE] ) )
		_usam_merge_customer_data();
	
	if ( is_user_logged_in() )
		return get_current_user_id();
	elseif ( isset($_COOKIE[USAM_CUSTOMER_COOKIE] ) )
		return usam_validate_customer_cookie();
	elseif ( $mode == 'create' )
		return usam_create_customer_id();

	return false;
}

/* Возвращает массив, содержащий все метаданные клиента
 */
function usam_get_all_customer_meta( $id = false )
{
	global $wpdb;

	if ( ! $id )
		$id = usam_get_current_customer_id(); 
	if ( ! $id )
		return new WP_Error( 'usam_customer_meta_invalid_customer_id', __('Неверный ID клиента', 'usam'), $id );

	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	if ( is_numeric( $id ) )
		$profile = get_user_meta( $id, "_usam_{$blog_prefix}customer_profile", true );
	else
		$profile = get_transient( "usam_customer_meta_{$blog_prefix}{$id}" );

	if ( ! is_array( $profile ) )
		$profile = array();

	return apply_filters( 'usam_get_all_customer_meta', $profile, $id );
}

/**
 * Получить значение клиентской меты.
 * @since  3.8.9
 */
function usam_get_customer_meta( $key = '', $id = false )
{	
	$profile = usam_get_all_customer_meta( $id );
	if ( is_wp_error( $profile ) && ! $id ) 
	{
		usam_create_customer_id();
		$profile = usam_get_all_customer_meta();
	}
	if ( is_wp_error( $profile ) || ! array_key_exists( $key, $profile ) )
		return null;

	return $profile[$key];
}

/**
 * Переписать мета клиентов с массивом meta_key => meta_value.
 * @since  3.8.9
 */
function usam_update_all_customer_meta( $profile, $id = false )
{
	global $wpdb;

	if ( ! $id )
		$id = usam_get_current_customer_id( 'create' );

	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';

	if ( is_numeric( $id ) )
		return update_user_meta( $id, "_usam_{$blog_prefix}customer_profile", $profile );
	else
		return set_transient( "usam_customer_meta_{$blog_prefix}{$id}", $profile, USAM_CUSTOMER_DATA_EXPIRATION - time());
}
/**
 * Обновить мета клиента
 * @since  3.8.9
 */
function usam_update_customer_meta( $key, $value, $id = false )
{
	if ( ! $id )
		$id = usam_get_current_customer_id( 'create' );
	$profile = usam_get_all_customer_meta( $id );

	if ( is_wp_error( $profile ) )
		return $profile;

	$profile[$key] = $value;
	return usam_update_all_customer_meta( $profile, $id );
}
/**
 * Добавить мета клиента
 * @since  3.8.9
 */
function usam_add_customer_meta( $key, $value, $id = false )
{
	if ( ! $id )
		$id = usam_get_current_customer_id( 'create' );
	$profile = usam_get_all_customer_meta( $id );

	if ( is_wp_error( $profile ) )
		return $profile;

	$return = false;
	if ( !isset($profile[$key]) )
	{
		$profile[$key] = $value;
		$return = usam_update_all_customer_meta( $profile, $id );
	}
	return $return;
}
/**
 * Удалить мета клиента
 */
function usam_delete_customer_meta( $key, $id = false ) 
{
	$profile = usam_get_all_customer_meta( $id );
	if ( is_wp_error( $profile ) )
		return $profile;
	if ( array_key_exists( $key, $profile ) )
		unset( $profile[$key] );

	return usam_update_all_customer_meta( $profile, $id );
}

/* Удалить все мета клиентов для определенного определенного идентификатора клиента
 */
function usam_delete_all_customer_meta( $id = false )
{
	global $wpdb;

	if ( ! $id )
		$id = usam_get_current_customer_id();
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	if ( is_numeric( $id ) )
		return delete_user_meta( $id, "_usam_{$blog_prefix}customer_profile" );
	else
		return delete_transient( "usam_customer_meta_{$blog_prefix}{$id}" );
}

/**
 * Вывести рейтинг товара.
 */
function usam_product_new_rating()
{			
	
}

function usam_site_map()
{			
	
}

function usam_icon( $icon )
{			
	echo usam_get_icon( $icon );
}


function usam_display_products_page( $query )
{
	
}

function usam_fb_like( )
{
	if( usam_show_fb_like() )
	{ 
	?>			
		<div class="button_like FB_like">						
			<iframe src="https://www.facebook.com/plugins/like.php?href=<?php echo usam_product_url(); ?>&amp;layout=button_count&amp;show_faces=true&amp;width=435&amp;action=like&amp;font=arial&amp;colorscheme=light"></iframe>
		</div>  
	<?php
	} 			
}	

function usam_vk_like( )
{
	
}	

/**
 * функция проверки, требуется ли показывать Facebook Like
 */
function usam_show_fb_like()
{
	
}

/**
 * функция проверки, требуется ли показывать Вконтакте Like
 */
function usam_show_vk_like()
{
	if('1' == get_option('usam_vk_like', ''))
		return true;
	else
		return false;
}

function usam_the_product_price_display( $args = array() )
{	
	if ( empty( $args['id'] ) )
		$id = get_the_ID();
	else
		$id = (int) $args['id'];
	
	$current_price       = usam_get_product_price_currency( $id );
	if ( !$current_price )
		return false;

	$defaults = array(
		'id' => $id,
		'old_price_text'   => __('Старая цена: %s', 'usam'),
		'price_text'       => __('Цена: %s', 'usam'),
		'you_save_text'    => __('Вы экономите: %s', 'usam'),
		'old_price_class'  => 'pricedisplay usam-product-old-price ' . $id,
		'old_price_before' => '<div %s>',
		'old_price_after'  => '</div>',
		'old_price_amount_id'     => 'old_product_price_' . $id,
		'old_price_amount_class' => 'js-old-price oldprice',
		'old_price_amount_before' => '<span class="%1$s" id="%2$s">',
		'old_price_amount_after' => '</span>',
		'price_amount_id'     => 'product_price_' . $id,
		'price_class'  => 'pricedisplay usam-product-price ' . $id,
		'price_before' => '<div itemprop="price" %s>',
		'price_after' => '</div>',
		'price_amount_class' => 'js-price currentprice pricedisplay ' . $id,
		'price_amount_before' => '<span class="%1$s" id="%2$s">',
		'price_amount_after' => '</span>',
		'you_save_class' => 'js-discount pricedisplay usam-product-you-save product_' . $id,
		'you_save_before' => '<div %s>',
		'you_save_after' => '</div>',
		'you_save_amount_id'     => 'yousave_' . $id,
		'you_save_amount_class' => 'yousave',
		'you_save_amount_before' => '<span class="%1$s" id="%2$s">',
		'you_save_amount_after'  => '</span>',
		'output_price'     => true,
		'output_old_price' => true,
		'output_you_save'  => true,
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	
	$old_price           = usam_get_product_price_currency( $id, true );
	$you_save_percentage = usam_get_percent_product_discount($id);
	$you_save            = usam_get_formatted_price( usam_get_product_discount( $id ) ) . '! ( ' . $you_save_percentage . '% )';

	$old_price_class = apply_filters( 'usam_the_product_price_display_old_price_class', $old_price_class, $id );
	$old_price_amount_class = apply_filters( 'usam_the_product_price_display_old_price_amount_class', $old_price_amount_class, $id );
	$attributes = 'class="' . esc_attr( $old_price_class ) . '"';
	
	if (  $old_price == '' )
		$attributes .= ' style="display:none;"';
	
	$old_price_before = sprintf( $old_price_before, $attributes );
	$old_price_amount_before = sprintf( $old_price_amount_before, esc_attr( $old_price_amount_class ), esc_attr( $old_price_amount_id ) );

	$price_class = 'class="' . esc_attr( apply_filters( 'usam_the_product_price_display_price_class', esc_attr( $price_class ), $id )  ) . '"';
	$price_amount_class = apply_filters( 'usam_the_product_price_display_price_amount_class', esc_attr( $price_amount_class ), $id );
	$price_before = sprintf( $price_before, $price_class );
	$price_amount_before = sprintf( $price_amount_before, esc_attr( $price_amount_class ), esc_attr( $price_amount_id ) );

	$you_save_class = apply_filters( 'usam_the_product_price_display_you_save_class', $you_save_class, $id );
	$you_save_amount_class = apply_filters( 'usam_the_product_price_display_you_save_amount_class', $you_save_amount_class, $id );
	$attributes = 'class="' . esc_attr( $you_save_class ) . '"';
	
	if (  $old_price == '' )
		$attributes .= ' style="display:none;"';
	$you_save_before = sprintf( $you_save_before, $attributes );
	$you_save_amount_before = sprintf( $you_save_amount_before, esc_attr( $you_save_amount_class ), esc_attr( $you_save_amount_id ) );

	$old_price     = $old_price_amount_before . $old_price . $old_price_amount_after;
	$current_price = $price_amount_before . $current_price . $price_amount_after;
	$you_save      = $you_save_amount_before . $you_save . $you_save_amount_after;

	$old_price_text = $old_price;
	$price_text     = $current_price;
	$you_save_text  = sprintf( $you_save_text, $you_save );

	if ( $output_old_price )
		echo $old_price_before . $old_price_text . $old_price_after . "\n";

	if ( $output_price )
		echo $price_before . $price_text . $price_after . "\n";
	
	if ( $output_you_save )
		echo $you_save_before . $you_save_text . $you_save_after . "\n";
}

/**
 * Получить ID товара
 */
function usam_the_product_id() 
{
	return get_the_ID();
}

function usam_you_save( $args = null )
{
	$defaults = array('product_id' => false, 'type' => 'percentage' );
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	if ( ! $product_id )		
		$product_id = get_the_ID();	

	if ( ! $product_id )
		return 0;
	
	$price = usam_get_product_price( $product_id );
	$old_price = usam_get_product_old_price( $product_id );

	switch( $type )
	{
		case "amount":
			$discount = $old_price - $price;
		break;
		default:
			if ( $old_price == 0 )
				$discount = 0;
			else
				$discount = round( ( $old_price - $price ) / $old_price * 100, 0);
	}
	return $discount;
}

function usam_get_buy_product_button( $product_id, $class = 'button_buy button main-button' )
{	
	echo usam_get_buy_button_and_gotocart( $product_id  );
}

//фильтры товаров
function usam_filter_form( )
{
		
}

function usam_the_checkout_item_error() 
{	
	return '';
}

function usam_the_checkout_item_error_class( $as_attribute = true ) 
{	
	return '';
}

function usam_get_account_transaction_types( )
{
	$types = array( 'friends' =>  __('За приведенных знакомых','usam'), 'birthday' => __('За день рождения','usam'), 'review' => __('За отзыв','usam'), 'socnetwork' => __('За активность в группе','usam'), 'help' => __('За помощь нашему проекту','usam'), 'buy' => __('За покупку','usam'), 'register' => __('За регистрацию','usam'), 'accumulative' => __('По программе &laquo;Накопительные скидки&raquo;','usam'), 'discont' => __('За участие в &laquo;Программе скидок&raquo;','usam'), 'product' => __('За товар','usam'), 'coupon' => __('Использование купона','usam')  );	
	return $types;
}

function usam_get_account_transaction_type( $key )
{
	$types = usam_get_account_transaction_types();
	$result = false;
	if ( isset($types[$key]) )
		$result = $types[$key];
	
	return $result;
}

/**
 * Получить дополнительное описание товара
 */
function usam_the_product_additional_description()
{
	global $post;
	if ( !empty( $post->post_excerpt ) )
		return wpautop($post->post_excerpt,1);
	else
		return false;
}

/**
 * Получить ID миниатюры товара
 */
function usam_the_product_thumbnail_id( $product_id ) 
{
	$thumbnail_id = null;
	if ( has_post_thumbnail( $product_id ) ) 
	{
		$thumbnail_id = get_post_thumbnail_id( $product_id  );
	} 
	else 
	{		
		$attached_images = (array) get_posts( array('post_type' => 'attachment', 'numberposts' => 1, 'post_status' => null, 'post_parent' => $product_id, 'orderby' => 'menu_order', 'order' => 'ASC') );
		if ( !empty( $attached_images ) )
			$thumbnail_id = $attached_images[0]->ID;
	}
	return $thumbnail_id;
}

/**
 * Отображает список желаний
 */
function usam_desired_product()
{	
	
}

function usam_get_chat_dialogs_query( $query )
{
	require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
	return usam_get_chat_dialogs( $query );
}

/**
 *  Проверяет текущий продукт является внешним
 */
function usam_is_product_external( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();

	$external_link = usam_product_external_link( $product_id ); // Получить ссылку на внешний товар
	if ( $external_link )
		return true;
	else
		return false;
}

/**
 * Получить внешнюю ссылку товара
 */
function usam_product_external_link( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();

	$external_link = usam_get_product_meta($product_id, 'webspy_link' );
	if ( $external_link ) 
		return esc_url($external_link);
	return false;
}


function usam_range_price_slider()
{		
	global $wp_query, $usam_query;
	
	$type_price = usam_get_customer_price_code();			
	if ( empty($usam_query) )
	{
		$new_query = $wp_query->query_vars;
		if ( isset($new_query['pagename']) )
			unset($new_query['pagename']);	
		if ( isset($new_query['name']) )
			unset($new_query['name']);	
		$new_query['post_type'] = 'usam-product';
		if ( isset($new_query['keyword']) )
			$new_query['s'] = $new_query['keyword'];
	}
	else
		$new_query = $usam_query->query_vars;		
		
	$new_query["orderby"]  = 'price';		
	$new_query["type_price"]  = usam_get_customer_price_code();
	$new_query["order"]    = 'DESC';			
	$new_query['posts_per_page'] = 1;
	$new_query['no_found_rows'] = true;	
	$new_query['cache_product'] = false;
	$new_query['fields'] = 'ids';		
	if ( !empty($new_query['price_meta_query']) )
		unset($new_query['price_meta_query']);		
	
	$posts = get_posts( $new_query );
	if ( empty($posts) || count($posts) > 2 )
		return false;
	
	$max_price = usam_get_product_price($posts[0], $type_price );	
	$new_query["order"] = 'ASC';
	$posts = get_posts( $new_query );	
	
	if ( empty($posts[0]) )
		$min_price = 0;
	else
	{		
		$min_price = usam_get_product_price($posts[0], $type_price );	
	}
	if( !empty($_GET['prices']) )
	{		
		$prices = array_map('intval', explode('-', $_REQUEST['prices']));
		$min_price_value = $prices[0];
		$max_price_value = $prices[1];		
	}
	else
	{
		$min_price_value = $min_price;
		$max_price_value = $max_price;
	}		
	$min_price_value = round($min_price_value);
	$max_price_value = round($max_price_value);	
	echo "<div class='price_range_slider'>";		
	echo usam_range_slider( $min_price, $max_price, $min_price_value, $max_price_value );
	echo "</div>";
}
	
	
function usam_range_slider( $min_price, $max_price, $min_price_value, $max_price_value, $number_cells = 4 )
{		
	$out = "		
	<div class='range_slider js-range-slider'>	
		<input type='number' min='$min_price' value='$min_price_value' class='option-input js-range-number-min'><span class='range_slider__dash'>-</span>
		<input type='number' max='$max_price' value='$max_price_value' class='option-input js-range-number-max'>
		<span class='prs_min'>$min_price_value</span>
		<span class='prs_max'>$max_price_value</span>
		<div class='js-range-slider-scale'></div>		
		<div class ='prs_grid'>		
			<span class='prs_grid-pol' style='left: 0%'></span>";			
			$d = ($max_price-$min_price)/$number_cells;	
			$max_price = round($max_price);
			$left = 0;		
			$margin = '-1.07407';			
			$range = $min_price;
			$p = 100/$number_cells;
			for ($i=0; $i<$number_cells; $i++)
			{
				$range = round($d * $i + $min_price);	
				$out .= "<span class='prs_grid-text js-grid-text-{$i}' style='left:{$left}%; visibility:visible; margin-left:{$margin}%;'>{$range}</span>";
				$left += 5;
				$end = $left + 15;
				for ( $j=$left; $j<=$end; )
				{		
					$out .= "<span class='prs_grid-pol small' style='left:{$j}%'></span>";
					$j += 5;
				} 
				$left += 20;
				$out .= "<span class='prs_grid-pol' style='left:{$left}%'></span>";
				$margin = '-2.07407';									
			}		
			$out .= "<span class='prs_grid-text js-grid-text-4' style='left:100%; margin-left:-5.18519%;'>$max_price</span></div></div>";
	return $out;
}

function usam_total_reviews( $page_id = null )
{	
	if ( $page_id == null )
	{
		global $post;
		$page_id = $post->ID;
	}
	$query = array( 'page_id' => $page_id, 'status' => 1 );
	
	require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
	$reviews = new USAM_Customer_Reviews_Query( $query );	
	$reviews->get_results();
	return $reviews->get_total();
}

function get_product_custom( $product_id )
{
	$product_data = get_post_custom( $product_id );
	$product_meta = array();
	foreach ( $product_data as $key => $meta ) 
		$product_meta[$key] = maybe_unserialize( $product_data[$key][0] );	
	return $product_meta;
}

/**
* Получает изображение бренда или возвращает ложь
*/
function usam_brand_image( $brand_id ) 
{
	return usam_taxonomy_image( $brand_id );	
}

function usam_get_img_shop_logo( ) 
{
	$thumbnail = usam_shop_logo( );	
	$html = '<img class="shop_logo" src="'.$thumbnail.'" alt ="'.get_option( 'blogname' ).'">';
	return $html;	
}

/**
* Получает изображение категории или возвращает ложь
*/
function usam_category_image( $category_id ) 
{
	return usam_taxonomy_image( $category_id );	
}

function usam_category_thumbnail( $category_id ) 
{
	$term = get_term( $category_id, 'usam-category' );	
	return usam_taxonomy_thumbnail( $category_id, 'full', $term->name );
}

/**
* Получить ссылку на категорию
*/
function usam_category_url( $category_id )
{
  return get_term_link( $category_id, 'usam-category');
}

/**
* Получить ссылку на бренд
*/
function usam_brand_url( $brand_id )
{
  return get_term_link( $brand_id, 'usam-brands' );
}

function usam_get_subscribers_list( )
{
	require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );	
	$results = [];
	$lists = usam_get_mailing_lists();	
	foreach ( $lists as $list )
		$results = (array)$list;
	return $results;	
}


function usam_get_payment_document_status_name( $status_number ) 
{
	return usam_get_object_status_name( $status_number, 'payment' );
}

/**
 * Виджет корзины
 * Может использоваться для отображения Корзины за исключением исключением доставки, налогов.
 */
function usam_cart_total_widget( $shipping = true, $bonus = true ) 
{
	$cart = USAM_CART::instance();
	$subtotal = $cart->get_property( 'subtotal' );
	
	if ( $shipping )
		$subtotal += $cart->get_property( 'shipping' );   
	
	return usam_get_formatted_price( $subtotal );  
}

function usam_get_formatted_price_in_currency( $price, $args = null )
{	
	return usam_get_formatted_price($price, $args);
}

// Описание название страницы
function usam_page_name_html() 
{		
	echo '<h1 class ="usam_page_name">'.usam_get_page_name().'</h1>';
}

// Описание: Получить название страницы
function usam_get_page_name() 
{	
	global $post, $wp_query;		

	$obj = $wp_query->get_queried_object();	
	$name = '';		
	if( is_single() )		
	{ 
		$name = $obj->post_title;
	}		
	elseif( !empty($obj->term_id) )		
	{				
		$name = $obj->name;
	}	
	elseif ( !empty($obj->post_type) && $obj->post_type == 'page' )
	{		
		$name = $obj->post_title;		
		if ( isset($wp_query->query['usam-category']) )
		{
			$cat_term = get_term_by('slug',$wp_query->query_vars['usam-category'], 'usam-category');
			$name .= ' - '.$cat_term->name;
		}
	}	
	return $name;
}

function usam_get_amount_basket()
{
	$cart = USAM_CART::instance();	
	return count($cart->get_products());
}



function usam_display_errors( $errors ) 
{	
	if( !empty($errors))
	{ 
		?>
		<div class='usam_message message_error'>
			<?php foreach( $errors as $error ){ ?>
				<p class='validation-error'><span><?php echo  __('Ошибка', 'usam').': '; ?></span><?php echo $error; ?></p>
			<?php } ?>
		</div>
		<?php
	}
}

function usam_display_cross_sells()
{ 
	
}

function usam_search_widget( $widget_id = 100 )
{ 		
	
}




function usam_brand_id_post( $post_id = null )
{	
	global $post;
	if (empty($post_id))
		$post_id = $post->ID;
	$term_list = wp_get_post_terms($post_id, 'usam-brands', ["fields" => "ids"]);
	if ( empty($term_list) )
		return 0;
	else
		return $term_list[0];
}

function usam_get_the_brand_id( $slug, $type = 'name')
{	
	$brand = get_term_by( $type, $slug, 'usam-brands');
	return empty( $brand ) ? false : $brand->term_id;
}


/**
* Существует налог корзины
*/
function usam_cart_tax_enabled( )
{
	$cart = USAM_CART::instance();	
	$product_taxes = $cart->get_product_taxes( );  
	if( !empty($product_taxes) )
		return true;
	else
		return false;
}

/**
* Показать формы для ввода купонов
*/
function usam_uses_coupons() 
{	
	return get_site_option( 'usam_uses_coupons', 1 );
}


function usam_uses_bonuses() 
{	
	return get_site_option( 'usam_uses_bonuses', 1 );
}


function usam_get_product_attributes_slug( $product_id )
{	
	$object_type = 'usam_product_attributes_slug';
	$results = wp_cache_get($product_id, $object_type );
	if( $results === false )
	{	
		$product_attributes = usam_get_product_attributes_display( $product_id );		
		wp_cache_set( $product_id, $results, $object_type );
	}	
	return $results;
}


function usam_get_basket_number_products()
{
	$cart = USAM_CART::instance();	
	return count($cart->get_products());
}

function usam_get_basket_number_items()
{
	$cart = USAM_CART::instance();	
	return $cart->get_number_products();
}



function usam_get_field( $attributes, $field, $error_code = '' ) 
{
	$default = ['id' => "field-".$field->code, 'class' => '', 'name' => 'fields['.$field->code.']', 'value' => ''];
	$attributes = array_merge( $default, $attributes );		
	$field->mandatory = $field->mandatory && !is_admin()?1:0;
	if  ( $field->mandatory )
		$attributes['required'] = 'required';		
	
	$attr_str = ''; 
	$results = '';
	switch ( $field->field_type ) 
	{
		case "location":				
			
		break;
		case "rating":		
			$attributes['class'] .= " option-input js-{$field->field_type}";
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}
			$results = usam_get_rating( $attributes['value'] )."<input type='hidden' $attr_str/>";
		break;
		case "location_type":					
			$types = usam_get_types_location();
			$code = '';
			foreach ( $types as $type ) 
			{
				if ( stripos($field->code, $type->code) )
				{
					$code = $type->code;
					break;
				}					
			}
			if ( $code )
			{				
				$locations = usam_get_locations( array( 'code' => $code ) );
				$attributes['class'] .= ' ';		
				foreach ( $attributes as $name => $v ) 
				{
					if ( $name != 'value' )
						$attr_str .= " $name='$v'";	
				}	
				$results = "<div class='option-select'><select $attr_str data-placeholder='".__('Выберете из списка...','usam')."'>
				<option value=''>--".__('Не выбрано','usam')."--</option>";	
				foreach ( $locations as $item )
				{		
					$results .= "<option value='".$item->id."' ".selected($item->id, $attributes['value'], false).">".esc_html( $item->name )."</option>";
				}
				$results .= "</select></div>";	
			}			
		break;		
		case 'one_checkbox':
			$attributes['class'] .= ' option-input';
			foreach ( $attributes as $name => $v ) 
			{
				if ( $name != 'value' )
					$attr_str .= " $name='$v'";	
			}				
			$results = "<input $attr_str type='checkbox' value='1' ". checked($attributes['value'], 1, false)."/>";
		break;	
		case "checkbox":
			$options = usam_get_property_metadata($field->id, 'options');	
			if ( !empty($options) ) 
			{
				$values = array();
				if ( is_array($attributes['value']) )
				{
					foreach ( $attributes['value'] as $v )
						$values[] = $v->meta_value;	
				}
				$attributes['class'] .= ' option-input';
				foreach ( $attributes as $name => $v ) 
				{
					if ( $name == 'name' )
						$attr_str .= " $name='{$v}[]'";	
					elseif ( $name != 'value' )
						$attr_str .= " $name='{$v}'";	
				}				
				$results = "<div class='option-inputs'>";				
				foreach ( $options as $option ) 
				{
					$checked = in_array($option['code'], $values) ? checked( 1, 1, false ) : '';
					$results .= "<label><input $attr_str $checked type='checkbox' value='".esc_attr__($option['code'])."'/>".esc_html__( $option['name'] )."</label>";
				}
				$results .= "</div>";
			}
		break;						
		case "select":						
			$options = usam_get_property_metadata($field->id, 'options');
			if ( !empty($options) ) 
			{			
				foreach ( $attributes as $name => $v ) 
				{
					if ( $name != 'value' )
						$attr_str .= " $name='$v'";	
				}	
				$results = "<div class='option-select'><select $attr_str>";
				foreach ( $options as $option )
				{
					$results .= "<option ".selected($option['code'], $attributes['value'], false)." value='".esc_attr__( $option['code'] )."'>".esc_html__( $option['name'] )."</option>";
				}
				$results .= "</select></div>";
			}
		break;
		case "radio":
			$options = usam_get_property_metadata($field->id, 'options');	
			if ( !empty($options) ) 
			{
				$attributes['class'] .= ' option-input';
				foreach ( $options as $option )		
				{
					if ( !empty($option['group']) )
					{
						$attributes['class'] .= ' js-group-show';
						break;
					}
				}												
				$results = "<div class='option-inputs'>";							
				foreach ( $options as $option )
				{			
					$attr_str = ''; 
					foreach ( $attributes as $name => $v ) 
					{
						if ( $name != 'value' )
						{ 
							$attr_str .= " $name='$v'";	
							if ( $name == 'class' )
							{
								$attr_str .= (!empty($option['group'])?' group-show="'.$option['group'].'"':'');
							}
						}
					}					
					$results .= "<label><input type='radio' $attr_str ".checked($option['code'], $attributes['value'], false)." value='".esc_attr__( $option['code'] )."'/>".esc_html__( $option['name'] )."</label>";
				}
				$results .= "</div>";
			}
		break;	
		case "click_show":		
			$hide = $attributes['value']?'':'hide';
			$attributes['class'] .= " option-input $hide js-{$field->field_type}";
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}			
			$results .= "<textarea $attr_str rows='3' cols='40' maxlength='255'>".esc_html( $attributes['value'] )."</textarea>";
		break;
		case "address":			
		case "textarea":			
			$attributes['class'] .= " option-input js-{$field->field_type}";
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}			
			$results .= "<textarea $attr_str rows='3' cols='40' maxlength='255'>".esc_html( $attributes['value'] )."</textarea>";
		break;
		case 'integer':
			$attributes['data-mask'] = "?99999999999999999999999999999999999999999999999999999999999999999999999999999999999999999";
			$attributes['class'] .= ' option-input';
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}			
			$results .= "<input type='text' $attr_str autocomplete='off'/>";
		break;	
		case "files":
			$value = maybe_unserialize($attributes['value']);						
			$results .= '';				
		break;
		case 'shops':		
			$storages = usam_get_storages(['issuing' => 1]);
			foreach ( $attributes as $name => $v ) 
			{
				if ( $name != 'value' )
					$attr_str .= " $name='$v'";	
			}	
			$results = "<div class='option-select'><select $attr_str>";
			$results .= "<option ".selected(0, $attributes['value'], false)." value='0'>".__('Не выбран','usam')."</option>";
			foreach ( $storages as $storage ) 
			{
				$results .= "<option ".selected($storage->id, $attributes['value'], false)." value='".esc_attr__( $storage->id )."'>".esc_html__( $storage->title )."</option>";
			}
			$results .= "</select></div>";		
		break;
		case "file":					
			$results .= '';
		break;
		case "date":
			$attributes['value'] = $attributes['value'] ? date("d.m.Y", strtotime($attributes['value']) ) : '';
			$attributes['placeholder'] = isset($attributes['placeholder']) ? $attributes['placeholder'] : __('дд.мм.гггг','usam');
			$attributes['class'] .= ' option-input js-date-picker';
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}
			$results .= "<input type='text' $attr_str autocomplete='off' data-mask='99-99-9999' maxlength='10'/>";
			wp_enqueue_script( 'jquery-ui-datepicker' );
		break;	
		case "button":	
			$url = usam_get_property_metadata($field->id, 'url');
			$button_name = usam_get_property_metadata($field->id, 'button_name');
			$results .= "<a href='{$url}&id=".$attributes['data-id']."' class='button'>{$button_name}</a>";
		break;
		case "none":	
			
		break;
		case "postcode":	
		case 'mobile_phone':	
		case 'number':
		case "phone":	
		case "text":		
		case "email":		
		case "company":		
		default:				
			if  ( $field->mask )
				$attributes['data-mask'] = $field->mask;
			$attributes['class'] .= " option-input js-{$field->field_type}";
			foreach ( $attributes as $name => $v ) 
			{
				$attr_str .= " $name='$v'";	
			}			
			$results .= "<input type='text' $attr_str/>";
		break;
	}
	if  ( $field->mandatory )
		$results .= "<div class='hidden message_error'><div class='validation-error'>".__("Это нужно обязательно заполнить","usam")."</div></div>";
	$error = '';	
	switch ( $error_code ) 
	{
		case "incorrect":									
			$error = sprintf(__('Пожалуйста, введите корректный <span class="usam_error_msg_field_name">%s</span>.', 'usam'), esc_attr($field->name) );
		break;
		case "not_completed":									
			$error = sprintf(__('Пожалуйста, введите <span class="usam_error_msg_field_name">%s</span>.', 'usam'), esc_attr($field->name) );		
		break;
		case "valid_email":									
			$error = __('Пожалуйста, введите корректную электронную почту.', 'usam');		
		break;
		case "valid_phone":									
			$error = __('Пожалуйста, введите корректный телефон.', 'usam');		
		break;
		case "valid_mobile_phone":									
			$error = __('Пожалуйста, введите корректный мобильный телефон.', 'usam');		
		break;		
		case "valid_location":									
			$error = __('Пожалуйста, введите корректное местоположение.', 'usam');		
		break;		
	}	
	if ( $error )
		$results .= "<div class='message_error'><div class='validation-error'>".$error."</div></div>";	
	return $results;
}

function usam_check_order_is_completed()
{
	
}

function usam_get_menu_terms( $args = [] )
{
	return usam_get_terms( $args );
}




// Заказ в корзину
function usam_load_order_in_basket( $order_id ) 
{
	$order_data = usam_get_order( $order_id );
	$products = usam_get_products_order( $order_id );	
	$bonus = usam_get_used_bonuses_order( $order_id );
	$properties = array( 'bonus' => $bonus, 'type_price' => $order_data['type_price'] );	
	$properties['location'] = usam_get_order_metadata( $order_id, 'shippinglocation' );	
	$properties['coupon_name'] = usam_get_order_metadata( $order_id, 'coupon_name');
	
	$cart = USAM_CART::instance();
	$cart->set_properties( $properties );
	$cart->empty_cart();
	foreach ( $products as $product ) 
	{
		$parameters['unit_measure'] = $product->unit_measure;
		$parameters['quantity'] = $product->quantity;	
		$cart->add_product_basket( $product->product_id, $parameters );		
	}	
	$cart->recalculate();
	return $cart;
}

//Проверить существует ли запрошенный заказ
function usam_show_details_order() 
{
	if ( !empty($_GET['id']) )
	{
		$user_id = get_current_user_id();
		$order_id = absint($_GET['id']);		
		$order = usam_get_order( $order_id );
		if ( $order['user_ID'] == $user_id )
			return true;		
	}
	return false;
}

function usam_show_details_event( $roles = array() ) 
{
	if ( !empty($_GET['id']) )
	{
		$user_id = get_current_user_id();
		$id = absint($_GET['id']);		
		$event = usam_get_event( $id );	
		if ( !empty($event) )
		{
			if ( !empty($roles) )
			{
				foreach ( $roles as $role )		
				{
					if ( usam_check_current_user_role($role) )
						return true;
				}	
			}
			if ( $event['user_id'] == $user_id )
				return true;
		}
	}
	return false;
}

function usam_show_details_document( $roles = array() )
{
	if ( !empty($_GET['id']) )
	{
		$user_id = get_current_user_id();
		$id = absint($_GET['id']);		
		if ( !empty($roles) )
		{
			foreach ( $roles as $role )		
			{
				if ( usam_check_current_user_role($role) )
					return true;
			}	
		}		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );			
		$document = usam_get_documents( array( 'include' => $id, 'user_id' => $user_id, 'cache_results' => true ) );		
		if ( !empty($document) )
			return true;		
	}
	return false;
}

function usam_go_back_user_account_tab( $tab = null, $args = array() ) 
{
	if ( $tab === null )
	{
		$account_current = usam_your_account_current_tab();	
		$tab = $account_current['tab'];
		$subtab = $account_current['subtab'];
	}
	$url = usam_get_user_account_url( $tab );
	$url = add_query_arg( $args, $url );
	?><a class="button go_back" href='<?php echo $url; ?>'><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></a><?php
}



function usam_get_product_price_per_batch( $product_id, $code_price = null, $unit_measure = null, $id_main_site = false ) 
{	
	return usam_get_product_price( $product_id, $code_price, $unit_measure, $id_main_site );	
}

function usam_get_product_old_price_per_batch( $product_id, $code_price = null, $unit_measure = null, $id_main_site = false ) 
{	
	return usam_get_product_old_price( $product_id, $code_price, $unit_measure, $id_main_site );
}

final class USAM_Your_Account
{
	private static $_instance = null;
	private static $current_tab_id = '';
	private static $current_tab_menu_id = '';	
	private static $name_group_tab = '';
	private static $tab = array();
	private static $tabs = array();
	private static $menu_group = array();
	public function __construct() 
	{  
		if ( self::$current_tab_id == null )
			$this->init();
	}
	
	public static function instance() 
	{ 
		if ( is_null( self::$_instance ) )
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}		
	
	public function init()
	{	
		$menu_account = usam_get_menu_your_account();
		self::$menu_group = apply_filters( 'usam_your_account_menu', $menu_account['menu_group'] );
		self::$tabs = apply_filters( 'usam_your_account_sub_menu', $menu_account['tabs'] );
	 
		$account_current = usam_your_account_current_tab();	
		$this->set_current_tab( $account_current['tab'] );		
	}
	
	public function set_current_tab( $tab_id = null )
	{		
		if ( empty(self::$tabs) )
			return;
		
		foreach ( self::$menu_group as $menu )
		{ 
			if ( !empty(self::$tabs[$menu['slug']]) )
			{
				foreach ( self::$tabs[$menu['slug']] as $submenu )
				{			
					if ( empty($tab_id) )
					{
						self::$name_group_tab = $menu['slug'];
						self::$current_tab_menu_id = $submenu['slug'];	
						self::$current_tab_id = $submenu['slug'];
						break 2;
					}
					elseif ( $submenu['slug'] == $tab_id || !empty($submenu['sub']) && in_array($tab_id, $submenu['sub']) )
					{
						self::$name_group_tab = $menu['slug'];
						self::$current_tab_menu_id = $submenu['slug'];	
						self::$current_tab_id = $tab_id;	
						self::$tab = $submenu;	
						break 2;
					}		
				}
			}
		}			
		if ( empty(self::$current_tab_id) ) 
			$this->set_current_tab( );
	}		
	
	public function display_header_tab() 
	{
		$count_menu_group = count(self::$menu_group);
		?>	
		<div class="profile_header">	
			<?php if ( $count_menu_group > 1 ) { ?>					
				<ul id="usam_accordion" class="usam_your_account_menu">
				<?php					
				foreach ( self::$menu_group as $menu )
				{
					if ( $menu['menu_title'] == '' )
						continue;
					?>
						<li class="usam_menu <?php echo (self::$name_group_tab == $menu['slug'])?'current_menu':''; ?> usam_menu-<?php echo $menu['slug']; ?>">
							<span class="usam_menu_name"><?php echo $menu['menu_title']; ?></span>		
							<?php 
							if ( !empty(self::$tabs[$menu['slug']]) ) { ?>
								<ul class="usam_your_account_sub_menu">
								<?php 
								foreach ( self::$tabs[$menu['slug']] as $sub_menu )
								{
									if ( $sub_menu['menu_title'] == '' )
										continue;
									?>
									<li class="usam_submenu <?php echo (self::$current_tab_menu_id == $sub_menu['slug'])?'current_submenu':''; ?> usam_submenu-<?php echo $sub_menu['slug']; ?>">
										<a class = "usam_submenu_link-<?php echo $sub_menu['slug']; ?>" href="<?php echo usam_get_user_account_url( $sub_menu['slug'] ); ?>"><?php echo $sub_menu['menu_title']; ?></a>
									</li>
									<?php
								}
								?>
								</ul>
								<?php 
							}
							?>
						</li>
					<?php
				}
				?>
				</ul>
			<?php } else { ?>
				<ul class="usam_your_account_sub_menu">
					<?php					
					foreach ( self::$menu_group as $menu )
					{				
						foreach ( self::$tabs[$menu['slug']] as $sub_menu )
						{
							if ( $sub_menu['menu_title'] == '' )
								continue;
							?>
							<li class="usam_submenu <?php echo (self::$current_tab_menu_id == $sub_menu['slug'])?'current_submenu':''; ?> usam_submenu-<?php echo $sub_menu['slug']; ?>">
								<a class = "usam_submenu_link-<?php echo $sub_menu['slug']; ?>" href="<?php echo usam_get_user_account_url( $sub_menu['slug'] ); ?>"><?php echo $sub_menu['menu_title']; ?></a>
							</li>
							<?php
						}
					}
					?>
				</ul>
			<?php } ?>
		</div>		
		<?php
	}	
	
	public function is_vue() 
	{
		return ['my-profile', 'my-company', 'my-referral', 'my-contacting', 'my-products', 'my-orders', 'seller-orders', 'subscribe'];
	}
	
	public function display_content_tab( $title = true ) 
	{		
		echo '<div id="'.self::$current_tab_id.'" class="profile_content profile_'.self::$current_tab_id.'" '.(in_array(self::$current_tab_id, $this->is_vue())?'v-cloak':'').'>';			
		usam_include_template_file( self::$current_tab_id, 'your-account' );
		$page = get_page_by_path( self::$current_tab_id );
		if ( !empty($page) )
			echo "<div class='profile_post_content'>".apply_filters( 'the_content', get_the_content( null, null, $page ) )."</div>";
		echo '</div>';
	}
}


function usam_products_view_type()
{	
	$views = get_option('usam_product_views', ['grid', 'list']);
	if ( count($views) > 1 )
	{
		$view_type = usam_get_display_type();				
		add_action( 'wp_footer', array('USAM_Assets', 'product_filter') );
		?>
		<div class="products_view_type"><?php
		foreach( usam_get_site_product_view() as $key => $title )	
		{	
			if( in_array($key,$views) )
			{
				?><span class="products_view_type__option grid js_option_display <?php echo $view_type==$key?'active':''; ?>" view_type="<?php echo $key; ?>" title="<?php echo sprintf(__('Просмотр %s','usam'), $title);?>"><?php echo usam_get_svg_icon($key) ?></span><?php
			}
		}
		?></div><?php
	}
}

function usam_quantity_of_products( $args = array() )
{	
	
}



// сортировка по цене, имени
function usam_sortby_form()
{
	global $wp_query;	
	
	if ( empty($wp_query) )
		return false;

	$sorting_options = usam_get_user_product_sorting_options();
	if ( empty($sorting_options) )
		return false;
	
	$orderby = usam_get_customer_orderby();
	?>
	<div class="option-select product_sort_options">
		<select id ="usam_products_sort" name="orderby">
		<?php
		foreach( $sorting_options as $key => $value )
		{			
			?><option <?php selected($orderby, $key) ?> value='<?php echo $key; ?>'><?php echo $value; ?></option><?php
		}	
		?>
		</select>	
	</div>
	<?php
	add_action( 'wp_footer', array('USAM_Assets', 'product_filter') );
}


/**
  Функция содержание виджет "диапазон цен". Отображает список ценовых диапазов.
*/
function usam_price_range( $args = null ) 
{
	global $wpdb;	
	$type_price = usam_get_customer_price_code();
	$args = wp_parse_args( (array)$args, array() );
	
	$product_page = usam_get_url_system_page('products-list');
	$result = $wpdb->get_results( "SELECT DISTINCT meta_value AS `price` FROM ".USAM_TABLE_PRODUCT_PRICE." AS `m` WHERE `meta_key`='price_".$type_price."' ORDER BY `price` ASC", ARRAY_A );
	
	if ( $result != null ) 
	{
		sort( $result );
		$count = count( $result );
		$price_seperater = ceil( $count / 6 );
		for ( $i = 0; $i < $count; $i += $price_seperater ) {
			$ranges[] = round( $result[$i]['price'], -1 );
		}
		$ranges = array_unique( $ranges );
		
		$final_count = count( $ranges );
		$ranges = array_merge( array(), $ranges );
		echo '<ul>';
		for ( $i = 0; $i < $final_count; $i++ ) 
		{
			$j = $i;
			if ( $i == $final_count - 1 )
				echo "<li><a href='" . esc_url(add_query_arg('prices', $ranges[$i].'-', $product_page )) . "'>".__('до', 'usam')." " . usam_get_formatted_price( $ranges[$i] ). "</a></li>";
			else if ( $ranges[$i] == 0 )
				echo "<li><a href='" . esc_url(add_query_arg('prices', '-'.($ranges[$i+1]-1), $product_page )) . "'>".__('после', 'usam')." " . usam_get_formatted_price( $ranges[$i + 1] ). "</a></li>";
			else 
				echo "<li><a href='".esc_url(add_query_arg('prices', $ranges[$i]."-".($ranges[$i + 1]-1), $product_page ))."'>".usam_get_formatted_price( $ranges[$i] )." - ".usam_get_formatted_price( ($ranges[$i + 1]-1) )."</a></li>";	
		}
		echo "<li><a href='" . esc_url(add_query_arg('prices', 'all', $product_page ) ) . "'>" . __('Показать все', 'usam') . "</a></li>";
		echo '</ul>';
	}	
}


/**
* Получить следующий товар корзины
*/
function usam_have_cart_items() 
{
	$cart = USAM_CART::instance();
	static $cache = false;
	if ( $cache === false )
	{		
		$cache = true;
		$poducts = $cart->get_products();   	
		$post_ids = array();
		foreach( $poducts as $key => $product )
		{
			$post_ids[] = $product->product_id;
		}
		if ( !empty($post_ids) )
			usam_get_products( array( 'post__in' => $post_ids ), true );		
	}
   return $cart->have_cart_items();
}

function usam_the_cart_item() 
{
	$cart = USAM_CART::instance();
	return $cart->the_cart_item();
}

/**
* Получить текущий товар корзины
*/
function usam_the_cart_item_key() 
{
   $cart = USAM_CART::instance();
   return $cart->product->id;
}

function usam_cart_item_sku( ) 
{
	$cart = USAM_CART::instance();	
	return usam_get_product_meta($cart->product->product_id, 'sku', false);
}

 /**
* Получить ID текущего товара в корзине
*/
function usam_cart_item_product_id() 
{
	$cart = USAM_CART::instance();		
	return isset($cart->product)?$cart->product->product_id:0;
}

function usam_get_cart_item_property( $property ) 
{
	$cart = USAM_CART::instance();	
	$result = usam_get_product_property( $cart->product->product_id, $property, false);
	switch ( $property ) 
	{
		case 'weight' :	
			$result = $result * $cart->product->quantity;
		break;
		case 'volume' :	
			$result = $result * $cart->product->quantity;
		break;	
	}
	return $result;
}

 /**
* Получить название текущего товара в корзине
*/
function usam_cart_item_name( ) 
{
	$cart = USAM_CART::instance();	
	if ( usam_is_multisite() && !is_main_site() )
	{    //Загрузить перевод
		$product_id = usam_get_post_id_multisite( $cart->product->product_id );
		$title = get_the_title( $product_id ); 
	}
	else
		$title = $cart->product->name;

	$title = apply_filters( 'the_title', $title, $cart->product->product_id );
	return apply_filters( 'usam_cart_item_name', $title, $cart->product->product_id );
}


function usam_get_cart_item_weight( $out_unit = '', $display_name_weight_units = false ) 
{
	$cart = USAM_CART::instance();			
	$weight = usam_get_product_weight( $cart->product->product_id, $out_unit, false )*$cart->product->quantity*$cart->product->unit;
	$weight = number_format( $weight, 2 );
	if ( $display_name_weight_units )
		$weight = $weight.' '.usam_get_name_weight_units( $weight );
	
	return $weight;
}

function usam_get_cart_item_volume( $out = 'm', $precision = 2 ) 
{
	$cart = USAM_CART::instance();	
	$weight = usam_get_product_volume( $cart->product->product_id, $out )*$cart->product->quantity*$cart->product->unit;	
	if ( $precision !== false )
		return number_format( $weight, $precision );
	else
		return $weight;
}


function usam_get_cart_product_remaining_stock()
{
	$cart = USAM_CART::instance();	   
	return usam_product_remaining_stock( $cart->product->product_id );	
}

function usam_get_cart_product_unit_name()
{
	$cart = USAM_CART::instance();	   
	$unit = usam_get_unit_measure( $cart->product->unit_measure );
	if ( empty($unit) )		
		$unit = usam_get_unit_measure('thing');		
	return $unit['title'];
}

function usam_get_cart_product()
{
	$cart = USAM_CART::instance();	   
	return $cart->product;
}

function usam_get_cart_product_unit()
{
	$cart = USAM_CART::instance();		
	return usam_get_product_unit( $cart->product->product_id, $cart->product->unit_measure );
}

/**
* Получить количество текущего товара в корзине
*/
function usam_cart_item_quantity() 
{
	$cart = USAM_CART::instance();
	return usam_get_formatted_quantity_product( $cart->product->quantity, $cart->product->unit_measure );
}

/**
* Цена товара в корзине
*/
function usam_cart_item_price( $forDisplay = true ) 
{
	$cart = USAM_CART::instance();	
	$total = $cart->product->total;		
	if( $forDisplay )   
		return usam_get_formatted_price( $total );
	else
		return $total;   
}

/**
 * Получить цену товара 
 */
function usam_cart_single_item_price($forDisplay = true)
 {
	$cart = USAM_CART::instance();	
	if( $forDisplay )
		return usam_get_formatted_price( $cart->product->price );
	else
		return $cart->product->price;
}

/**
 * Получить скидку 
 */
function usam_cart_single_item_discont($forDisplay = true)
{
	$cart = USAM_CART::instance();	
	if( $cart->product->old_price )
		$price = $cart->product->old_price - $cart->product->price;
	else
		$price = 0;
	
	if( $forDisplay )
		return usam_get_formatted_price( $price );
	else
		return $price;
}

function usam_cart_single_item_oldprice($forDisplay = true)
{
	$cart = USAM_CART::instance();	
	if( $cart->product->old_price )
		$price = $cart->product->old_price;
	else
		$price = $cart->product->price;
	
	if( $forDisplay )
		return usam_get_formatted_price( $price );
	else
		return $price;
}

/**
 * Получить налог товара
 */
function usam_get_cart_single_item_tax( $forDisplay = true )
 {
	$cart = USAM_CART::instance();	
	if( $forDisplay )
		return usam_currency_display( $cart->product->tax, array( 'decimal_point' => true ));
	else
		return $cart->product->tax;
}

/**
* Получить ссылку на товар
*/
function usam_cart_item_url() 
{
	$cart = USAM_CART::instance();	
	$product_id = usam_get_post_id_multisite( $cart->product->product_id );
	$product_url = usam_product_url( $product_id );
	return apply_filters( 'usam_cart_item_url', $product_url, $product_id );
}

/**
* возвращает URL к изображению для продукта в корзине
*/
function usam_cart_item_image( $size = 'small-product-thumbnail' )
{ 
	$cart = USAM_CART::instance();	
	static $thumbnail_cache = false;   
	if ( $thumbnail_cache == false )
	{ 		
		$thumbnail_cache = true;
		$thumb_ids = array();	
		foreach( $cart->get_products() as $product )	
		{ 
			if ( $id = get_post_thumbnail_id( $product->product_id ) )
				$thumb_ids[] = $id;
		}				
		_prime_post_caches( $thumb_ids, false, true );
	} 
	$product_id = usam_get_post_id_multisite( $cart->product->product_id );
	return "<img src='".usam_get_product_thumbnail_src($product_id, $size)."' alt='".usam_cart_item_name()."' width='100' height='100'/>";
}

function usam_get_products_license_contracts( ) 
{
	$cart = USAM_CART::instance();	
	$products = $cart->get_products();   	
	$ids = array();
	foreach( $products as $key => $product )
	{
		$license_agreement = usam_get_product_meta($product->product_id, 'license_agreement');
		if ( $license_agreement )
			$ids[] = $license_agreement;
	}
	if ( !empty($ids) )		
		$agreements = (array)get_posts(['post_type' => 'usam-agreement', 'numberposts' => -1, 'post__in' => $ids, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC']);		
	else
		$agreements = array();
	return $agreements;
}

/**
* Вес всей корзины
*/
function usam_cart_weight_total( $out_unit = 'kg', $precision = 2 ) 
{
	$cart = USAM_CART::instance();
	$weight = $cart->calculate_total_weight();
	$weight = usam_convert_weight( $weight, $out_unit );	
	if ( $precision !== false )
		return number_format( $weight, $precision );
	else
		return $weight;
}

function usam_cart_volume_total( $out_unit = 'm', $precision = 2 ) 
{
	$cart = USAM_CART::instance();
	$volume = $cart->calculate_total_volume();
	$volume = usam_convert_volume( $volume, $out_unit );
	if ( $precision !== false )
		return number_format( $volume, $precision );
	else
		return $volume;
}


/*
 * Бонусы корзины
 */
function usam_bonuses_cart( $forDisplay = true ) 
{
	$cart = USAM_CART::instance();	
	$discount = $cart->get_property( 'bonuses' );   
	if($forDisplay == true)
		$discount = usam_get_formatted_price( $discount);	
	return $discount;
}


/*
 * Скидка корзины
 */
function usam_discount_cart( $forDisplay = true ) 
{
   $cart = USAM_CART::instance();	

   $discount = $cart->get_property( 'products_discount' );
   if( $forDisplay == true )
		$discount = usam_get_formatted_price( $discount);
   return $discount;
}

function usam_coupons_code()
{
	$cart = USAM_CART::instance();
	$coupon_name = $cart->get_property( 'coupon_name' );
	return $coupon_name;
}

function usam_get_product_new_rating( $product_id = null )
{
	global $wpdb;
	
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$previous_vote = 1;
	if ( isset($_COOKIE['prating'][$product_id]) &&  is_numeric( $_COOKIE['prating'][$product_id] ) )	
		$p_rating = absint( $_COOKIE['prating'][$product_id] );		
	else
		$p_rating = (int)usam_get_product_meta( $product_id, 'rating', true );
	
	$output = "<span class='your_product_rating rating' data-product_id='".$product_id."'>";
	for ( $l = 1; $l <= 5; ++$l )
	{
		$output .= "<span class='star'></span>";
	}
	$output .= "</span>";	
	return $output;
}


function usam_get_unanswered_chat_messages( )
{
	$count = 0;	
	return $count;
}

require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');

function usam_get_aggregate_reviews( $page_id )
{
	global $wpdb;
	$page_id = intval($page_id);
	$row = $wpdb->get_results("SELECT COUNT(*) AS `total`,AVG(rating) AS `aggregate_rating` FROM `".USAM_TABLE_CUSTOMER_REVIEWS."` WHERE `page_id`=$page_id AND `status`=1");
	if ($wpdb->num_rows == 0 || $row[0]->total == 0) 
		return array( "aggregate" => 0, "max" => 0, "total" => 0 );
	else
		return ["aggregate" => round($row[0]->aggregate_rating,1), "total" => $row[0]->total];
}	

function usam_taxonomy_image( $term_id, $size = 'full' ) 
{
	return usam_get_term_image_url( $term_id, $size );
}


// Получить статус консультанта
function usam_get_status_online_consultants( ) 
{			
	static $status_online_consultant = null;
	
	if ( $status_online_consultant !== null )
		return $status_online_consultant;
	$contact_id = usam_get_contact_id( );	
	$contacts = usam_get_contacts(['fields' => 'id', 'meta_key' => 'online_consultant', 'source' => 'employee', 'meta_value' => 1, 'include' => $contact_id, 'number' => 1]);		
	if ( !empty($contacts) )
		$status_online_consultant = true;
	else
		$status_online_consultant = false;
	return $status_online_consultant;		
}

function usam_ajax_nonce_field( $action )
{	
	$out = "<input name='usam_action' class='js-action-field' type='hidden' value='$action'/>
	<input name='nonce' id='nonce_$action' type='hidden' value=''/>";
	return $out;
}


function usam_quick_purchase_button( $product_id = null, $class = "quick_purchase button" )
{		
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	if( usam_hide_addtocart_button() && usam_product_has_stock( $product_id ) && get_option('usam_website_type', 'store' ) != 'price_platform' && usam_get_product_price( $product_id ) > 0 ) 
	{ 			
		echo usam_get_webform_link( 'quick_purchase', $class );
	} 	
}

function usam_widget_cart( $args, $instance )
{		

}



function usam_product_tag_cloud( $args = '' ) 
{
	$defaults = array(
		'smallest' => 8,
		'largest'  => 22,
		'unit'     => 'pt',
		'number'   => 45,
		'format'   => 'flat',
		'orderby'  => 'name',
		'order'    => 'ASC',
		'exclude'  => '',
		'include'  => ''
	);

	$args = wp_parse_args( $args, $defaults );

	// Always query top tags
	$args = array_merge( $args, ['orderby' => 'count', 'order' => 'DESC', 'taxonomy' => 'product_tag']);
	$tags = get_terms( $args );
	if ( empty( $tags ) )
		return;

	// Here's where those top tags get sorted according to $args
	$return = usam_generate_product_tag_cloud( $tags, $args );
	if ( is_wp_error( $return ) )
		return false;
	else
		echo apply_filters( 'usam_product_tag_cloud', $return, $args );
}

function usam_generate_product_tag_cloud( $tags, $args = '' ) 
{
	global $wp_rewrite;
	$defaults = array(
		'smallest' => 8,
		'largest'  => 22,
		'unit'     => 'pt',
		'number'   => 45,
		'format'   => 'flat',
		'orderby'  => 'name',
		'order'    => 'ASC'
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	if ( !$tags )
		return;

	$counts = $tag_links = array();

	foreach ( (array)$tags as $tag ) {
		$counts[$tag->name] = $tag->count;
		$tag_links[$tag->name] = get_term_link( $tag->slug, $tag->taxonomy ); //get_product_tag_link( $tag->term_id );

		if ( is_wp_error( $tag_links[$tag->name] ) )
			return $tag_links[$tag->name];

		$tag_ids[$tag->name] = $tag->term_id;
	}

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;

	if ( $spread <= 0 )
		$spread = 1;

	$font_spread = $largest - $smallest;

	if ( $font_spread <= 0 )
		$font_spread = 1;

	$font_step = $font_spread / $spread;

	// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
	if ( 'name' == $orderby )
		uksort( $counts, 'strnatcasecmp' );
	else
		asort( $counts );

	if ( 'DESC' == $order )
		$counts = array_reverse( $counts, true );

	$a = array( );

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

	foreach ( $counts as $tag => $count ) 
	{
		$tag_id = $tag_ids[$tag];
		$tag_link = esc_url( $tag_links[$tag] );
		$tag = str_replace( ' ', '&nbsp;', esc_html( $tag ) );
		$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( sprintf( _n( '%d заголовок', '%d заголовоки', $count, 'usam'), $count ) ) . "'$rel style='font-size: " .
				( $smallest + ( ( $count - $min_count ) * $font_step ) )
				. "$unit;'>$tag</a>";
	}

	switch ( $format ) :
		case 'array' :
			$return = & $a;
			break;

		case 'list' :
			$return = "<ul class='product_tag_cloud'>\n\t<li>";
			$return .= join( "</li>\n\t<li>", $a );
			$return .= "</li>\n</ul>\n";
			break;

		default :
			$return = "<div id='product_tag_wrap'>".join( "\n", $a )."</div>";
			break;

	endswitch;

	return apply_filters( 'usam_generate_product_tag_cloud', $return, $tags, $args );
}


//Вывести теги товаров
function usam_get_the_product_tag_list( $before = '', $sep = '', $after = '' )
 {
	global $post;

	if ( empty($post->ID) )
		return false;

	$tags = get_the_term_list( $post->ID, 'product_tag', $before, $sep, $after );

	if ( empty( $tags ) )
		return false;

	return apply_filters( 'the_product_tag_list', $tags );
}

function usam_the_product_tags( $before = null, $sep = ', ', $after = '' ) 
{
	if ( is_null( $before ) )
		$before = __('Tags', 'usam');
	echo usam_get_the_product_tag_list( $before, $sep, $after );
}

function usam_get_product_tag_link( $product_tag ) 
{
	$taglink = get_term_link( $product_tag, 'product_tag' );
	return apply_filters( 'product_tag_link', $taglink, $product_tag );
}

function usam_get_the_product_tags( $id = 0 ) 
{
	$tags = get_the_terms( $id, 'product_tag' );
	return apply_filters( 'get_the_product_tags', $tags, $id );
}

/**
 * Нужно ли выводить условия при оформлении заказа
 */
function usam_has_tnc()
{
	
}


function usam_get_product_id_by_attribute( $key, $value, $cache = true ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_product_attribute_$key-$value";
	$product_id = wp_cache_get( $cache_key );
	if ($product_id === false) 
	{	
		$product_id = (int)$wpdb->get_var($wpdb->prepare("SELECT product_id FROM ".usam_get_table_db('product_attribute')." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		if ( $cache )
			wp_cache_set($cache_key, $product_id);
	}
	else
		$product_id = (int)$product_id;
	return $product_id;
}

function usam_brand_name_id_post( $post_id = null )
{	
	global $post;
	if (empty($post_id))
		$post_id = $post->ID;
	$term_list = wp_get_post_terms($post_id, 'usam-brands', ["fields" => "names"]);
	if ( empty($term_list) )
		return 0;
	else
		return $term_list[0];
}



/**
 * Ссылка на изменения категории товаров
 */
function usam_edit_the_product_cat_link( $term_id, $before = '<span class="edit-link">', $after = '</span>' )
{	
	if ( current_user_can( 'manage_categories' ) )
	{		
		$text = __('редактировать', 'usam');	
		$url = admin_url("edit-tags.php?action=edit&taxonomy=usam-category&tag_ID=".$term_id."&post_type=usam-product");
		$link = '<a class="product_cat-edit-link" href="' . $url . '">' . $text . '</a>';		
		echo $before . apply_filters( 'edit_product_link', $link, $term_id, $text ) . $after;
	}
}

final class USAM_Product_Tabs
{
	private $current_tab_id = '';
	private $tab = array();
	private $product_tabs = array();
	
	private $messages = array();
	private $errors = array();
	
	public function __construct() 
	{
		$this->init();
	}
	
	public function init()
	{			
		global $post;
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tabs_query.class.php' );
		$tabs = usam_get_custom_product_tabs(['active' => 1, 'product_id' => $post->ID]);
		foreach ( $tabs as $tab ) 
		{
			$content = usam_get_product_tab_template( $tab );
			if ( $content != '' )
			{
				$tab->content = $content;
				$this->product_tabs[] = $tab;
			}
		}				
		$this->set_current_tab( );
	}
	
	private function set_current_tab( $tab_id = null )
	{
		if ( empty($this->product_tabs) )
			return;
		
		foreach ( $this->product_tabs as $tab )
		{ 
			if ( $tab->id == $tab_id || empty($tab_id) )
			{
				$this->current_tab_id = $tab->id;	
				$this->tab = $tab;	
				break;
			}						
		}		
		if ( empty($this->current_tab_id) ) 
			$this->set_current_tab( );
	}		
	
	public function display_header_tabs() 
	{		
		?>	
		<div class="header_tab product_tabs_header">	
			<?php		
			foreach ( $this->product_tabs as $tab )
			{ 
				?>
				<a href='#product_tab-<?php echo $tab->id; ?>' id="product_tab-<?php echo $tab->id; ?>" class="tab <?php echo ($this->current_tab_id == $tab->id)?'current':''; ?> usam_menu-<?php echo $tab->id; ?>"><h2><?php echo $tab->name; ?></h2></a>
				<?php
			}
			?>
		</div>		
		<?php
	}
	
	private function display_tab( $tab ) 
	{		//wpautop($tab->content,1)
		?>
		<div class = "product_block product_block_<?php echo $tab->id; ?>">
			<?php echo $tab->content; ?>	
		</div>
		<?php 			
	}
	
	public function display_content_tabs() 
	{		
		echo '<div class = "countent_tabs">';			
		foreach ( $this->product_tabs as $tab )
		{
			?>
			<div id = "product_tab-<?php echo $tab->id; ?>" class = "tab product_tab <?php echo ($this->current_tab_id == $tab->id)?'current':''; ?>">
				<?php $this->display_tab( $tab ); ?>
			</div>
			<?php 
		}		
		echo '</div>';
	}

	private function view_tab() 
	{	
		echo '<div class = "parameters_products__tabs usam_tabs">';
		$this->display_header_tabs();
		$this->display_content_tabs();	
		echo '</div>';	
	}	
	
	private function view_list() 
	{	
		foreach( $this->product_tabs as $tab )
		{
			?>
			<div class="parameters_products__list">
				<h2><?php echo $tab->name; ?></h2>
				<?php $this->display_tab( $tab ); ?>
			</div>
			<?php
		}	
	}	
	
	public function display( $view = 'default' ) 
	{		 
		if ( $view == 'default' )
			$view = get_option('usam_product_content_display', 'tab');
		
		?>		
		<div id = "usam_parameters_products" class = "parameters_products">
			<?php
				$method = 'view_'.$view;				
				if ( method_exists($this, $method) )
				{
					$html = $this->$method();	
				}			
			?>			
		</div>
		<?php
	}
}

function usam_product_tabs( $view = 'default' ) 
{
	usam_change_block( admin_url( "admin.php?page=interface&tab=product_view&table=custom_product_tabs" ), __("Управление вкладками", "usam") );
	$ptab = new USAM_Product_Tabs();
	$ptab->display( $view );
}



function usam_user_checkout_update( $checkout )
{	
	
}


function usam_products_for_buyers() 
{	
	global $post;
	do_action('usam_single_product_after', $post->ID);
}


/*  Отображения блоков с товарами в шаблоне
	template-black
 */
 
/* $query    - какие товары вывести 
 * $template - шаблон
 * $limit    - количество товаров
 */
class USAM_Display_Product_Groups
{
	private $limit = 7;
	private $product_row = 5;	
	private $args;
	private $product_id = 0;
	private $post_meta_cache = false;
	
	function __construct( $args = [] )
	{					
		if ( !empty($args) )
		{		
			$this->set_args( $args );
			$this->display();
		}
    }	
	
	public function set_args( $args )
	{	
		$this->args = $args;	
		
		$this->product_id = !empty($args['product_id'])?(int)$args['product_id']:get_the_ID();
		$this->limit = !empty($args['limit'])?(int)$args['limit']:$this->limit;  //Количество товаров, для вывода
		$this->product_row = !empty($args['product_row'])?(int)$args['product_row']:$this->product_row;
		$this->post_meta_cache = !empty($args['post_meta_cache'])?(bool)$args['post_meta_cache']:$this->post_meta_cache;		
	}
	
	public function display()
	{
		global $title_products_for_buyers;
		
		$title_products_for_buyers = !empty($this->args['title'])?$this->args['title']:'';			
		
		require_once( USAM_FILE_PATH . '/includes/product/products_query.class.php' );
		global $wp_query;
		$query = new USAM_Products_Query(['compilation' => $this->args['query'], 'product_id' => $this->product_id, 'posts_per_page' => $this->limit, 'post_meta_cache' => $this->post_meta_cache]);
		$wp_query = $query->query();
		
		if ( !$wp_query )
			return '';			
	
		if( have_posts() )
		{
			global $post, $product_limit;	
			$r = $product_limit;
			$product_limit = $this->product_row;
			$file_name = usam_get_module_template_file( 'products', $this->args['template'] );
			if ( file_exists($file_name) )
				include( $file_name );
				
			$product_limit = $r;
		}
		wp_reset_postdata();
		wp_reset_query();
	}
} 

function usam_display_product_groups( $args, $lzy = false )
{	
	if ( $lzy )
	{
		$default = ['title' => '', 'query' => '', 'template' => 'simple_list', 'limit' => '', 'product_row' => '', 'class' => '', 'post_meta_cache' => false];
		$args = array_merge( $default, $args );	
		?><div class="js-lzy-products-group products_for_buyers <?php echo $args['class']; ?>" data-title="<?php echo $args['title']; ?>" data-post_meta_cache="<?php echo $args['post_meta_cache']; ?>" data-query="<?php echo $args['query']; ?>" data-template="<?php echo $args['template']; ?>" data-number="<?php echo $args['limit']; ?>" data-product_row="<?php echo $args['product_row']; ?>">		
			<div class="screen_loading">
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
			</div>			
		</div><?php	
	}
	else
		new USAM_Display_Product_Groups( $args );	
}
?>