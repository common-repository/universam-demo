<?php 
foreach ($orders as $order):
	$shipped_documents = usam_get_shipping_documents_order( $order->id );	
	$shipped_document = !empty($shipped_documents) ? (array)current($shipped_documents) : [];
	
	$storages = [];
	foreach ($shipped_documents as $document)
	{
		if ( $document->storage ) 
		{ 
			$storage = usam_get_storage( $document->storage ); 
			if ( empty($storage['code']) )
				continue;
			$address = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));
			$phone = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'phone'));				
			$storages[] = ['ИД' => $storage['code'], 'Наименование' => htmlspecialchars($storage['title']), 'address' => htmlspecialchars($address), 'phone' => $phone];
		}
	}
	if ( empty($storages) )
		continue;
	
	$products = usam_get_products_order( $order->id );
	$payment_documents = usam_get_payment_documents_order( $order->id );
	$payment = !empty($payment_documents) ? (array)current($payment_documents) : [];	
	$currency = usam_get_currency_price_by_code($order->type_price);
	$note = usam_get_order_metadata($order->id, 'note');
	$properties_1с = ['Ид' => $order->id, 'Номер' => $order->number, 'Дата' => usam_local_date($order->date_insert, 'Y-m-d'), 'Время' => usam_local_date($order->date_insert, 'H:i:s'), 'ПометкаУдаления' => $order->status=='delete'?'true':'false', 'ХозОперация' => $this->get_document_name('order'), 'Источник' => 'Заказ с сайта', 'Роль' => 'Продавец', 'Валюта' => $currency, 'Курс' => 1, 'Сумма' => $order->totalprice, 'Комментарий' => usam_utf8_for_xml( $note ) ];
	$properties_1с = apply_filters( 'usam_main_properties_xml_order_1c', $properties_1с );	
	?>
