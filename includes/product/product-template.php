<?php
/**
 * Функции товаров. Эквивалент post-template.php
 */  
 
/*
 * генерирует и возвращает URL-адреса для нумерации страниц
 num_paged_links - количество ссылок, которые нужно показать по обе стороны от текущей страницы
 */
function usam_pagination( $args = [] )
{
	global $wp_query, $usam_query, $wp_the_query;
	
	$args = wp_parse_args( (array)$args, ['totalpages' => 0, 'view' => 'link', 'current_page' => '', 'page_link' => '', 'num_paged_links' => 2]);	
	$args = apply_filters('usam_pagination_args', $args);	
	if( empty($args['totalpages']) )	
		$totalpages = $wp_query->max_num_pages;
	// Если имеется только одна страница
	if($totalpages <= 1) 
		return;		
		
	$permalinks  = get_option( 'usam_permalinks' );
	
	//дополнительные ссылки, элементов на странице заказа и продуктов
	if( get_option('permalink_structure') != '' )
		$links_separator = '?';
	else
		$links_separator = '&';
	
	$gets = array('items_per_page' => 'int', 'f' => 'array', 'prices' => 'str', 'orderby' => 'str', 'brand' => 'str', 'number_products' => 'int');
	$additional_links = '';
	foreach ( $gets as $key => $value )
	{		
		if( !empty($_REQUEST[$key]) )
		{			
			$parametrs = $_REQUEST[$key];
			if ( $value == 'array' || is_array($parametrs) )
				$parametrs = usam_url_array_encode( $parametrs );
			$additional_links .= $links_separator.$key.'='.$parametrs;
			$links_separator = '&';
		}
	}	
	$additional_links = apply_filters('usam_pagination_additional_links', $additional_links);
	if ( $additional_links )
		$additional_links = $args['view'] == 'get' || !get_option('permalink_structure') ? '&'.$additional_links : '/'.$additional_links;

	if( empty($args['current_page']) )	
		$current_page = absint( get_query_var('paged') );
	if($current_page == 0)
		$current_page = 1;

	if( empty($args['page_link']))
		$page_link = usam_a_page_url();			
	//если пагинация отключена
	if(!get_option('permalink_structure'))
	{	
		$category = '?';
		if( !empty( $wp_query->query_vars['usam-category'] ) )
			$category = '?usam-category='.$wp_query->query_vars['usam-category'];
		if(isset($wp_query->query_vars['usam-category']) && is_string($wp_query->query_vars['usam-category']))
			$page_link = get_option('blogurl').$category.'&amp;paged';
		else
			$page_link = usam_get_url_system_page('products-list').$category.'&amp;paged';		
		$separator = '=';	
	}
	else
	{
		if ( !empty( $wp_query->query_vars['usam-category'] ) && empty($usam_query->query_vars['pagename']) ) 
		{ 
			$category_id = get_term_by( 'slug', $wp_query->query_vars['usam-category'], 'usam-category' );
			$page_link = get_term_link( $category_id, 'usam-category' );				
			// in case we're displaying a category using shortcode, need to use the page's URL instead of the taxonomy URL
			if ( $wp_the_query->is_page() )
			{
				$page = $wp_the_query->get_queried_object();
				if ( preg_match( '/\[usam\_products[^\]]*category_id=/', $page->post_content ) )
					$page_link = get_permalink( $page->ID );	
			}		
		} 
		elseif ( is_tax( 'product_tag' ) ) 
		{
			$tag = get_queried_object();
			$page_link = get_term_link( (int) $tag->term_id, 'product_tag' );
		} 
		elseif( !empty($usam_query->query['attribute']) )		
			$page_link = trailingslashit( home_url($usam_query->query['usam-product_attributes'])).'/'.$usam_query->query['attribute'];
		elseif ( is_tax( 'usam-catalog' ) ) 
		{
			$tag = get_queried_object();
			$page_link = get_term_link( (int) $tag->term_id, 'usam-catalog' );
		} 
		elseif ( is_tax( 'usam-brands' ) ) 
		{
			$tag = get_queried_object();
			$page_link = get_term_link( (int) $tag->term_id, 'usam-brands' );
		} 
		elseif ( isset($wp_query->query_vars['usam-brands']) ) 
		{											
			$brand_id = get_term_by( 'slug', $wp_query->query_vars['usam-brands'], 'usam-brands' );
			$page_link = get_term_link( $brand_id, 'usam-brands' );		
		}
		elseif ( !empty($usam_query) )
		{ 
			$page_link = usam_get_url_system_page('products-list');
			if ( $usam_query->is_front_page() )
				$page_link = home_url();
			elseif ( !empty($usam_query->query_vars['pagename']) )
			{
				if ( in_array($usam_query->query['pagename'], usam_get_product_pages()) )
				{			
					$page_link = usam_get_url_system_page($usam_query->query['pagename']);		
					if ( !empty( $wp_query->query_vars['usam-category']) )
						$page_link = $page_link.'/'.$wp_query->query_vars['usam-category'];
					if ( !empty( $wp_query->query_vars['usam-brands']) )
						$page_link = $page_link.'/'.$wp_query->query_vars['usam-brands'];				
				} 
				elseif ( $usam_query->query_vars['pagename'] == 'your-account' )
				{						
					$tab = isset($usam_query->query_vars['tabs']) ? $usam_query->query_vars['tabs'] : '';
					$page_link = usam_get_url_system_page( $usam_query->query_vars['pagename'], $tab );
				}
				else		
					$page_link = usam_get_url_system_page($usam_query->query_vars['pagename']);
			}
			elseif ( isset($usam_query->query_vars['usam-brands']) ) 
			{			
				$page_link = usam_get_url_system_page('brands');
				$page_link = trailingslashit( $page_link );
				$page_link = $page_link.$usam_query->query_vars['term'];	
			} 
			elseif ( isset($usam_query->query_vars['usam-category_sale']) ) 
			{			
				$category_id = get_term_by( 'slug', $usam_query->query_vars['usam-category_sale'], 'usam-category_sale' );
				$page_link = get_term_link( $category_id, 'usam-category_sale' );					
			}			
		}
		else 
			$page_link = usam_get_url_system_page('products-list');
		$separator = $args['view'] != 'get' ? 'page/' : '?paged=';			
	}	
	$page_link = trailingslashit( $page_link );	
	$output = "<div class='pagination usam_pagination'>";
	if( get_option('permalink_structure') )
	{		
		if($current_page > 1) 
		{
			$previous_page = $current_page - 1;
			$output .= " <a href='".esc_url( $previous_page == 1?$page_link.$additional_links:$page_link.$separator.$previous_page.$additional_links )."' title='".__('Предыдущая Страница', 'usam')."' class='page-prev'><span>&lt;</span></a>";
		}
		if($current_page > 4) 
		{			
			$output .= "<a href='". esc_url( $page_link.$separator. 1 . $additional_links ) . "' title='".sprintf( __('Страница %s', 'usam'), 1)."'  class='pagination__item'><span>1</span></a>";
			$output .= "<span class='pagination__points'>...</span>";
		} 
		elseif($current_page == 4) 						
			$output .= "<a href='". esc_url( $page_link.$separator. 1 . $additional_links )."' title='".sprintf( __('Страница %s', 'usam'), ( 1 ) )."'  class='pagination__item'><span>1</span></a>";
			
		$i = $current_page - $args['num_paged_links'];
		$count = 1;
		if($i <= 0) 
			$i = 1;
		while($i < $current_page)
		{
			if( $count <= $args['num_paged_links'])
			{			
				if ( $i == 1 )
					$url = $page_link;
				else
					$url = $page_link .$separator. $i.$additional_links;
				$output .= " <a href='". esc_url( $url ) ."' title='".sprintf( __('Страница %s', 'usam'), $i )."'  class='pagination__item'><span>$i</span></a>";
			}			
			$i++;
			$count++;
		}	
		if($current_page > 0)
			$output .= "<span class='pagination__item current'>$current_page</span>";
		$i = $current_page + $args['num_paged_links'];
		$count = 1;

		if($current_page < $totalpages)
		{
			while($i > $current_page)
			{				
				if($count < $args['num_paged_links'] + 1 && ($count + $current_page) <= $totalpages)
				{
					$output .= " <a href='".esc_url($page_link.$separator.($count+$current_page)).$additional_links."' title='".sprintf( __('Страница %s', 'usam'), ($count+$current_page) ). "' class='pagination__item'><span>".($count + $current_page)."</span></a>";
					$i++;
				}
				else	
					break;				
				$count ++;
			}			
		}
		if($current_page + 3 < $totalpages) 
		{				
			$output .= "<span class='pagination__points'>...</span>";
			$output .= "<a href=\"". esc_url( $page_link.$separator.$totalpages.$additional_links )."\" title='".sprintf(__('Страница %s', 'usam'), $totalpages)."' class='pagination__item'><span>$totalpages</span></a>";
		} 
		elseif($current_page + 3 == $totalpages) 						
			$output .= "<a href=\"". esc_url( $page_link.$separator.$totalpages.$additional_links )."\" title='".sprintf(__('Страница %s', 'usam'), $totalpages)."' class='pagination__item'><span>$totalpages</span></a>";
			
		if( $current_page < $totalpages ) 
		{
			$next_page = $current_page + 1;		
			$output .= "<a href='". esc_url( $page_link.$separator.$next_page.$additional_links )."' title='". __('Следующая страница', 'usam')."' class='page-next'><span>&gt;</span></a>";	
		}
	} 
	else
	{  // Должны ли мы показать связь Первая страница?
		if($current_page > 1)
			$output .= "<a href='".remove_query_arg('paged')."' title='".__('Первая страница', 'usam')."'>&laquo;</a>";

		// Should we show the PREVIOUS PAGE link?
		if($current_page > 1)
		{
			$previous_page = $current_page - 1;
			if( $previous_page == 1 )
				$output .= " <a href='".remove_query_arg('paged').$additional_links."' title='" . __('Предыдущая страница', 'usam')."' class='page-prev'>&lt;</a>";
			else
				$output .= " <a href='". add_query_arg('paged', ($current_page - 1) ).$additional_links."' title='".__('Предыдущая страница', 'usam')."' class='page-prev'>&lt;</a>";
		}
		$i = $current_page - $args['num_paged_links'];
		$count = 1;
		if($i <= 0) $i =1;
		while($i < $current_page)
		{
			if($count <= $args['num_paged_links'])
			{
				if($i == 1)
					$output .= " <a href='". remove_query_arg('paged' ) . "' title='" . sprintf( __('Страница %s', 'usam'), $i ) . "'  class='pagination__item'>$i</a>";
				else
					$output .= " <a href='". add_query_arg('paged', $i ) . "' title='" . sprintf( __('Страница %s', 'usam'), $i ) . "'  class='pagination__item'>$i</a>";
			}
			$i++;
			$count++;
		}	
		if($current_page > 0)
			$output .= "<span class='current'>$current_page</span>";
		//Ссылки после текущей страницы
		$i = $current_page + $args['num_paged_links'];
		$count = 1;
		if($current_page < $totalpages)
		{
			while(($i) > $current_page)
			{
				if($count < $args['num_paged_links'] && ($count+$current_page) <= $totalpages)
				{
					$output .= " <a href='".add_query_arg('paged', ($count+$current_page))."' title='".sprintf( __('Страница %s', 'usam'), ($count+$current_page) )."'  class='pagination__item'>".($count+$current_page)."</a>";
					$i++;
				}
				else
					break;
				$count ++;
			}
		}
		if($current_page < $totalpages) 
		{
			$next_page = $current_page + 1;
			$output .= "<a href='".add_query_arg('paged', $next_page)."' title='".__('Следующая страница', 'usam')."' class='page-next'>&gt;</a>";
		}
		// Должны ли мы показать связь последней странице?
		if($current_page < $totalpages) 
			$output .= "<a href='".add_query_arg('paged', $totalpages)."' title='".__('Предыдущая страница', 'usam')."' class='page-prev'>&raquo;</a>";
	}
	$output.="</div>";	
	echo $output;
}

