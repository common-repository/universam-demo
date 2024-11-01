<?php
function usam_get_content( ) 
{
	global $wp_query, $usam_query, $post;	
	
	do_action( 'usam_get_content' );

	$content = isset($post->post_content ) ? $post->post_content : '';
	$post_name = isset($post->post_name) ? $post->post_name : '';

	$replace_query = false;	
	$templates = [];	
	if( get_query_var('usam-category_sale') )		
	{		
		$term = get_query_var('usam-category_sale');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-category_sale-products");
		$templates[] = usam_get_template_file_path("content-page-products");	
	}
	elseif( get_query_var('usam-brands') )		
	{		
		$term = get_query_var('usam-brands');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-brands-products");
		$templates[] = usam_get_template_file_path("content-page-products");			
	}
	elseif( get_query_var('usam-selection') )		
	{		
		$term = get_query_var('usam-selection');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-selection-products");
		$templates[] = usam_get_template_file_path("content-page-products");			
	}
	elseif( get_query_var('product_tag') )		
	{		
		$term = get_query_var('product_tag');
		$templates[] = usam_get_template_file_path("content-{$term}-products");		
		$templates[] = usam_get_template_file_path("content-product_tag-products");
		$templates[] = usam_get_template_file_path("content-page-products");			
	}	
	elseif ( !empty($wp_query->query['attribute']) )
	{
		$templates[] = usam_get_template_file_path("content-".$wp_query->query['attribute']."-products");
		$templates[] = usam_get_template_file_path("content-product_tag-products");
		$templates[] = usam_get_template_file_path("content-page-products");
	}
	elseif( get_query_var( 'usam-category' ) )		
	{								
		$term = get_query_var('usam-category');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-categories-products");
		$templates[] = usam_get_template_file_path("content-page-products");
	}	
	elseif( get_query_var('usam-catalog') )		
	{		
		$term = get_query_var('usam-catalog');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-catalog-products");
		$templates[] = usam_get_template_file_path("content-page-products");			
	}
	elseif( get_query_var( 'usam-product_attributes' ) )		
	{
		$term = get_query_var('usam-product_attributes');
		$templates[] = usam_get_template_file_path("content-{$term}-products");
		$templates[] = usam_get_template_file_path("content-page-products");
	}
	elseif( is_single() && get_query_var( 'post_type' ) == 'usam-product' )		
	{							
		$templates[] = USAM_THEMES_PATH."content-single_product-{$post->post_name}.php";
		$templates[] = USAM_THEMES_PATH."content-single_product-{$post->ID}.php";
		$templates[] = usam_get_template_file_path("content-single_product");	
	}			
	elseif( is_page()  )	
	{				
		$pages = ['set', 'productspage', 'newarrivals', 'shareonline', 'reviews',  'brands', 'special_offer', 'recommend', 'popular', 'agreements'];	
		$virtual_page = usam_virtual_page();
		foreach( $virtual_page as $page )
		{	
			$pages[] = str_replace(['[',']'], '', $page['content']);
		}		
		foreach( $pages as $page )
		{	
			if ( preg_match("/\[$page\]/", $content) )
			{ 
				$templates[$page] = usam_get_template_file_path("content-page-$page");
				if ( in_array($page, ['compare_products', 'wishlist', 'special_offer'] ) )
					$replace_query = true;
				break;				
			}
		}		
	}
	if ( !empty($templates) )
	{ 			
		foreach( $templates as $page_slug => $template )
		{ 
			if ( !empty($template) && file_exists($template) )
			{ 
				if ( $replace_query )
					list($wp_query, $usam_query) = [$usam_query, $wp_query];
				ob_start();

				include( $template );	
	
				$output = ob_get_clean();
		
				if ( is_numeric($page_slug) )
					echo $output;					
				else
					echo str_ireplace("[$page_slug]", "<div id='{$page_slug}_content' class='usam_{$page_slug}__content'>$output</div>", $content); 			
				
				if ( $replace_query )
					list($wp_query, $usam_query) = [$usam_query, $wp_query];
				if ( is_numeric($page_slug) )
					break;
			}
		}
	} 	
	else
	{
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile; 
	}
}

