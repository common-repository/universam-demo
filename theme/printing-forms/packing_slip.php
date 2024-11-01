<?php
/*
Printing Forms: Упаковочный лист
type:shipped
object_type:document
object_name:shipped
Description: Используется в документах доставки
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>	
<?php	
$columns = ['name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'barcode_picture' => __('Штрих-код','usam'), 'price' => __('Цена','usam'), 'quantity' => __('Количество','usam'),'total' => __('Всего','usam')];
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Упаковочный лист #%s', 'usam'), $this->id ); ?></title>
	<style type="text/css">		
		@media print  {
			header { background: none; color: #000; }
			header img { -webkit-filter: invert(100%);	filter: invert(100%); }
		}	
		#logo-table td{border:none}
		.usam_details_box {width:100%;border:none}	
		.usam_details_box td{border:none}	
		.usam_details_box h2{font-size:1.2em; border-bottom: 2px solid maroon; font-weight: normal; padding-bottom: 5px;}	
		.usam_details_box table td {text-align: left;}
		.usam_details_box table td, 
		.usam_details_box table{ border:0px solid #ccc; }		
		table#order{margin-bottom:1em;	}	
		table#order td {  border: 0.1pt solid #606060; text-align:center;}		
		td, tbody th { border-top:1px solid #ccc;}		
		th.column-total { width:90px;}
		th.column-shipping { width:120px;}
		th.column-price { width:100px;}
		#shipping_notes{margin: 5px 0;width:100%;display:inline-block;}
		#shipping_notes #notes{border:1px solid #000; padding: 5px;}	
	</style>
	<?php $this->style(); ?>	
</head>
<body <?php echo $print; ?>>
	<div style="margin: 0pt; padding: 10pt 10pt 10pt 10pt; width: 571pt; background: #ffffff">
		<div id="header">
			<h1>
				%%name input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>" "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%% <br />
				<span><?php printf( esc_html__('Упаковочный лист #%s для заказа #', 'usam'), $this->id ); ?>%order_id%</span>
			</h1>
		</div>
		<?php $this->display_customer_details(); ?>
		<table id="order">
			<thead>
				<tr>
					<th><?php echo esc_html_e( 'Заказ', 'usam'); ?></th>		
					<th><?php echo esc_html_e( 'Дата заказа', 'usam'); ?></th>				
					<th><?php echo esc_html_e( 'Способ доставки', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Способ оплаты', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Оплата', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Оплачено', 'usam'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>%order_id%</td>	
					<td>%order_date%</td>					
					<td>%shipping_method_name% 
					[if storage_address!= {(%storage_address%)}]</td>
					<td>%gateway_name%</td>
					<td>%payment_status%</td>
					<td>%total_paid_currency%</td>					
				</tr>
			</tbody>
		</table>		
		[if shipped_notes != '' {
			<div id="shipping_notes">	
				<h3><?php _e( 'Заметка для курьера', 'usam'); ?></h3>
				<div id="notes">%shipped_notes%</div>
			</div>}]	
		<?php $this->display_table( $columns ); ?>	
	</div>
</body>
</html>