function usam_product_info()
{
	global $wp_query;		
	
	if ( empty($wp_query) )
		return false;
	
	$kol_prod = $wp_query->found_posts;
	if ( $kol_prod == 0 )
		return;			
	$product_count = $wp_query->post_count;	// количество товаров на текущей странице
	$max_num_pages = $wp_query->max_num_pages; 	// количество страниц
	$current_page = $wp_query->query_vars['paged']; // текущая страница	

	switch ( $current_page ) 
	{
		case 0:
		case 1:
			$first_product = 1;			
			$prod_end = $product_count;				
		break;
		case $max_num_pages:	
			$first_product = $kol_prod - $product_count + 1;
			$prod_end = $kol_prod;		
		break;		
		default:				
			$first_product = ($current_page-1) * $product_count + 1;		
			$prod_end = $current_page * $product_count;			
		break;
	}		
	echo '<div class="products_info">'.sprintf(_n('%s товар', '%s товаров', $wp_query->found_posts, 'usam'), $wp_query->found_posts).' ('.$first_product.' - '.$prod_end.')</div>';
}

/* 
 * добавить в корзину, функция кнопки используются для PHP шаблонов тегов и шорткодов
 */
function usam_add_to_cart_button( $product_id, $return = false ) 
{
	$output = '';	
	if ( $product_id > 0 ) 
	{					
		if ( $return )
			ob_start();
		?>
			<div class='usam-add-to-cart-button'>
				<?php do_action( 'usam_add_to_cart_button_form_begin' ); ?>		
				<?php usam_product_variations( $product_id ); ?>					
				<?php usam_addtocart_button( $product_id ); ?>				
				<?php do_action( 'usam_add_to_cart_button_form_end' ); ?>			
			</div>
		<?php
		if ( $return )
			return ob_get_clean();
	}
}
 

