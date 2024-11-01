<?php
/**
 * Класса виджета товарных фильров
 */
class USAM_Widget_Shop_Tools extends WP_Widget 
{	
	function __construct() 
	{
		$widget_ops = array( 
			'classname' => 'widget_shop_tools',
			'description' => __('Универсам: Виджет инструментов просмотра каталога', 'usam')
		);		
		parent::__construct( 'usam_shop_tools', __('Инструменты просмотра каталога', 'usam'), $widget_ops );
	}

	function widget( $args, $instance ) 
	{				
		ob_start();	
		
		add_action( 'wp_footer', array('USAM_Assets', 'product_filter') );
		$instance = wp_parse_args((array) $instance, array('title' => '', 'products_sort' => 1, 'range_price' => 1, 'number_products' => 1, 'view_type' => 1));
		
		echo $args['before_widget'];
		
		if ( $instance['title'] )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		
		if ( $instance['products_sort'] )
		{
			global $wp_query;	
	
			if ( !empty($wp_query) )
			{
				$sorting_options = usam_get_user_product_sorting_options();
				if ( !empty($sorting_options) )
				{				
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
			}
		}
		if ( $instance['range_price'] )
		{			
			?>
			<div class='products_prices'>
				<div id='products_prices' v-if="custom.prices.max_price!=0">
					<filter-prices @changeprice="custom.prices.selected=$event" v-bind:min="custom.prices.min_price" v-bind:max="custom.prices.max_price" v-bind:min_value="custom.prices.selected[0]" v-bind:max_value="custom.prices.selected[1]"></filter-prices>
				</div>
			</div>
			<?php
		}
		if( !wp_is_mobile() )
		{
			if ( $instance['number_products'] )
			{
				$per_page = usam_get_number_products_page_customer();	
				if ( !empty($per_page) )
				{				
					add_action( 'wp_footer', array('USAM_Assets', 'product_filter') );
					if ( !isset($instance['block']) )
						$instance['block'] = [ $per_page, $per_page * 2, $per_page * 3 ];					
					$out ='<div class="number_products">';
					foreach ($instance['block'] as $block)
					{
						if ( $per_page == $block )
							$out.= '<div class="number_products__number active js_number_products">'.$block.'</div>';
						else
							$out.= '<div class="number_products__number js_number_products">'.$block.'</div>';
					}
					$out.= '</div>';	
					echo $out;
				}
			}
			if ( $instance['view_type'] )
			{
				$views = get_option('usam_product_views', ['grid', 'list']);
				$possible_views = usam_get_site_product_view();
				foreach( $possible_views as $key => $title )	
				{	
					if( !in_array($key, $views) )
						unset($possible_views[$key]);
				}
				if ( count($possible_views) > 1 )
				{
					$view_type = usam_get_display_type();				
					add_action( 'wp_footer', array('USAM_Assets', 'product_filter') );					
					?>
					<div class="products_view_type"><?php
					foreach( $possible_views as $key => $title )	
					{	
						?><span class="products_view_type__option grid js_option_display <?php echo $view_type==$key?'active':''; ?>" view_type="<?php echo $key; ?>" title="<?php echo sprintf(__('Просмотр %s','usam'), $title);?>"><?php echo usam_get_svg_icon($key) ?></span><?php
					}
					?></div><?php
				}
			}
		}	
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']      = strip_tags($new_instance['title']);	
		$instance['products_sort']   = absint($new_instance['products_sort']);
		$instance['range_price']   = absint($new_instance['range_price']);
		$instance['number_products'] = sanitize_title($new_instance['number_products']);	
		$instance['view_type'] = sanitize_title($new_instance['view_type']);		
		return $instance;
	}

	function form( $instance )
	{			
		$instance = wp_parse_args( (array)$instance, array(	'title' => '', 'products_sort' => 1, 'range_price' => 1, 'number_products' => 1, 'view_type' => 1 ) );		
		$title    = esc_attr( $instance['title'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars($title); ?>" />
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('products_sort'); ?>" name="<?php echo $this->get_field_name('products_sort'); ?>"<?php checked( $instance['products_sort'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('products_sort'); ?>"><?php _e('Сортировка товаров', 'usam'); ?></label>
		</p>	
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('range_price'); ?>" name="<?php echo $this->get_field_name('range_price'); ?>"<?php checked( $instance['range_price'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('range_price'); ?>"><?php _e('Фильтр цены', 'usam'); ?></label>
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('number_products'); ?>" name="<?php echo $this->get_field_name('number_products'); ?>"<?php checked( $instance['number_products'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('number_products'); ?>"><?php _e('Выбор количества товаров', 'usam'); ?></label>
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('view_type'); ?>" name="<?php echo $this->get_field_name('view_type'); ?>"<?php checked( $instance['view_type'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('view_type'); ?>"><?php _e('Вариант просмотра каталога', 'usam'); ?></label>
		</p>		
	<?php
	}
}
?>