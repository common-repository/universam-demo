<?php
class USAM_Banner
{
	// строковые
	private static $string_cols = [
		'date_insert',		
		'start_date',		
		'end_date',
		'type',
		'name',	
		'status',
		'device',	
		'object_url',
		'settings',		
	];
	// цифровые
	private static $int_cols = [
		'id',			
		'object_id',
		'sort',	
		'views',
		'actuation_time',		
	];
	private $data = array();		
	private $fetched = false;
	private $args = ['col' => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_banner' );
		}			
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_banner' );	
		do_action( 'usam_banner_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_banner' );	
		do_action( 'usam_banner_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_banner_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_BANNERS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_banner_delete', $id );
		
		return $result;
	}		
	
	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{
		global $wpdb;
		if ( $this->fetched )
			return;

		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_BANNERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$data['settings'] = (array)maybe_unserialize($data['settings']);
			$this->exists = true;
			$this->data = apply_filters( 'usam_banner_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_banner_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_banner_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_banner_get_data', $this->data, $this );
	}
	
	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 */
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );			
		}		
		$properties = apply_filters( 'usam_banner_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( ) 
	{
		$formats = array();
		foreach ( $this->data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;	
			else
				unset($this->data[$key]);
		}
		return $formats;
	}		
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_banner_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_banner_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_banner_update_data', $this->data );	
			$formats = $this->get_data_format( );
			
			foreach( $this->data as $key => $value)
			{				
				if ( $key == 'settings' )
					$value = maybe_serialize($value);
				if (  $key == 'date_insert' || $key == 'id' )
					continue;				
				if ( $key == 'start_date' || $key == 'end_date' )
					$set[] = !$value ?"`{$key}`=NULL" : "`{$key}`='".date( "Y-m-d H:i:s", strtotime( $value ) )."'";
				else
					$set[] = "`{$key}`='{$value}'";						
			}	
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_BANNERS."` SET ".implode( ', ', $set )." WHERE $where_col ='$where_format'", $where_val) );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_banner_update', $this );
		} 
		else 
		{   
			do_action( 'usam_banner_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );
			
			if ( isset($this->data['start_date']) )
				unset($this->data['start_date']);
			
			if ( isset($this->data['end_date']) )
				unset($this->data['end_date']);
			
			if ( !isset($this->data['sort']) )
				$this->data['sort'] = 100;			
			$this->data = apply_filters( 'usam_banner_insert_data', $this->data );
			$format = $this->get_data_format();
			$data = $this->data;
			$data['settings'] = maybe_serialize($data['settings']);				
			$result = $wpdb->insert( USAM_TABLE_BANNERS, $data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_banner_insert', $this );
		} 		
		do_action( 'usam_banner_save', $this );

		return $result;
	}
}

function usam_get_banner( $id, $colum = 'id' )
{
	$banner = new USAM_Banner( $id, $colum );
	return $banner->get_data( );	
}

function usam_delete_banner( $id ) 
{
	$banner = new USAM_Banner( $id );
	$result = $banner->delete( );
	return $result;
}

function usam_insert_banner( $data, $banner_locations = null ) 
{
	$c = new USAM_Banner( $data );
	$c->save();
	$id = $c->get('id');
	if ( $id )
	{		
		if ( is_array($banner_locations) )
			usam_set_banner_location( $id, $banner_locations );
	}
	return $id;
}

function usam_update_banner( $id, $data, $banner_locations = null ) 
{
	$result = false;
	if ( $id )
	{
		$c = new USAM_Banner( $id );	
		if ( $data )
		{
			$c->set( $data );
			$result = $c->save();
		}
		if ( is_array($banner_locations) )
		{
			if ( usam_set_banner_location( $id, $banner_locations ) )
				$result = true;
		}
	}
	return $result;
}

function usam_get_banner_statuses( ) 
{	
	return ['draft' => __('Отключен','usam'), 'active' => __('Активен','usam')];	
}

function usam_get_status_name_banner( $status ) 
{	
	$statuses = usam_get_banner_statuses( );	
	if ( isset($statuses[$status]) )
		return $statuses[$status];
	else
		return '';
}