/**
 * Используется в usam_pagination, генерирует ссылки в нумерации страниц
 */
function usam_a_page_url( $page = null )
{
	global $wp_query;
	$output = '';
	$curpage = $wp_query->query_vars['paged'];
	if($page != '')
		$wp_query->query_vars['paged'] = $page;
	if($wp_query->is_single === true)
	{
		$wp_query->query_vars['paged'] = $curpage;
		return usam_product_url($wp_query->post->ID);
	} 
	else 
	{		
		if( 1 < $wp_query->query_vars['paged'])
		{
			if(get_option('permalink_structure'))
				$output .= "paged/{$wp_query->query_vars['paged']}/";
			else
				$output = add_query_arg('paged', '', $output);
		}
		return $output;
	}
}

/**
 * Используется для определения, следует ли отображать продукты на странице
 */
function usam_display_categories()
{
	global $wp_query;
	$output = false;
	if ( !is_numeric(get_option('usam_default_category') ) && ! get_query_var( 'product_tag' ) )
	{
		if ( isset($wp_query->query_vars['products'] ) )
			$category_id = $wp_query->query_vars['products'];
		elseif ( isset($_GET['products'] ) )
			$category_id = absint($_GET['products']);

		// if we have no categories, and no search, show the group list
		if ( is_numeric(get_option('usam_default_category') ) || (isset($product_id ) && is_numeric( $product_id )) )
			$output = true;
		if ( (get_option( 'usam_default_category' ) == 'all+list'))
			$output = true;

		if (get_option( 'usam_default_category' ) == 'list' && (!isset($wp_query->query_vars['usam-category']) || !isset($wp_query->query_vars['product_tag']) && get_option('usam_display_categories')))
			$output = true;
	}
	if ( isset($category_id ) && $category_id > 0 )
		$output = false;
	if ( get_option( 'usam_display_categories' ))
		$output = true;

	return $output;
}

