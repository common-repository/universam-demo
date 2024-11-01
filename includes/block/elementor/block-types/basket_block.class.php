<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Basket extends Widget
{	
	public function get_name() 
	{
		return 'basket';
	}
	
	public function get_script_depends() {
		return ['usam-theme'];
	}
	
	public function get_title() 
	{
		return esc_html__( 'Корзина', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-button';
	}

	public function get_keywords() 
	{
		return ['basket'];
	}
	
	public function render() 
	{ 	
		$attributes = $this->get_settings_for_display();
		require_once(USAM_FILE_PATH.'/includes/block/template-parts/basket.php');
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);		
		$this->add_control('signature', ['label' => __('Подпись корзины', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => 0, 'options' => [1 => __('Да', 'usam'), 0 => __('Нет', 'usam')]]);
		$this->add_control('basket_view', ['label' => __('Вид корзины', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => 'icon', 'options' => ['table' => __('Таблица товаров', 'usam'), 'icon' => __('Иконка и итог', 'usam')]]);
		$this->end_controls_section();		
	}
}