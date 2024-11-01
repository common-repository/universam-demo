<?php
/*
Printing Forms: Заказ
type:order
object_type:document
object_name:order
Description: Используется в заказе
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Заказ #%s', 'usam'), $this->id ); ?></title>
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
<?php
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
<body <?php echo $print; ?>>
	<div style="margin: 0pt; padding: 10pt; width: 571pt; background: #ffffff">
		<table id="logo-table" width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="100px;">%shop_logo%</td>
				<td><h1>%%name input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>" "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%%<br /><span><?php printf( esc_html__('Заказ #%s', 'usam'), $this->id ); ?></span></h1></td>
			</tr>
		</table>		
		<?php
		$this->display_customer_details(); 
		$note = usam_get_order_metadata($this->id, 'note');
		if ( $note ) 
		{
			?>
			<div id="shipping_notes">	
				<h3><?php _e( 'Заметка', 'usam'); ?></h3>
				<div id="notes"><?php echo esc_html($note); ?></div>
			</div>
			<?php 
		}
		$payments = $this->purchase_log->get_payment_status_sum();
		?>
		<table id="order">
			<thead>
				<tr>
					<th><?php echo esc_html_e( 'Дата заказа', 'usam'); ?></th>					
					<th><?php echo esc_html_e( 'Способ доставки', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Способ оплаты', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Оплата', 'usam'); ?></th>
					<th><?php echo esc_html_e( 'Оплачено', 'usam'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo usam_local_date($this->data['date_insert'], "d.m.Y"); ?></td>					
					<td><?php echo isset($this->shipped_document['name'])?$this->shipped_document['name']:''; ?></td>
					<td><?php echo isset($this->payment_document['name'])?$this->payment_document['name']:''; ?></td>
					<td><?php echo usam_get_order_payment_status_name( $this->data['paid'] ); ?></td>
					<td><?php echo usam_get_formatted_price($payments['total_paid'], $this->price_args); ?></td>					
				</tr>
			</tbody>
		</table>
		<?php				
		$columns = array( 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'quantity' => __('Количество','usam'),'total' => __('Всего','usam') );
		$this->display_table( $columns );
		?>	
	</div>
</body>
</html>