/**
 * Используется, чтобы определить, следует ли отображать продуктов на странице
 */
function usam_display_products() 
{
	global $wp_query;
	$output = false;
	if ( is_object($wp_query) )
	{
		$post = $wp_query->get_queried_object();	
		$output = true;
		if ( usam_display_categories() && $post )
		{
			if ( get_option( 'usam_default_category' ) == 'list' && $post->ID == usam_get_system_page_id('products-list') )
				$output = false;
		}
	}
	return $output;
}

/**
 * 	возвращает URL этой страницы
 */
function usam_this_page_url() 
{
	global $usam_query;
	if ( isset($usam_query->is_single) && $usam_query->is_single ) 
	{
		$output = usam_product_url( $usam_query->post->ID );
	} 
	else if ( isset($usam_query->category ) && $usam_query->category != null ) 
	{
		$output = get_term_link( $usam_query->category, 'usam-category');
		if ( $usam_query->query_vars['page'] > 1 ) {
			if ( get_option( 'permalink_structure' ) ) {
				$output .= "page/{$usam_query->query_vars['page']}/";
			} else {
				$output = add_query_arg( 'page_number', $usam_query->query_vars['page'], $output );
			}
		}
	} 
	elseif ( isset($id ) ) 
		$output = get_permalink( $id );
	else 
		$output = get_permalink( get_the_ID() );
	return $output;
}


/**
 * цикл по товарам
 */
function usam_have_products() 
{
	return have_posts();
}

/**
 * Получить следующий товар
 */
function usam_the_product() 
{
	the_post();		
}


/**
 * Ссылка на изменения товаров
 */
function usam_edit_the_product_link( $product_id = null, $before = '<span class="edit-link">', $after = '</span>' )
{	
	if ( current_user_can( 'edit_published_posts' ) )
	{
		if ( $product_id == null )
			$product_id = get_the_ID();
		
		$text = __('редактировать', 'usam');	
		$link = '<a class="post-edit-link" href="' . get_edit_post_link($product_id) . '">' . $text . '</a>';		
		echo $before . apply_filters( 'edit_product_link', $link, $product_id, $text ) . $after;
	}
}

/**
 * Получить название товара
 */
function usam_the_product_title( $product_id = 0 )
{
	return get_the_title( $product_id );
}

/**
 * Получить описание товара
 */
function usam_the_product_description()
{
	$content = usam_display_product_attributes();
	$product_components = usam_display_product_components();
	if ( $product_components != '' )
	{		
		$content .= $product_components;
	}
	$content .= get_the_content( __('Прочитайте описание полностью &raquo;', 'usam') );
	if (!empty($content))
	{	
		$content = str_replace(']]>', ']]>', $content);  
	}
	else
	{
		$content = 'К сожалению описание отсутствует. В ближайшее время мы сможем его написать.';
	}	
	return do_shortcode( wpautop( $content,1 ) );
}

