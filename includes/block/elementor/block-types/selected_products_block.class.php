<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Selected_Products extends Widget
{		
	protected $taxonomies = ["usam-brands", "usam-category", "usam-category_sale", 'usam-catalog', 'usam-selection', 'product_tag'];
	public function get_name() 
	{
		return 'selected_products';
	}

	public function get_title() 
	{
		return esc_html__( 'Выбранные товары', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-products';
	}

	public function get_keywords() 
	{
		return ['products'];
	}

	//public function get_script_depends() {}

	//public function get_style_depends() {}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Фильтры выбора товаров', 'usam' )]);
		$this->add_control('column', ['label' => __('Количество в строке', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => 4]);
		$this->add_control('limit', ['label' => __('Максимальное количество', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => 4]);		
		//$this->add_control('from_price', ['label'   => __('Диапазон цен', 'usam'), 'type' => 'interval', 'default' => '']);
		//$this->add_control('from_stock', ['label'   => __('Остаток', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => '']);
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']], 'objects');
		$taxonomy = [];
		$options = [];
		foreach( $taxonomies as $k => $tax ) 		
		{
			if ( in_array($tax->name, $this->taxonomies) )
			{
				$options[$tax->name][0] = '-';
				$taxonomy[] = $tax->name;
			}
			else
				unset($taxonomies[$k]);
		}
		$terms = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => $taxonomy]);	
		foreach( $terms as $term ) 		
		{
			$options[$term->taxonomy][$term->slug] = $term->name;
		}
		foreach( $taxonomies as $tax ) 	
		{
			$this->add_control($tax->name, ['label' => $tax->label, 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => isset($options[$tax->name])?$options[$tax->name]:[]]);
		}			
		$sort = usam_get_product_sorting_options();		
		$sorting_options = get_option( 'usam_sorting_options', ['name', 'price', 'popularity', 'date']);	
		$options = [];
		foreach( $sort as $id => $name )
		{
			if( in_array($id, $sorting_options) )
				$options[$id] = $name;
		}
		$sort = explode('-', get_option('usam_product_sort_by', 'date-desc') );
		$this->add_control('orderby', ['label'   => __('Сортировка', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => $sort[0], 'options' => $options]);
		$this->add_control('order', ['label'   => __('Направление сортировки', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => $sort[1], 'options' => ['ASC'  => esc_html__('По возрастанию', 'usam'), 'DESC' => esc_html__('По убыванию', 'usam')]]);
		$this->end_controls_section();
		$this->start_controls_section('section_view', ['label' => __( 'Вид', 'usam' )]);
		$this->add_control('view_type', ['label'   => __('Вариант отображения товара', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => 'grid', 'options' => ['grid'  => esc_html__('Плиткой', 'usam'), 'list' => esc_html__('Списком', 'usam')]]);
		$this->end_controls_section();
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		global $post, $lazy_loading;
		$lazy_loading = 0;		
					
		require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );	
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$type_price = usam_get_customer_price_code();	
		$args = ['post_type' => 'usam-product', 'type_price' => $type_price];				
		if ( isset($attributes['from_price']) && $attributes['from_price'] !== '' )
			$args['from_price'] = $attributes['from_price'];
		if ( !empty($attributes['to_price']) )
			$args['to_price'] = $attributes['to_price'];
		if ( isset($attributes['from_stock']) && $attributes['from_stock'] !== '' )
			$args['from_stock'] = $attributes['from_stock'];
		if ( !empty($attributes['to_stock']) )
			$args['to_stock'] = $attributes['to_stock'];		
		foreach( $this->taxonomies as $taxonomy)
		{
			if( !empty($attributes[$taxonomy]) )
				$args[$taxonomy] = $attributes[$taxonomy];	
		}				
		if ( isset($attributes['orderby']) )		
			$args['orderby'] = $attributes['orderby'];
		else 			
			$args = usam_get_default_catalog_sort( $args, 'array' );   // сортировка по умолчанию
					
		$args = array_merge( $args, usam_product_sort_order_query_vars( $args['orderby'] ) );
		if(!empty($attributes['order']))
			$args['order'] = $attributes['order'];
		
		$args['posts_per_page'] = !empty($attributes['limit']) ? $attributes['limit'] : 4;
		$view_type = !empty($attributes['view_type']) ? $attributes['view_type'] : 'grid';
		query_posts( $args );	

		global $product_limit;
		$product_limit = !empty($attributes['column']) ? $attributes['column'] : 4;		
		?>
		<div class="products_grid">
			<?php			
			while (usam_have_products()) :  			
				usam_the_product(); 			
				include( usam_get_template_file_path( $view_type.'_product' ) );
			endwhile; 
			?>	
		</div>
		<?php									
		wp_reset_query();
		wp_reset_postdata();
	}	
}