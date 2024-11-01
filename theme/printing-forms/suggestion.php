<?php
/*
Printing Forms:Коммерческое предложение
type:crm
object_type:document
object_name:suggestion
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
$columns = array( 'n' => "№", 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'discount_price' => __('Цена со скидкой','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'total' => __('Всего','usam') );
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">					
		#invoice-content{vertical-align:top;}
		#invoice-footer{color:#444;}	
		table.sign td { font-weight: bold; vertical-align: top; }			
	</style>
	<?php $this->style(); ?>		
	<title><?php echo esc_html__('Коммерческое предложение', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>	
</head>
<body <?php echo $print; ?>>
	<div style="margin: 15pt; width: 565pt; background: #ffffff">
		<table id="logo-table" width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="100px;">%shop_logo%</td>
				<td>					
					<p style="font-size: 20px; font-weight: 700;">%%name_shop input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%%</p>				
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;"><?php esc_html_e( 'Коммерческое предложение', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</h1>			
		<p>%closedate%</p>
		
		<h2 style="text-align: left;">%%name_company input "Продавец" "Напишите название контрагента"%%</h2>				
		<p>%%company_details input "<?php echo '%recipient_company_name% '.__('ИНН', 'usam').' %recipient_inn% '.__('КПП', 'usam').' %recipient_ppc% %recipient_legalpostcode% %recipient_legalcity% %recipient_legaladdress%'; ?>" "Укажите реквизиты вашей компании"%%</p>			
		<h2 style="text-align: left;">%%name_counterparty input "Покупатель" "Напишите название контрагента"%%</h2>				
		<p>%%counterparty input "[if code_type_payer=company {%customer_name% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_legalpostcode%, %customer_legalcity%, %customer_legaladdress%} {%customer_name% %customer_address%}]" "Укажите реквизиты покупателя"%%</p>
				
		<table id="content-table" width="100%" border="0" cellpadding="0" cellspacing="0">
		[if description!= {		
			<tr>
				<td>
					<h2 style="text-align: left;"><?php _e( 'Описание', 'usam'); ?></h2>				
					<p>%description%</p>
				</td>
			</tr>}]	
		[if conditions!= {		
			<tr>
				<td>
					<h2 style="text-align: left;"><?php _e( 'Условия', 'usam'); ?></h2>				
					<p>%conditions%</p>
				</td>
			</tr>}]	
		</table>			
		<?php $this->display_table( $columns );	?>
		<div class ="invoice-footer" style="margin-top:10px">%%signatures textarea "<table class='sign' width='100%'><tbody><tr><td>Руководитель</td><td>_________________</td><td>%recipient_gm%</td><td style='width: 20pt;'></td><td style='text-align: right;'>Главный бухгалтер</td><td style='text-align: right;'>____________________</td><td style='text-align: right;'>%recipient_accountant%</td></tr></tbody></table>" "Подписи"%%</div>
	</div>
</body>
</html>		