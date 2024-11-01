<?php
/*
Printing Forms: Складской лист
type:order
object_type:document
object_name:order
orientation:landscape
Description: Используется в заказе
*/
 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Складской лист для заказа #%s', 'usam'), $this->id ); ?></title>	
	<style type="text/css">
		@page {size:landscape} 			
		table{table-layout:auto;}
		table#products-table thead th, table#products-table thead td{ writing-mode: tb-rl; }
		th {background-color:#efefef; text-align:center;}		
		#products-table td.amount {	text-align:right; }
		td, tbody th { border-top:1px solid #ccc; }	
		.column-n{ width:50px;}		
		.column-sku{max-width:120px;}
		.column-storage{width:30px;}
		<?php $this->style(); ?>	
	</style>
</head>
<?php
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
<body style="margin: 5pt; width: 830pt; background: #ffffff;" <?php echo $print; ?>>
	<div id="header">
		<h1>
			%%name input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>" "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%% <br />
			<span><?php printf( esc_html__('Складской лист для заказа #%s', 'usam'), $this->id ); ?></span>
		</h1>
	</div>	
	<?php
	$columns = array(
		'n'        => __('Номер','usam'),
		'name'     => __('Название товара','usam'),
		'sku'      => __('Артикул','usam'),
		'barcode_picture'  => __('Штрих-код','usam'),
		'quantity' => __('Кол-во','usam'),	
	);
	$storages = usam_get_storages();
	foreach ( $storages as $storage )
	{
		$columns['storage_'.$storage->id] = $storage->title;		
	}	
	$this->load_table( $columns );
	?>		
	<table id="products-table" style="width:100%;border-collapse:collapse;">
		<thead><?php $this->display_table_thead( ); ?></thead>
		<tbody><?php $this->display_table_tbody( ); ?></tbody>
	</table>
</body>
</html>