function usam_label_product( $product_id = null )
{ 
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$tags = get_option('usam_product_tags', ['sold', 'percent_action', 'new']);
	if( usam_product_has_stock( $product_id ) )
	{
		$label_new_product = true;
		if( in_array('percent_action', $tags) && usam_is_product_discount( $product_id ) )
		{ 			
			?><div class="label_percent_action"><?php echo '-'.usam_get_percent_product_discount( $product_id ).'%'; ?></div><?php 
		}			
		$bonus = usam_get_client_product_bonuses(); 				
		if ( $bonus )
		{						
			?><div class = "label_product_bonus"><?php printf( __('+%s бонусов','usam'), "<span class='product_bonus_value'>$bonus</span>"); ?></div><?php
		}
		$code_price = usam_get_customer_price_code();
		$discounts = usam_get_current_product_discount( $product_id );		
		if ( !empty($discounts[$code_price]) )
		{ 
			foreach( $discounts[$code_price] as $discount_id ) 
			{			
				$label_name = usam_get_discount_rule_metadata($discount_id, 'label_name');
				$label_color = usam_get_discount_rule_metadata($discount_id, 'label_color');
				if( $label_name )
				{
					$label_new_product = false;
					$style = $label_color?"background-color:{$label_color}":$label_color;
					?><div class="label_action" style="<?php echo $style; ?>"><?php echo esc_html($label_name); ?></div><?php 
				}
			}
		}
		if ( $label_new_product && in_array('new', $tags) && usam_product_has_new( $product_id ) ) { 
			?><div class="label_new_product"><?php _e('Новинка', 'usam'); ?></div><?php 
		}
	}
	else
	{
		if ( is_active_sidebar( 'product-sold' ) ) { ?>					
			<div class="label_product_sold"><?php dynamic_sidebar('product-sold'); ?></div><?php 
		} 
		elseif ( in_array('sold', $tags) ) 
		{ 			
			?><div class="label_product_sold"><?php _e('Все запасы проданы', 'usam'); ?></div><?php
		}
	}	
	do_action( 'usam_label_product', $product_id );	
}

function usam_check_bonuses_displayed( $total_purchased = 0 )
{	
	$args = ['rule_type' => 'order_close', 'active' => 1];
	if ( $total_purchased )
		$args['total_purchased'] = $total_purchased;
	$rules = usam_get_bonuses_rules( $args );
	if ( $rules )
		return true;
	else
		return false;
}

function usam_get_client_product_bonuses( $product_id = null )
{	
	if ( usam_check_bonuses_displayed() )
		return usam_get_product_bonuses( $product_id );
	else
		return 0;
}


function usam_single_image( $product_id = null, $args = [] )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	$default = [
		'alt' => get_the_title( $product_id ),
		'prev' => '',
		'next' => '',
	];
	$args = array_merge( $default, $args );		
	$attachments = usam_get_product_images( $product_id );	
	
	$image_size = get_site_option( 'usam_single_view_image' );
	$width  = $image_size['width'];
	$height = $image_size['height'];	
	?>			
	<div class="product_images">			
		<?php
		if ( !empty($attachments) ) 
		{			
			?>		
			<div class="product_images__slider slides js-product-slides" style="width:<?php echo $width; ?>px" <?php echo $args['prev']?'prev="'.$args['prev'].'"':''; ?> <?php echo $args['next']?'next="'.$args['next'].'"':''; ?>>			
			<?php 
			foreach ( $attachments as $attachment ) 
			{
				$thumbnail_full = wp_get_attachment_image_src( $attachment->ID, 'full' );
				$medium = wp_get_attachment_image_src( $attachment->ID, 'medium-single-product' );
				if ( !empty($thumbnail_full) && $thumbnail_full[1]>$width && $thumbnail_full[2]>$height ) { ?>
					<div id="product_image_<?php echo $attachment->ID; ?>" href="<?php echo $thumbnail_full[0]; ?>" class="product_image_wrapper open_product_media_viewer ProductZoom hide_image">
						<img itemprop="image" class="product_image" alt="<?php echo $args['alt']; ?>" src="<?php echo $medium[0]; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>">
					</div>
				<?php } else { ?>
					<img id="product_image_<?php echo $attachment->ID; ?>" itemprop="image" class="product_image open_product_media_viewer hide_image" alt="<?php echo $args['alt']; ?>" src="<?php echo $medium[0]; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>">
				<?php } 
			}			
			foreach ( usam_get_product_video() as $video_id )
			{ 
				?><div class="site_youtube"><div class="site_youtube__video js-youtube" id="<?php echo $video_id; ?>"></div></div><?php
			} 
			?>	
			</div>				
			<div class="thumbs" id="thumbs">					
				<?php echo usam_get_images_for_product( $product_id ); ?>		
			</div>	
			<?php 
		}
		else 
		{ 	
			$src = usam_get_no_image_uploaded_file([$width, $height]);				
			$src = apply_filters( 'usam_product_no_image', $src, $product_id, [$width, $height]);
			?><img class="product_image" alt="<?php _e("Нет изображения","usam"); ?>" src="<?php echo $src; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>"><?php 
		} ?>
	</div>
	<?php 
}