function usam_locations_checklist( $args = array() ) 
{	
	require_once( USAM_FILE_PATH . '/includes/walker-location-list.class.php' );
	
	wp_enqueue_script( 'usam-selecting_locations' );
	$defaults = array(
		'parent' => 0,
		'selected' => false,	
		'walker' => null,			
		'echo' => true,	
		'type_display' => 'detail',	
	);
	$params = apply_filters( 'usam_locations_checklist_args', $args );

	$r = wp_parse_args( $params, $defaults );

	if ( empty( $r['walker'] ) || ! ( $r['walker'] instanceof Walker ) ) 
		$walker = new Walker_Location_Checklist;
	else 
		$walker = $r['walker'];
	
	$parent = (int) $r['parent'];	

	$args['list_only'] = !empty( $r['list_only'] );
	$args['code'] = !empty($args['code_location_to'])?$args['code_location_to']:'city';
	
	if ( is_array( $r['selected'] ) ) 
		$args['selected'] = $r['selected'];	
	else 
		$args['selected'] = array();
	
	$locations = (array) usam_get_locations( array( 'code_to' => $args['code'] ) );
	$args['after'] = !empty($args['after'])?$args['after']:'';
	$args['before'] = !empty($args['before'])?$args['before']:'';	

	$output = '';
	if ( $r['type_display'] == 'detail' )
	{	
		$output .= "<div class ='display_locations'>";
		$output .= "<h4>".__('Выбор местоположений', 'usam').":</h4>";
		$output .= "<ul>";
		$output .= call_user_func_array( array( $walker, 'walk' ), array( $locations, 0, $args ) );
		$output .= "</ul>";
		$output .= "</div>";
		$output .= '<div class ="display_locations_select">';
		$output .= "<h4>". __('Выбранные местоположения', 'usam').":</h4>";
		$output .= "<ul>";									
		foreach ( $args['selected'] as $location_id )			
		{		
			$str = usam_get_full_locations_name( $location_id );
			$output .= "<li>$str</li>";
		}
		$output .= "</ul></div>";	
	}	
	else
		$output .= call_user_func_array( array( $walker, 'walk' ), array( $locations, 0, $args ) );

	if ( $r['echo'] ) 
		echo $output;

	return $output;
}

function usam_get_modal_window( $name, $id, $body = '', $size = 'large' ) 
{	
	ob_start();		
	?>	
	<div id="<?php echo $id; ?>" class="modal fade modal-<?php echo $size; ?>">
		<div class="modal-header">
			<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
			<div class = "header-title"><?php echo $name; ?></div>
		</div>
		<?php echo $body; ?>
	</div>
	<?php
	$return = ob_get_contents();
	ob_end_clean();
	return $return;
}

function usam_display_datetime_picker( $id = 'current_time', $date_time = '', $units = 'time' ) 
{ 
	echo usam_get_display_datetime_picker( $id, $date_time, $units );
}

