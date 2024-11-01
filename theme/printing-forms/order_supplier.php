<?php
/*
Printing Forms: Заказ поставщику
type:crm
object_type:document
object_name:order_contractor
Description: Используется в документах CRM
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php	
$columns = ['n' => "№", 'name' => __('Товары','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'total' => __('Всего','usam')];
$print = $this->edit?'':'onload="window.print()"';
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo esc_html__('Заказ поставщику', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>
	<style type="text/css">		
		#content-table{width:100%;}
		#invoice-content{vertical-align:top;}
		#invoice-footer{color:#444; padding-top:10px;}		
		#invoice-footer{color:#444; padding-top:10px;}		
		
		table { border-collapse: collapse; }
		table.acc{margin:20px 10px 20px 0}
		table.acc td { border: 1pt solid #000000; padding: 0pt 3pt; line-height: 16px; }
		table.it td { border: 1pt solid #000000; padding: 0pt 3pt; }
		table.sign td { font-weight: bold; vertical-align: top; }
		table.header td { padding: 0pt; vertical-align: top; }
		.qr{margin-left:10px}
	</style>
	<?php $this->style(); ?>	
</head>
<body <?php echo $print; ?> style="background: #ffffff">	
	<div style="margin: 0pt; padding:15pt; width:565pt; background: #ffffff">	
		<h1 style="text-align:center; white-space:normal;">Заказ поставщику № %id% от %date%</h1>
		
		<h2 style="text-align: left; margin: 0.4em 0;">%%name_counterparty input "Поставщик" "Напишите название контрагента"%%</h2>	
		<p style="text-align: left;">%%counterparty input "[if code_type_payer=company {%customer_name% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_legalpostcode%, %customer_legalcity%, %customer_legaladdress% %counterparty_accounts%} {%customer_name% %customer_address%}]" "укажите реквизиты"%%</p>	
		<h2 style="text-align: left; margin: 0.4em 0;">%%name_company input "Заказчик" "Напишите название контрагента"%%</h2>
		<p style="text-align: left;white-space:normal;">%%company_details input "<?php echo '%recipient_company_name% ИНН %recipient_inn% КПП %recipient_ppc%, '.__('Адрес', 'usam').': %recipient_contactcity% %recipient_contactaddress%, %recipient_legaloffice%'; ?>"%%</p>
		<br>
		<?php $this->display_table( $columns );	?>
		<?php _e( 'Всего наименований %number_products%, на сумму %total_price_currency%', 'usam'); ?><br>
		<b>%total_price_word%</b>
		<p style="border-top: 2pt solid #000000;"></p>
		[if conditions!= {		
		<div class ="invoice-conditions" style="margin-top:10px">
			<h2 style="text-align: left;"><?php _e( 'Условия и комментарии', 'usam'); ?></h2>				
			<p style="text-align: left;">%conditions%</p>
		</div>
		}]
	</div>	
</body>
</html>		