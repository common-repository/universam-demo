<?php
namespace usam;

class Customize
{	
	public function __construct( ) 
	{
		add_action( 'customize_register', [$this, 'customize_register'], 12 );
	}
	
	public function customize_register( $wp_customize ) 
	{		
		$wp_customize->remove_section('static_front_page'); 
		$wp_customize->remove_control( 'header_textcolor' );
		
		$this->color_scheme( $wp_customize );		
		$this->theme_customize_register( $wp_customize );
	}	
	
	public function color_scheme( $wp_customize ) 
	{				
		$color_scheme_choices = $this->get_color_scheme_choices();
		if ( count($color_scheme_choices) > 1 )
		{
			$wp_customize->add_setting( 'color_scheme', ['default' => 'default', 'sanitize_callback' => 'sanitize_title', 'transport' => 'postMessage']);
			$wp_customize->add_control( 'color_scheme', ['label' => __('Базовая цветовая схема', 'usam'), 'section' => 'colors', 'type' => 'select', 'choices' => $this->get_color_scheme_choices(), 'priority' => 1]);
		}	
		$site_style = usam_get_site_style();
		foreach( $site_style as $key => $style )
		{
			if ( $style['type'] == 'color' )
			{
				$wp_customize->add_setting( $key, ['default' => $style['default'],'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage']);
				$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, $key, ['label' => $style['label'], 'section' => 'colors']) );	
			}
			else
			{
				$wp_customize->add_setting($key, ['default' => $style['default'], 'type' => 'theme_mod', 'transport' => 'postMessage'] );
				$wp_customize->add_control($key, ['section' => 'colors', 'label' => $style['label'],'type' => $style['type']] );
			}
		}		
		$wp_customize->add_setting('copyright_text', ['default' => 'wp-universam.ru - разработчики платформы для управления бизнесом', 'type' => 'theme_mod', 'transport' => 'postMessage'] );
		$wp_customize->add_control('copyright_text', ['section' => 'footer_blocks', 'label' => __('Текст в разделе copyright','usam'),'type' => 'text'] );			
			
		$wp_customize->add_setting('copyright_url', ['default' => 'https://wp-universam.ru', 'type' => 'theme_mod', 'transport' => 'postMessage'] );
		$wp_customize->add_control('copyright_url', ['section' => 'footer_blocks', 'label' => __('Ссылка в разделе copyright','usam'),'type' => 'text'] );	
	}
	
	function get_color_scheme_choices()
	{
		$site_colors = usam_get_site_color_scheme();
		$color_scheme_control_options = [];		
		foreach ( $site_colors as $color_scheme => $value )		
			$color_scheme_control_options[$color_scheme] = $value['label'];
		return $color_scheme_control_options;
	}
	
	protected function theme_customize_register( $wp_customize ) { }		
	
	public function sanitize_multiple_checkbox( $values ) 
	{
		$multi_values = !is_array( $values ) ? explode( ',', $values ) : $values;	
		return !empty( $multi_values ) ? array_map( 'sanitize_text_field', $multi_values ) : array();				
	}
	
	public function sanitize_array_init( $choice ) 
	{
		return array_map('intval', $choice);
	}
}

add_action( 'customize_register', __NAMESPACE__ . '\\multiselect_customize_register' );
function multiselect_customize_register( $wp_customize ) 
{
	class Customize_Control_Checkbox_Multiple extends \WP_Customize_Control 
	{				
		public $type = 'multiple-checkbox';		
		public function render_content() 
		{
			if ( empty( $this->choices ))
				return;			
		
			$multi_values = !is_array( $this->value()) ? explode( ',', $this->value()) : $this->value(); ?>
			<span class="customize-control-title"><?php echo $this->label; ?></span>
			<div class="categorydiv">
				<div class="tabs-panel">
					<ul class="categorychecklist form-no-clear">
						<?php
						foreach ($this->choices as $id => $title) 
						{       
							?>
							<li><label class="selectit"><input type="checkbox" value="<?php echo $id ?>"
							<?php
							if ( in_array($id, $multi_values))				
								echo 'checked="checked"'; 	
									
							echo '>'; ?>
							&nbsp;
							<span class="name"><?php echo $title ?></span></label></li><?php         
						}	
						?>							
					</ul>
				</div>							
			</div>	
			<input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr( implode( ',', $multi_values )); ?>" />					
			<?php 
		}
		
		public function enqueue() 
		{
			wp_enqueue_script( 'usam-customizer', USAM_CORE_JS_URL . 'customize-controls.js', array('jquery'), USAM_VERSION_ASSETS );
		}
	}

	class Customize_Control_Multiple_Select extends \WP_Customize_Control 
	{		
		public $type = 'multiple-select';		
		public function render_content() 
		{

			if ( empty( $this->choices ))
				return;
			?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<select <?php $this->link(); ?> multiple="multiple" style="height: 100%;">
						<?php
							foreach ( $this->choices as $value => $label ) {
								$selected = ( in_array( $value, $this->value()) ) ? selected( 1, 1, false ) : '';
								echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . $label . '</option>';
							}
						?>
					</select>
				</label>
			<?php 
		}
	}
}
?>