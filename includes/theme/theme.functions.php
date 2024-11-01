<?php
/**
 * Функции для темы
 */
function usam_get_map( $args, $class = '' )
{
	if ( empty($args['route']) && empty($args['points']) )
		return false;
		
	static $i = 0;
	$i++;	
		
	$args['location_id'] = isset($args['location_id'])?$args['location_id']:usam_get_customer_location();
	if ( empty($args['latitude']) || empty($args['latitude']) )
	{
		if ( $args['location_id'] )
		{
			if ( $args['location_id'] )
			{		
				$args['latitude'] = (string)usam_get_location_metadata( $args['location_id'], 'latitude' );
				$args['longitude'] = (string)usam_get_location_metadata( $args['location_id'], 'longitude' );
			}
		}
	}	
	$args['latitude'] = !empty($args['latitude'])?$args['latitude']:54.71082307941693;
	$args['longitude'] = !empty($args['longitude'])?$args['longitude']:20.495988051123053;
	$args['zoom'] = !empty($args['zoom'])?$args['zoom']:13;	
		
	wp_enqueue_script("yandex_maps");
	wp_localize_script("yandex_maps", "usam_map", $args );		
	ob_start();
	?><div id = "usam_map_<?php echo $i; ?>" class = "usam_map js-map <?php echo $class; ?>"></div><?php
	return ob_get_clean();	
}

function usam_your_company_map( $class = '' )
{	
	static $i = 0;
	$i++;
	wp_enqueue_script("yandex_maps");
	wp_localize_script("yandex_maps", "usam_map", ['route' => 'point_your_company_map']);		
	ob_start();
	?><div id = "usam_map_<?php echo $i; ?>" class = "usam_map js-map <?php echo $class; ?>"></div><?php
	return ob_get_clean();
}

function usam_your_partners_map( )
{				
	return usam_get_map(['route' => 'points_partners']);
}


function _usam_is_in_custom_loop() 
{
	global $usam_query;
	if ( isset($usam_query->usam_in_the_loop) && $usam_query->usam_in_the_loop )
		return true;
	return false;
}

function usam_get_webform_link( $code, $class = '', $svg_icon = '' ) 
{				
	if ( !$code )
		return '';	
	
	$webform = usam_get_webform_theme( $code );
	if ( $webform )
	{ 
		$button_name = 'Отправить';
		if( !empty($webform['settings']['modal_button_name']) )
			$button_name = $webform['settings']['modal_button_name'];		
		elseif( !empty($webform['settings']['button_name']) )
			$button_name = $webform['settings']['button_name'];		
		if ( $svg_icon )
		{
			$svg_class = $class?$class.'_svg':'';
			$svg = usam_get_svg_icon($svg_icon, $svg_class);
			$button_class = $class?$class.'_name':'';
			$button_name = $svg."<span class='$button_class'>".$button_name."</span>";
		}
		$style = [];		
		if ( !empty($webform['settings']['buttonCSS']) )
		{
			foreach( $webform['settings']['buttonCSS'] as $key => $value )
				if ( $value !== '' )
					$style[] = "$key:$value";
		}
		if ( $style )
			$style = "style='".implode(";", $style)."'";
		else
			$style = '';				
		if ( $webform['action'] == 'payment_gateway' && !empty($webform['settings']['payment_gateway']) && (!defined('REST_REQUEST') || !REST_REQUEST) )
		{			
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
			$merchant_instance = usam_get_merchant_class( $webform['settings']['payment_gateway'] );
			if ( $merchant_instance )
			{ 
				global $post;
				$onclick = $merchant_instance->get_button_onclick(['product_id' => $post->ID]);		
				if ( $onclick )
				{ 
					return "<a class='usam_modal_feedback webform_button webform_button_{$code} {$class}' onclick='$onclick' $style>".$button_name."</a>";
				}
			}
		}
		else
		{
			$class = "js-feedback usam_modal_feedback webform_button webform_button_{$code} {$class}";
			$class = apply_filters( 'usam_webform_link_class', $class, $webform );
			return "<a href='#webform_{$code}' class = '{$class}' $style>".$button_name."</a>";
		}
	}
	return '';
}

