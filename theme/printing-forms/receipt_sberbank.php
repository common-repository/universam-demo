<?php
/*
Printing Forms:Квитанция Сбербанка
type:payment
object_type:document
object_name:order
Description: Используется в заказе
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Квитанция #%s', 'usam'), $this->id ); ?></title>	
	<?php $this->style(); ?>
	<style type="text/css">		
		.delivery_conditions{font-size:10px;}
		h2{font-size:12px; margin:0 0 5px 0; padding: 0;}
		h3{font-size:12px; margin:0 0 3px 0; padding: 0;}
		ul, ol{margin:3px 0;}
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
<body <?php echo $print; ?> bgColor="#ffffff" style="margin: 0pt; padding:10pt; width:565pt; background: #ffffff">
<table border="0" cellspacing="0" cellpadding="0" style="width:180mm; height:145mm;">
<tr valign="top">
	<td style="width:50mm; height:70mm; border:1pt solid #000000; border-bottom:none; border-right:none;" align="center">
	<b>Извещение</b><br>
	<font style="font-size:53mm">&nbsp;<br></font>
	<b>Кассир</b>
	</td>
	<td style="border:1pt solid #000000; border-bottom:none;" align="center">
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td align="right"><small><i>Форма № ПД-4</i></small></td>
			</tr>
			<tr>
				<td style="border-bottom:1pt solid #000000;">%recipient_full_company_name%</td>
			</tr>
			<tr>
				<td align="center"><small>(наименование получателя платежа)</small></td>
			</tr>
		</table>

		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td style="width:45mm; border-bottom:1pt solid #000000;">%recipient_inn%</td>
				<td style="width:9mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">%recipient_bank_number%</td>
			</tr>
			<tr>
				<td align="center"><small>(ИНН получателя платежа)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(номер счета получателя платежа)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>в&nbsp;</td>
				<td style="width:73mm; border-bottom:1pt solid #000000;">%recipient_bank_name%</td>
				<td align="right">БИК&nbsp;&nbsp;</td>
				<td style="width:33mm; border-bottom:1pt solid #000000;">%recipient_bank_bic%</td>
			</tr>
			<tr>
				<td></td>
				<td align="center"><small>(наименование банка получателя платежа)</small></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="60%" nowrap>Номер кор./сч. банка получателя платежа&nbsp;&nbsp;</td>
				<td width="40%" style="border-bottom:1pt solid #000000;">%recipient_bank_ca%</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td style="width:60mm; border-bottom:1pt solid #000000;">Оплата заказа №
	%order_id%
	от
	%order_date%</td>
				<td style="width:2mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center"><small>(наименование платежа)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(номер лицевого счета (код) плательщика)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="30%" nowrap>Ф.И.О. плательщика&nbsp;&nbsp;</td>
				<td width="70%" style="border-bottom:1pt solid #000000;">%customer_billingfirstname% %customer_billinglastname%</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="30%" nowrap>Адрес плательщика&nbsp;&nbsp;</td>
				<td width="70%" style="border-bottom:1pt solid #000000;">%customer_address%&nbsp;</td>
			</tr>
		</table>	
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>Сумма платежа&nbsp;
				<font style="text-decoration:underline;\"> %totalprice1% </font>&nbsp;руб.&nbsp;<font style="text-decoration:underline;\"> %totalprice2% </font>&nbsp;коп.</td>
				<td align="right">&nbsp;&nbsp;Сумма платы за услуги&nbsp;&nbsp;_____&nbsp;руб.&nbsp;____&nbsp;коп.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>Итого&nbsp;&nbsp;%totalprice1%&nbsp;руб.&nbsp;%totalprice2%&nbsp;коп.</td>
				<td align="right">&nbsp;&nbsp;&laquo;______&raquo;________________ 201____ г.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td><small>С условиями приема указанной в платежном документе суммы,
				в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен.</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td align="right"><b>Подпись плательщика _____________________</b></td>
			</tr>
		</table>
	</td>
</tr>
<tr valign="top">
	<td style="width:50mm; height:70mm; border:1pt solid #000000; border-right:none;" align="center">
	<b>Извещение</b><br>
	<font style="font-size:53mm">&nbsp;<br></font>
	<b>Кассир</b>
	</td>
	<td style="border:1pt solid #000000;" align="center">
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td align="right"><small><i>Форма № ПД-4</i></small></td>
			</tr>
			<tr>
				<td style="border-bottom:1pt solid #000000;">%recipient_full_company_name%</td>
			</tr>
			<tr>
				<td align="center"><small>(наименование получателя платежа)</small></td>
			</tr>
		</table>

		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td style="width:45mm; border-bottom:1pt solid #000000;">%recipient_inn%</td>
				<td style="width:9mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">%recipient_bank_number%</td>
			</tr>
			<tr>
				<td align="center"><small>(ИНН получателя платежа)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(номер счета получателя платежа)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>в&nbsp;</td>
				<td style="width:73mm; border-bottom:1pt solid #000000;">%recipient_bank_name%</td>
				<td align="right">БИК&nbsp;&nbsp;</td>
				<td style="width:33mm; border-bottom:1pt solid #000000;">%recipient_bank_bic%</td>
			</tr>
			<tr>
				<td></td>
				<td align="center"><small>(наименование банка получателя платежа)</small></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="60%" nowrap>Номер кор./сч. банка получателя платежа&nbsp;&nbsp;</td>
				<td width="40%" style="border-bottom:1pt solid #000000;">%recipient_bank_ca%</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td style="width:60mm; border-bottom:1pt solid #000000;">Оплата заказа №
	%order_id%
	от
	%order_date%</td>
				<td style="width:2mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center"><small>(наименование платежа)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(номер лицевого счета (код) плательщика)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="30%" nowrap>Ф.И.О. плательщика&nbsp;&nbsp;</td>
				<td width="70%" style="border-bottom:1pt solid #000000;">%customer_billingfirstname% %customer_billinglastname%</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td width="30%" nowrap>Адрес плательщика&nbsp;&nbsp;</td>
				<td width="70%" style="border-bottom:1pt solid #000000;">%customer_address%&nbsp;</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>Сумма платежа&nbsp;
				<font style="text-decoration:underline;\"> %totalprice1% </font>&nbsp;руб.&nbsp;<font style="text-decoration:underline;\"> %totalprice2% </font>&nbsp;коп.</td>
				<td align="right">&nbsp;&nbsp;Сумма платы за услуги&nbsp;&nbsp;_____&nbsp;руб.&nbsp;____&nbsp;коп.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td>Итого&nbsp;&nbsp;%totalprice1%&nbsp;руб.&nbsp;%totalprice2%&nbsp;коп.</td>
				<td align="right">&nbsp;&nbsp;&laquo;______&raquo;________________ 201____ г.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td><small>С условиями приема указанной в платежном документе суммы,
				в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен.</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:2pt;">
			<tr>
				<td align="right"><b>Подпись плательщика _____________________</b></td>
			</tr>
		</table>
	</td>
</tr>
</table>
<div class="delivery_conditions">
<h2>Внимание! В стоимость заказа не включена комиссия банка.</h2>
<!-- Условия поставки -->
<h3>Способ оплаты:</h3>
<ol>
	<li>Распечатайте квитанцию. Если у вас нет принтера, перепишите верхнюю часть квитанции и заполните по этому образцу стандартный бланк квитанции в вашем банке.</li>
	<li>Вырежьте по контуру квитанцию.</li>
	<li>Оплатите квитанцию в любом отделении банка, принимающего платежи от частных лиц.</li>
	<li>Сохраните квитанцию до подтверждения исполнения заказа.</li>
</ol>
<h3>Условия поставки:</h3>
<ul>
	<li>Отгрузка оплаченного товара производится после подтверждения факта платежа.</li>
	<li>Идентификация платежа производится по квитанции, поступившей в наш банк.</li>
</ul>
<p><b>Примечание:</b>
%recipient_full_company_name%
	не может гарантировать конкретные сроки проведения вашего платежа. За дополнительной информацией о сроках доставки квитанции в банк получателя, обращайтесь в свой банк.</p>
	</div>
</body>
</html>