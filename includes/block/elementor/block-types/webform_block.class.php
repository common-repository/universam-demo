<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Webform extends Widget
{	
	public function get_name() 
	{
		return 'webform';
	}
	
	public function get_script_depends() {
		return ['usam-theme'];
	}
	
	public function get_title() 
	{
		return esc_html__( 'Веб-форма', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-form-horizontal';
	}
	
/*	public function get_script_depends() {
		\USAM_Assets::theme();	
		\wp_enqueue_script( 'universam' );
	}*/

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
		if ( !empty($attributes['code']) )
		{
			remove_filter( 'the_content', 'wptexturize' ); // иначе искажается html
			require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php');
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php');
			echo \usam_get_webform_template( $attributes['code'] ); 
		}
	}	
}