function usam_get_banner_location( $banner_id ) 
{	
	global $wpdb;
	$results = $wpdb->get_col( "SELECT banner_location FROM ".USAM_TABLE_BANNER_RELATIONSHIPS." WHERE banner_id='$banner_id'" );	
	return $results;
}

function usam_get_banner_locations( ) 
{		
	$object_type = 'usam_banner_locations';		
	$cache = wp_cache_get( $object_type );
	if ( $cache === false )		
	{			
		global $wpdb;
		$results = $wpdb->get_results( "SELECT banner_location, banner_id FROM ".USAM_TABLE_BANNER_RELATIONSHIPS."" );	
		$cache = array();
		foreach ( $results as $result ) 
		{
			$cache[$result->banner_location][] = $result->banner_id;
		}
		wp_cache_set( $object_type, $cache );
	}	
	return $cache;
}

function usam_set_banner_location( $banner_id, $new_banner_locations, $append = false ) 
{	
	global $wpdb;
	
	if ( !is_array($new_banner_locations) )
		$new_banner_locations = array($new_banner_locations);	
	$i = 0;
	$banner_locations = usam_get_banner_location( $banner_id );
	foreach ( $new_banner_locations as $key => $banner_location ) 
	{ 
		if ( !in_array($banner_location, $banner_locations) )
		{
			$insert = $wpdb->insert( USAM_TABLE_BANNER_RELATIONSHIPS, ['banner_id' => $banner_id, 'banner_location' => $banner_location], ['%d', '%s'] );	
			if ( $insert )
				$i++;
			unset($new_banner_locations[$key]);
		}
	}
	if ( $append == false )
	{
		$results = array_diff($banner_locations, $new_banner_locations);
		if ( !empty($results) )
		{
			usam_delete_banner_location( $banner_id, $results );
		}
	}
	return $i;
}

