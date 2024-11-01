<?php
/**
 * Класса виджета последние опубликованные товары
 */
class USAM_Widget_Latest_Products extends WP_Widget 
{	
	function __construct() 
	{
		$widget_ops = array( 
			'classname' => 'widget_usam_latest_products',
			'description' => __('Универсам: Виджет новых товаров', 'usam') 
		);		
		parent::__construct( 'usam_latest_products', __('Новые товары', 'usam'), $widget_ops );
	}

	function widget( $args, $instance ) 
	{
		$type_price = usam_get_customer_price_code();			
		
		$instance = wp_parse_args( (array)$instance, array(	'title' => '',	'number' => 5,	'width' => 160,	'height' => 160, 'image' => 1, 'price' => 1 ) );
		extract( $args );
		
		$instance['title'] = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( $instance['title'] )
			echo $before_title . $instance['title'] . $after_title;		
	
		$number  = isset($instance['number']) ? (int)$instance['number'] : 5;
		$show_image  = isset($instance['image']) ? (bool)$instance['image'] : FALSE;
		$show_price  = isset($instance['price']) ? (bool)$instance['price'] : FALSE;
		$width = isset($instance['width'])?$instance['width']:160;
		$height = isset($instance['height'])?$instance['height']:160;
		
		$products = usam_get_products(['posts_per_page' => $instance['number'], 'orderby' => 'post_date', 'post_parent' => 0, 'post_status' => 'publish', 'order' => 'DESC', 'in_stock' => true], $show_image );
 
		echo '<div class="usam_latest_products">';			
		include( usam_get_template_file_path( 'widget-products' ) );
		echo '</div>';	
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = (int)$new_instance['number'];
		$instance['price']      = (bool)$new_instance['price'];
		$instance['image']      = (bool)$new_instance['image'];
		$instance['height']     = (int)$new_instance['height'];
		$instance['width']      = (int)$new_instance['width'];
		return $instance;

	}

	function form( $instance )
	{		
		$instance = wp_parse_args( (array)$instance, array(	'title' => '',	'number' => 5,	'width' => 160,	'height' => 160, 'image' => 1, 'price' => 1 ) );
		
		$title    = esc_attr( $instance['title'] );
		$number   = (int)$instance['number'];
		$price    = (bool)$instance['price'];
		$image    = (bool)$instance['image'];
		$width    = (int) $instance['width'];
		$height   = (int) $instance['height']; 
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars($title); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Количество товаров для вывода:', 'usam'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'price' ); ?>" name="<?php echo $this->get_field_name( 'price' ); ?>"<?php checked($price); ?>/>
			<label for="<?php echo $this->get_field_id( 'price' ); ?>"><?php _e( 'Отображение цены', 'usam'); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'image' ); ?>" name="<?php echo $this->get_field_name( 'image' ); ?>"<?php checked($image); ?> onclick="jQuery('.usam_latest_image').toggle()">
			<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Отображение миниатюры', 'usam'); ?></label>
		</p>
		<div class="usam_latest_image"<?php if( !checked( $image ) ) { echo ' style="display:none;"'; } ?>>
			<p>
				<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Ширина:', 'usam'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" value="<?php echo $width ; ?>" size="3" />
				<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Высота:', 'usam'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" value="<?php echo $height ; ?>" size="3" />
			</p>
		</div>
	<?php
	}
}
?>