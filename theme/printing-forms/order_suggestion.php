<?php
/*
Printing Forms: Коммерческое предложение
type:order
object_type:document
object_name:order
*/
$columns = array( 'n' => "№", 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'discount_price' => __('Цена со скидкой','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'total' => __('Всего','usam') );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Коммерческое предложение #%s', 'usam'), $this->id ); ?></title>
	<style type="text/css">	
		@media print  {
			header { background: none; color: #000; }
			header img { -webkit-filter: invert(100%);	filter: invert(100%); }
			table.sign td { font-weight: bold; vertical-align: top; }
		}					
		#logo-table td{border:none}		
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
	<div style="margin: 15pt; width: 565pt; background: #ffffff;">
		<table id="logo-table" width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="100px;">%shop_logo%</td>
				<td>					
					<p style="font-size: 20px; font-weight: 700;">%%name_shop input "<?php echo __('Интернет-магазин', 'usam')." - ".get_bloginfo('name'); ?>"%%</p>				
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;"><?php esc_html_e( 'Коммерческое предложение', 'usam'); ?>: %order_id% <?php esc_html_e( 'от', 'usam'); ?> %order_date%</h1>	
		
		<h2 style="text-align: left;">%%name_company input "Продавец" "Напишите название контрагента"%%</h2>				
		<p>%%company_details input "%recipient_company_name% <?php _e( 'ИНН', 'usam') ?> %recipient_inn% <?php _e( 'КПП', 'usam') ?> %recipient_ppc%, <?php _e( 'Адрес', 'usam') ?>: %recipient_contactcity% %recipient_contactaddress%, %recipient_legaloffice%"%%</p>	
		<h2 style="text-align: left;">%%name_counterparty input "Покупатель" "Напишите название контрагента"%%</h2>				
		<p>%%counterparty input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "Укажите реквизиты покупателя"%%</p>						
			
		<?php $this->display_table( $columns );	?>
		
		<div class ="invoice-footer" style="margin-top:10px">%%conditions textarea "" "Условия"%%</div>	
		
		<div class ="invoice-footer" style="margin-top:10px">%%signatures textarea "<table class='sign' width='100%'><tbody><tr><td>Руководитель</td><td>_________________</td><td>%recipient_gm%</td></tr></tbody></table>" "Подписи"%%</div>
	</div>
</body>
</html>