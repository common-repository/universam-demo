<?php
/*
Printing Forms:Доверенность
type:crm
object_type:document
object_name:proxy
Description: Используется в документах CRM
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
$columns = array( 'n' => "№", 'name' => __('Наименование','usam'), 'unit_measure' => __('Eд.','usam'), 'quantity' => __('Количество','usam') );
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
		.header_table td{ border: 0.2pt solid #efefef;}
	</style>
	<?php $this->style(); ?>	
	<title><?php echo esc_html__('Доверенность', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>	
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding: 15pt; width: 555pt; background: #ffffff">	
	<table class="header_table" style="border-collapse:collapse;">
		<tr>
			<td style="width:10%"><?php esc_html_e( 'Номер доверенности', 'usam'); ?></td>
			<td style="width:10%"><?php esc_html_e( 'Дата выдачи', 'usam'); ?></td>
			<td style="width:10%"><?php esc_html_e( 'Срок действия', 'usam'); ?></td>
			<td style="width:40%"><?php esc_html_e( 'Должность и фамилия лица, которому выдана доверенность', 'usam'); ?></td>
			<td style="width:30%"><?php esc_html_e( 'Расписка в получении доверенности', 'usam'); ?></td>
		</tr>
		<tr>
			<td style="width:10%">1</td>
			<td style="width:10%">2</td>
			<td style="width:10%">3</td>
			<td style="width:40%">4</td>
			<td style="width:30%">5</td>
		</tr>
		<tr>
			<td style="width:10%">%id%</td>
			<td style="width:10%">%date%</td>
			<td style="width:10%">%date%</td>
			<td style="width:40%">%date%</td>
			<td style="width:30%"></td>
		</tr>
		<tr>
			<td colspan='3'><?php esc_html_e( 'Поставщик', 'usam'); ?></td>
			<td><?php esc_html_e( 'Номер и дата наряда или извещения', 'usam'); ?></td>
			<td><?php esc_html_e( 'Номер, дата документа, подтверждающего выполнение поручения', 'usam'); ?></td>
		</tr>
		<tr>
			<td colspan='3'>6</td>
			<td>7</td>
			<td>8</td>
		</tr>
		<tr>
			<td colspan='3'></td>
			<td></td>
			<td></td>
		</tr>
	</table>
	<hr style="margin:20px 0"></hr>
	<p style="text-align:right;"><?php esc_html_e( 'Типовая межотраслевая форма № М-2', 'usam'); ?></p>
	<p style="text-align:right;"><?php esc_html_e( 'Утвержденная постановлением Госкомстата России от 30.10.97 №71а', 'usam'); ?></p>
	<table style="border-collapse:collapse;">
		<tr>
			<th style="text-align:right;"><?php esc_html_e( 'Коды', 'usam'); ?></th>
		</tr>
		<tr>
			<th style="text-align:right;"><?php esc_html_e( 'Форма по ОКУД', 'usam'); ?></th>
			<td style="width:100px; text-align:center;border: 0.2pt solid #efefef;">0315001</td>
		</tr>
		<tr>
			<th style="text-align:right;"><?php esc_html_e( 'по ОКПО', 'usam'); ?></th>
			<td style="width:100px;border: 0.2pt solid #efefef;"></td>
		</tr>
	</table>	
	<table style="border-collapse:collapse;">
		<tr>
			<th style="width:100px;"><?php esc_html_e( 'Организация', 'usam'); ?></th>
			<th style="border-bottom: 0.2pt solid #000000;"></th>
		</tr>		
	</table>		
	<h1 style="text-align: center;">%%document_name input "<?php esc_html_e( 'Доверенность', 'usam'); ?> №%id% <?php esc_html_e( 'от', 'usam'); ?> %date%" "Название документа"%%</h1>	
	<p style="margin:5px 0;"><?php esc_html_e( 'Дата выдачи', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'Доверенность действительна', 'usam'); ?></p>
	<p style="margin-top:5px;border-bottom: 0.2pt solid #000000;"> </p>
	<p style="margin-bottom:5px;font-size:10px; text-align:center;"><?php esc_html_e( 'Наименование потребителя и его адрес', 'usam'); ?></p>
	<p style="margin-top:5px;border-bottom: 0.2pt solid #000000;"> </p>
	<p style="margin-bottom:5px;font-size:10px; text-align:center;"><?php esc_html_e( 'Наименование плательщика и его адрес', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'Счет', 'usam'); ?></p>
	<br><br>
	<p style="margin:5px 0;"><?php esc_html_e( 'Доверенность выдана:', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'Паспорт:', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'Кем выдан:', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'Дата выдачи:', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'На поручение от', 'usam'); ?></p>
	<p style="margin:5px 0;"><?php esc_html_e( 'материальных ценностей по', 'usam'); ?></p>
	
	<h2 style="text-align: left;">Перечень товарно-материальных ценностей, подлежащих получению</h2>		
	<?php 
	$this->load_table( $columns ); 
	$cols = count( $this->options['table'] ) - 1;			
	?>
	<table id="products-table" style="border-collapse:collapse;">
		<thead><?php $this->display_table_thead( ); ?></thead>
		<tbody><?php $this->display_table_tbody( ); ?></tbody>		
	</table>
	<br>	
	<table style='table-layout:fixed'>
		<tr>
			<td><strong>Подпись лица, получившего доверенность</strong></td>
			<td>______________________________________</td>
			<td><?php esc_html_e( 'удостоверяем', 'usam'); ?></td>
		</tr>
		<tr>
			<td><strong>Руководитель предприятия</strong></td>
			<td>______________________________________</td>
			<td>%recipient_gm%</td>
		</tr>
		<tr>
			<td>М.П.</td>			
		</tr>
		<tr>
			<td><strong>Главный бухгалтер</strong></td>
			<td>______________________________________</td>
			<td>%recipient_gm%</td>
		</tr>
	</table>
</body>
</html>		