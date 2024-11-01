<?php
// Управление слайдером
class USAM_Slider
{
	 // строковые
	private static $string_cols = array(		
		'name',		
		'source',			
		'settings',
		'device',	
		'settings',		
	);
	
	// цифровые
	private static $int_cols = array(
		'id',		
		'active',					
	);
	// рациональные
	private static $float_cols = [];	
	private $data     = array();		
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
	private $exists   = false; // если существует строка в БД
	
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
		if ( $col == 'code'  )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_slider' );
		}			
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
		
		if ( in_array( $col, self::$float_cols ) )
			return '%f';
		if ( $col === 'slides' )
			return true;
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_slider' );			
		do_action( 'usam_slider_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_slider' );	
		wp_cache_delete( $this->get( 'id' ), 'usam_slides' );	
		do_action( 'usam_slider_delete_cache', $this );	
	}
	
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );		
		$data = $this->get_data();
		do_action( 'usam_slider_before_delete', $data );
		
		$this->delete_cache( );	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SLIDES." WHERE slider_id = '$id'");
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SLIDER." WHERE id = '$id'");
		
		do_action( 'usam_slider_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SLIDER." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{		
			$this->exists = true;			
			$data['settings'] = maybe_unserialize($data['settings']);			
			$this->data = apply_filters( 'usam_slider_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache( );
		}			
		do_action( 'usam_slider_fetched', $this );	
		$this->fetched = true;			
	}
	
	public function get_slides()
	{
		$object_type = 'usam_slides';	
		$slider_id = $this->get( 'id' );
		$cache = false;
		if ( $slider_id )
		{
			if( ! $cache = wp_cache_get($object_type, $slider_id ) )			
			{				
				global $wpdb;	
				$cache = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SLIDES." WHERE slider_id = %d", $slider_id) );
				foreach ($cache as $k => &$value ) 
				{				
					$value->settings = $value->settings ? maybe_unserialize($value->settings) : [];
				}
				wp_cache_set( $object_type, $cache, $slider_id );						
			}
		}
		return $cache;
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
		return apply_filters( 'usam_slider_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_slider_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();
		
		foreach ( $properties as $key => &$value ) 
		{						
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;			
		}	
		$this->data = apply_filters( 'usam_slider_set_properties', $this->data, $this );			
		return $this;
	}
		
	
	private function get_data_format_slides( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
		{			
			$format = false;
			
			if ( in_array( $key, ['object_url', 'type', 'title', 'link', 'settings','start_date', 'end_date'] ) )
				$format = '%s';
			elseif ( in_array( $key, ['id','slider_id', 'object_id', 'sort'] ) )
				$format = '%d';
			
			if ( $format !== false )		
				$formats[$key] = $format;			
		}
		return $formats;
	}	

	public function insert_slides( $slide ) 
	{
		global $wpdb;	
		
		if ( isset($slide['settings']) )
			$slide['settings'] = maybe_serialize($slide['settings']);		
		if ( isset($slide['id']) )
			unset($slide['id']);
		$slide['slider_id'] = $this->get('id');		
		if ( isset($slide['start_date']) && $slide['start_date'] == '' )
			unset($slide['start_date']);				
		$format = $this->get_data_format_slides( $slide );			
		foreach ( $slide as $key => $value )
		{
			if ( !isset($format[$key]) )
				unset($slide[$key]);
		}
		$result = $wpdb->insert( USAM_TABLE_SLIDES, $slide, $format );		
		return $wpdb->insert_id;
	}	
	
	public function update_slides( $slide ) 
	{
		global $wpdb;

		if ( empty($slide['id']) || count($slide) < 2 )
			return false;
		
		if ( isset($slide['settings']) )
			$slide['settings'] = maybe_serialize($slide['settings']);
		$format = $this->get_data_format_slides( $slide );
		foreach ( $slide as $key => $value )
		{
			if ( !isset($format[$key]) )
				unset($slide[$key]);
		}
		foreach ( $format as $key => $value ) 
		{				
			if ( $slide[$key] == null )
			{
				$str[] = "`$key` = NULL";
				unset($slide[$key]);
			}
			else
				$str[] = "`$key` = '$value'";	
		}		
		$result = $wpdb->query( $wpdb->prepare( "UPDATE `".USAM_TABLE_SLIDES."` SET ".implode( ', ', $str )." WHERE id='%d'", array_merge( array_values( $slide ), array( $slide['id'] ) ) ) );	
		return $result;		
	}
		
	public function save_slides( $new_slides ) 
	{
		$result = false;
		$slides = $this->get_slides();
		foreach ( $new_slides as $slide ) 
		{						
			if ( is_object($slide) )
				$slide = (array)$slide;
			if ( !empty($slide['id']) )
			{
				if ( $this->update_slides( $slide ) )
					$result = true;
				foreach ( $slides as $i => $v ) 
					if ( $slide['id'] == $v->id )	
						unset($slides[$i]);
			}
			elseif ( $this->insert_slides( $slide ) )
				$result = true;
		}	
		if ( $slides )
		{
			if( $this->delete_slides( $slides ) )
				$result = true;
		}		
		return $result;
	}
	
	public function delete_slides( $slides ) 
	{
		global $wpdb;
		$ids = [];
		foreach ( $slides as $slide ) 
			$ids[] = $slide->id;	
		$result = false;
		if ( $ids )
			$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SLIDES." WHERE id IN (".implode(',',$ids).")");
		return $result;
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

		do_action( 'usam_slider_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{		
			$where_format = self::get_column_format( $this->args['col'] );			
			
			do_action( 'usam_slider_pre_update', $this );	

			$this->data = apply_filters( 'usam_slider_update_data', $this->data );			
			$format = $this->get_data_format( );
			$data = $this->data;
			if ( isset($data['settings']) )
				$data['settings'] = maybe_serialize($data['settings']);					
			$result = $wpdb->update( USAM_TABLE_SLIDER, $data, [$this->args['col'] => $this->args['value']], $format, array($where_format) );					
			if ( $result )
			{
				$this->delete_cache( );			
				do_action( 'usam_slider_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_slider_pre_insert' );		
			if( isset($this->data['id']) )		
				unset( $this->data['id'] );	
		
			if( !isset($this->data['name']) )		
				$this->data['name'] = '';

			if( !isset($this->data['active']) )		
				$this->data['active'] = 0;			

			if( !isset($this->data['source']) )		
				$this->data['source'] = 'custom';
			
			if( !isset($this->data['settings']) )		
				$this->data['settings'] = '';
			
			$this->data = apply_filters( 'usam_slider_insert_data', $this->data );			
			$format = $this->get_data_format();
			$data = $this->data;
			$data['settings'] = maybe_serialize($data['settings']);			
			
			$result = $wpdb->insert( USAM_TABLE_SLIDER, $data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				do_action( 'usam_slider_insert', $this );				
			}			
		} 					
		do_action( 'usam_slider_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_slider( $id, $data, $slides = null )
{
	$result = false;
	$slider = new USAM_Slider( $id );
	if ( $data )
	{
		$slider->set( $data );
		$result = $slider->save();
	}
	if ( $slides !== null )
		if( $slider->save_slides( $slides ) )
			$result = true;
	return $result;
}

// Получить
function usam_get_slider( $id )
{
	$slider = new USAM_Slider( $id );
	$data = $slider->get_data();
	if ( empty($data) )
		return array();
	
	$data['slides'] = $slider->get_slides();	
	usort($data['slides'], function($a, $b){  return ($a->sort - $b->sort); });
	
	return $data;	
}

// Добавить
function usam_insert_slider( $data, $slides = null )
{
	if ( !empty($data['slides']) )
	{
		$slides = $data['slides'];
		unset($data['slides']);
	}
	$slider = new USAM_Slider( $data );
	$result = $slider->save();
	if ( $result )
	{
		if ( !empty($slides) )
		{
			foreach( $slides as $slide ) 
			{
				if ( is_object($slide) )
					$slide = (array)$slide;
				$slider->insert_slides( $slide );
			}
		}
		return $slider->get( 'id' );
	}
	else
		return false;
}

// Удалить
function usam_delete_slider( $id )
{
	$slider = new USAM_Slider( $id );
	return $slider->delete();
}

function usam_delete_slides( $slide_id ) 
{
	global $wpdb;

	$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SLIDES." WHERE id = '$slide_id'");
	return $result;		
}

function usam_display_slider( $number = 1 )
{
	if ( !is_admin() )
		usam_change_block( admin_url( "admin.php?page=interface&tab=sliders&form=edit&form_name=slider&id={$number}" ), __("Изменить слайдер", "usam") );
	
	$slider = usam_get_slider( $number );	
	if ( empty($slider) ) 
		return;		

	$current_device = wp_is_mobile();	
	if ( !empty($slider['device']) && ($current_device == false && $slider['device'] == 'mobile' || $current_device && $slider['device'] == 'desktop') ) 
		return;	
	
	if ( empty($slider['slides']) ) 
		return;		
		
	if ( !empty($slider['settings']['condition']) ) 
	{
		if ( !usam_conditions_user( $slider['settings']['condition'] ) )
			return ;
	} 
	$current_time = current_time('timestamp');	
	$slides = array();
	$thumb_ids = array(); 
	foreach( $slider['slides'] as $key => $slide )
	{		
		if ( ( !empty($slide->end_date) && strtotime($slide->end_date) < $current_time ) || ( !empty($slide->start_date) && strtotime($slide->start_date) > $current_time ) )
			continue;
	
		if (  $slider['source'] == 'custom' )
			$slides[] = ['id' => $slide->id, 'settings' => $slide->settings, 'link' => $slide->link, 'object_url' => $slide->object_url, 'type' => $slide->type, 'title' => $slide->title];
		elseif (  $slider['source'] == 'products' )
		{			
			$post_title = get_the_title( $slide->object_id );			
			$thumbnail_id = get_post_thumbnail_id($slide->object_id);
			$link = usam_product_url( $slide->object_id );
			
			$slides[] = ['id' => $slide->id, 'object_id' => $thumbnail_id, 'link' => $link, 'object_url' => $slide->object_url, 'type' => $slide->type, 'title' => $slide->title];
			$thumb_ids[] = $slide->object_id;
		}			
	}		
	if ( !$slides )
		return false;	
	
	_prime_post_caches( $thumb_ids, false, true );	
	$autospeed = !empty($slider['settings']['autospeed'])?(int)$slider['settings']['autospeed']:100;
	?>
	<div id="slider-<?php echo $slider['id']; ?>" class="slider loading-slider">				
		<?php 
		$file_path = usam_get_template_file_path( 'slider', 'template-parts' );
		if ( $file_path )
			include( $file_path );
		?>
	</div>			
	<style>			
		#slider-<?php echo $slider['id']; ?>{position:relative;
			<?php
			foreach(['margin', 'padding', 'z-index', 'border-radius', 'overflow'] as $k)	
				echo !empty($slider['settings'][$k]) ? $k.':'.$slider['settings'][$k].';':'';
			if( !empty($slider['settings']['button']['design']) && $slider['settings']['button']['design'] === 'indicator' && !empty($slider['settings']['button']['show']) )
			{
				?>display: flex; flex-direction: column;<?php
			}	
			?>		
		}
		.slider .slides{display:block;height:100%;}		
		#slider-<?php echo $slider['id']; ?> .layer_grid{width:<?php echo $slider['settings']['size']['computer']['width'].';'; ?>}
		#slider-<?php echo $slider['id']; ?> .slide_image_container{overflow:hidden;position:absolute; height:100%;}
		<?php 			
		foreach ($slides as $slide_number => $slide)
		{		
			if ( !empty($slide['settings']['custom_css']) )
			{
				echo "#slider-".$slider['id']." .slider-".$slider['id']."-slide-".$slide['id']; ?>{<?php echo $slide['settings']['custom_css']; ?>}<?php
			}
		}
		if ( $slider['settings']['layouttype'] == 'image' )
		{
			?>
			#slider-<?php echo $slider['id']; ?> .slide_image{width:100%; height:100%}
			#slider-<?php echo $slider['id']; ?> .slides{height:auto}
			#slider-<?php echo $slider['id']; ?> .slide_image_container{left:50%; transform: translate(-50%, 0%);}
			<?php			
			foreach ($slides as $slide_number => $slide)
			{
				$side_id = "#slider-".$slider['id']." .slider-".$slider['id']."-slide-".$slide['id'];
				if ( !empty($slide['settings']['object_size']['width']) )
				{
					echo $side_id; ?>{padding:0 0 <?php echo $slide['settings']['object_size']['height']*100/$slide['settings']['object_size']['width']; ?>% 0;'}<?php
				}
			}
		}
		elseif ( $slider['settings']['layouttype'] !== 'css' ) 
		{
			?>#slider-<?php echo $slider['id']; ?> .slide_image{width:100%; height:100%}<?php			
			if( $slider['settings']['layouttype'] === 'layout' )
			{
				?>#slider-<?php echo $slider['id']; ?> .slide_image_container{left:50%; transform: translate(-50%, 0%);width:<?php echo $slider['settings']['size']['computer']['width'] ?>;}<?php
			}
			else
			{
				?>#slider-<?php echo $slider['id']; ?> .slide_image_container{left:0;right:0;}<?php
			}				
			if( $slider['settings']['layouttype'] != 'fullscreen' && $slider['settings']['layouttype'] != 'fullscreenheight' ) 
			{ 
				?>#slider-<?php echo $slider['id']; ?> .slides{height:<?php echo $slider['settings']['size']['computer']['height']; ?>;}<?php
				if( $slider['settings']['layouttype'] == 'layout' && stripos($slider['settings']['size']['computer']['width'], '%') === false ) { ?>
					@media screen and (max-width:<?php echo $slider['settings']['size']['computer']['width']; ?>) 
					{
						#slider-<?php echo $slider['id']; ?> .layer_grid,
						#slider-<?php echo $slider['id']; ?> .slide_image_container{width:100%;}				
					} <?php 
				} 				
				?>
				@media screen and (min-width: 778px) and (max-width: 1023px)
				{
					#slider-<?php echo $slider['id']; ?> .slides{height:<?php echo $slider['settings']['size']['notebook']['height']; ?>}
				}	
				@media screen and (min-width: 480px) and (max-width: 777px)
				{
					#slider-<?php echo $slider['id']; ?> .slides{height:<?php echo $slider['settings']['size']['tablet']['height']; ?>}
				}
				@media screen and (max-width: 479px)
				{
					#slider-<?php echo $slider['id']; ?> .slides{height:<?php echo $slider['settings']['size']['mobile']['height']; ?>}
				}	
				<?php 
			} 
		}
		if( empty($slider['settings']['button']['design']) )
		{
			?>
			#slider-<?php echo $slider['id']; ?> .slider_buttons{display:flex; position:absolute; z-index: 10; 
			<?php 
				if( isset($slider['settings']['button']['position']) )
				{
					switch ( $slider['settings']['button']['position'] ) 
					{					
						case 'top left':
							?>top:0px; left:0;<?php
						break;
						case 'top center':
							?>top:0px; left:50%; transform:translate(-50%, 0%);<?php
						break;
						case 'center left':
							?>top:50%; left:0; transform:translate(0, -50%);<?php
						break;
						case 'center center':
							?>top:50%; left:50%; transform:translate(-50%, -50%);<?php
						break;
						case 'center right':
							?>top:50%; right:0; transform:translate(0, -50%);<?php
						break;
						case 'bottom left':
							?>bottom:0px; left:0;<?php
						break;	
						case 'bottom right':
							?>bottom:0px; right:0;<?php
						break;			
						case 'bottom center':
						default:
							?>bottom:0px; left:50%; transform:translate(-50%, 0%);<?php
						break;
					}			
					if ( !empty($slider['settings']['button']['orientation']) )
					{
						?>flex-direction:<?php echo $slider['settings']['button']['orientation']; ?>;<?php
					}
				}			
			?>		
			}
			<?php	
		}
		if( isset($slider['settings']['button']['css']) )
		{
			if( empty($slider['settings']['button']['design']) )
			{
				?>#slider-<?php echo $slider['id']; ?> .slider_buttons__button{ <?php foreach( $slider['settings']['button']['css'] as $name => $value ) echo "$name:$value;"; ?> }<?php 
			} 
			elseif ( $slider['settings']['button']['design'] === 'indicator' ) 
			{ 				
				?>				
				#slider-<?php echo $slider['id']; ?> .slider_buttons_indicator{margin:<?php echo $slider['settings']['button']['css']['margin']; ?>}
				#slider-<?php echo $slider['id']; ?> .slider_buttons_indicator__scale{background-color:<?php echo $slider['settings']['button']['css']['border-color']; ?> }
				#slider-<?php echo $slider['id']; ?> .active .slider_buttons_indicator__scale:after{background-color:<?php echo $slider['settings']['button']['css']['active-color-number']; ?>;animation: slideloader <?php echo $autospeed/1000; ?>s 1 forwards; -webkit-animation: slideloader <?php echo $autospeed/1000; ?>s;}
				#slider-<?php echo $slider['id']; ?> .slider_buttons_indicator__number{color:<?php echo $slider['settings']['button']['css']['color-number']; ?> }
				#slider-<?php echo $slider['id']; ?> .active .slider_buttons_indicator__number{color:<?php echo $slider['settings']['button']['css']['active-color-number']; ?>}
				#slider-<?php echo $slider['id']; ?> .slider_buttons_indicator__title{color:<?php echo $slider['settings']['button']['css']['color']; ?> }
				#slider-<?php echo $slider['id']; ?> .active .slider_buttons_indicator__title{color:<?php echo $slider['settings']['button']['css']['active-color']; ?> }
				<?php 
			} 
			?>
		<?php } ?>	
		#slider-<?php echo $slider['id']; ?> .slider_buttons__button.active{background-color:var(--main-open-color); border-color:var(--main-open-color);}	
		<?php			
		$screens = ['computer' => '', 'notebook' => '1023px', 'tablet' => '777px', 'mobile' => '479px'];
		foreach ($slides as $slide_number => $slide)
		{				
			if ( !empty($slide['settings']['layers']) )
			{
				$count = count($slide['settings']['layers']);			
				foreach ($slide['settings']['layers'] as $i => $layer)		
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
					
					$layer_id = "#slider-".$slider['id']." #slider-".$slider['id']."-slide-".$slide['id']."-layer-$i";
					$layer_content_id = "$layer_id .slide_layer_content";
					if ( $layer['group'] )
						$layer_id = ".slide_layer ".$layer_id;
					elseif ( $layer['type'] == 'group' )
						$layer_content_id = $layer_id;
					if ( $css )
						echo $layer_id.'{'.$css.'}';
					foreach( $slider['settings']['devices'] as $device => $status )	
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
								foreach ($layer[$device]['hover'] as $name => $value)
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
							$css = $layer['group'] ? "" : 'z-index:'.(50+$count-$i).";inset:".$layer[$device]['inset'].";$t";
							
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
		}		
		?>				
	</style>
	<?php
	wp_enqueue_script('owl-carousel');
	wp_enqueue_style('usam-silder-filter');
	$anonymous_function = function() use  ( $slider, $autospeed )
	{
		?>				
		<script>
			<?php if ( $slider['settings']['layouttype'] == 'fullscreen' ) { ?>
				var ff = (e) => {
					document.querySelectorAll('#slider-<?php echo $slider['id']; ?>').forEach((el) => { 
						el.style.width = document.documentElement.clientWidth+'px';		
						el.style.height = document.documentElement.clientHeight+'px';						
						el.style.left = '-'+el.getBoundingClientRect().x+'px';
					});
					document.querySelectorAll('#slider-<?php echo $slider['id']; ?> .slider_slide').forEach((el) => el.style.height = window.innerHeight+'px');		
				}
				ff();
				window.addEventListener('resize', ff); <?php
				} 
			else if ( $slider['settings']['layouttype'] === 'fullscreenwidth' ) { ?>				
				var ff = (e) => {
					document.querySelectorAll('#slider-<?php echo $slider['id']; ?>').forEach((el) => { 
						el.style.width = document.documentElement.clientWidth+'px';
						el.style.left = '-'+el.getBoundingClientRect().x+'px';
					});
				}
				ff();
				window.addEventListener('resize', ff); <?php
			} 
			else if ( $slider['settings']['layouttype'] === 'fullscreenheight' ) { ?>				
				var ff = (e) => {
					document.querySelectorAll('#slider-<?php echo $slider['id']; ?>').forEach((el) => { 
						el.style.height = document.documentElement.clientHeight+'px';
					});
				}
				ff();
				window.addEventListener('resize', ff);			
			<?php } ?>				
			jQuery(document).ready(function()
			{
				jQuery('.loading-slider').removeClass('loading-slider');
				var $slides = jQuery('#slider-<?php echo $slider['id']; ?> .slides');
				if ( $slides.length ) 
				{
					$slides.css( 'backgroundImage', 'none' );
					$slides.on({'initialized.owl.carousel': (e) => jQuery('#slider-<?php echo $slider['id']; ?> .js-slider-button').removeClass("active").eq(0).addClass("active")}).owlCarousel({
						autoplay:<?php echo $slider['settings']['autoplay']?'true':'false'; ?>,
						autoplayTimeout: <?php echo $autospeed; ?>,
						items:1,
						loop:<?php echo !empty($slider['settings']['loop']) && $slider['settings']['loop']?'true':'false'; ?>,
						navText:["<?php usam_svg_icon( 'prev' ) ?>", "<?php usam_svg_icon( 'next' ) ?>"],
						lazyLoad:true,
						nav:true,
						dots:false,
						dotsContainer:'.slider_buttons',					
					}).on('changed.owl.carousel', function(e) 
					{
						var i = 0;
						jQuery('#slider-<?php echo $slider['id']; ?> .slides .owl-item').each(function(index){
							if( !jQuery(this).hasClass('cloned') )
								return false;
							else
								i++;
						});			
						i = e.item.index - i;
						i = e.item.count <= i ? 1 : i;	
						jQuery('#slider-<?php echo $slider['id']; ?> .js-slider-button.active').removeClass("active");						
						jQuery('#slider-<?php echo $slider['id']; ?> .js-slider-button').eq(i).addClass("active");
					})		
					jQuery('#slider-<?php echo $slider['id']; ?> .js-slider-button').on('click', function(e) {					
						var i = jQuery(this).index();
						$slides.trigger('to.owl.carousel', [i, 300]);
						$slides.data('owl.carousel').options.autoplay = false;						
						jQuery('#slider-<?php echo $slider['id']; ?> .js-slider-button.active').removeClass("active");
						jQuery(this).addClass("active");
					}); 					
				};
			}); 
		</script>
		<?php
	};
	add_action('wp_footer', $anonymous_function, 2);	
	add_action('admin_footer', $anonymous_function, 2);	
//	add_action('elementor/frontend/slider/after_render', $anonymous_function, 2);		
	add_action('admin_footer', $anonymous_function, 2);
}

function usam_get_sliders( $qv = array() )
{ 
	global $wpdb;	
	
	if ( empty($qv['fields']) )
	{
		$qv['fields'] = 'all';
	}		
	
	$fields = $qv['fields'] == 'all'?'*':$qv['fields'];
	
	$_where[] = '1=1'	;
	if ( !isset($qv['active']) || $qv['active'] == '1')
		$_where[] = "active = '1'";
	elseif ( $qv['active'] == '0' )
		$_where[] = "active = '0'";			
		
	if ( !isset($qv['cache_results']) )
		$qv['cache_results'] = false;	
	
	if ( !isset($qv['cache_slides']) )
		$qv['cache_slides'] = false;	
		
	if ( isset($qv['include']) )
		$_where[] = "id IN( '".implode( "','", $qv['include'] )."' )";
	
	$where = implode( " AND ", $_where);	
	if ( !empty($qv['conditions']) ) 
	{		
		foreach ( $qv['conditions'] as $condition )
		{					
			$select = '';
			if ( empty($condition['key']) )
				continue;
			
			switch ( $condition['key'] )
			{		
				case 'code' :
					$select = "code";			
				break;				
				default:				
					$select = $condition['key'];			
				break;				
			}
			if ( $select == '' )
				continue;
			
			$compare = "=";	
			switch ( $condition['compare'] ) 
			{
				case '<' :
					$compare = "<";					
				break;
				case '=' :
					$compare = "=";					
				break;	
				case '!=' :
					$compare = "!=";					
				break;
				case '>' :
					$compare = ">";					
				break;				
			}
			$value = $condition['value'];
			
			if ( empty($condition['relation']) )
				$relation = 'AND';
			else
				$relation = $condition['relation'];
			
			$where .= $wpdb->prepare( " $relation $select $compare %s", $value );			
		}
	}	
	if ( isset($qv['orderby']) )	
		$orderby = $qv['orderby'];	
	else
		$orderby = 'id';
	$orderby = "ORDER BY $orderby";
	
	if ( isset($qv['order']) )	
		$order = $qv['order'];	
	else
		$order = 'DESC';	
	if ( isset($qv['output_type']) )	
		$output_type = $qv['output_type'];	
	else
		$output_type = 'OBJECT';
	
	if ( $where != '' )
		$where = " WHERE $where ";
	
	$results = $wpdb->get_results( "SELECT $fields FROM ".USAM_TABLE_SLIDER." $where $orderby $order", $output_type );	
	foreach ( $results as $k => $result ) 
		$results[$k]->settings = maybe_unserialize($result->settings);
	if ( 'all' == $qv['fields'] )
	{			
		if ( $qv['cache_results'] )
		{	
			foreach ( $results as $result ) 
				wp_cache_set( $result->id, (array)$result, 'usam_slider' );
		}
		if ( $qv['cache_slides'] )
		{
			$ids = array();
			foreach ( $results as $result ) 
			{
				$ids[] = $result->id;
			}	
			usam_update_cache( $ids, [USAM_TABLE_SLIDES => 'slides'], 'slider_id' );	
		}
	}	
	return $results;
}

function usam_theme_sliders( $code )
{ 
	$sliders = wp_cache_get( 'usam_sliders' );			
	if ( $sliders === false )			
	{							
		$sliders = usam_get_sliders(['cache_results' => true]);
		wp_cache_set( 'usam_sliders', $sliders );						
	}
	if ( !empty($sliders) )
	{ 
		foreach ( $sliders as $slider ) 
		{
			$settings = maybe_unserialize($slider->settings);	
			if ( isset($settings['show']) && $settings['show'] == $code )
				usam_display_slider( $slider->id );
		}
	}	
}

function usam_register_sliders( )
{
	return apply_filters( 'usam_register_sliders', usam_get_hooks() );	
}
?>