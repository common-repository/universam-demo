<?php	
include( USAM_FILE_PATH . '/admin/includes/product/product_form_importer.php' );
class USAM_Product_Discount_Importer extends USAM_Product_Form_Importer
{			
	protected function get_columns()
	{
		return ['sku' => __('Артикул', 'usam'), 'barcode' => __('Штрих-код', 'usam')];
	}
	
	public function display( )
	{					
		?>
		<div id="product_discount_importer" class="importer progress_form">
			<?php $this->display_content_import(); ?>
		</div>	
		<?php		
	}
}
?>