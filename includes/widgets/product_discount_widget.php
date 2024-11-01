<?php
/**
 * Виджет специальные предложения товаров
 */
class USAM_Widget_Products_Discount extends WP_Widget 
{
	function __construct() 
	{
		$widget_ops = array(
			'classname'   => 'widget_usam_products_discount',
			'description' => __('Универсам: Товары на акции', 'usam')
		);
		parent::__construct( 'usam_products_discount', __('Товары на акции', 'usam'), $widget_ops );
	}

	/**
	 * Widget Output
	 */
	function widget( $args, $instance ) 
	{
		$type_price = usam_get_customer_price_code();

		$instance = wp_parse_args( (array)$instance, array(	'title' => '',	'number' => 5,	'width' => 160,	'height' => 160, 'image' => 1, 'price' => 1, 'category_sale' => '' ) );
		extract( $args );

		echo $before_widget;
		$instance['title'] = apply_filters( 'widget_title', $instance['title'] );
		if ( $instance['title'] )
			echo $before_title . $instance['title'] . $after_title;

		$show_image  = isset($instance['image']) ? (bool)$instance['image'] : FALSE;
		$show_price  = isset($instance['price']) ? (bool)$instance['price'] : FALSE;
		$width = isset($instance['width'])?$instance['width']:160;
		$height = isset($instance['height'])?$instance['height']:160;		
				
		$query_vars = array('posts_per_page' => $instance['number'], 'orderby' => 'post_date', 'post_parent' => 0, 'post_status' => 'publish', 'order' => 'DESC', 'in_stock' => true);
		
		if ( !empty($instance['category_sale']) )
			$query_vars['tax_query'] = array( array('taxonomy' => 'usam-category_sale', 'field' => 'slug', 'terms' => $instance['category_sale']) );
		
		$query_vars['price_meta_query'] = array( array( 'key' => 'old_price_'.$type_price, 'value' => 0, 'type' => 'numeric', 'compare' => '>' ) );		
		$products = usam_get_products( $query_vars, $show_image );
		echo '<div class="usam_product_specials">';			
		include( usam_get_template_file_path( 'widget-products' ) );
		echo '</div>';	
		echo $after_widget;
	}

	/**
	 * Update Widget
	 */
	function update( $new_instance, $old_instance ) 
	{
		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = (int)$new_instance['number'];
		$instance['price']      = (bool)$new_instance['price'];
		$instance['image']      = (bool)$new_instance['image'];
		$instance['height']     = (int)$new_instance['height'];
		$instance['width']      = (int)$new_instance['width'];
		$instance['category_sale'] = $new_instance['category_sale'];

		return $instance;
	}

	/**
	 * Widget Options Form
	 * @param $instance (array) Widget values.
	 */
	function form( $instance )
	{	
		require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-category-select.php' );
		$instance = wp_parse_args( (array)$instance, array(	'title' => '',	'number' => 5,	'width' => 160,	'height' => 160, 'image' => 1, 'price' => 1, 'category_sale' => '' ) );
		
		$title    = esc_attr( $instance['title'] );
		$number   = (int)$instance['number'];
		$price    = (bool)$instance['price'];
		$image    = (bool)$instance['image'];
		$width    = (int) $instance['width'];
		$height   = (int) $instance['height']; 	
		$category_sale  =  $instance['category_sale'];	
		?>	
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars($title); ?>" />
		</p>
		<p>
			<select id="<?php echo $this->get_field_id( 'category_sale' ); ?>" name='<?php echo $this->get_field_name( 'category_sale' ); ?>' class='term_manager_product chzn-select' data-placeholder='<?php _e( 'Выберите', 'usam'); ?>' style="width: 100%;">		
				<option value=''><?php _e( 'Все товары из активных акций', 'usam'); ?></option>
			<?php
			$args = array(
						'descendants_and_self' => 0,
						'selected_cats'        => array( $category_sale ),
						'walker'               => new Walker_Category_Select(),
						'taxonomy'             => 'usam-category_sale',
						'list_only'            => 'slug',							
						'checked_ontop'        => false, 
						'echo'                 => true,
					);
			usam_terms_checklist($args );	
			?>		
			</select>
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