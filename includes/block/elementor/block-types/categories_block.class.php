<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Categories extends Widget
{	
	public function get_name() 
	{
		return 'categories';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Список категорий', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-post-category';
	}

	public function get_keywords() 
	{
		return ['category'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);		
		$terms = \get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-category']);	
		foreach( $terms as $term ) 		
		{
			$options[$term->term_id] = $term->name;
		}	
		$this->add_control('ids', ['label' => __('Категории', 'usam'), 'multiple' => true, 'type' => Controls_Manager::SELECT2, 'default' => '', 'options' => $options]);
		$this->add_control('column', ['label' => __('Количество в строке', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => 4]);
		$this->add_control('limit', ['label' => __('Максимальное количество', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => 4]);
		$this->end_controls_section();		
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();		
		$args = ['orderby' => 'sort', 'taxonomy' => 'usam-category', 'meta_query' => [['key' => 'thumbnail', 'value' => 0, 'compare' => '!=']]];
		if ( !empty($attributes['ids']) )
			$args['include'] = $attributes['ids'];
		else
		{
			if ( $attributes['limit'] )
				$args['number'] = $attributes['limit'];
			$args['parent'] = 0;
		}		
		$terms = get_terms($args);
		if ( !empty($terms) )
		{ 	
			?>
			<div class="categories_block categories list_terms">
				<?php
				foreach($terms as $term) 
				{	
					$term_link = get_term_link($term->term_id, 'usam-category');				
					?>
					<div class="list_terms__term column<?php echo $attributes['column']; ?>">		
						<div class="list_terms__image">
							<a href='<?php echo $term_link ?>' class="list_terms__image_wrap image_container"><?php echo usam_term_image($term->term_id, 'full',  ['alt' => $term->name]) ?></a>
						</div>
						<div class="list_terms__name">
							<a href='<?php echo $term_link ?>'><?php echo $term->name ?></a>		
						</div>	
					</div>
					<?php
				}
				?>
			</div>
			<?php
}
	}	
}