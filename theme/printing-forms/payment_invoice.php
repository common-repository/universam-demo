<?php
/*
Printing Forms: Счет на оплату
type: payment
object_type: document
object_name: order
Description: Бланк счета из заказа
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php	
$columns = array( 'n' => "№", 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'price' => __('Цена','usam'), 'discount_price' => __('Цена со скидкой','usam'), 'tax' => __('Налог','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'total' => __('Всего','usam') );
if ( $this->edit )
	$print = '';
else
	$print = 'onload="window.print()"';
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo esc_html__('Счет на оплату', 'usam'); ?> № %order_id% <?php esc_html_e( 'от', 'usam'); ?> %order_date%</title>
	<style type="text/css">					
		#products-table td.item_number{width:100px}
		#products-table tbody .column-total p{white-space: nowrap;}	
		#products-table tbody .column-barcode p{white-space: nowrap;}
		#products-table tbody .column-price p{white-space: nowrap;}
		#products-table .product_name{text-align:left;}
		#content-table{width:100%; margin-top:0;}
		#invoice-content{vertical-align:top;}
		#invoice-footer{color:#444; padding-top:10px;}		
		#invoice-footer{color:#444; padding-top:10px;}		
		
		table { border-collapse: collapse; }
		table.acc{margin:20px 10px 20px 0}
		table.acc td { border: 1pt solid #000000; padding: 0pt 3pt; line-height: 16px; }
		table.it td { border: 1pt solid #000000; padding: 0pt 3pt; }
		table.sign td { font-weight: bold; vertical-align: top; }
		table.header td { padding: 0pt; vertical-align: top; }
		.total_price_word:first-letter {text-transform: uppercase;}	
		.qr{margin-left:10px}		
	</style>
	<?php $this->style(); ?>	
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding:10pt; width: 555pt; background: #ffffff">
	<?php
	if ( !empty($this->products) || $this->edit )
	{		 
	?>	
	<p>%%header_info input "" "Напишите любую информацию"%%</p>
	<table class="acc_qr" width="100%">
		<tr>
			<td style="padding:0">
				<table class="acc" width="100%">				
					<tr>
						<td>ИНН %recipient_inn%</td>
						<td>КПП %recipient_ppc%</td>
						<td  width="40px" rowspan="2">
							<br>
							<br>
							Сч. №</td>
						<td width="110px" rowspan="2">
							<br>
							<br>%recipient_bank_number%</td>
					</tr>
					<tr>
						<td colspan="2"><?php _e( 'Получатель', 'usam'); ?><br>%recipient_full_company_name%</td>
					</tr>
					<tr>
						<td colspan="2"><?php _e( 'Банк получателя', 'usam'); ?><br>%recipient_bank_name%</td>
						<td width="40px">БИК<br>
							Сч. №<br>
						</td>
						<td width="110px">%recipient_bank_bic%
							<br>%recipient_bank_ca%
						</td>
					</tr>
				</table>	
			</td>		
			<td style="width:85px;padding:0"><img class="qr" src="%qr%"></td>
		</tr>
	</table>		
	<h1 style="text-align:center; white-space:normal;">СЧЕТ № %document_number% от %order_date%</h1>		
	<h2 style="text-align: left; margin: 0.4em 0;">%%name_company input "Поставщик" "Напишите название контрагента"%%</h2>		
	<p>%%company_details input "%recipient_company_name% <?php _e( 'ИНН', 'usam') ?> %recipient_inn% <?php _e( 'КПП', 'usam') ?> %recipient_ppc%, <?php _e( 'Адрес', 'usam') ?>: %recipient_contactcity% %recipient_contactaddress%, %recipient_legaloffice%"%%</p>		
	<h2 style="text-align: left; margin: 0.4em 0;">%%name_counterparty input "Заказчик" "Напишите название контрагента"%%</h2>		
	<p>%%counterparty input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "Укажите реквизиты покупателя"%%</p>			
	<br>
	<?php $this->display_table( $columns );	?>
	<br>
	<?php _e( 'Всего наименований %number_products%, на сумму %total_price_currency%', 'usam'); ?><br>
	<p class="total_price_word"><b>%total_price_word%</b></p>
	<br>
	<br>
	<div class ="invoice-footer" style="margin-top:10px">%%conditions textarea "" "Условия"%%</div>		
	<?php
	}
	if ( !empty($this->license_products) || $this->edit )
	{
		if ( !empty($this->products) )
		{
			?><p class="more" style="page-break-after: always;"></p><?php	
		}
		$this->products = $this->license_products;
		$this->taxes = $this->taxes_license_products;
		?>
	<table class="acc_qr" width="100%">
		<tr>
			<td style="padding:0">
				<table class="acc" width="100%">				
					<tr>
						<td>ИНН %recipient_inn%</td>
						<td>КПП %recipient_ppc%</td>
						<td  width="40px" rowspan="2">
							<br>
							<br>
							Сч. №</td>
						<td width="110px" rowspan="2">
							<br>
							<br>%recipient_bank_number%</td>
					</tr>
					<tr>
						<td colspan="2"><?php _e( 'Получатель', 'usam'); ?><br>%recipient_full_company_name%</td>
					</tr>
					<tr>
						<td colspan="2"><?php _e( 'Банк получателя', 'usam'); ?><br>%recipient_bank_name%
						</td>
						<td width="40px">БИК<br>
							Сч. №<br>
						</td>
						<td width="110px">%recipient_bank_bic%
							<br>%recipient_bank_ca%
						</td>
					</tr>
					<tr>
						<td colspan="4">					
							<?php _e( 'Назначение платежа', 'usam'); ?><br>
							<p style="line-height:normal"><?php _e( 'Оплата по сч. %document_number% от %order_date% за неисключительные права на использование ПО для ЭВМ по лицензионному договору.', 'usam'); ?></p>
						</td>				
					</tr>
				</table>	
			</td>		
			<td style="width:85px;padding:0"><img class="qr" src="%qr%"></td>
		</tr>
	</table>
	<h1 style="text-align:center; white-space:normal;"><?php _e('Счет-оферта (Лицензионный договор)', 'usam'); ?> № LD%order_id% от %order_date%</h1>		
	<h2 style="text-align: left; margin: 0.4em 0;"><?php _e( 'Лицензиар', 'usam'); ?></h2>		
	<p>%%licensor_company input "%recipient_company_name% <?php _e( 'ИНН', 'usam') ?> %recipient_inn% <?php _e( 'КПП', 'usam') ?> %recipient_ppc%, <?php _e( 'Адрес', 'usam') ?>: %recipient_contactcity% %recipient_contactaddress%, %recipient_legaloffice%"%%</p>
	<h2 style="text-align: left; margin: 0.4em 0;"><?php _e( 'Лицензиат', 'usam'); ?></h2>		
	<p>%%licensee input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address% <?php _e( 'эл.адрес', 'usam') ?> %email%} {%customer_billingfirstname% %customer_billinglastname% %customer_address% <?php _e( 'эл.адрес', 'usam') ?> %email%}]" "Укажите реквизиты покупателя"%%</p>	
	<br>
	<?php $this->display_table( $columns );	?>
	<br>
	<?php _e( 'Всего наименований %number_products%, на сумму %total_price_currency%', 'usam'); ?><br>
	<b>%total_price_word%</b>
	<br>  
	<div class ="invoice-footer" style="text-align:justify; margin-top:10px; font-size:12px;">%%license_agreement textarea "" "<?php _e( 'Лицензионный договор или другие условия', 'usam'); ?>"%%</div>
	<?php
	}		
	if ( $this->edit && empty($this->shipped_documents) ) 
	{
		$document = new stdClass();
		$document->name = __('Оплата за услуги по доставке(тест)','usam');		
		$document->price = 100;
		$document->id = 100;
		$document->courier_company = get_option( 'usam_shop_company' );
		$document->tax_is_in_price = 1;
		$document->tax_id = 0;
		$document->date_insert = date( "Y-m-d H:i:s");		
		$this->shipped_documents[] = $document; 
	}
	if ( !empty($this->shipped_documents) )
	{
		foreach ( $this->shipped_documents as $document )
		{ 
			$company = usam_get_company_by_acc_number( $document->courier_company, 'courier' );	
			$totalprice = $document->tax_is_in_price ? $document->price : $document->price + $document->tax_value;
			$sum = $totalprice*100;
			$qr_str = "ST00012|Name={$company['courier_name']}|PersonalAcc={$company['courier_bank_ca']}|BankName={$company['courier_bank_name']}|BIC={$company['courier_bank_bic']}|CorrespAcc={$company['courier_bank_number']}|PayeeINN={$company['courier_inn']}|KPP={$company['courier_ppc']}|Sum={$sum}|Purpose=Оплата заказа DPS{$document->id}";	
			$qr_str = usam_get_qr( $qr_str );		
			?>
			<p class="more" style="page-break-after: always;"></p>
			<table class="acc_qr" width="100%">
			<tr>
				<td style="padding:0">
					<table class="acc" width="100%">				
						<tr>
							<td>ИНН <?php echo $company['courier_inn']; ?></td>
							<td>КПП <?php echo $company['courier_ppc']; ?></td>
							<td  width="40px" rowspan="2">
								<br>
								<br>
								Сч. №</td>
							<td width="110px" rowspan="2">
								<br>
								<br><?php echo $company['courier_bank_number']; ?></td>
						</tr>
						<tr>
							<td colspan="2"><?php _e( 'Получатель', 'usam'); ?><br><?php echo $company['courier_full_company_name']; ?></td>
						</tr>
						<tr>
							<td colspan="2"><?php _e( 'Банк получателя', 'usam'); ?><br><?php echo $company['courier_bank_name']; ?></td>
							<td width="40px">БИК<br>
								Сч. №<br>
							</td>
							<td width="110px"><?php echo $company['courier_bank_bic']; ?>
								<br><?php echo $company['courier_bank_ca']; ?>
							</td>
						</tr>
					</table>	
				</td>		
				<td style="width:85px;padding:0"><img class="qr" src="<?php echo $qr_str; ?>"></td>
			</tr>
		</table>
		<h1 style="text-align:center; white-space:normal;"><?php _e('Счет', 'usam'); ?> № DPS<?php echo $document->id; ?> от <?php echo usam_local_date($document->date_insert, "d.m.Y") ?></h1>
		<h2 style="text-align: left; margin: 0.4em 0;"><?php _e( 'Исполнитель', 'usam'); ?></h2>
		<p>
		<?php
		if ( $this->edit ) 
		{
			?>%%shipped_company input "%recipient_company_name% <?php _e( 'ИНН', 'usam') ?> %recipient_inn% <?php _e( 'КПП', 'usam') ?> %recipient_ppc%, <?php _e( 'Адрес', 'usam') ?>: %recipient_contactcity% %recipient_contactaddress%, %recipient_legaloffice%"%%<?php
		}
		elseif ( !empty($this->options['data']['shipped_company']) )
		{
			echo str_ireplace('recipient', 'shipped_'.$document->courier_company, $this->options['data']['shipped_company'] );
		}
		?>
		</p>
		<h2 style="text-align: left; margin: 0.4em 0;"><?php _e( 'Плательщик', 'usam'); ?></h2>		
		<p>%%consignee input "[if code_type_payer=company {%customer_company% <?php _e( 'ИНН', 'usam') ?> %customer_inn% <?php _e( 'КПП', 'usam') ?> %customer_ppc% <?php _e( 'Адрес', 'usam') ?>: %customer_address%} {%customer_billingfirstname% %customer_billinglastname% %customer_address%}]" "Укажите реквизиты покупателя"%%</p>	
		<br>
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
					<td class="column-total"><?php echo usam_get_formatted_price( $document->price, $this->price_args ); ?></td></tr>				
			</tbody>
			<tfoot>
				<tr class="subtotal">
					<td></td>
					<td style="text-align: right;"><?php _e( 'Итого', 'usam'); ?>:</td>
					<td><?php echo usam_get_formatted_price( $document->price, $this->price_args ); ?></td>
				</tr>	
				<?php				
				if ( $document->tax_id ) 
				{
					?>
					<tr class="subtotal">
						<td></td>
						<td style="text-align: right;"><?php echo $document->tax_name; // добавляется вручную  ?>:</td>
						<td><?php echo usam_get_formatted_price( $document->tax_value, $this->price_args ); ?></td>
					</tr>	
					<?php					
				}
				?>
				<tr class="subtotal">
					<td></td>
					<td style="text-align: right;"><?php _e( 'Всего к оплате', 'usam'); ?>:</td>
					<td><?php echo usam_get_formatted_price( $totalprice, $this->price_args ); ?></td>
				</tr>						
			</tfoot>
		</table>
		<br>
		<?php printf( __('Всего наименований 1, на сумму %s', 'usam'), usam_get_formatted_price( $document->price, $this->price_args )); ?><br>
		<b><?php echo mb_ucfirst(usam_get_number_word($document->price)); ?></b>
		<br>  
		<div style="text-align:justify; margin-top:20px; font-size:7px;">
			<?php 
			if ( $this->edit ) 
			{
				?>%%shipping_conditions textarea "" "<?php _e( 'Условия доставки', 'usam'); ?>"%%<?php
			}
			elseif ( !empty($this->options['data']['shipped_company']) )
			{
				echo str_ireplace('recipient', 'shipped_'.$document->courier_company, $this->options['data']['shipping_conditions'] );
			}
			?>
		</div>
		<?php
		}
	}
	?>
</body>
</html>		