/**
 * Создает канонические ссылки
 */
function usam_change_canonical_url( $url = '' ) 
{
	global $wpdb, $wp_query;
	if ( $wp_query->is_single == true && 'usam-product' == $wp_query->query_vars['post_type'] && !empty($wp_query->get_queried_object()->ID) )
		$url = get_permalink( $wp_query->get_queried_object()->ID );	
	return apply_filters( 'usam_change_canonical_url', $url );
}

function usam_insert_canonical_url() 
{
	$usam_url = usam_change_canonical_url( null );
	echo "<link rel='canonical' href='$usam_url' />\n";
}

function usam_get_form_subscribe_for_newsletter( $options = array() )
{
	$options = wp_parse_args( (array)$options, ['placeholder' => __('Ваш адрес почты','usam'),'button_text' => __('Подписаться','usam')]);
	$out = "<div class='subscribe_for_newsletter'>
				<input type='search' class='subscribe_for_newsletter__input' value='' autocomplete='off' placeholder='".$options['placeholder']."'><span class='subscribe_for_newsletter__button js-subscribe-for-newsletter'>".$options['button_text']."</span>	
			</div>";	
	return $out;
}

function usam_edit_home_blocks( $blocks, $dafault = array() ) 
{
	$home_blocks = get_option("usam_theme_home_block", array( 'banners', 'category', 'admin-fav', 'brands', 'sharing' ));		
	if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
		$edit = true;
	else
		$edit = false;
	foreach ($blocks as $block) 
	{
		if ( in_array($block['key'], $home_blocks) )
		{
			if ( $edit )
			{
				?>
				<div class="change_home_block"><input class="change_home_block__active" title="<?php __("Изменить активность", "usam"); ?>" type="checkbox" class="template_active" name="pbuyers[active][collection]" value="1"><span class="change_block__sort"></span></div>
				<?php
			}
			get_template_part( 'templates/home_blocks/'.$block['key'] );
		}
	}	
}

function usam_home_block( $v = '' )
{
	static $block;
	if( is_array($v) )
		$block = $v;
	else
		return $block;	
}

function usam_home_blocks( )
{
	do_action( 'usam_home_head' );
	$home_blocks = usam_get_home_blocks();	
	if ( !empty($home_blocks) )
	{						
		$current_device = wp_is_mobile();
		foreach ( $home_blocks as $key => $block )
		{					
			if ( !empty($block['device']) && ($current_device == false && $block['device'] == 'mobile' || $current_device && $block['device'] == 'desktop') ) 
				continue;		
			if ( !empty($block['active']) )
			{
				usam_home_block( $block );
				if ( $block['type'] == 'partners_map' )
					echo usam_your_partners_map();				
				else
					get_template_part( $block['template'] );
			}			
		}
	}	
	do_action( 'usam_home_footer' );
}

function usam_get_title_home_block( $type = '', $before = '', $after = '' )
{
	$home_block = usam_get_home_block( $type );
	if ( !empty($home_block['display_title']) )
		return $before.$home_block['display_title'].$after;
	else
		return '';
}

function usam_get_description_home_block( $before = '', $after = '' )
{
	$home_block = usam_get_home_block();
	if ( !empty($home_block['description']) )
		return $before.$home_block['description'].$after;
	else
		return '';
}

function usam_get_option_home_block( $type, $code = '' )
{
	if( $code == '' )
	{
		$code = $type;
		$type = '';
	}
	$home_block = usam_get_home_block( $type );	
	if ( !empty($home_block['options']) )
	{ 		
		foreach( $home_block['options'] as $option )
		{				
			if ( $option['code'] == $code )
				return isset($option['value']) ? $option['value'] : $option['default'];
		}			
	}
	return '';
}
?>