<?php
namespace usam\Blocks\Elementor;

class Interval_Control extends \Elementor\Base_Data_Control {

	public function get_type() {
		return 'interval';
	}
	
	protected function get_default_settings() 
	{
		return [
			'input_type' => 'text',
			'placeholder' => '',
			'title_from' => '',
			'title_to' => '',
			'from' => '',
			'to' => '',	
			'interval' => ['from' => 0, 'to' => 0],
		];
	}
	
	public function get_default_value() {
		return '';
	}
	
	public function enqueue() {}
	
	public function content_template()
	{
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) {#>
				<label for="<?php $this->get_control_uid(); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
				<input id="<?php $this->get_control_uid( 'from' ); ?>" type="{{ data.input_type }}" class="tooltip-target elementor-control-tag-area" data-tooltip="{{ data.title_from }}" title="{{ data.title_from }}" data-setting="{{ data.interval }}" placeholder="{{ view.getControlPlaceholder() }}">
				<input id="<?php $this->get_control_uid( 'to' ); ?>" type="{{ data.input_type }}" class="tooltip-target elementor-control-tag-area" data-tooltip="{{ data.title_to }}" title="{{ data.title_to }}" data-setting="{{ data.interval }}" placeholder="{{ view.getControlPlaceholder() }}">
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}

}