<Документ>
	<?php
	foreach ($properties_1с as $type => $value)
	{
		?><<?php echo $type; ?>><?php echo $value; ?></<?php echo $type; ?>><?php 
	}	
	?>
	<Контрагенты>
		<?php 
		$contragents = [];
		$types_location = ['address' => __("Адрес","usam"), 'postcode' => __("Индекс","usam")];	
		foreach (usam_get_types_location() as $type)
		{
			$types_location[$type->code] = $type->name;
		}		
		if ( usam_is_type_payer_company( $order->type_payer ) )				
		{				
			$properties_1с = ['inn' => 'ИНН', 'ppc' => 'КПП', 'okpo' => 'ОКПО', 'name' => 'Наименование', 'full_company_name' => 'ОфициальноеНаименование'];
			$contact_properties_1с = ['email' => 'Электронная почта', 'phone' => 'Телефон'];				
			$contragents['billing']['name'] = usam_get_order_metadata( $order->id, 'company' );
			$contragents['billing']['contact']['email'] = usam_get_order_metadata( $order->id, 'company_email' );
			$contragents['billing']['contact']['phone'] = usam_get_order_metadata( $order->id, 'company_phone' );
						
			$properties_adress_1с = ['postcode' => 'company_shippingpostcode', 'location' => 'company_shippinglocation', 'address' => 'company_shippingaddress'];	
			$contragents['shipping']['address'] = usam_get_crm_address( $order->id, $properties_adress_1с );
			$contragents['billing']['contact_person'] = usam_get_order_metadata( $order->id, 'contact_person' );		
			$contragents['billing']['inn'] = usam_get_order_metadata( $order->id, 'inn' );
			$contragents['billing']['ppc'] = usam_get_order_metadata( $order->id, 'ppc' );	
			$code = '';
			$bank_accounts = [];
			if ( $order->company_id )
			{										
				$bank_accounts = usam_get_company_bank_accounts( $order->company_id );
				$code = usam_get_company_metadata( $order->company_id, 'code');		
				$contragents['billing']['okpo'] = usam_get_company_metadata( $order->company_id, 'okpo');	
				$contragents['billing']['full_company_name'] = usam_get_company_metadata( $order->company_id, 'full_company_name');
				
				$properties_adress_1с = ['postcode' => 'contactpostcode', 'location' => 'contactlocation', 'address' => 'legaladdress'];	
				$contragents['billing']['legaladdress'] = usam_get_crm_address( $order->company_id, $properties_adress_1с, 'company' );	
			}						
			$contragents['shipping'] = $contragents['billing'];							
			foreach ($contragents as $type => $contragent):
			?>
			<Контрагент>
				<Ид><?php echo $code?$code:"usam-company-".$order->company_id; ?></Ид>
				<ПометкаУдаления>false</ПометкаУдаления>				
				<Роль><?php echo $type == 'billing' ? "Покупатель" : "Получатель" ?></Роль>
				<?php
				foreach ($contragent as $property_type => $value)
				{
					if ( !empty($properties_1с[$property_type]) && $value )
					{
						?><<?php echo $properties_1с[$property_type]; ?>><?php echo htmlspecialchars($value); ?></<?php echo $properties_1с[$property_type]; ?>><?php 
					}
				}				
				if ( !empty($bank_accounts) )
				{
					?><РасчетныеСчета><?php
					foreach ( $bank_accounts as $account)
					{
					?>
						<РасчетныйСчет>
							<НомерСчета><?php echo $account->number; ?></НомерСчета>
							<Банк>
								<Наименование><?php echo htmlspecialchars($account->name); ?></Наименование>
								<СчетКорреспондентский><?php echo $account->bank_ca; ?></СчетКорреспондентский>
								<БИК><?php echo $account->bic; ?></БИК>
							</Банк>
							<БанкКорреспондент/>
						</РасчетныйСчет>					
					<?php
					}
					?></РасчетныеСчета><?php
				}
				if ( !empty($contragent['contact_person']) )
				{
					?>
					<Представители>
						<Представитель>
							<Отношение>Контактное лицо</Отношение>
							<Ид>usam-order-contact-<?php echo $order->id; ?></Ид>
							<Наименование><?php echo htmlspecialchars($contragent['contact_person']) ?></Наименование>
						</Представитель>
					</Представители>					
					<?php 
				}
				if (!empty($contragent['legaladdress']) ): ?>
				  <АдресРегистрации>  
					<Представление><?php echo implode(', ',$contragent['legaladdress']); ?></Представление>
					<?php 
					foreach ($contragent['legaladdress'] as $address_item_name => $address_item_value): 
						if (!empty($address_item_value) ){
						?>
						<АдресноеПоле>								
							<Тип><?php echo $types_location[$address_item_name]; ?></Тип>
							<Значение><?php echo htmlspecialchars($address_item_value); ?></Значение>
						  </АдресноеПоле>
						<?php } ?>
					<?php endforeach ?>
				  </АдресРегистрации>
				<?php endif ?>
				<?php if (!empty($contragent['address']) ): ?>
				  <Адрес>             
					<Представление><?php echo implode(', ',$contragent['address']); ?></Представление>
					<?php foreach ($contragent['address'] as $address_item_name => $address_item_value): 
					  if (!empty($address_item_value) ){ ?>
					  <АдресноеПоле>
						<Тип><?php echo $types_location[$address_item_name]; ?></Тип>
						<Значение><?php echo htmlspecialchars($address_item_value); ?></Значение>
					  </АдресноеПоле>
					  <?php } ?>
					<?php endforeach ?>
				  </Адрес>
				<?php endif ?>			
				<Контакты>
					<?php 
					foreach ($contragent['contact'] as $name => $value): 
						$value = $name == 'phone' ? usam_get_phone_format($value):$value;
					?>
					<Контакт>
						<Тип><?php echo htmlspecialchars($contact_properties_1с[$name]); ?></Тип>
						<Значение><?php echo htmlspecialchars($value); ?></Значение>
					</Контакт>
					<?php endforeach ?>					
				</Контакты>	
			  </Контрагент>
			<?php endforeach; 	
		}
		else
		{ 
			$properties_1с = array('firstname' => 'Имя', 'lastname' => 'Фамилия', 'name' => 'Наименование');
			$contact_properties_1с = array('email' => 'Электронная почта', 'phone' => 'Телефон');
			$firstname = usam_get_order_metadata( $order->id, 'billingfirstname' );
			$lastname = usam_get_order_metadata( $order->id, 'billinglastname' );						
			if ( $firstname || $lastname )
			{	
				$contragents['billing']['firstname'] = $firstname;
				$contragents['billing']['lastname'] = $lastname;			
				$contragents['billing']['name'] = trim($contragents['billing']['firstname'].' '.$contragents['billing']['lastname']);					
				if (  usam_get_order_metadata( $order->id, 'billingaddress' ) )
				{						
					$properties_adress_1с = array('postcode' => 'billingpostcode', 'location' => 'billinglocation', 'address' => 'billingaddress');	
					$contragents['billing']['address'] = usam_get_crm_address( $order->id, $properties_adress_1с );
				}
				else
				{
					$properties_adress_1с = array('postcode' => 'shippingpostcode', 'location' => 'shippinglocation', 'address' => 'shippingaddress');	
					$contragents['billing']['address'] = usam_get_crm_address( $order->id, $properties_adress_1с );
				}
				$contragents['billing']['contact']['email'] = usam_get_order_metadata( $order->id, 'billingemail' );
				$contragents['billing']['contact']['phone'] = usam_get_order_metadata( $order->id, 'billingmobilephone' );
				if ( !$contragents['billing']['contact']['phone'] )
					$contragents['billing']['contact']['phone'] = usam_get_order_metadata( $order->id, 'billingphone' );
				
			}
			$firstname = usam_get_order_metadata( $order->id, 'shippingfirstname' );
			$lastname = usam_get_order_metadata( $order->id, 'shippinglastname' );	
			if ( $firstname || $firstname )
			{										
				$contragents['shipping']['firstname'] = $firstname;
				$contragents['shipping']['lastname'] = $lastname;						
				$properties_adress_1с = array('postcode' => 'shippingpostcode', 'location' => 'shippinglocation', 'address' => 'shippingaddress');	
				$contragents['shipping']['address'] = usam_get_crm_address( $order->id, $properties_adress_1с );													
				$contragents['shipping']['contact']['email'] = usam_get_order_metadata( $order->id, 'shippingemail' );
				$contragents['shipping']['contact']['phone'] = usam_get_order_metadata( $order->id, 'shippingmobilephone' );
				if ( !$contragents['shipping']['contact']['phone'] )
					$contragents['shipping']['contact']['phone'] = usam_get_order_metadata( $order->id, 'shippingphone' );
			}	
			$code = usam_get_contact_metadata( $order->contact_id, 'code');	
			foreach ($contragents as $type => $contragent):
			?>
			<Контрагент>
				<Ид><?php echo !empty($code)?$code:"usam-contact-".$order->contact_id; ?></Ид>
				<ПометкаУдаления>false</ПометкаУдаления>
				<Роль><?php echo $type == 'billing' ? "Покупатель" : "Получатель" ?></Роль>					
				<?php
				foreach ($contragent as $property_type => $value)
				{
					if ( !empty($properties_1с[$property_type]) && $value )
					{
						?><<?php echo $properties_1с[$property_type]; ?>><?php echo usam_utf8_for_xml($value); ?></<?php echo $properties_1с[$property_type]; ?>><?php 
					}
				}
				?>
				<?php if (!empty($contragent['address']) ): ?>
				  <АдресРегистрации>             
					<Представление><?php echo htmlspecialchars(implode(', ',$contragent['address'])); ?></Представление>
					<?php foreach ($contragent['address'] as $address_item_name => $address_item_value): 
					  if (!empty($address_item_value) ){ ?>
					  <АдресноеПоле>
						<Тип><?php echo usam_utf8_for_xml($types_location[$address_item_name]); ?></Тип>
						<Значение><?php echo usam_utf8_for_xml($address_item_value); ?></Значение>
					  </АдресноеПоле>
					  <?php } ?>
					<?php endforeach ?>
				  </АдресРегистрации>
				<?php endif ?>		
				<Контакты>
					<?php 
					foreach ($contragent['contact'] as $name => $value): 
					$value = $name == 'phone' ? usam_get_phone_format($value):$value;
					?>
					<Контакт>
						<Тип><?php echo usam_utf8_for_xml($contact_properties_1с[$name]); ?></Тип>
						<Значение><?php echo usam_utf8_for_xml($value); ?></Значение>
					</Контакт>
					<?php endforeach ?>					
				</Контакты>				
			</Контрагент>
			<?php endforeach; 			
		}				
		?>	
	</Контрагенты>			
	<?php if ( !empty($shipped_documents) ) { ?>
	<Склады>
		<?php foreach ($shipped_documents as $document): ?>
			<?php 						
			if ( $document->storage ) { ?>
				<?php $storage = usam_get_storage( $document->storage ); ?>
				<Склад>						
					<Ид><?php echo $storage['code'] ?></Ид>
					<Наименование><?php echo htmlspecialchars($storage['title']); ?></Наименование>
				</Склад>
			<?php } ?>
		<?php endforeach; ?>
	</Склады>    
	<?php } ?>	
	<Налоги>
		<?php
		$taxes = usam_get_order_taxes( $order->id );
		if( !empty($taxes) )
		{
			foreach ( $taxes as $tax ) 
			{
				?>
				<Налог>
					<Наименование><?php echo $tax['name']; ?></Наименование>
					<УчтеноВСумме><?php echo $tax['is_in_price']? 'true' : 'false'; ?></УчтеноВСумме>	
					<Сумма><?php echo $tax['tax']; ?></Сумма>						
				</Налог>							
				<?php 
			}
		}	
		?>	
	</Налоги>						
	<ЗначенияРеквизитов>
		<ЗначениеРеквизита>
			<Наименование>Проведен</Наименование>
			<Значение><?php echo usam_check_object_is_completed($order->status, 'order') ? 'true' : 'false' ?></Значение>
		</ЗначениеРеквизита>
		<?php if ( !empty($payment['name']) ): ?>
		<ЗначениеРеквизита>
			<Наименование>Метод оплаты</Наименование>
			<Значение><?php echo htmlspecialchars($payment['name']) ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Номер транзакции</Наименование>
			<Значение><?php echo htmlspecialchars($payment['transactid']); ?></Значение>
		</ЗначениеРеквизита>
		<?php endif ?>			
		<?php if ( !empty($shipped_document) ): ?>
		<ЗначениеРеквизита>
			<Наименование>Метод доставки</Наименование>
			<Значение><?php echo $shipped_document['storage_pickup']?'Самовывоз':'Курьер' ?></Значение>
		 </ЗначениеРеквизита>
		<?php endif ?>	
		<?php if ( !empty($shipped_document['name']) ): ?>
		<ЗначениеРеквизита>
			<Наименование>Способ доставки</Наименование>
			<Значение><?php echo htmlspecialchars($shipped_document['name']); ?></Значение>
		 </ЗначениеРеквизита>
		<?php endif ?>	
		<?php if (!empty($contragent['address']) ): ?>
			<ЗначениеРеквизита>
				<Наименование>Адрес доставки</Наименование>
				<Значение><?php echo htmlspecialchars(implode(', ',$contragent['address'])); ?></Значение>
			</ЗначениеРеквизита>			
		<?php endif ?>		
		<ЗначениеРеквизита>
			<Наименование>Сайт</Наименование>
			<Значение><?php echo home_url(); ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Источник</Наименование>
			<Значение>Заказ с сайта</Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Заказ оплачен</Наименование>
			<Значение><?php echo $order->paid == 2 ? 'true' : 'false'; ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Доставка разрешена</Наименование>
			<Значение><?php echo !empty($shipped_documents[0]) && $shipped_documents[0]->status != 'canceled' ? 'true' : 'false' ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Отменен</Наименование>
			<Значение><?php echo $order->status == 'canceled' ? 'true' : 'false' ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Финальный статус</Наименование>
			<Значение><?php echo usam_check_object_is_completed($order->status, 'order') ? 'true' : 'false' ?></Значение>
		</ЗначениеРеквизита>
		<ЗначениеРеквизита>
			<Наименование>Статуса заказа ИД</Наименование>
			<Значение><?php echo $order->status; ?></Значение>
		</ЗначениеРеквизита>			
		<ЗначениеРеквизита>
			<Наименование>Статус заказа</Наименование>
			<Значение><?php echo htmlspecialchars(usam_get_object_status_name($order->status, 'order')); ?></Значение>
		</ЗначениеРеквизита>					
	</ЗначенияРеквизитов>
	<Товары>
		<?php foreach ($shipped_documents as $document): 
		if ( !$document->storage_pickup ) {
			$document = apply_filters('usam_export_shipped_document_xml', $document, $order );
			?>
			<Товар>					
				<Ид>ORDER_DELIVERY</Ид>        
				<Наименование><?php echo $document->name; ?></Наименование>				
				<ЗначенияРеквизитов>								
					<ЗначениеРеквизита>
						<Наименование>ВидНоменклатуры</Наименование>
						<Значение>Услуга</Значение>
					</ЗначениеРеквизита>
					<ЗначениеРеквизита>
						<Наименование>ТипНоменклатуры</Наименование>
						<Значение>Услуга</Значение>
					</ЗначениеРеквизита>
					<ЗначениеРеквизита>
						<Наименование>СлужбаДоставки</Наименование>
						<Значение><?php echo htmlspecialchars($document->name); ?></Значение>
					</ЗначениеРеквизита>
				</ЗначенияРеквизитов>
				<БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>							
				<Коэффициент>1</Коэффициент>
				<Количество>1</Количество>
				<Цена><?php echo $document->price; ?></Цена>
				<Сумма><?php echo $document->price; ?></Сумма>										
				<?php 
				if ( !empty($document->tax_value) )
				{ 
					?>
					<СтавкиНалогов>
						<СтавкаНалога>
							<Наименование>ндс</Наименование>							
							<Ставка><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_rate' ); ?></Ставка>													
						</СтавкаНалога>
					</СтавкиНалогов>
					<Налоги>
						<Налог>
							<Наименование>ндс</Наименование>
							<УчтеноВСумме><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_is_in_price' )?'true':'false'; ?></УчтеноВСумме>
							<Ставка><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_rate' ); ?></Ставка>
							<Сумма><?php echo $document->tax_value; ?></Сумма>		
						</Налог>			
					</Налоги>						
				<?php } ?>	
		  </Товар>
		<?php 
		}
		endforeach; ?>
		<?php 
		foreach ($products as $product): 
			$product_taxes = usam_get_order_product_taxes( $order->id );
			?>
			<Товар>
				<?php $product = apply_filters_ref_array('usam_1c_product_order_xml', [$product, $order, &$product_taxes] ); ?>
				<Ид><?php echo usam_get_product_meta( $product->product_id, 'code' ); ?></Ид>        
				<Наименование><?php echo htmlspecialchars($product->name); ?></Наименование>				
				<ЗначенияРеквизитов>
					<ЗначениеРеквизита>
						<Наименование>ВидНоменклатуры</Наименование>
						<Значение><?php echo usam_check_product_type_sold( 'service', $product->product_id )?'Услуга':'Товар'; ?></Значение>
					</ЗначениеРеквизита>
					<ЗначениеРеквизита>
						<Наименование>ТипНоменклатуры</Наименование>
						<Значение><?php echo usam_check_product_type_sold( 'service', $product->product_id )?'Услуга':'Товар'; ?></Значение>
					</ЗначениеРеквизита>
				</ЗначенияРеквизитов>
				<?php $unit = usam_get_unit_measure( $product->unit_measure ); ?>
				<БазоваяЕдиница Код="<?php echo $unit['external_code']; ?>" НаименованиеПолное="<?php echo $unit['title']; ?>" МеждународноеСокращение="<?php echo strtoupper($unit['international_code']); ?>"><?php echo $unit['short']; ?></БазоваяЕдиница>
				<Коэффициент>1</Коэффициент>
				<Единица><?php echo $unit['short']; ?></Единица>
				<Количество><?php echo $product->quantity; ?></Количество>
				<Цена><?php echo $product->price; ?></Цена>
				<Сумма><?php echo $product->price * $product->quantity; ?></Сумма>										
				<?php 
				if( !empty($product_taxes) )
				{ 
					?>
					<СтавкиНалогов>
						<?php 						
						foreach( $product_taxes as $product_tax )
						{ 
							if ( $product_tax->product_id == $product->product_id && $product->unit_measure == $product_tax->unit_measure )
							{
								?>
								<СтавкаНалога>
									<Наименование>ндс</Наименование>									
									<Ставка><?php echo $product_tax->rate; ?></Ставка>
								</СтавкаНалога>
								<?php 		
							}
						}
						?>
					</СтавкиНалогов>	
					<Налоги>
						<?php 	
						foreach( $product_taxes as $product_tax )
						{ 
							if ( $product_tax->product_id == $product->product_id && $product->unit_measure == $product_tax->unit_measure )
							{
								?>
								<Налог>
									<Наименование>ндс</Наименование>
									<УчтеноВСумме><?php echo $product_tax->is_in_price?'true':'false'; ?></УчтеноВСумме>
									<Сумма><?php echo $product_tax->tax; ?></Сумма>
									<Ставка><?php echo $product_tax->rate; ?></Ставка>										
								</Налог>
								<?php 		
							}
						}
						?>	
					</Налоги>
				<?php } ?>
		  </Товар>
		<?php endforeach ?>
	  </Товары>
</Документ> 
<?php endforeach ?>