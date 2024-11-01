<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Reviews extends Widget
{	
	public function get_name() 
	{
		return 'reviews';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Отзывы', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_keywords() 
	{
		return ['product','review','comment'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);
		$this->add_control('summary_rating', ['label' => __('Показывать сумму рейтинга', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
		$this->add_control('hide_response', ['label' => __('Ответ менеджера', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
		$this->add_control('pagination', ['label' => __('Показать страницы', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
		$this->add_control('per_page', ['label'   => __('Количество отзывов', 'usam'), 'type' => Controls_Manager::TEXT, 'default' => 10]);			
		$this->end_controls_section();		
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		$args = ['per_page' => $attributes['per_page']];			
		$args['hide_response'] = $attributes['hide_response'] == 'yes' ? true : false;
		$args['summary_rating'] = $attributes['summary_rating'] == 'yes' ? true : false;	
		$args['pagination'] = $attributes['pagination'] == 'yes' ? true : false;		
		if ( !empty($attributes['page_id']) )
			$args['page_id'] = $attributes['page_id'];	
		else
			$args['page_id'] = get_the_ID();	
		$customer_reviews = new \USAM_Customer_Reviews_Theme();
		echo $customer_reviews->output_reviews_show( $args );
	}	
}