function usam_get_display_datetime_picker( $id = 'current_time', $date_time = '', $units = 'time' ) 
{	
	if ( $units == 'time' )
		$units = ['hour', 'minute'];
	else
		$units = [ $units ];
		
	if ( !empty($date_time) && $date_time != '0000-00-00 00:00:00' )
	{	
		if ( is_numeric( $date_time ) )
		{			
			$date_time = date("Y-m-d H:i:s", $date_time);			
		}				
		$date = get_date_from_gmt($date_time, "Y-m-d H:i:s");	
		$time = strtotime($date);

		$date = date("d.m.Y", $time);	
		$h = date( 'H', $time );
		$m = date( 'i', $time );	
		$time = "{$h}:{$m}";
	}
	else
	{
		$date = '';	
		$time = '';	
		$h = '';
	}	
	$out = "<span class='datetime_picker'><input autocomplete='off' type='text' class='date-picker-field js-date-picker' name='date_{$id}' maxlength='10' value='{$date}' pattern='(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}' placeholder='".__('дд.мм.гггг','usam')."'>";
	if ( in_array('hour', $units) ) 
	{ 
		if ( in_array('minute', $units) ) 
		{	
			$out .= "@<input type='text' class='time' autocomplete='off' placeholder='__:__' name='date_time_{$id}' id='date_time-{$id}' maxlength='5' size='5' value='{$time}' pattern='(0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])'>";
		} 
		else	
			$out .= "@<input type='text' class='hour' autocomplete='off' placeholder='".__('час', 'usam')."' name='date_time_{$id}' id='date_time-{$id}' maxlength='2' size='2' value='{$h}' pattern='(0[0-9]|1[0-9]|2[0-4])'>";
	} 	//defaultDate: "-20y",
	$out .= '</span>';	
	return $out;	
}

function usam_display_date_picker( $id = 'current_time', $date_time = '', $echo = true ) 
{	
	$out = usam_get_display_datetime_picker( $id, $date_time, array() );
	if ( $echo )
		echo $out;
	else
		return $out;
}

function usam_get_datepicker( $format = '', $data = null ) 
{	
	if ( $data === null )
		$data = $_REQUEST;
	if ( !empty($data['date_'.$format]) )
	{ 
		$date_hour = '00';	
		$date_minute = '00';	
		if ( !empty($data['date_time_'.$format]) )
		{
			$time = explode(":",$data['date_time_'.$format]);
			$date_hour = (int)$time[0];
			$date_minute = !empty($time[1])?(int)$time[1]:$date_minute;
		}		
		$date = explode('.', $data['date_'.$format]);
		$result_date = date("Y-m-d H:i:s", mktime($date_hour,$date_minute,0,$date[1],$date[0],$date[2]));					
		$result_date = get_gmt_from_date($result_date, "Y-m-d H:i:s");
	}	
	else
		$result_date = '';
	return $result_date;
}

function usam_upload_theme( $theme_slug )
{	
	if ( !empty($theme_slug) ) 
	{			
		try 
		{ 			
			$theme = wp_get_theme( $theme_slug ); //&& !empty($theme['Version'])	
			if ( !$theme->exists()  ) 
			{ 
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
				include_once( ABSPATH . 'wp-admin/includes/theme.php' );

				WP_Filesystem();		
				$theme_url = strtolower("https://wp-universam.ru/wp-content/uploads/sl/downloadables/wp-theme/{$theme_slug}/{$theme_slug}.zip");			

				$skin     = new Automatic_Upgrader_Skin;					
				$upgrader = new Theme_Upgrader( $skin );							
				$result   = $upgrader->install( $theme_url );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				} elseif ( is_wp_error( $skin->result ) ) {
					throw new Exception( $skin->result->get_error_message() );
				} elseif ( is_null( $result ) ) {
					throw new Exception( 'Unable to connect to the filesystem. Please confirm your credentials.' );
				}
			}
			return true;			
		} 
		catch ( Exception $e ) 
		{
			usam_log_file( sprintf(__( "Не удалось загрузить тему %s.", 'usam'), $theme_slug ) );				
		}			
	}
	return false;
}

