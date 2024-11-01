<?php
/*
Printing Forms: Акт приемки-передачи
type:shipped
object_type:document
object_name:shipped
Description: Акт приемки-передачи используется для придаче клиенту товара
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php 
$columns = array( 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'quantity' => __('Количество','usam'),'total' => __('Всего','usam') );
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Акт приемки-передачи товара № %s', 'usam'), $this->id ); ?></title>
	<style type="text/css">		
		#wrapper {margin:0 auto; width:95%;}		
		#usam_requisite_company{font-size:10px;}
		#customer {	overflow:hidden;}
		#customer .shipping, #customer .billing {float: left; width: 50%;}
		table#order{margin-bottom:1em;	}	
		table#order td {  border: 0.1pt solid #606060; text-align:center;}			
		.footer_purchase_receipt{margin-top: 15px}	
		.footer_purchase_receipt .terms_and_conditions{font-size: 9px; margin-bottom:15px}
		.footer_purchase_receipt .consent_date{ border:1px solid #ccc; padding: 3px 10px; margin 10px 0; font-weight:bold; text-align:center;}
		.footer_purchase_receipt .consent{font-size:11px;}
		.footer_purchase_receipt .date{font-size:11px;}
		.footer_purchase_receipt .customer_signature{margin-top: 10px; border: none;}
		.footer_purchase_receipt .customer_signature td{border: none; width: 25%; font-size:12px;}
		.footer_purchase_receipt .customer_signature td.name{text-align:center;}
		.line{border-bottom:1px solid #000; margin: 0 10%; height:30px;}		
	</style>
	<?php $this->style(); ?>	
</head>
<body <?php echo $print; ?>>
	<div style="margin: 0pt; padding: 10pt 10pt 10pt 10pt; width: 571pt; background: #ffffff">
		<div id="header">
			<h1>				
				%%name input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>" "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%%<br />
				<span><?php printf( esc_html__('Акт приемки-передачи товара № %s', 'usam'), $this->id ); ?></span>
			</h1>
		</div>			
		<div id="usam_requisite_company">%%seller input "<?php _e( "Продавец:", 'usam'); ?> %recipient_full_company_name% <?php _e( "ИНН:", 'usam'); ?> %recipient_inn% <?php _e( "КПП:", 'usam'); ?> %recipient_ppc%, <?php _e( 'Адрес', 'usam'); ?>: %recipient_full_legaladdress%"%%</div>
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
					<td>%order_date%</td>					
					<td>%shipping_method_name%</td>
					<td>%gateway_name%</td>
					<td>%payment_status%</td>
					<td>%total_paid_currency%</td>					
				</tr>
			</tbody>
		</table>	
		<?php $this->display_table( $columns ); ?>
		<div class = "footer_purchase_receipt">		
			<h4><?php esc_html_e( 'Условия', 'usam'); ?></h4>
			<p class = "terms_and_conditions">%%conditions_act_transferring textarea "Условия"%%</p>
			<div class = "consent_date">					
				<p class = "consent"><?php esc_html_e( 'Подтверждаю, что с условиями акта в том числе с правилами возврата товара, предъявлении претензий к продавцу в отношении товара, ОЗНАКОМЛЕН И СОГЛАСЕН', 'usam'); ?></p>
				<p class = "date"><?php esc_html_e( 'Дата подписания акта', 'usam'); ?>: &laquo;_________&raquo;__________________________</p>
			</div>
			<table class = "customer_signature">
				<tr>
					<td class = "name" colspan='2'><strong><?php esc_html_e( 'Выдал', 'usam'); ?></strong></td>
					<td class = "name" colspan='2'><strong><?php esc_html_e( 'Покупатель', 'usam'); ?></strong></td>
				</tr>				
				<tr>
					<td style ="width:25%;"><p class = "line">%%courier input "Фамилия продавца"%%</p></td>
					<td><p class = "line"></p></td>
					<td style ="width:25%;"><p class = "line"></p></td>
					<td><p class = "line"></p></td>
				</tr>
				<tr>
					<td style="text-align:center;"><?php esc_html_e( 'ФИО', 'usam'); ?></td>
					<td style="text-align:center;"><?php esc_html_e( 'Подпись', 'usam'); ?></td>
					<td style="text-align:center;"><?php esc_html_e( 'ФИО', 'usam'); ?></td>
					<td style="text-align:center;"><?php esc_html_e( 'Подпись', 'usam'); ?></td>
				</tr>				
			<table>		
		</div>
	</div>
</body>
</html>