function usam_get_theme_banner( $banner, $args = [], $footer = true ) 
{	
	if ( is_numeric($banner) )
		$banner = usam_get_banner( $banner );
	if ( empty($banner) )
		return '';
	$default = ['layouttype' => 'layout', 'size' => ['computer' => ['width' => '100%', 'height' => '300px'], 'notebook' => ['width' => '100%', 'height' => '300px'], 'tablet' => ['width' => '100%', 'height' => '300px'], 'mobile' => ['width' => '100%', 'height' => '200px']]];	 
	$slide_settings =& $banner['settings'];
	$slide_settings = array_merge( $default, $slide_settings );
	$content = ''; 
	$lzy = isset($args['lzy'])?$args['lzy']:true;
	$video_iframe = '';
	switch ( $banner['type'] ) 
	{		
		case 'html' :
			$content = !empty($banner['settings']['html']) ? $banner['settings']['html'] : '';
		break;			
		case 'shops' :
			global $post;
			$location_id = usam_get_customer_location( );
			$storages = usam_get_storages(['fields' => 'count', 'issuing' => 1, 'product_id' => $post->ID, 'location_id' => [0, $location_id], 'number' => 1]);
			if ( $storages )
				$content = "<p>".sprintf(_n('Забрать в %s магазине', 'Забрать в %s магазинах',$storages,'usam'),$storages)."</p>".(empty($banner['settings'])?'':$banner['settings']['html']);
			else
				$content = "<p>".__("Временно отсутствует в магазинах", "usam")."</p>";
		break;
		default:
			$tag_args = ['class' => 'usam_banner_content_image'];
			$tag_args['class'] .= !empty($banner['settings']['effect'])?'effect_'.$banner['settings']['effect']:'';			
			if( $banner['type'] == 'product_day_image' )
			{
				$ids = usam_get_active_products_day_id_by_codeprice();
				if( $ids )
					$url = usam_get_product_thumbnail_src( $ids[0], 'full' );
			}
			else
			{
				$url = $banner['object_url'] ? $banner['object_url'] : '';
				if ( $banner['object_id'] )
				{				
					$src = wp_get_attachment_image_src( $banner['object_id'], 'full' );	
					if ( !empty($src[0]) )
					{
						$url = $src[0];	
						if ( $lzy )
						{
							$tag_args['data-src'] = $url;
							$tag_args['class'] .= ' js-lzy-img';	
							$url = usam_create_image_by_size( $banner['object_id'], 100, !empty($src[1]) ? round(100 * ($src[2]/$src[1])) : 100 );	
						}
					}
				}
			}
		//	$size = $banner['settings']['css']['background-size']=='percent'?$banner['settings']['css']['percent']:$banner['settings']['css']['background-size'];
			$tag_args['style'] = '';
			if ( !empty($banner['settings']['css']) )
			{
				foreach( $banner['settings']['css'] as $name => $key )
					$tag_args['style'] .= "$name:$key;";
			}
			if ( $banner['settings']['layouttype'] == 'image' && !empty($banner['settings']['object_size']['width']) )
				$tag_args['style'] .= "height:auto;padding:0 0 ".$banner['settings']['object_size']['height']*100/$banner['settings']['object_size']['width'].'% 0;';
			$tag_args['style'] .= "background-image: url($url);";
			if ( $banner['type'] == 'products' && !empty($banner['settings']['products']) )
			{
				$post_ids = [];
				foreach( $banner['settings']['products'] as $product )
					$post_ids[] = $product['product_id'];
				usam_get_products(['post__in' => $post_ids, 'update_post_meta_cache' => true], true);
				foreach( $banner['settings']['products'] as $product )
				{
					$content .= "<div class='banner_point banner_point_".$product['product_id']."'>";
					ob_start();
					include( usam_get_template_file_path('product-banner', 'template-parts') ); 
					$content .= ob_get_clean();	
					$content .= "</div>";					
				}
			}
			elseif ( $banner['type'] == 'vimeo' )
			{
				$autoplay = !empty($slide_settings['autoplay']) || $banner['object_url'];
				$url_args = [];
				if( $autoplay )
					$url_args[] = 'autoplay=1&loop=1&autopause=0&muted='.(!empty($slide_settings['muted'])?0:1);
				if( !empty($slide_settings['quality']) )
					$url_args[] = 'quality='.$slide_settings['quality'];
				if( empty($slide_settings['controls']) )
					$url_args[] = 'controls=0';
				$url = implode('&', $url_args);
				ob_start();
				?>
				<iframe src="https://player.vimeo.com/video/<?php echo $slide_settings['video_id']; ?>?<?php echo $url; ?>" width="100%" height="100%" frameborder="0" allow="<?php echo $autoplay?'autoplay':''; ?>;picture-in-picture;<?php echo !empty($slide_settings['muted'])?'muted':''; ?>" allowfullscreen></iframe>
				<?php
				if( $banner['object_url'] )
					$video_iframe = ob_get_clean();	
				else
					$content .= ob_get_clean();					
			}
			elseif ( $banner['type'] == 'youtube' )
			{
				$url_args = [];
				$autoplay = !empty($slide_settings['autoplay']) || $banner['object_url'];
					
				if( $autoplay )
					$url_args[] = 'autoplay=1&muted='.(!empty($slide_settings['muted'])?0:1);
				if( !empty($slide_settings['quality']) )
					$url_args[] = 'quality='.$slide_settings['quality'];
				$url = implode('&', $url_args);
				ob_start();
				?>
				<iframe src="https://www.youtube.com/embed/<?php echo $slide_settings['video_id']; ?>?<?php echo $url; ?>" width="100%" height="100%" frameborder="0" allow="autoplay=<?php echo $autoplay?1:0; ?>;<?php echo !empty($slide_settings['muted'])?'muted;':''; ?>"></iframe>
				<?php
				if( $banner['object_url'] )
					$video_iframe = ob_get_clean();	
				else
					$content .= ob_get_clean();						
			}	
			elseif ( $banner['type'] == 'video' )
			{
				$autoplay = !empty($slide_settings['autoplay']) || $banner['object_url'];
				ob_start();
				?>
				<video playsinline <?php echo $autoplay?'autoplay':''; ?> loop <?php echo empty($slide_settings['muted'])?'muted':''; ?> poster="<?php echo $banner['object_url']; ?>">
					<?php if ( $slide_settings['video_mp4'] ) { ?>
						<source src="<?php echo $slide_settings['video_mp4']; ?>" type="video/mp4">
					<?php } ?>
					<?php if ( $slide_settings['video_webm'] ) { ?>
						<source src="<?php echo $slide_settings['video_webm']; ?>" type="video/webm">
					<?php } ?>
				</video>		
				<?php	
				if( $banner['object_url'] )
					$video_iframe = ob_get_clean();	
				else
					$content .= ob_get_clean();				
			}	
			elseif( !empty($banner['settings']['html']) )
				$content .= "<div class='usam_banner__text'>".$banner['settings']['html']."</div>";
			if( $banner['type'] === 'video' )
			{
				$tag_args['video'] = $banner['settings']['video'];
			}
			$tag = '';
			foreach( $tag_args as $name => $value )
				$tag .= "$name='$value' ";
			$content .= "<div $tag></div>";
		break;			
	}
	$edit_html = '';	 
	if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
		$edit_html = "<a href='".admin_url( 'admin.php?page=interface&tab=banners&table=banners&form=edit&form_name=banner&id='.$banner['id'] )."' class='edit_banner'>".__('Изменить', 'usam')."</a>";
	if ( !empty($banner['settings']['layers']) )
	{
		$side_id = "banner-".$banner['id'];		
		ob_start();				
		include( usam_get_template_file_path('layer-grid', 'template-parts') );	
		$content = ob_get_clean().$content; 		
	}
	$anonymous_function = function() use ( $banner, $video_iframe )
	{ 
		$js_content = '';
		if ( $banner['settings']['layouttype'] == 'fullscreen' ) 
		{
			$js_content = "var ff = (e) => {
				document.querySelectorAll('#banner-".$banner['id']."').forEach((el) => { 
					el.style.width = document.documentElement.clientWidth+'px';		
					el.style.height = document.documentElement.clientHeight+'px';						
					el.style.left = '-'+el.getBoundingClientRect().x+'px';
				});
				document.querySelectorAll('#banner-".$banner['id']." .slider_slide').forEach((el) => el.style.height = window.innerHeight+'px');		
			}
			ff();
			window.addEventListener('resize', ff);";
		} 
		else if ( $banner['settings']['layouttype'] === 'fullscreenwidth' ) 
		{				
			$js_content = "var ff = (e) => {
				document.querySelectorAll('#banner-".$banner['id']."').forEach((el) => { 
					el.style.left = '-'+el.getBoundingClientRect().x+'px';
					el.style.width = document.documentElement.clientWidth+'px';					
				});
			}
			ff();
			window.addEventListener('resize', ff); ";
		} 
		else if ( $banner['settings']['layouttype'] === 'fullscreenheight' ) 
		{				
			$js_content = "var ff = (e) => {
				document.querySelectorAll('#banner-".$banner['id']."').forEach((el) => { 
					el.style.height = document.documentElement.clientHeight+'px';					
				});
			}
			ff();
			window.addEventListener('resize', ff); ";
		} 		
		if( !empty($video_iframe) )
		{
			$js_content .= "var fFrame = (e) => {
				e.currentTarget.querySelector('.usam_banner_content').innerHTML = `$video_iframe`;
				e.currentTarget.removeEventListener('click', fFrame);
			}
			document.getElementById('banner-".$banner['id']."').addEventListener('click', fFrame); ";
		}
		if( $js_content )
			echo "<script>document.addEventListener('DOMContentLoaded', () => {".$js_content."})</script>";
		?>	
		<style>	
		<?php 		
		if ( !empty($banner['settings']['overflow']) && $banner['settings']['overflow'] == 'hidden' )
		{
			?>#banner-<?php echo $banner['id']; ?> .usam_banner_content{overflow:hidden}<?php
		}
		if ( $banner['type'] == 'products' && !empty($banner['settings']['products']) )
		{
			foreach( $banner['settings']['products'] as $product )
			{
				foreach ($banner['settings']['devices'] as $device => $status)	
				{
					if ( $status )
					{
						$banner_point_id = "#banner-".$banner['id']." .banner_point_".$product['product_id'];
						$css_content = "inset:".$product[$device]['inset'].";";
						switch ( $device ) 
						{
							case 'computer' :								
								echo $banner_point_id.'{'.$css_content.'}';
							break;
							case 'notebook' :
								?>
								@media screen and (max-width: 1023px)
								{
									<?php echo $banner_point_id.'{'.$css_content.'}'; ?>
								}
							<?php
							break;
							case 'tablet' :
								?>						
								@media screen and (max-width: 777px)
								{
									<?php echo $banner_point_id.'{'.$css_content.'}'; ?>
								}
							<?php
							break;
							case 'mobile' :
								?>						
								@media screen and (max-width: 479px)
								{
									<?php echo $banner_point_id.'{'.$css_content.'}'; ?>
								}
							<?php
							break;
						}
					}
				}				
			}
		}
		?>#banner-<?php echo $banner['id']; ?>{<?php 
			echo !empty($banner['settings']['custom_css']) ? $banner['settings']['custom_css']:""; 		
			foreach(['margin', 'padding', 'z-index', 'border-radius', 'background-color'] as $k)	
				echo !empty($banner['settings'][$k]) ? $k.':'.$banner['settings'][$k].';':'';		
		?>}<?php
		if( !empty($banner['settings']['layouttype']) && $banner['settings']['layouttype'] !== 'image' && $banner['settings']['layouttype'] !== 'css' ) 
		{
			?>
			#banner-<?php echo $banner['id']; ?>{height: <?php echo $banner['settings']['size']['computer']['height']; ?>}
			#banner-<?php echo $banner['id']; ?> .usam_banner_content{height:100%}
			<?php
			if( $banner['settings']['layouttype'] != 'fullscreen' )
			{ 
				if( $banner['settings']['layouttype'] == 'layout' && stripos($banner['settings']['size']['computer']['width'], '%') === false ) 
				{ 
					?>
					#banner-<?php echo $banner['id']; ?> .usam_banner_content{width: <?php echo $banner['settings']['size']['computer']['width']; ?>;}
					@media screen and (max-width:<?php echo $banner['settings']['size']['computer']['width']; ?>) 
					{
						#banner-<?php echo $banner['id']; ?> .usam_banner_content{width:100%;}
					} <?php
				} ?>
				@media screen and (min-width: 778px) and (max-width: 1023px)
				{
					#banner-<?php echo $banner['id']; ?>{height:<?php echo $banner['settings']['size']['notebook']['height']; ?>}
				}	
				@media screen and (min-width: 480px) and (max-width: 777px)
				{
					#banner-<?php echo $banner['id']; ?>{height:<?php echo $banner['settings']['size']['tablet']['height']; ?>}
				}
				@media screen and (max-width: 479px)
				{
					#banner-<?php echo $banner['id']; ?>{height:<?php echo $banner['settings']['size']['mobile']['height']; ?>}
				}	
				<?php
			}
			if( $banner['settings']['layouttype'] == 'fullscreen' || $banner['settings']['layouttype'] == 'fullscreenwidth' ) 
			{
				?>#banner-<?php echo $banner['id']; ?> .usam_banner_content{position: absolute; width:100%}<?php
			}			
			if( !empty($banner['settings']['size']['computer']) )
			{
				?>
				#banner-<?php echo $banner['id']; ?> .layer_grid{width: <?php echo $banner['settings']['size']['computer']['width']; ?>;}
				@media screen and (max-width:<?php echo $banner['settings']['size']['computer']['width']; ?>) 
				{
					#banner-<?php echo $banner['id']; ?> .layer_grid{width:100%;}
				}		
				<?php
			}
		}	
		else
		{
			?>#banner-<?php echo $banner['id']; ?> .layer_grid{width:100%;}<?php
		}
		if ( !empty($banner['settings']['layers']) )
		{
			$count = count($banner['settings']['layers']);
			$screens = ['computer' => '', 'notebook' => '1023px', 'tablet' => '777px', 'mobile' => '479px'];
			foreach ($banner['settings']['layers'] as $i => $layer)		
			{				
				$css = "";				
				if ( $layer['transform']['originx'] || $layer['transform']['originy'] )
					$css .= 'transform-origin: '.$layer['transform']['originx'].'% '.$layer['transform']['originy'].'%;';					
				$t = '';
				if ( $layer['transform']['z'] )
					$t .= ' translate3d('.$layer['transform']['x'].', '.$layer['transform']['y'].', '.$layer['transform']['z'].')';						
				else if ( $layer['transform']['x'] || $layer['transform']['y'] )
					$t.= ' translate('.$layer['transform']['x'].', '.$layer['transform']['y'].')';
				if ( $layer['transform']['scalex'] || $layer['transform']['scaley'] )
					$t.= ' scale('.$layer['transform']['scalex'].', '.$layer['transform']['scaley'].')';
				if ( $layer['transform']['skewx'] || $layer['transform']['skewy'] )
					$t.= ' skew('.$layer['transform']['skewx'].', '.$layer['transform']['skewy'].')';
				if ( $layer['transform']['rotatex'] )
					$t.= ' rotateX('.$layer['transform']['rotatex'].')';
				if ( $layer['transform']['rotatey'] )
					$t.= ' rotateY('.$layer['transform']['rotatey'].')';
				if ( $layer['transform']['rotatez'] )
					$t.= ' rotateZ('.$layer['transform']['rotatez'].')';	
				if ( $t )
					$css .= 'transform:'.$t.';';
				if ( $layer['animation_in'] )
					$css.= 'transition:'.$layer['animation_in'].' '.$layer['duration'].'s '.$layer['easing'].' '.$layer['delay'].'s;';	
				$layer_id = "#banner-".$banner['id']."-layer-$i";
				$layer_content_id = "$layer_id .slide_layer_content";
				if ( $layer['group'] )
					$layer_id = ".slide_layer ".$layer_id;
				elseif ( $layer['type'] == 'group' )
					$layer_content_id = $layer_id;
				if ( $css )
					echo $layer_id.'{'.$css.'}';
				foreach ($banner['settings']['devices'] as $device => $status)	
				{
					if ( $status )
					{						
						$css_content = '';
						foreach ($layer[$device]['css'] as $name => $value)
							if ( $value !== '' )
								$css_content .= "$name:$value;";
						$css_content_hover = '';
						if( !empty($layer['hover_active']) && !empty($layer[$device]['hover']) )
						{							
							foreach( $layer[$device]['hover'] as $name => $value)
								if ( $value !== '' )
									$css_content_hover .= "$name:$value;";
						}
						$t = '';
						if( $layer[$device]['transform'] )
						{
							$t = "transform:".$layer[$device]['transform'];
							if( !empty($layer['rotate']) )
								$t .= ' rotate('.$layer['rotate'].'deg);';
						}
						elseif( !empty($layer['rotate']) )
							$t = "transform:rotate(".$layer['rotate']."deg);";
						else
							$t = "transform:none;";
						$css = $layer['group'] ? "" : 'z-index:'.(50+$count-$i).";inset:".$layer[$device]['inset'].";$t;";
						if( $screens[$device] )
						{
							?>
							@media screen and (max-width: <?php echo $screens[$device]; ?>){
							<?php
						}
								if ( $css )
									echo $layer_id.'{'.$css.'}';
								echo $layer_content_id.'{'.$css_content.'}';
								if ( $css_content_hover )
									echo $layer_content_id.':hover{'.$css_content_hover.'}';
						if( $screens[$device] )
						{
							?>	} <?php
						}
					}
				}								
			}
		}
		?>				
	</style>
	<?php
	};
	if ( $footer )
	{ 
		add_action('wp_footer', $anonymous_function, 2);
		add_action('admin_footer', $anonymous_function, 2);	
		add_action('usam_load_banner', $anonymous_function, 2);	
	}
	else
		$anonymous_function();
	if (!empty($slide_settings['actions']['type']))
	{
		switch( $slide_settings['actions']['type'] ) 
		{		
			case 'link' :
				$content .= "<a href='".$slide_settings['actions']['value']."' class='layer_grid_action'></a>";
			break;	
			case 'webform' :
				$content .= "<a href='#webform_".$slide_settings['actions']['value']."' class = 'js-feedback usam_modal_feedback banner_action layer_grid_action'></a>";
			break;	
			case 'modal' : 
				$content .= "<a href='' class='usam_modal banner_action layer_grid_action' data-modal='".$slide_settings['actions']['value']."'></a>";
			break;							
		}						
	}
	$class = isset($args['class'])?$args['class']:'';
	$class .= !empty($banner['settings']['classes'])?' '.$banner['settings']['classes']:'';
	$class .= ' slide_'.$banner['type'];
	$content = "<div id='banner-{$banner['id']}' class='usam_banner $class'><div class='usam_banner_content'>{$edit_html}{$content}</div></div>";
	return $content;
}