function usam_upload_plugin( $plugin_slug )
{	
	if ( !empty($plugin_slug) ) 
	{			
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );		

        WP_Filesystem();
		
		$skin = new Automatic_Upgrader_Skin;
        $upgrader = new WP_Upgrader($skin);
        
		$plugins = get_plugins();
		$installed_plugins = array();
		foreach ( $plugins as $key => $plugin )
		{
			$slug = explode('/', $key);
			$slug = explode('.', end($slug));
			$installed_plugins[] = $slug[0];
		}	
        $plugin = $plugin_slug . '/' . $plugin_slug . '.php';
        $installed = false;
        $activate = false;
        // See if the plugin is installed already
        if (in_array($plugin_slug, $installed_plugins)) 
		{
            $installed = true;
            $activate = !is_plugin_active($plugin);			
        }
        if ( !$installed ) 
		{
            ob_start();
            try 
			{
             /*   $plugin_information = plugins_api('plugin_information', array(
                    'slug' => $plugin_slug,
                    'fields' => array(
                        'short_description' => false,
                        'sections' => false,
                        'requires' => false,
                        'rating' => false,
                        'ratings' => false,
                        'downloaded' => false,
                        'last_updated' => false,
                        'added' => false,
                        'tags' => false,
                        'homepage' => false,
                        'donate_link' => false,
                        'author_profile' => false,
                        'author' => false,
                    ),
                ));

                if (is_wp_error($plugin_information)) {
                    throw new Exception($plugin_information->get_error_message());
                }

                $package = $plugin_information->download_link;
				*/
				$package = "https://wp-universam.ru/wp-content/uploads/sl/downloadables/wp-plugins/{$plugin_slug}.zip";
                $download = $upgrader->download_package($package);
				
                if (is_wp_error($download)) {
                    throw new Exception($download->get_error_message());
                }

                $working_dir = $upgrader->unpack_package($download, true);

                if (is_wp_error($working_dir)) {
                    throw new Exception($working_dir->get_error_message());
                }

                $result = $upgrader->install_package(array(
                    'source' => $working_dir,
                    'destination' => WP_PLUGIN_DIR,
                    'clear_destination' => false,
                    'abort_if_destination_exists' => false,
                    'clear_working' => true,
                    'hook_extra' => array(
                        'type' => 'plugin',
                        'action' => 'install',
                    ),
                ));

                if (is_wp_error($result)) 
				{
                    throw new Exception($result->get_error_message());
                }
                $activate = true;
            } 
			catch (Exception $e)
			{
                new WP_Error( 'usam_plugin_installer', sprintf(__( "Не удалось загрузить плагин %s.", 'usam'), $plugin_slug) );	
            }
            ob_end_clean();
        }
		if ( $activate ) 
		{
            try 
			{
                $result = activate_plugin($plugin);
                if (is_wp_error($result)) 
				{
                    throw new Exception($result->get_error_message());
                }
				else
					return true;
            } 
			catch (Exception $e) {
                new WP_Error( 'usam_plugin_installer', sprintf(__( "Не удалось активировать плагин %s.", 'usam'), $plugin_slug) );	
            }
        }
		else
			return true;
        wp_clean_plugins_cache();
	}
	return false;
}