// отображение маленьких картинок в просмотре продукта 
function usam_get_images_for_product( $product_id )
{
	$attachments = usam_get_product_images( $product_id );
	$videos = usam_get_product_video();
	$count = count($attachments);
	$html = '';
	if ( $count > 1 || $count == 1 && !empty($videos) )
	{	
		$html.= "<div class='slides js-product-gallery'>";
		foreach ($attachments as $attachment)
		{						
			$small = wp_get_attachment_image_src($attachment->ID, 'small-product-thumbnail');		
			$html .= "<img id='thumbnail_small-".$attachment->ID."' src='".$small[0]."' alt='".$attachment->post_title."' width='".$small[1]."' height='".$small[2]."'/>";
		}	
		foreach ( $videos as $video_id )
		{
			$html .= "<img src='http://i.ytimg.com/vi/{$video_id}/hqdefault.jpg' width='".$small[1]."' height='".$small[2]."'/>";
		}	
		$html .= "</div>";		
	}
	return $html;
}

/**
 * Проверить тип отображения
 */
function usam_get_display_type()
{
	global $usam_query, $post, $wp_query;
	$term = $wp_query->get_queried_object();
	if( !empty($term->term_id) && !empty($usam_query->post_count) )
		$view_type = usam_get_term_metadata( $term->term_id, 'display_type' );	
	if ( empty($view_type) || $view_type == 'default' )
	{ 		
		$contact_id = usam_get_contact_id();
		$view_type = usam_get_contact_metadata($contact_id, 'catalog_view' );
		$view_type = $view_type?$view_type:get_option('usam_product_view', 'grid');
	}
	$view_type = apply_filters( 'usam_display_type', $view_type );
	if ( $view_type == 'default' || !usam_get_template_file_path($view_type.'_product') )
		$view_type = 'grid';	

	$possible_views = usam_get_site_product_view();
	if ( !isset($possible_views[$view_type]) )
		$view_type = key($possible_views);
	return $view_type;
}

function usam_chek_user_product_list( $list )
{
	$lists = get_option("usam_users_product_lists", ['compare', 'desired']);
	if ( in_array($list, $lists) )
		return true;
	return false;
}

 // функция выводит продукты в соответствии с запрошенными сортировками
function usam_include_products_page_template( $replace_request = true )
{			
	global $wp_query, $usam_query;	

	if ( $replace_request ) 
		list($wp_query, $usam_query) = [$usam_query, $wp_query];		
			
	$view_type = usam_get_display_type();
	$templates = ["{$view_type}_view", "products_view"];
	foreach ($templates as $template)
	{
		if ( usam_load_template( $template ) )
			break;
	}
	if ( $replace_request )
		list($wp_query, $usam_query) = [$usam_query, $wp_query];	
}

/**
 * Получить комплектацию товара
 */
function usam_display_product_components( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$output = '';
	$components = usam_get_product_components( $product_id );
	if ( !empty($components) ) 
	{
		$output .= '<div class = "product_components">';	
		$output .= "<div class='product_components__group_title'>".__('Комплектация', 'usam')."</div>";		
		foreach( $components as $id => $component )
		{				
			$output .= "<div class='product_components__component'>".$component->quantity." х ".$component->component."</div>";
		}				
		$output .= '</div>';
	}	
	return $output;
}

/**
 * Получить характеристики товара.
 */
function usam_display_product_attributes( $product_id = null, $group_name = true, $is_important = false, $count = 0 )
{
	if ( !$product_id )
		$product_id = get_the_ID();
	$output = '';

	$product_attributes = usam_get_product_attributes_display( $product_id, ['is_important' => $is_important] ); 
	if ( !empty($product_attributes) ) 
	{	
		$i = 0;
		if ( $group_name )
		{
			foreach( $product_attributes as $attribute )
			{
				if ( $attribute['parent'] == 0 )
					$i++;
			}
			if ( $i < 2 )
				$group_name = false;
		}
		$i = 0;
		$output .= "<div class ='product_characteristics product_attributes'>";
		foreach( $product_attributes as $attribute )
		{					
			if ( $attribute['parent'] == 0 )
			{					
				if ( !$group_name )
					continue;
				if ( $i )
					$output .= "</div>";
				$output .= "<div class='product_characteristics__group product_characteristics__group_attribute".$attribute['attributes_count']."'>";
				$output .= "<div class='product_characteristics__group_title'>".$attribute['name']."</div>";				
			}
			else
			{				
				$do_not_show_in_features = usam_get_term_metadata($attribute['term_id'], 'do_not_show_in_features');
				if ( !empty($do_not_show_in_features) )
					continue;			
				$text = implode(', ', $attribute['value']);			
				if ( usam_get_term_metadata($attribute['term_id'], 'switch_to_selection') )
				{
					$k = array_key_first($attribute['value']);
					$text = "<a href='".rtrim(home_url( $attribute['slug'] ), '/')."/$k'>$text</a>";	
				}
				$i++;				
				$output .= "<div class='product_characteristics__attribute product_characteristic_".$attribute['slug']."'>
					<div class='product_characteristics__attribute_name'><span>".$attribute['name'].":</span></div>
					<div class='product_characteristics__attribute_value'><span>$text</span></div>
				</div>";
				if ( $count && $count == $i )	
					break;
			}				
		}
		if ( $group_name )
			$output .= "</div>";
		$output .= "</div>";
	}
	return $output;
}

