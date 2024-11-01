<?php
/**
 * Проверяет активную папку темы для конкретного файла, если он существует, то вернуть активную тему URL, в противном случае вернуть глобальную usam_theme_url
 */
function usam_get_template_file_url( $file, $group = '' ) 
{		
	$template_dir = $group != '' ? "$group/" : "";	
	$file_path =  USAM_THEMES_PATH.$template_dir.$file;	
	if ( file_exists( $file_path ) )
	{
		$file_url =  USAM_THEME_URL.$template_dir.$file;		
		if ( USAM_DEBUG_THEME && $file == 'sprite.svg' )
		{ 
			$file_url = str_replace(get_template_directory_uri(), WP_HOME, $file_url);	
		}		
	}
	else
	{
		$file_url = USAM_CORE_THEME_URL.$template_dir.$file;	
		if ( USAM_DEBUG_THEME && $file == 'sprite.svg' )
		{ 
			$file_url = str_replace(plugins_url('universam/theme'), WP_HOME, $file_url);
		}	
	}			
	if ( is_ssl() )
		$file_url = str_replace('http://', 'https://', $file_url);
	return $file_url;
}

/**
 * Проверяет активную папку темы для конкретного файла, если он существует, то вернуть активную директорию темы в противном случае вернет глобальное usam_theme_path
 */
function usam_get_template_file_path( $file, $folder = '' )
{	
	$template_dir = $folder != '' ? "$folder/" : "";		
	$file_path = USAM_THEMES_PATH.$template_dir.$file.'.php';	//Ищите файл в папке магазина		
	if ( file_exists( $file_path ) )		
		return $file_path;
	
	$file_path = USAM_CORE_THEME_PATH .$template_dir.$file.'.php'; //Ищите файл в шаблоне	
	if ( file_exists($file_path) )		
		return $file_path;

	return false;
}

function usam_get_filepath_admin( $filepath )
{	
	$filepath = USAM_FILE_PATH.'/admin/'.$filepath;
	return apply_filters( 'usam_admin_include_filepath', $filepath );
}

function usam_get_admin_template_file_path( $file, $folder = '' )
{	
	if ( $folder != '' )
		$template_dir = "$folder/";
	else
		$template_dir = '';
	
	$file_path = USAM_THEMES_PATH.'admin/'.$template_dir.$file.'.php';	//Ищите файл в папке магазина	
	if ( file_exists( $file_path ) )		
		return $file_path;
	
	$file_path = USAM_FILE_PATH.'/admin/' .$template_dir.$file.'.php'; //Ищите файл в шаблоне	
	if ( file_exists($file_path) )		
		return $file_path;
	return false;
}

/**
 * Подключает заданный файл
 */
function usam_include_template_file( $file, $folder = '' )
{
	$file_path = usam_get_template_file_path( $file, $folder );
	if ( $file_path )
		include( $file_path );
}

function usam_load_template( $file, $folder = '' )
{	
	$file_path = usam_get_template_file_path( $file, $folder );
	if ( $file_path )
	{
		do_action( 'usam_before_load_template', $file );
		
		load_template( $file_path );	
		
		do_action( 'usam_after_load_template', $file );
		return true;
	}
	return false;
}


//===================================================== ШАБЛОНЫ МОДУЛЕЙ =============================================

/*
 * Получить ссылку на файлу шаблона
 */
function usam_get_module_template_url( $type_template, $template, $file = 'style.css' )
{	
	ob_start();	
	$file_name = USAM_THEMES_PATH . "$type_template/$template/$file";				
	if ( !file_exists($file_name) )
		$file_url = USAM_CORE_THEME_URL. "$type_template/$template/$file";	
	else
		$file_url = USAM_THEME_URL . "$type_template/$template/$file";			
	
	if ( is_ssl() )
		$file_url = str_replace('http://', 'https://', $file_url);	

	if ( $file == 'style.css' )
		$file_url .= "?ver=".USAM_VERSION_ASSETS;
	return $file_url;
}

/**
 * Получить путь к файлу шаблона
 */
function usam_get_module_template_file( $type_template, $template, $file = 'index.php' )
{	
	$file_name = USAM_THEMES_PATH ."$type_template/$template/$file";
	if ( !file_exists($file_name) )
		$file_name = USAM_CORE_THEME_PATH. "$type_template/$template/$file";
	if ( !file_exists($file_name) )
		$file_name = '';		

	return $file_name;
}

// Получить шаблоны
function usam_get_templates( $type, $filename = 'index.php' )
{	
	$template_list = array();
	$dir_path = USAM_CORE_THEME_PATH. $type;
	if ($dir = opendir($dir_path)) 
	{			
		while ( ($name_template = readdir( $dir )) !== false ) 
		{			
			if ($dir_mailtemplate = opendir($dir_path. '/'.$name_template)) 
			{
				while ( ($file = readdir( $dir_mailtemplate )) !== false ) 
				{	
					if( $file == $filename )
					{
						$data = get_file_data( $dir_path.'/'.$name_template.'/'.$file, ['ver'=>'Version', 'author'=>'Author', 'name'=>'Theme Name'] );
						$screenshot = "{$type}/{$name_template}/screenshot.";
						foreach(['jpg','png'] as $k )
						{
							if( file_exists(USAM_CORE_THEME_PATH.$screenshot.$k) )
							{
								$data['screenshot'] = USAM_CORE_THEME_URL.$screenshot.$k;
								break;
							}
						}
						$template_list[$name_template] = $data;
						break;
					}
				}
			}
		}
	}		
	return $template_list;
}

