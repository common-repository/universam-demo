<?php
/*
Printing Forms:Акт выполненных работ
type:shipped
object_type:document
object_name:shipped
Description: Используется в документах доставки
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
if ( $this->edit )
	$print = '';
else
	$print = 'onload="window.print()"';
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">			
		#invoice-content{vertical-align:top;}
		#invoice-footer{color:#444;}	
		h2{margin:0}		
	</style>
	<?php $this->style(); ?>	
	<title><?php echo esc_html__('Акт выполненных работ', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>	
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding: 15pt; width: 555pt; background: #ffffff">	
	<h1 style="text-align: center;">%%document_name input "<?php esc_html_e( 'Акт выполненных работ', 'usam'); ?> №%id% <?php esc_html_e( 'от', 'usam'); ?> %date%" "Название документа"%%</h1>	
	
	<h2 style="text-align: left;">%%name_company input "Исполнитель" "Напишите название контрагента"%%</h2>				
	<p class="counterparty">%%company_details input "<?php echo '%recipient_company_name% '.__('ИНН', 'usam').' %recipient_inn% '.__('КПП', 'usam').' %recipient_ppc% %recipient_legalpostcode% %recipient_legalcity% %recipient_legaladdress%'; ?>" "<?php _e( 'Укажите реквизиты вашей компании', 'usam') ?>"%%</p>
	<h2 style="text-align: left;">%%name_counterparty input "Заказчик" "Напишите название контрагента"%%</h2>				
	<p class="counterparty">%%counterparty input "[if code_type_payer=company {%customer_name% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_legalpostcode%, %customer_legalcity%, %customer_legaladdress%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "укажите реквизиты"%%</p>	
	<table id="products-table" style="width:100%">
		<thead>
			<tr>
				<th class="column-n">№</th>
				<th class="column-name"><?php _e( 'Название услуги', 'usam'); ?></th>					
				<th class="column-total"><?php _e( 'Стоимость', 'usam'); ?></th>						
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="column-n">1</td>
				<td class="column-name"><p style="line-height:normal"><?php _e( 'Оплата за услуги по доставке по сч. %document_number% от %order_date%.', 'usam'); ?></p></td>
				<td class="column-total">%shipped_price%</td></tr>				
		</tbody>
		<tfoot>
			<tr class="subtotal">
				<td></td>
				<td style="text-align: right;"><?php _e( 'Итого', 'usam'); ?>:</td>
				<td>%shipped_price%</td>
			</tr>				
		</tfoot>
	</table>
	<br>
	<?php _e( 'Всего 1 наименование, на сумму %shipped_price_currency%', 'usam'); ?><br>
	<p class="total_price_word"><b>%total_price_word%</b></p>
	<p style="margin-top:10px;">%%description textarea "Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг не имеет." "Условия"%%</p>
	<div class ="invoice-footer" style="margin-top:20px">%%signatures textarea "<table style='table-layout:fixed'><tr><td><strong>Исполнитель</strong></td><td><strong>Заказчик</strong></td></tr><tr><td>%recipient_company_name%</td><td>%customer_name%</td></tr><tr><td></td><td></td></tr><tr><td>______________________________________</td><td>______________________________________</td></tr><tr><td>%recipient_gm%</td><td>%customer_gm%</td></tr></table>" "Подписи"%%</div>
</body>
</html>		