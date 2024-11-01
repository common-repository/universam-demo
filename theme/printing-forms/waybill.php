<?php
/*
Printing Forms:Накладная ТОРГ-12
type:order
object_type:document
object_name:order
orientation:landscape
Description: Используется в заказе
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
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Накладная для заказа #%s', 'usam'), $this->id ); ?></title>	
	<style type="text/css">		
		@page {size:landscape; margin: 0; }
		* {font-size:10px;}
		body {font-family:"dejavu serif", Helvetica, Arial, Verdana, sans-serif;}
		table td{white-space: nowrap}
		h1 {font-size:1.3em;}
		h1 span {font-size:0.8em;}
		h2 {font-size:1.0em; color: #333; margin: 0 0 0.4em 0;}
		h3 {margin: 0 0 0.4em 0;}
		h4 {margin: 0 0 0.4em 0;}	
	</style>
	<?php $this->style(); ?>	
</head>
<body  <?php echo $print; ?> style="margin: 5pt; width: 830pt; background: #ffffff">
<?php 
$products_count = count($this->products);
$requisites = usam_shop_requisites_shortcode( ); 
$company = $requisites['full_company_name'].' '.$requisites['inn'].'/'.$requisites['ppc'].' '.$requisites['full_legaladdress'].' '.$requisites['phone'].' '.$requisites['bank_details'];
?>
<div class=Section1>
<p class=Normal style='line-height:133%'>Унифицированная форма<span lang=EN-US>N</span> ТОРГ-12 Утверждена постановлением Госкомстата России от 25 12.98 N 132</p>
<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 style='width:100%;border-collapse:collapse'>
	<tr style='page-break-inside:avoid;height:10.0pt'>
		<td colspan=4 rowspan=3 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:5.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;word-break: break-all;white-space:normal;'><?php echo $company ; ?></p>
		</td>
		<td rowspan=4 valign=top style='width:47.15pt;border:solid windowtext 1.0pt; border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:5.0pt'>			
			<p class=Normal align=right style='margin-top:1.0pt;margin-right:0cm; margin-bottom:0cm;margin-left:5.0pt;margin-bottom:.0001pt;text-align:right;	text-indent:-5.0pt;line-height:normal'>&nbsp;</p>
			<p class=Normal align=right style='margin-top:1.0pt;margin-right:0cm; margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal;white-space:normal;'>
				<span style='font-size:7.0pt'>Форма по ОКУД </span>
			</p>
			<p class=Normal align=right style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:right;	line-height:normal'><span style='font-size:7.0pt'>по ОКПО</span></p>		
		</td>
		<td valign=top style='width:67.0pt;border:solid windowtext 1.0pt; border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:5.0pt'>
			<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm; margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center; line-height:normal'><span style='font-size:7.0pt'>Код</span></p>
		</td>
	</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
	<td valign=top style='width:67.0pt;border:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><span style='font-size:7.0pt'>0330212</span></p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
	<td valign=top style='width:67.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><?php echo $requisites['okpo']; ?></p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
<td colspan=4 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
	<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal;white-space:normal;font-style:italic'>грузоотправитель, адрес, номер телефона, банковские реквизиты</p>
	<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
</td>
<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
	<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
</td>
</tr>
<tr style='page-break-inside:avoid;height:10.0pt'>
<td colspan=4 rowspan=2 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;border-top:none; border-bottom:none; padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;font-style:italic'>структурное подразделение</p>
</td>
<td rowspan=2 valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=right style='margin:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal'><span style='font-size:7.0pt'>Вид деятельности<br />по ОКДП</span></p>
</td>
<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
	<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
<td colspan=4 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal;white-space:normal;'><?php _e( 'Грузополучатель', 'usam') ?>: %%consignee input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "укажите реквизиты"%%</p>
<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
</td>
<td valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt; padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
<p class=Normal align=right style='margin:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal'><span style='font-size:7.0pt'>по ОКПО</span></p>
</td>
<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
	<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
</td>
</tr>
<tr style='page-break-inside:avoid;height:10.0pt'>
	<td colspan=4 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;white-space:normal;'><span style='font-size:7.0pt'><?php _e( 'Поставщик', 'usam'); ?>: </span><span style='font-size:7.0pt'><?php echo $company ; ?></span></p>
	</td>
<td valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
<p class=Normal align=right style='margin:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal'><span style='font-size:7.0pt'>по ОКПО</span></p>
</td>
<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo $requisites['okpo']; ?></p>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
</td>
</tr>
<tr style='page-break-inside:avoid;height:10.0pt'>
<td colspan=4 valign=top style='width:502.85pt;border:solid windowtext 1.0pt;border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;white-space:normal'><span style='font-size:7.0pt'><?php _e( 'Плательщик', 'usam'); ?>:   </span><span style='font-size:7.0pt'>%%payer input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "укажите реквизиты"%%</span></p>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>&nbsp;</span></p>
</td>
<td valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=right style='margin:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal'><span style='font-size:7.0pt'>по ОКПО</span></p>
</td>
<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
</td>
</tr>
<tr style='height:9.0pt'>
	<td colspan=4 valign=top style='width:502.85pt;border:solid windowtext 1.0pt; border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>Основание: </span><span style='font-size:7.0pt'>Cчет № 
			<?php 
			echo $this->data['number'].' '.__('от','usam').' '.usam_local_date($this->data['date_insert'],'d.m.Y');
			?></span></p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>номер</span></p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
	<td rowspan=4 style='width:190.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><b><span style='font-size:7.0pt'>ТОВАРНАЯ НАКЛАДНАЯ</span></b></p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><b>&nbsp;</b></p>
	</td>
	<td colspan=2 valign=top style='width:162.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>договор, заказ-наряд</p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td rowspan=4 valign=top style='width:120.85pt;border-top:none;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'>
			<span style='font-size:7.0pt'>Валюта: рубль</span>
		</p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>
			<span style='font-size:7.0pt'>Дата курса: </span><span style='font-size:7.0pt'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		</p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'>
			<span style='font-size:7.0pt'>Курс</span><span lang=EN-US style='font-size:7.0pt'> USD:</span><span lang=EN-US	style='font-size:7.0pt'> </span><span style='font-size:7.0pt'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		</p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>дата</span></p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'></p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:9.0pt'>
	<td width=69 valign=top style='width:52.0pt;border:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'><span style='font-size:7.0pt'>Номер документа</span></p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	</td>
	<td width=147 valign=top style='width:110.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'><span style='font-size:7.0pt'>Дата составления</span></p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	</td>
	<td width=130 valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>номер</span></p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td width=116 valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'></p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt; text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:10.0pt'>
	<td width=69 valign=top style='width:52.0pt;border:solid windowtext 1.0pt;border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center; line-height:normal'><?php echo $this->id ; ?></p>	
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td width=147 valign=top style='width:110.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo usam_local_date($this->data['date_insert'],'d.m.Y'); ?></p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td width=130 valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>дата</span></p>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td width=116 valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:11.0pt'>
	<td width=216 colspan=2 valign=top style='width:162.0pt;padding:0cm 2.0pt 0cm 2.0pt;
	height:11.0pt'>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td width=130 valign=top style='width:47.15pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'><span style='font-size:7.0pt'>Вид операции</span></p>
	<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td width=116 valign=top style='width:67.0pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
</table>

<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 style='border-collapse:collapse;width:100%;'>
<tr style='page-break-inside:avoid;height:10.0pt'>
	<td rowspan=2 valign=top style='width:10.0pt;border:solid windowtext 1.0pt;border-bottom:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;white-space:nowrap;'>Номер<br />по<br />порядку</p>
	</td>
	<td colspan="2" style='width:80.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Товар</p>
	</td>
	<td colspan="2" valign=top style='width:80.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Единица измерения</p>
	</td>
	<td rowspan=2 valign=top style='width:45.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'><p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Вид<br />упаковки</p></td>
	<td colspan="2" valign=top style='width:80.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Количество</p>
	</td>
	<td rowspan=2 valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Масса<br /> брутто</p>
	</td>
	<td rowspan=2 valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>Кол-во<br />(масса<br />нетто)</p></td>
	<td rowspan=2 valign=top style='width:60.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>Цена,<br />руб., коп.</p>
	</td>
	<td rowspan=2 valign=top style='width:60.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>Сумма<br />без учета НДС,<br />руб. коп.</p>
	</td>
	<td colspan=2 valign=top style='width:81.0pt;border:solid windowtext 1.0pt;	border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>НДС</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td rowspan=2 valign=top style='width:48.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:6.0pt;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>Сумма<br />с учетом НДС,<br />руб. коп.</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='page-break-inside:avoid;height:26.0pt'>
	<td valign=top style='width:60.0pt;border:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:26.0pt'>
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>наименование, характеристика<br />сорт, артикул товара</p>
		<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:20.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>код</p>
	</td>
	<td valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
	<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>наиме-<br />нование</p>
	</td>
	<td valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt;'> 
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>код по ОКЕИ</p>
	</td>
	<td valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'> 
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal;white-space:normal;'>в одном<br />месте</p>
	</td>
	<td valign=top style='width:40.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>мест,<br />штук</p>
	</td>
	<td valign=top style='width:38.0pt;border:none;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:26.0pt'>
		<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>ставка, %</p>
		<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:43.0pt;border-top:solid windowtext 1.0pt;border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:26.0pt'>
		<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>сумма,<br />руб, коп</p>
		<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='height:9.0pt'>
	<td valign=top style='width:10.0pt;border:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>1</p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:60.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>2</p>
		<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>3</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>4</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>5</p>
	</td>
	<td valign=top style='width:45.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>6</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>7</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>8</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>9</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>10</p>
	</td>
	<td valign=top style='width:60.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>11</p>
	</td>
	<td valign=top style='width:60.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>12</p>
	</td>
	<td valign=top style='width:38.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>13</p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:43.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>14</p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='width:48.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>15</p>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<?php 
$i = 0;
foreach ( $this->products as $product ) 
{
	$i++;
?>
	<tr style='height:9.0pt'>
		<td valign=top style='border:solid windowtext 1.0pt; border-top:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'><?php echo $i; ?></p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt;width:60.0pt;white-space:normal;'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal;'><?php echo $product->name;	?></p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt;'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:left;line-height:normal'><?php echo usam_get_product_meta( $product->product_id, 'sku' ); ?></p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt; padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo usam_get_product_unit_name( $product->product_id ); ?></p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;	text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
		<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>

		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;	text-align:center;line-height:normal'><?php echo $product->quantity; ?></p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;		padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo $product->price; ?></p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
		padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo $product->price; ?></p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>0%</p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>0,00</p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
		<td valign=top style='border-top:none;border-left:none;	border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'><?php echo $product->price; ?></p>
			<p class=Normal align=center style='margin:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
		</td>
</tr>
<?php 
}
?>
<tr style='height:10.0pt'>
	<td colspan=7 valign=top style='border:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:right;line-height:normal'>Итого на странице №1</p>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm; margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'></p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>X</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>X</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>0,00</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='border-top:none;border-left:none; border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:normal'>0,00</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:normal'>&nbsp;</p>
	</td>
	<td valign=top style='border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:normal'>0,00</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:normal'>&nbsp;</p>
	</td>
</tr>
<tr style='height:11.0pt'>
	<td colspan=7 valign=top style='width:425.0pt;border:none;	border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;	margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:right;line-height:133%'>Всего по накладной: </p>
		<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;	margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:133%'>&nbsp;</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;	margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:133%'>&nbsp;</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>&nbsp;</p>
	</td>
	<td valign=top style='width:40.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm; margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;	line-height:133%'>1</p>
	</td>
	<td valign=top style='width:60.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>X</p>
	</td>
	<td valign=top style='width:60.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>%order_final_basket%</p>
	</td>
	<td valign=top style='width:38.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'></p>
	</td>
	<td valign=top style='width:43.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>0,00</p>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>&nbsp;</p>
	</td>
	<td valign=top style='width:48.0pt;border:solid windowtext 1.0pt;border-left:none;padding:0cm 2.0pt 0cm 2.0pt;height:11.0pt'>
		<p class=Normal align=center style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;text-align:center;line-height:133%'>%order_final_basket%</p>
	</td>
</tr>
</table>
<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 style='border-collapse:collapse;'>
	<tr style='height:9.0pt'>
		<td valign=top style='width:160.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>Товарная накладная имеет приложение на </p>		
		</td>
		<td colspan=4 valign=top style='width:230.0pt;border:none;border-bottom:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>	
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'></p>
		</td>
		<td colspan=3 valign=top style='width:150.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>	
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>листах</p>
		</td>
		<td colspan=2 valign=top style='width:77.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
	</tr>
	<tr style='height:9.0pt'>
		<td valign=top style='width:122.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>		
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>и содержит </p>
		</td>
		<td colspan=4 valign=top style='width:261.0pt;border:none;border-bottom:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>			
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'><?php echo usam_get_number_word( $products_count, 0 ) ?></p>
		</td>
		<td colspan=3 valign=top style='width:157.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:133%'><?php echo _n( 'порядковый номер записи', 'порядковых номеров записей', $products_count, 'usam'); ?></p>
		</td>
		<td colspan=2 valign=top style='width:77.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:9.0pt'>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
			<p class=Normal style='margin:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
	</tr>
	<tr style='height:10.0pt'>
		<td valign=top style='width:122.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
		<td colspan=4 valign=top style='width:261.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-bottom:.0001pt;text-align:center;line-height:133%;font-style:italic;'>прописью</p>
		</td>
		<td colspan=3 valign=top style='width:157.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
		<td colspan=2 valign=top style='width:77.0pt;border:none;border-bottom:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
	</tr>
	<tr style='height:19.0pt'>
		<td valign=top style='width:130.5pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
		<td valign=top style='width:130.5pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Масса груза (нетто) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>	
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>		
		</td>
		<td colspan=6 valign=top style='width:307.0pt;border-top:none;border-left:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;border-bottom:solid windowtext 1.0pt;'>&nbsp;</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:72.0pt;margin-bottom:.0001pt;line-height:133%;font-style:italic'>прописью </p>
		</td>
		<td colspan=2 valign=top style='width:77.0pt;border-top:none;	border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
		</td>
	</tr>
	<tr style='height:19.0pt'>
		<td valign=top style='width:130pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Всего мест_______________________</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:100px;margin-bottom:.0001pt;line-height:133%;font-style:italic'>прописью</p>
		</td>
		<td valign=top style='width:130pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Масса груза (брутто) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>	
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
		<td colspan=6 valign=top style='width:280.0pt;border-top:none;border-left:none;border-right:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal;border-bottom:solid windowtext 1.0pt;'>&nbsp;</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:72.0pt;margin-bottom:.0001pt;line-height:133%;font-style:italic'>прописью </p>
		</td>
		<td colspan=2 valign=top style='width:77.0pt;border-top:none;	border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;	padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
	</tr>
	<tr style='height:28.0pt'>
		<td colspan=2 valign=top style='width:361.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:28.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Приложение (паспорта, сертификаты и т.п.) на _________  листах</p>
			<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:210.0pt;margin-bottom:.0001pt;line-height:133%;font-style:italic'>прописью</p>
		</td>
		<td colspan=8 valign=top style='width:256.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:28.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
			<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>По доверенности №  ____________________________________________________________   от  ________________________</p>
			<p class=Normal style='margin-top:2.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>		
	</tr>
	<tr style='height:10.0pt'>
		<td colspan=2 valign=top style='width:361.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
		<td colspan=2 valign=top style='width:57.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Выданной   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
		</td>
		<td colspan=6 valign=top style='width:199.0pt;border-bottom:solid windowtext 1.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt;'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'></p>
		</td>
	</tr>
	<tr style='height:19.0pt'>
		<td colspan=2 valign=top style='width:233.0pt;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Всего отпущено  <?php echo usam_get_number_word( $products_count, 0 ) ?>  порядковых номера наименований </p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>на сумму %order_final_basket_string%</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
		<td colspan=2 valign=top style='width:57.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%;font-style:italic'></p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>
		<td colspan=4 valign=top style='width:240.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:10.0pt;'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;	margin-left:0cm;margin-bottom:.0001pt;line-height:133%;font-style:italic'>кем, кому	(организация, должность, фамилия, и.о.)</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>&nbsp;</p>
		</td>		
		<td colspan=2 valign=top style='width:77.0pt;border:none;padding:0cm 2.0pt 0cm 2.0pt;height:19.0pt'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:normal'>&nbsp;</p>
		</td>
	</tr>	
	<tr style='height:10.0pt'>
		<td colspan=3 valign=top style='padding:0cm 2.0pt 0cm 2.0pt;'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%'>Товар отпустил</p>
		</td>		
		<td colspan=7 valign=top style='border:none;padding:0cm 2.0pt 0cm 2.0pt;'>
			<p class=Normal style='margin-top:1.0pt;margin-right:0cm;margin-bottom:0cm;margin-left:0cm;margin-bottom:.0001pt;line-height:133%;'>Товар принял</p>
		</td>
	</tr>
	<tr style='height:18.0pt'>
		<td colspan=3 valign=top style='padding:0cm 2.0pt 0cm 2.0pt;'>
			<p class=Normal style='margin-top:0pt;margin-right:0cm;margin-bottom:0cm;margin-left:72.0pt;margin-bottom:.0001pt;line-height:133%;border-top:solid windowtext 1.0pt;font-style:italic;text-align:right'>должность&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; подпись &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;расшифровка подписи</p>
			<p class=Normal style='margin:0pt;margin-right:0cm;margin-bottom:0cm;margin-left:5cm;margin-bottom:.0001pt;line-height:133%'>М.П.</p>
		</td>		
		<td colspan=7 valign=top style='border:none;padding:0cm 2.0pt 0cm 2.0pt;'>
			<p class=Normal style='margin:0pt;margin-right:0cm;margin-bottom:0cm;margin-left:72.0pt;margin-bottom:.0001pt;line-height:133%;border-top:solid windowtext 1.0pt;font-style:italic;text-align:right'>должность&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; подпись &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;расшифровка подписи</p>
			<p class=Normal style='margin:0pt;margin-right:0cm;margin-bottom:0cm;margin-left:5cm;margin-bottom:.0001pt;line-height:133%'>М.П.</p>
		</td>		
	</tr>
</table>
</div>
</body>
</html>