<?php	
include( USAM_FILE_PATH . '/admin/includes/product/product_form_importer.php' );
class USAM_Products_Fix_Price_Form_Importer extends USAM_Product_Form_Importer
{	
	protected function get_columns()
	{
		return ['sku' => __('Артикул', 'usam'), 'barcode' => __('Штрих-код', 'usam'), 'discount_price' => __('Цена со скидкой', 'usam')];
	}
}
?>