function usam_get_templates2( $type )
{	
	require_once ABSPATH . "wp-admin/includes/file.php";
	$template_list = [];
	foreach( [USAM_CORE_THEME_PATH, USAM_THEMES_PATH] as $path )
	{		
		$dir_path = $path.$type;		
		if( is_dir($dir_path) && $dir = opendir($dir_path) ) 
		{						
			while( ($file = readdir( $dir )) !== false ) 
			{									
				if( $file == '.' || $file == '..' )
					continue;
				$key = str_replace( ".php", "", $file );
				$template_list[$key] = get_file_data( $dir_path.'/'.$file, ['name'=>'Name']);
			}	
		}
	}		
	return $template_list;
}


function usam_get_html_templates( $sub_path )
{	
	require_once ABSPATH . "wp-admin/includes/file.php";
	$template_list = [];
	foreach( [USAM_CORE_THEME_PATH, USAM_THEMES_PATH] as $path )
	{		
		$dir_path = $path.$sub_path;		
		if( is_dir($dir_path) && $dir = opendir($dir_path) ) 
		{						
			while( ($item = readdir( $dir )) !== false ) 
			{									
				if( $item == '.' || $item == '..' )
					continue;
				if( is_dir($dir_path.'/'.$item) ) 
				{
					$data = get_file_data( $dir_path.'/'.$item.'/index.php', ['name'=>'Name']);
					$data['path'] = $dir_path.'/'.$item;
					$template_list[$item] = $data;
				}
			}	
		}
	}	
	return $template_list;
}

//===================================================== РАССЫЛКА =============================================

/**
 * Получить путь к файл шаблона рассылки
 */
function usam_get_email_template( $template = '', $file = 'index' )
{	
	if ( $template == '' )
	{
		$template = get_option('usam_mailtemplate');	
		if ( $template == '' )
		{
			$templates = usam_get_templates( 'mailtemplate' );
			$template = key($templates);	
			update_option('usam_mailtemplate', $template);	
		}
	} 
	$file_name = usam_get_module_template_file( 'mailtemplate', $template, $file.'.php' );
	$out = '';
	if ( $file_name )
	{
		ob_start();	
		include $file_name;			
		$out = ob_get_clean();
		
		$out = trim($out);
	}	
	return $out;
}

function usam_get_webform_template( $webform, $post_id = false, $modal = false, $change_block = true )
{	
	if ( !is_array($webform) )
		$webform = usam_get_webform_theme( $webform );
	if ( !$webform )
		return '';

	$html = '';		
	$args = [			
		'shop_name' => get_bloginfo('name'),
	];	
	if ( !$post_id ) 
	{
		global $post;
		$post_id = !empty($post->ID)?$post->ID:0;
	}
	if ( $post_id ) 
	{			
		$args['product_price'] = usam_get_product_price_currency( $post_id );
		$args['product_name'] =  get_the_title( $post_id );		
	}
	$shortcode = new USAM_Shortcode();
	$description = $shortcode->process_args( $args, $webform['settings']['description'] );		
			
	$file_name = usam_get_template_file_path( $webform['template'], 'webforms' );	
	if ( !$file_name )
		$file_name = usam_get_template_file_path( 'contact-form', 'webforms' );
	if ( $file_name )
	{		
		ob_start();	
		if ( $change_block && function_exists('usam_change_block') )
			usam_change_block( admin_url( "admin.php?page=interface&tab=webforms&table=webforms&form=edit&form_name=webform&id=".$webform['id'] ), __("Изменить веб-форму", "usam") );		
		?>
		<div id = "webform_<?php echo $webform['code']; ?>" class = "js-webform" v-cloak>
			<?php include $file_name; ?>
		</div>	
		<?php	
		$html = ob_get_clean();
	}
	return $html;
}