function usam_get_product_rating( $class = '', $product_id = null, $vote_total = true, $no_grey = false ) 
{			
	if ( !get_option('usam_show_product_rating', 1) ) 
		return '';
	
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$rating_count = usam_get_post_meta( $product_id, 'rating_count' );
	$p_rating = usam_get_post_meta( $product_id, 'rating' );
	if ( $no_grey && empty($p_rating) )
		return '';
	
	$p_rating = $p_rating > 6 ? 5 : $p_rating;
	
	$selected_star_html = apply_filters( 'usam_selected_star_html', usam_get_svg_icon('star-selected', 'star rating__selected') );	
	$star_html = apply_filters( 'usam_star_html', usam_get_svg_icon('star', 'star') );	
	
	$itemtype = $rating_count ? "itemprop='aggregateRating' itemtype='http://schema.org/AggregateRating' itemscope":"";
	$output = "<span class='rating product_rating $class' data-product_id='".$product_id."' $itemtype>";
	for ( $l = 1; $l <= $p_rating; ++$l )
	{ 
		$output .= $selected_star_html;		
	}
	$remainder = 5 - $p_rating;
	for ( $l = 1; $l <= $remainder; ++$l ) {
		$output .= $star_html;
	}
	if ( $vote_total )	
		$output .= "<span class='vote_total' id='vote_total_{$product_id}'>".$rating_count."</span>";
	
	if ( $rating_count )
	{
		$output .= "
		<meta itemprop='bestRating' content='5'/>
		<meta itemprop='worstRating' content='0'/>
		<meta itemprop='reviewCount' content='$rating_count'/>
		<meta itemprop='ratingValue' content='$p_rating'/>";
	}
	$output .= "</span>";
	return $output;
}

function usam_product_rating( $class = '', $vote_total = false, $no_grey = true ) 
{
	$product_id = get_the_ID();
	echo usam_get_product_rating( $class, $product_id, $vote_total, $no_grey );
}

/**
 * Используется ли разбиение на страницы
 */
function usam_has_pages()
{
	if( get_option('usam_product_pagination', 1) )
		return true;
	else
		return false;
}
/**
 * Добавить поле "количество"
 */
function usam_has_multi_adding() 
{	
	if ( get_option('usam_show_multi_add') )
		return true;
	else 
		return false;	
}

function usam_has_additional_units( $product_id = null ) 
{		
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	$additional_units = usam_get_product_property( $product_id, 'additional_units' );
	return $additional_units ? true : false;
}
/**
 * функция возвращает количество выведенных продуктов
 */
function usam_product_count() 
{
	global $wp_query;
	
	return !empty($wp_query->posts)?count($wp_query->posts):0;
}

/**
 * show category description
 */
function usam_show_category_description()
{
	return get_option('usam_category_description', '' );
}

function usam_hide_addtocart_button(  )
{	
	$hide_addtocart_button = get_site_option('usam_hide_addtocart_button', 0);
	if ( $hide_addtocart_button == 0 ) 
		return true;
	elseif ( $hide_addtocart_button == 2 && is_user_logged_in() ) 
		return true;
	else
		return false;
}

