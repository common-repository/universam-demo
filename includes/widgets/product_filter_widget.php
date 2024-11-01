<?php
/**
 * Класса виджета товарных фильров
 */
class USAM_Widget_Filter_Products extends WP_Widget 
{	
	function __construct() 
	{
		$widget_ops = array( 
			'classname' => 'widget_filter_products',
			'description' => __('Универсам: Виджет фильтрации товаров в каталоге', 'usam')
		);		
		parent::__construct( 'usam_filter_products', __('Фильтр товаров', 'usam'), $widget_ops );
	}

	function widget( $args, $instance ) 
	{			
		add_action( 'wp_footer', ['USAM_Assets', 'product_filter']);
		$instance = wp_parse_args((array)$instance, $this->get_default_option());			
		echo $args['before_widget'];	
		
		if ( version_compare( get_bloginfo('version'), '5.8', '<=' ) && is_admin() )		
			echo $args['before_title'] . __('Фильтр товаров', 'usam') . $args['after_title'];	
		?>
		<div id='product_filters' class='filter_activation_<?php echo $instance['filter_activation']; ?>' v-cloak>
			<div class='filters_form_button'>
				<span class='filters_form_button__title' @click="click_tab('filter')" v-if="filter_display"><?php _e("Фильтры","usam") ?></span>
				<span class='filters_form_button__title' @click="click_tab('category')"><?php _e("Категории","usam")  ?></span>
			</div>	
			<div class='filters_form filters_form_panel' :class="[tab=='filter'?'active':'']" v-show="filter_display"><?php include( usam_get_template_file_path('product-filter') ); ?></div>
			<div class="categories_form filters_form_panel" :class="[tab=='category'?'active':'']" v-html="categories"></div>
		</div>
		<?php
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {

		$new_instance = wp_parse_args( (array)$new_instance, $this->get_default_option());	
		$instance['storages']   = absint($new_instance['storages']);
		$instance['individual_price']   = absint($new_instance['individual_price']);		
		$instance['categories']   = sanitize_title($new_instance['categories']);
		$instance['range_price']   = absint($new_instance['range_price']);
		$instance['filter_activation'] = sanitize_title($new_instance['filter_activation']);		
		$instance['product_rating'] = sanitize_title($new_instance['product_rating']);
		return $instance;
	}
	
	protected function get_default_option( ) 
	{
		return ['storages' => 0, 'individual_price' => 0, 'range_price' => 0, 'categories' => 'no_hierarchy', 'product_rating' => 0, 'filter_activation' => 'button'];
	}

	function form( $instance )
	{			
		$instance = wp_parse_args( (array)$instance, $this->get_default_option());
		?>		
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('storages'); ?>" name="<?php echo $this->get_field_name('storages'); ?>"<?php checked( $instance['storages'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('storages'); ?>"><?php _e('Фильтр по магазинам', 'usam'); ?></label>
		</p>	
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('individual_price'); ?>" name="<?php echo $this->get_field_name('individual_price'); ?>"<?php checked( $instance['individual_price'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('individual_price'); ?>"><?php _e('Выбор цен компаний', 'usam'); ?></label>
		</p>	
		<p>		
			<label for="<?php echo $this->get_field_id('categories'); ?>"><?php _e('Фильтр по вложенным категориям', 'usam'); ?></label>			
			<select id="<?php echo $this->get_field_id( 'categories' ); ?>" name='<?php echo $this->get_field_name( 'categories' ); ?>'>	
				<option value=''><?php _e( 'Не показывать', 'usam'); ?></option>			
				<option value='no_hierarchy' <?php selected('no_hierarchy', $instance['categories']); ?>><?php _e( 'Без иерархии', 'usam'); ?></option>
				<option value='hierarchy' <?php selected('hierarchy', $instance['categories']); ?>><?php _e( 'С иерархией', 'usam'); ?></option>				
			</select>	
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('range_price'); ?>" name="<?php echo $this->get_field_name('range_price'); ?>"<?php checked( $instance['range_price'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('range_price'); ?>"><?php _e('Фильтр цены', 'usam'); ?></label>
		</p>	
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('product_rating'); ?>" name="<?php echo $this->get_field_name('product_rating'); ?>"<?php checked( $instance['product_rating'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('product_rating'); ?>"><?php _e('Рейтинг товара', 'usam'); ?></label>
		</p>
		<p>		
			<label for="<?php echo $this->get_field_id('filter_activation'); ?>"><?php _e('Активировать кнопкой', 'usam'); ?></label>
			<select id="<?php echo $this->get_field_id( 'filter_activation' ); ?>" name='<?php echo $this->get_field_name( 'filter_activation' ); ?>'>		
				<option value='button' <?php selected('button', $instance['filter_activation']); ?>><?php _e( 'Кнопкой применить', 'usam'); ?></option>
				<option value='auto' <?php selected('auto', $instance['filter_activation']); ?>><?php _e( 'Автоматически', 'usam'); ?></option>				
			</select>			
		</p>	
	<?php
	}
}
?>