function usam_get_theme_banners( $args = [] )
{	
	$args = array_merge(['number' => 0], $args );
	require_once( USAM_FILE_PATH . '/includes/theme/banners_query.class.php' );	
	
	$object_type = 'usam_banners';		
	$cache = wp_cache_get( $object_type );
	if ( $cache === false )		
	{			
		$device = wp_is_mobile()?'mobile':'desktop';
		$query_args = ['status' => 'active', 'orderby' => 'sort', 'device' => ['', $device], 'acting_now' => true];
		$cache = usam_get_banners( $query_args );			
		wp_cache_set( $object_type, $cache );
		$image_ids = [];
		foreach ($cache as $banner)
		{
			if( $banner->object_id )
				$image_ids[] = $banner->object_id;
		}
		if( $image_ids ) 
			$attachments = (array)get_posts(['post_type' => 'attachment', 'post_status' => 'all', 'post__in' => $image_ids, 'numberposts' => -1, 'update_post_term_cache' => false]);	
	}
	$banner_locations = usam_get_banner_locations();	
	
	$banners = [];
	$i = 0;	
	foreach ($cache as $banner) 
	{					
		$add = false;
		if ( !empty($args['ids']) )
		{
			if ( in_array($banner->id, $args['ids']) )
				$add = true;
		}
		elseif ( (empty($args['banner_location']) || !empty($banner_locations[$args['banner_location']]) && in_array($banner->id, $banner_locations[$args['banner_location']])) )
			$add = true;
		if( $add )
		{
			$banners[] = (array)$banner;
			$i++;
			if ( $i == $args['number'] )
				break;
		}
	}			
	if ( !empty($args['orderby']) && $args['orderby'] == 'random' )
		shuffle($banners);
	return $banners;
}

function usam_theme_banners( $args = [] )
{
	$banners = usam_get_theme_banners( $args );	
	foreach ($banners as $banner) 
	{ 
		echo usam_get_theme_banner( (array)$banner, $args );
	}
	if ( empty($args['number']) || count($banners) < $args['number'] )
	{
		usam_change_block( admin_url( "admin.php?page=interface&tab=banners" ), __("Добавить баннер", "usam") );
	}
}

function usam_delete_banner_location( $banner_id, $banner_location ) 
{	
	global $wpdb;	
	if ( !is_array($banner_location) )
		$banner_location = array($banner_location);
	return $wpdb->query("DELETE FROM `".USAM_TABLE_BANNER_RELATIONSHIPS."` WHERE banner_id='$banner_id' AND banner_location IN ('".implode("','",$banner_location)."')"); 
}

function usam_register_banners( )
{
	$banners = ['purchase_terms' => __("Баннеры в карточке товаров","usam"), 'ordering' => __("Баннеры при оформлении заказа","usam")];
	return apply_filters( 'usam_register_banners', $banners );	
}
?>