if ( get_option('usam_cache_menu') && !USAM_DEBUG_THEME )
{
	add_filter( 'pre_wp_nav_menu', function( $output, $args ) 
	{ 
		$menu = wp_get_nav_menu_object( $args->menu );	
		if ( !$menu && $args->theme_location && ( $locations = get_nav_menu_locations() ) && isset($locations[$args->theme_location] ) )
			$menu = wp_get_nav_menu_object( $locations[$args->theme_location] );
			
		if ( !$menu && !$args->theme_location ) 
		{ 
			$menus = wp_get_nav_menus();
			foreach ( $menus as $menu_maybe ) 
			{ 
				if ( $menu_items = wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) ) )
					$menu = $menu_maybe;
					break;		
			}
		}	
		if ( !empty($menu->term_id) )
		{
			$cached_output = get_option( 'menu-cache-'.$menu->term_id );
			if ( $cached_output )
			{
				global $wp_query;
				$term = $wp_query->get_queried_object();
				if ( !empty($term->term_id) )
				{						
					$cached_output = str_replace('current-menu-item', '', $cached_output);
					$cached_output = str_replace('menu-item-'.$term->taxonomy.'-'.$term->term_id, 'current-menu-item', $cached_output);	
				}	
				elseif ( !empty($wp_query->query_vars['pagename']) )
				{ 
					$cached_output = str_replace('current-menu-item', '', $cached_output);
					if ( !empty($wp_query->post) )
						$cached_output = str_replace('menu-item-page-'.$wp_query->post->ID, 'current-menu-item', $cached_output);
				}
				if ( strpos($cached_output, 'menu-item-object-usam-catalog') !== false) 
				{
					$catalog = usam_get_active_catalog();
					if ( $catalog )
						$cached_output = str_replace('menu-item-usam-catalog-'.$catalog->term_id, 'current-menu-item', $cached_output);
				}
				$output = $cached_output;		
			}
		} 
		return $output;
	}, 10, 2);


	add_filter( 'nav_menu_css_class', function( $classes, $item, $args, $dept )
	{
		$classes[] = "menu-item-{$item->object}-{$item->object_id}";
		return $classes;
	}, 10, 4);

	add_filter( 'wp_nav_menu', function( $nav_menu, $args )
	{ 
		$menu = wp_get_nav_menu_object( $args->menu );	
		if ( !$menu && $args->theme_location && ( $locations = get_nav_menu_locations() ) && isset($locations[$args->theme_location] ) )
			$menu = wp_get_nav_menu_object( $locations[$args->theme_location] );
				
		if ( !$menu && !$args->theme_location ) 
		{ 
			$menus = wp_get_nav_menus();
			foreach ( $menus as $menu_maybe ) 
			{ 
				if ( $menu_items = wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) ) )
					$menu = $menu_maybe;
					break;		
			}
		}	
		if ( isset($menu->term_id) )
			update_option( 'menu-cache-' . $menu->term_id, $nav_menu );	
		return $nav_menu;
	}, 10, 2);

	add_action( 'wp_update_nav_menu', function( $menu_id, $menu_data = null ) 
	{	
		global $wpdb;
		$wpdb->query("DELETE FROM ".$wpdb->options." WHERE option_name LIKE 'menu-cache-%'");
	}, 10, 2);
}

add_action( 'after_switch_theme', 'usam_after_switch_theme', 10, 2 );
function usam_after_switch_theme( $old_name, $old_theme ) 
{	
	global $wpdb;
	$wpdb->query("DELETE FROM ".$wpdb->options." WHERE option_name LIKE 'menu-cache-%'");	
}

function usam_get_site_color_scheme()
{	
	return apply_filters( 'usam_registration_site_colors', [] );	
}

