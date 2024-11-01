<?php
class USAM_Tab_Product_Importer extends USAM_Page_Tab
{
	protected $display_save_button = false;	
	
	public function get_title_tab()
	{ 	
		return __('Импорт товаров', 'usam');	
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'product_import', 'title' => __('Добавить шаблон', 'usam')], ['form' => 'progress', 'form_name' => 'product_importer', 'title' => __('Импортировать товары', 'usam')]];
	}
	
	public function display() 
	{				
		include( USAM_FILE_PATH . '/admin/includes/product/progress-form-product_importer.php' );
		$progress_form = new USAM_Progress_Form_product_importer( );
		$progress_form->display();
	}
}