function usam_get_home_blocks( )
{
	$register_blocks = [];
	$register_blocks = apply_filters( 'usam_register_home_blocks', $register_blocks );	
	foreach ( $register_blocks as $key => $block )
	{
		$block['type'] = $key;
		$register_blocks[$key] = $block;
	}	
	$home_blocks = get_option( "usam_theme_home_blocks" );
	if ( !empty($home_blocks) )
	{
		$blocks = [];
		foreach( $register_blocks as $key => $block )
		{		
			$home_block = $block;
			foreach( $home_blocks as $block_db )
			{
				if( $block_db['type'] == $block['type'] )
				{
					$home_block = $block_db;					
					break;			
				}
			}					
			if ( !empty($block['options']) )
			{				
				$options = [];
				foreach($block['options'] as $option )
				{				
					$option['default'] = isset($option['default'])?$option['default']:'';
					$option['value'] = isset($home_block['options'][$option['code']])?$home_block['options'][$option['code']]:$option['default'];					
					if( $option['field_type'] == 'autocomplete' )
					{ 
						$option['search'] = '';
						if( $option['value'] )
						{							
							if( $option['request'] == 'pages' )
								$option['search'] = get_the_title( $option['value'] );
						}
					}
					$options[] = $option;
				}
				$home_block['options'] = $options;
			}
			$blocks[] = $home_block;
		}
		$home_blocks = $blocks;	
		$comparison = new USAM_Comparison_Object( 'sort', 'ASC' );
		usort( $home_blocks, [$comparison, 'compare'] );
	}
	else
		$home_blocks = $register_blocks;
	$results = [];
	foreach( $home_blocks as $block )
	{
		if ( !empty($register_blocks[$block['type']]) )
		{
			$block['options'] = !empty($block['options'])?$block['options']:[];			
			$block['active'] = !isset($block['active']) || $block['active'] ?1:0;
			$block['device'] = empty($block['device']) ?'all':$block['device'];			
			$block['title'] = $register_blocks[$block['type']]['title'];
			$block['template'] = $register_blocks[$block['type']]['template'];
			$block['display_title'] = !isset($block['display_title'])?$block['title']:$block['display_title'];	
			$block['description'] = isset($block['description'])?$block['description']:'';
			$results[] = $block;	
		}
	}		
	return $results;
}

function usam_get_home_block( $type = '' )
{
	if( $type == '' )
		$result = usam_home_block();
	else
	{	
		$home_blocks = usam_get_home_blocks();
		$result = array();
		if ( !empty($home_blocks) )
		{						
			foreach ( $home_blocks as $key => $block )
			{				
				if ( $block['type'] == $type )
				{
					$result = $block;
					break;
				}			
			}
		}
	}
	return $result;
}

function usam_get_product_selections()
{
	$selections = [
		'popularity' => __('Популярные товары по количеству просмотров', 'usam'),
		'sale' => __('Акционные товары', 'usam'),		
		'collection' => __('Товары из этой коллекции', 'usam'), 
		'also_bought' => __('С этим товаром покупали', 'usam'), 
		'related_products' => __('К этому товару подойдет', 'usam'), 
		'same_category' => __('Товары в той же категории', 'usam'), 
		'history_views' => __('История просмотра товаров', 'usam'), 		
		'upsell' => 'upsell', 
		'last_purchased' => __('Последние проданные товары', 'usam'),
		'leaders_sells_month' => __('Лидеры продаж за месяц', 'usam'), 
		'news' => __('Новинки', 'usam'), 
		'sticky' => __('Избранные менеджером товары', 'usam'), 		
		'similar' => __('Аналоги', 'usam'), 
	];
	require_once( USAM_FILE_PATH . '/admin/includes/filters_query.class.php' );														
	$filters = usam_get_admin_filters(['screen_id' => 'edit-usam-product']);
	foreach ( $filters as $filter )
		$selections[$filter->id] = $filter->name;
	return $selections;
}


function usam_get_html_blocks( $args = [] )
{
	$args = array_merge( ['active' => 1], $args );		
	$blocks = get_option( "usam_html_blocks", [] );	
	foreach ( $blocks as $k => $block )
	{		
		if( isset($args['active']) && $args['active'] != $block['active'] && $args['active'] != 'all' )
			unset($blocks[$k]);
		elseif( !empty($args['include']) && !in_array( $block['id'], $args['include'] ) )
			unset($blocks[$k]);
	}
	return $blocks;
}

function usam_get_html_block( $id )
{	
	$blocks = get_option( "usam_html_blocks", [] );	
	if ( !empty($blocks) )
	{ 		
		foreach( $blocks as $block )
		{				
			if ( $block['id'] == $id )
			{
				$block = apply_filters( 'usam_block_output', $block );
				return $block;
			}
		}			
	}
	return false;
}

function usam_get_register_html_blocks()
{
	$results = [];
	$files = usam_get_html_templates('template-parts/blocks');	
	foreach($files as $template => $data )
	{				
		if( file_exists($data['path'].'/options.php') )
		{	
			$block = [];
			require( $data['path'].'/options.php' );	
			$block['template'] = 'template-parts/blocks/'.$template;					
			$results[] = $block;
		}
	}
	$results = apply_filters( 'usam_register_html_blocks', $results );
	return $results;
}


function usam_get_hooks()
{
	$hooks = [
		'home_head' => __("На главной вверху","usam"), 
		'home_footer' => __("На главной внизу","usam"), 
		'catalog_head' => __("В верхней части категорий","usam"),
		'catalog_footer' => __("В нижней части категорий","usam"),
		'single_product_before' => __("В начале карточки товара","usam"),	
		'single_product_after' => __("В конце карточки товара","usam"),		
		'basket_before' => __("В начале страницы Корзина","usam"),
		'basket_after' => __("В конце страницы Корзина","usam"),
	];
	return apply_filters( 'usam_register_hooks', $hooks );	
}
?>