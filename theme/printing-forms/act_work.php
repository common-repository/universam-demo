<?php
/*
Printing Forms:Акт
type:crm
object_type:document
object_name:act
Description: Используется в документах CRM
Shortcode: Вы можете использовать %document_name% %recipient_company_name% %recipient_inn% %recipient_ppc% %recipient_legalpostcode% %recipient_legalcity% %recipient_legaladdress% %recipient_bank_number% %recipient_bank_name%  %recipient_bank_bic% %recipient_bank_address% %recipient_bank_ca%
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
$columns = array( 'n' => "№", 'name' => __('Работы, услуги','usam'), 'discount_price' => __('Цена','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'total' => __('Всего','usam') );
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
	</style>
	<?php $this->style(); ?>	
	<title><?php echo esc_html__('Акт', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>	
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding: 15pt; width: 555pt; background: #ffffff">	
	<h1 style="text-align: center;">%%document_name input "<?php esc_html_e( 'Акт', 'usam'); ?> №%id% <?php esc_html_e( 'от', 'usam'); ?> %date%" "Название документа"%%</h1>	
	
	<h2 style="text-align: left;">%%name_company input "Исполнитель" "Напишите название контрагента"%%</h2>				
	<p>%%company_details input "<?php echo '%recipient_company_name% '.__('ИНН', 'usam').' %recipient_inn% '.__('КПП', 'usam').' %recipient_ppc% %recipient_legalpostcode% %recipient_legalcity% %recipient_legaladdress%'; ?>" "<?php _e( 'Укажите реквизиты вашей компании', 'usam') ?>"%%</p>	
	<h2 style="text-align: left;">%%name_counterparty input "Заказчик" "Напишите название контрагента"%%</h2>				
	<p>%%counterparty input "[if code_type_payer=company {%customer_name% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_legalpostcode%, %customer_legalcity%, %customer_legaladdress%} {%customer_name% %customer_address%}]" "укажите реквизиты"%%</p>
			
	
	<?php 
	$this->load_table( $columns ); 
	$cols = count( $this->options['table'] ) - 1;			
	?>
	<table id="products-table" style="border-collapse:collapse;">
		<thead><?php $this->display_table_thead( ); ?></thead>
		<tbody><?php $this->display_table_tbody( ); ?></tbody>
		<tfoot>
			<?php 			
			$taxes = usam_get_document_taxes( $this->id ); 
			if( !empty($taxes) )
			{ 
				foreach ( $taxes as $tax ) 
				{
				?>
				<tr>
					<td colspan='<?php echo $cols-3; ?>'></td>
					<th colspan='3' style="text-align:right;"><?php echo $tax['name']; ?>:</th>
					<td><?php echo $tax['tax'] == 0?__('без НДС', 'usam'):usam_currency_display( $tax['tax'] ); ?></td>
				</tr>
				<?php 
				}
			}	
			?>		
			<tr class="subtotal">
				<th colspan='<?php echo $cols; ?>'><?php esc_html_e( 'Итого', 'usam'); ?>:</td>
				<td>%subtotal%</td>
			</tr>					
		</tfoot>
	</table>
	<br>
	<?php _e( 'Всего наименований %number_products%, на сумму %total_price_currency%', 'usam'); ?><br>
	<p class="total_price_word"><b>%total_price_word%</b></p>
	<p style="margin-top:10px;">%%description textarea "Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг не имеет." "Условия"%%</p>
	<div class ="invoice-footer" style="margin-top:20px">%%signatures textarea "<table style='table-layout:fixed'><tr><td><strong>Исполнитель</strong></td><td><strong>Заказчик</strong></td></tr><tr><td>%recipient_company_name%</td><td>%customer_name%</td></tr><tr><td></td><td></td></tr><tr><td>______________________________________</td><td>______________________________________</td></tr><tr><td>%recipient_gm%</td><td>%customer_gm%</td></tr></table>" "Подписи"%%</div>
</body>
</html>		