function usam_addtocart_button( $product_id = null, $title = null, $class = "button_buy button main-button" )
{		
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if ( $title == null )
		$title = get_site_option("usam_name_addtocart_button", __('В корзину', 'usam') );	
	
	if( usam_is_product_under_order( $product_id ) ) 
	{ 
		$under_order_button = get_site_option('usam_under_order_button');
		if ( $under_order_button )
		{
			echo usam_get_webform_link( $under_order_button, 'under_order_button button' );
		}
	}	
	elseif( usam_product_has_stock( $product_id ) ) 
	{
		if( usam_hide_addtocart_button() ) 
		{
			if ( get_option('usam_website_type', 'store' ) == 'price_platform' )
			{
				?><a class="<?php echo $class ?>" href="<?php echo usam_product_url($product_id)."?click_pay=".$product_id; ?>" target="<?php echo get_option("usam_target_addtocart_button", ''); ?>"><?php echo $title; ?></a><?php
			}
			elseif ( usam_get_product_price( $product_id ) > 0 )
			{
				?><button class="js-product-add <?php echo $class ?>" product_id="<?php echo $product_id ?>" id="product_<?php echo $product_id ?>_button"><?php echo $title; ?></button><?php 		
			}
		}		
	} 
	elseif( usam_chek_user_product_list('subscription') ) 
	{ 
		usam_include_template_file('product-subscription', 'template-parts');
	}
} 

//Добавить и перейти в Корзину
function usam_get_buy_button_and_gotocart( $product_id = null, $title = null, $class = 'button_buy button main-button' )
{		
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if( usam_hide_addtocart_button() && usam_product_has_stock( $product_id ) ) 
	{
		$url = add_query_arg(['product_id' => $product_id, 'usam_action' => 'buy_product', 'empty' => 1] , usam_get_url_system_page('basket') );		
		if ( $title == null )
			$title = get_site_option("usam_name_addtocart_button", __('В корзину', 'usam') );
		
		return "<a href='$url' rel='nofollow' class='$class'>$title</a>";
	}
	return '';
}

function usam_field_product_number( $product_id = null, $class = "quantity_update" )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
		
	$stock = usam_product_remaining_stock( $product_id );
	?><input type="number" class="<?php echo $class ?> js-quantity" id="usam_quantity_update_<?php echo $product_id; ?>" value="<?php echo usam_get_product_property( $product_id, 'unit' ); ?>" min='<?php echo usam_get_product_property( $product_id, 'unit' ); ?>' step='<?php echo usam_get_product_property( $product_id, 'unit' ); ?>' <?php echo $stock == USAM_UNLIMITED_STOCK?"":"max='$stock'"; ?>/><?php
}


function usam_selection_product_units( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	$file_path = usam_get_template_file_path( 'product-units', 'template-parts' );
	if ( $file_path )
	{		
		include( $file_path );	
	}	
}

function usam_product_variations( $product_id = null )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
		
	USAM_Variations::instance( );
	USAM_Variations::init( $product_id );
		
	if ( usam_have_variation_groups() ) 
	{ 
		?>						
		<div class="variations js-product-variations-<?php echo usam_get_product_id_group_variations(); ?>">
			<?php 
			while (usam_have_variation_groups()) : 
				usam_the_variation_group(); 
				$tag_template = usam_get_term_metadata(usam_vargrp_id(), 'template');	
				$tag_template = $tag_template == '' ? 'select' : $tag_template; 
				$file_path = usam_get_module_template_file( 'variations', $tag_template );
				if ( $file_path )
				{		
					include( $file_path );	
				}				
			endwhile; ?>
		</div>	
		<?php 
	}
}

function usam_get_product_tab_template( $tab, $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	$html = '';									
	$file_path = usam_get_template_file_path( "tab-{$tab->code}", "template-parts/product-tab" );	
	if ( $file_path )
	{ 
		ob_start();					
		include( $file_path );					
		$html = ob_get_clean();
	}
	else
	{ 
		require_once(USAM_FILE_PATH.'/includes/product/product_shortcode.class.php');
		$s = new USAM_Product_Shortcode( $product_id );
		$html = $s->get_html( $tab->description );
	}
	return $html;
}

function usam_is_product() 
{
	return is_single() && get_post_type() == 'usam-product';
} 

function usam_is_transaction_results( $tab = '' ) 
{
	global $wp_query;
	return is_page('transaction-results') &&( $tab == '' || isset($wp_query->query['tabs']) && $wp_query->query['tabs'] == $tab ); 
} 

function usam_is_product_category() 
{
	return is_tax( 'usam-category' );
}

function usam_is_product_brand() 
{
	return is_tax( 'usam-brands' );
}

function usam_is_product_category_sale() 
{
	return is_tax( 'usam-category_sale' );
}

function usam_is_page( $page ) 
{
	global $usam_query;
	if ( !empty($usam_query->query_vars) && $usam_query->query_vars['pagename'] === $page )
		return true;
	return false;
}

function usam_is_post_category( $slug, $post_id = null ) 
{	
	if ( !$post_id && is_single() )
		$post_id = get_the_ID();
	if( $post_id )
	{
		$categories = get_the_category( $post_id );
		if( $categories && is_array($categories) )
		{
			foreach( $categories as $cat )
				if( $cat->slug === $slug )
					return true;
		}
	}
	return false;
} 

