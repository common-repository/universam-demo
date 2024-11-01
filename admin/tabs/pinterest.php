<?php
class USAM_Tab_pinterest extends USAM_Tab
{		
	protected  $display_save_button = true;
	protected $views = ['simple'];
	public function get_title_tab()
	{
		return __('Социальные сети', 'usam');
	}	

	public function display() 
	{
		usam_add_box( 'usam_pinterest', __('Pinterest', 'usam'), [$this, 'pinterest_meta_box']);
	}	
	
	function pinterest_meta_box()
	{	
		$options = array( 						
			array( 'key' => 'verify', 'type' => 'input', 'title' => __('Подтвердить права на сайт', 'usam'), 'option' => 'pinterest', 'attribute' => array( 'maxlength' => "50", 'size' => "50") ),	
		 );	  
		 $this->display_table_row_option( $options );
	}	
}