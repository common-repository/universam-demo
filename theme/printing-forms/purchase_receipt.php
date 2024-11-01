<?php
/*
Printing Forms: Товарный чек
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
	<title><?php printf( esc_html__('Товарный чек № %s', 'usam'), $this->id ); ?></title>
	<style type="text/css">		
		#customer {	overflow:hidden;}
		#customer .shipping, #customer .billing {float: left; width: 50%;}
		th.column-total { width:90px;}
		th.column-shipping { width:120px;}
		th.column-price { width:100px;}	
		.footer_purchase_receipt{margin-top: 15px}	
		.footer_purchase_receipt .terms_and_conditions{font-family: Verdana, sans-serif; font-size: 9px; margin-bottom:15px}
		.footer_purchase_receipt .consent_date{ border:1px solid #ccc; padding: 3px 10px; margin 10px 0; font-weight:bold; text-align:center;}
		.footer_purchase_receipt .consent{font-family: Verdana, sans-serif; font-size:11px;}
		.footer_purchase_receipt .date{font-family: Verdana, sans-serif; font-size:11px;}
		.footer_purchase_receipt .customer_signature{margin-top: 10px; border: none;}
		.footer_purchase_receipt .customer_signature td{border: none; width: 25%; font-size:10px;}
		.line{border-bottom:1px solid #000; margin: 0 10%; height:30px;}		
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
<body style="margin: 0pt; padding: 10pt 10pt 10pt 10pt; width: 571pt; background: #ffffff" <?php echo $print; ?>>
	<div id="header">
		<h1>
			%%name input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>" "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%% <br />
			<span><?php printf( esc_html__('Товарный чек № %s от %s', 'usam'), $this->id, date_i18n( 'd.m.Y' ) ); ?></span>
		</h1>
	</div>
	<br>
	<div id="usam_requisite_company">
		<?php	
			$requisites = usam_shop_requisites_shortcode();	
			echo sprintf( __( "Продавец: %s", 'usam'), $requisites['full_company_name'] ).' ';
			echo sprintf( __( "ИНН: %s", 'usam'), $requisites['inn'] ).' ';
			echo sprintf( __( "Адрес: %s", 'usam'), $requisites['full_legaladdress'] );
		?>
	</div>
	<?php		
	$columns = array( 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'quantity' => __('Количество','usam'),'total' => __('Всего','usam') );
	$this->display_table( $columns );
	?>				
	<div class = "footer_purchase_receipt">			
		<table class = "customer_signature">
			<tr>
				<td class = "name"><strong><?php esc_html_e( 'Продавец', 'usam'); ?></strong></td>
				<td><p class = "line"></p></td>
				<td><p class = "line"></p></td>
				<td><p class = "line"></p></td>
			</tr>
			<tr>
				<td></td>
				<td style="text-align:center;"><?php esc_html_e( 'Должность', 'usam'); ?></td>
				<td style="text-align:center;"><?php esc_html_e( 'ФИО', 'usam'); ?></td>					
				<td style="text-align:center;"><?php esc_html_e( 'Подпись', 'usam'); ?></td>
			</tr>
			<tr>					
				<td></td>
				<td></td>					
				<td></td>
				<td><?php esc_html_e( 'М.П.', 'usam'); ?></td>
			</tr>
			<tr>					
				<td><strong><?php esc_html_e( 'Покупатель', 'usam'); ?></strong></td>
				<td></td>
				<td><p class = "line"></p></td>
				<td><p class = "line"></p></td>
			</tr>	
			<tr>					
				<td colspan='2'></td>
				<td style="text-align:center;"><?php esc_html_e( 'ФИО', 'usam'); ?></td>
				<td style="text-align:center;"><?php esc_html_e( 'Подпись', 'usam'); ?></td>
			</tr>				
		<table>		
	</div>
</body>
</html>