function usam_get_site_style()
{	
	$color_scheme = usam_get_site_color_scheme();
	$color_scheme_option = get_theme_mod( 'color_scheme', 'default' );		
	if ( !empty($color_scheme[$color_scheme_option]) )
		$theme_styles = !empty($color_scheme[$color_scheme_option]['styles'])?$color_scheme[$color_scheme_option]['styles']:$color_scheme[$color_scheme_option]['colors'];
	elseif ( !empty($color_scheme['default']) )
		$theme_styles = $color_scheme['default']['styles'];
	$styles = [
		'body-color' => ['label' => __("Цвет для body","usam"), 'default' => '', 'type' => 'color'],
		//'site-width' => ['label' => __("Ширина сайта","usam"), 'default' => '1344px', 'type' => 'text'],	
		'main-color' => ['label' => __("Основной цвет","usam"), 'default' => '#d85656', 'type' => 'color'],
		'main-text-color' => ['label' => __("Цвет текста","usam"), 'default' => '#494949', 'type' => 'color'],
		'text-color' => ['label' => __("Цвет не важного текста","usam"),  'default' => '#999999', 'type' => 'color'],
		'main-open-color' => ['label' => __("Цвет открытых блоков","usam"),  'default' => '#d85656', 'type' => 'color'],
		'main-hover-color' => ['label' => __("Цвет наведения(hover)","usam"),  'default' => '#d85656', 'type' => 'color'],
		'main-price-color' => ['label' => __("Цвет цены","usam"),  'default' => '#494949', 'type' => 'color'],
		'main-button-color' => ['label' => __("Цвет важных кнопок","usam"),  'default' => '#d85656', 'type' => 'color'],
		'button-color' => ['label' => __("Цвет кнопок","usam"),  'default' => '#d85656', 'type' => 'color'],
		'complementary-color' => ['label' => __("Цвет не важных кнопок и элементов","usam"),  'default' => '#f7f7f7', 'type' => 'color'],
		'block-color' => ['label' => __("Цвет блоков","usam"),  'default' => '#F8F8F8', 'type' => 'color'],
		'percent-action-color' => ['label' => __("Цвет процентов скидки","usam"),  'default' => '#619926', 'type' => 'color'],
		'new-product-color' => ['label' => __("Цвет метки новинок","usam"),  'default' => '#494949', 'type' => 'color'],
		'product-bonus-color' => ['label' => __("Цвет метки количества бонусов","usam"), 'default' => '#d85656', 'type' => 'color'], 
		'input-color' => ['label' => __("Цвет полей форм","usam"),  'default' => '#f2f2f2', 'type' => 'color'],
		'input-text' => ['label' => __("Цвет текста полей форм","usam"),  'default' => '#494949', 'type' => 'color'],	
		'input-border-color' => ['label' => __("Цвет рамки полей форм","usam"), 'default' => '#dfe5e8', 'type' => 'color'],
		'input-border-radius' => ['label' => __("Радиус рамки полей форм","usam"), 'default' => '5px', 'type' => 'text'],			
		'input-padding' => ['label' => __("Внутренние отступы полей форм","usam"), 'default' => '10px', 'type' => 'text'],
		'field-border-width' => ['label' => __('Толщина рамки полей','usam'), 'default' => '2px', 'type' => 'text'],
		'radius' => ['label' => __('Радиус кнопок и полей','usam'), 'default' => '5px', 'type' => 'text'],
		'grid-post-radius' => ['label' => __('Радиус элемента в плитке','usam'), 'default' => '5px', 'type' => 'text'],
		'field-font-size' => ['label' => __('Размер шрифта для полей форм','usam'), 'default' => '16px', 'type' => 'text']
	];
	foreach( $styles as $key => $style )
	{
		if ( isset($theme_styles[$key]) )
			$styles[$key]['default'] = $theme_styles[$key];
	}	
	return apply_filters( 'usam_registration_site_style', $styles );	
}

function usam_get_site_triggers(  )
{
	$triggers = [
		'order_close' => __('Заказ завершен','usam'), 
		'review' => __('Отзыв опубликован','usam'), 
		'register' => __('Пользователь зарегистрировался','usam'), 
		'user_profile_activation' => __('При первом сохранение профиля в ЛК','usam'), 
		'open_url' => __('За переход по ссылке','usam') 
	];	
	return apply_filters( 'usam_registration_site_triggers', $triggers );	
}

function usam_get_webform_actions(  )
{
	$actions = ['contacting' => __('Создать обращение','usam'), 'order' => __('Создать заказ','usam'), 'review' => __('Создать отзыв','usam'), 'payment_gateway' => __('Обработка платежной системой','usam') ];
	return apply_filters( 'usam_webform_actions', $actions );	
}

function usam_get_site_trigger( $trigger )
{
	$triggers = usam_get_site_triggers();	
	return isset($triggers[$trigger])?$triggers[$trigger]:'';	
}


function usam_get_site_product_view(  )
{
	$views = ['grid' => __('Плиткой', 'usam'),'list' => __('Списком', 'usam')];	
	return apply_filters( 'usam_site_product_view', $views );
}

function usam_get_users_product_lists( )
{
	$views = ['compare' => __('Сравнение', 'usam'),'desired' => __('Избранное', 'usam'), 'subscription' => __('Подписка', 'usam')];	
	return apply_filters( 'usam_users_product_lists', $views );
}
?>