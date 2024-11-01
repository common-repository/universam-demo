<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Product_Form_Importer extends USAM_Importer
{		
	protected $template = false;	
	public function get_steps()
	{
		return ['file' => __('Выбор файла', 'usam'), 'columns' => __('Назначение столбцов', 'usam'), 'finish' => __('Импорт', 'usam')];
	}
	
	public function display( )
	{					
		?>
		<div class="importer progress_form">
			<?php $this->display_content_import(); ?>
		</div>	
		<?php		
	}
	
	protected function get_columns()
	{
		return apply_filters( "usam_product_importer_columns", ['sku' => __('Артикул', 'usam'), 'barcode' => __('Штрих-код', 'usam'), 'price' => __('Цена', 'usam'), 'discount' => __('Скидка', 'usam')]);
	}
	
	public function process_finish()
	{
		?><h3><?php _e('Процесс импорта завершен!' , 'usam'); ?></h3><?php 
	} 
}
?>