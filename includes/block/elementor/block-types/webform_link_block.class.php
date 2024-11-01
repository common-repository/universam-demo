<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Webform_Link extends Widget
{	
	public function get_name() 
	{
		return 'webform_link';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Ссылка на веб-форму', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-button';
	}

	public function get_keywords() 
	{
		return ['product', 'webform'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_view', ['label' => __( 'Настройки', 'usam' )]);
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$webforms = usam_get_webforms();
		$options = [];
		foreach ($webforms as $webform ) 
		{
			$options[$webform->code] = $webform->title;
		}		
		$this->add_control('code', ['label' => __('Веб-форма', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => $options]);
		$this->end_controls_section();
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php');
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php');
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php');
		if ( !empty($attributes['code']) )
			echo "<div class='usam_block_webform_button usam_block_webform_button_".$attributes['code']."'>".\usam_get_webform_link( $attributes['code'], 'button' )."</div>";
	}	
}