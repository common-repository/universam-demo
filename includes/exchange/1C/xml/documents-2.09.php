<?php foreach ($documents as $document): ?>
	<Контейнер>
		<Документ>
			<?php
			$currency = usam_get_currency_price_by_code($document->type_price);
			$properties_1с = ['Ид' => $document->id, 'Номер' => $document->number, 'Дата' => usam_local_date($document->date_insert, 'Y-m-d'), 'Время' => usam_local_date($document->date_insert, 'H:i:s'), 'ПометкаУдаления' => $document->status=='delete'?'true':'false', 'ХозОперация' => $this->get_document_name($document->type), 'Источник' => 'Заказ с сайта', 'Роль' => 'Продавец', 'Валюта' => $currency, 'Курс' => 1, 'Сумма' => $document->totalprice];
			?>
			<?php
			foreach ($properties_1с as $type => $value)
			{
				?><<?php echo $type; ?>><?php echo $value; ?></<?php echo $type; ?>><?php 
			}	
			?>			
			<НомерВерсии>AAAAEAAAAAA=</НомерВерсии>
			<Контрагенты>
				<?php if ( $document->customer_type == 'contact' ) 
				{ 
					$contact = usam_get_contact( $document->customer_id );		
				?>			
				<Контрагент>
					<Ид><?php echo $contact['id']; ?></Ид>
					<НомерВерсии>AAAAAQAAAG4=</НомерВерсии>
					<ПометкаУдаления>false</ПометкаУдаления>
					<Наименование><?php echo $contact['appeal']; ?></Наименование>
					<ОфициальноеНаименование><?php echo $contact['appeal']; ?></ОфициальноеНаименование>
					<Роль>Покупатель</Роль>               
					<Адрес>
						<Представление><?php echo usam_get_full_contact_address($contact['id']); ?></Представление>
					</Адрес>
				</Контрагент>
				<?php } else { 
				$company = usam_get_company_metas( $document->customer_id, 'display' );	
				$bank_accounts = usam_get_company_bank_accounts( $document->customer_id );
				?>
				<Контрагент>
					<Ид><?php echo $document->customer_id; ?></Ид>
					<НомерВерсии>AAAAAQAAAG4=</НомерВерсии>
					<ПометкаУдаления>false</ПометкаУдаления>
					<Наименование><?php echo $company['company_name']; ?></Наименование>
					<ОфициальноеНаименование><?php echo $company['full_company_name']; ?></ОфициальноеНаименование>
					<Роль>Покупатель</Роль>
					<ИНН><?php echo $company['inn']; ?></ИНН>
					<КПП><?php echo $company['ppc']; ?></КПП>
					<КодПоОКПО><?php echo $company['okpo']; ?></КодПоОКПО>
					<?php if ( !empty($bank_accounts) ) { ?>
					<РасчетныеСчета>
					   <?php foreach ($bank_accounts as $bank_account): ?>
						   <РасчетныйСчет>
								<НомерСчета><?php echo $bank_account->number; ?></НомерСчета>
								<Банк>
									<Наименование><?php echo $bank_account->name; ?></Наименование>
									<СчетКорреспондентский><?php echo $bank_account->bank_ca; ?></СчетКорреспондентский>
									<БИК><?php echo $bank_account->bic; ?></БИК>
								</Банк>
							</РасчетныйСчет>
						<?php endforeach ?>	
					</РасчетныеСчета>  
					<?php } ?>				
					<Адрес>
						<Представление><?php echo usam_get_company_address($document->customer_id); ?></Представление>
					</Адрес>
				</Контрагент>
				<?php } ?>
			</Контрагенты>      
			<Валюта><?php echo usam_get_currency_price_by_code($document->type_price) ?></Валюта>
			<Курс>1.0000</Курс>
			<Сумма><?php echo $document->totalprice; ?></Сумма>
			<Роль>Продавец</Роль>
			<Комментарий><?php echo usam_utf8_for_xml(htmlspecialchars(usam_get_document_metadata($document->id, 'note'))); ?></Комментарий>
			<Налоги>
				<?php
				$taxes = usam_get_document_taxes( $document->id );
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
					<Значение><?php echo $document->status == 'approved'? 'true' : 'false'; ?></Значение>
				</ЗначениеРеквизита>        
				<ЗначениеРеквизита>
					<Наименование>Статуса заказа ИД</Наименование>
					<Значение><?php echo $document->status; ?></Значение>
				</ЗначениеРеквизита>
			</ЗначенияРеквизитов>
			<Товары>
				<?php foreach ($shipped_documents as $document): ?>
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
							<Налоги>
								<Налог>
									<Наименование><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_name' ); ?></Наименование>
									<УчтеноВСумме><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_is_in_price' )?'true':'false'; ?></УчтеноВСумме>
									<Сумма><?php echo $document->tax_value; ?></Сумма>
									<Ставка><?php echo usam_get_shipped_document_metadata( $document->id, 'tax_rate' ); ?></Ставка>											
								</Налог>
							</Налоги>		
						<?php } ?>					
				  </Товар>
				<?php endforeach; ?>
				<?php 				
				$products = usam_get_products_document( $document->id );			
				foreach ($products as $product): ?>
				<?php $code = usam_get_product_meta( $product->product_id, 'code' );	?>
				<Товар>
					<Ид><?php echo $code; ?></Ид>        
					<Наименование><?php echo $product->name; ?></Наименование>
					<СтавкиНалогов>
						<СтавкаНалога>
							<Наименование>НДС</Наименование>
							<Ставка>0</Ставка>
						</СтавкаНалога>
					</СтавкиНалогов>
				   <ЗначенияРеквизитов>
						<ЗначениеРеквизита>						
							<?php $product_categories = get_the_terms( $product->product_id, 'usam-category' ); ?> 						
							<?php 
							if ( !empty($product_categories) )
							{
								foreach ($product_categories as $category): ?>
									<Наименование>ВидНоменклатуры</Наименование>
									<Значение><?php echo usam_utf8_for_xml(htmlspecialchars($category->name)); ?></Значение>
								<?php endforeach;
							}										
							?>
						</ЗначениеРеквизита>
						<ЗначениеРеквизита>
							<Наименование>ТипНоменклатуры</Наименование>
							<Значение><?php echo usam_check_product_type_sold( 'service', $product->product_id )?'Услуга':'Товар'; ?></Значение>
						</ЗначениеРеквизита>
					</ЗначенияРеквизитов>					 
					<?php $unit = usam_get_unit_measure( $product->unit_measure ); ?>
					<Единица>
						<Ид><?php echo $unit['external_code']; ?></Ид>
						<НаименованиеКраткое><?php echo usam_get_product_unit_name( $product->product_id, 'short'); ?></НаименованиеКраткое>
						<Код><?php echo $product->unit_measure; ?></Код>
						<НаименованиеПолное><?php echo usam_get_product_unit_name( $product->product_id ); ?></НаименованиеПолное>
					</Единица>						
					<Коэффициент>1</Коэффициент>
					<Количество><?php echo $product->quantity; ?></Количество>
					<Цена><?php echo $product->price; ?></Цена>
					<Сумма><?php echo $product->price * $product->quantity; ?></Сумма>				
					<?php 
					$product_taxes = usam_get_document_product_taxes( $document->id ); 
					if ( !empty($product_taxes) )
					{ 
						?>
						<Налоги>
							<?php 						
							foreach ( $product_taxes as $product_tax )
							{ 
								if ( $product_tax->product_id == $product->product_id && $product->unit_measure == $product_tax->unit_measure )
								{
									?>
									<Налог>
										<Наименование><?php echo $product_tax->name; ?></Наименование>
										<УчтеноВСумме><?php echo $product->is_in_price?'true':'false'; ?></УчтеноВСумме>
										<Сумма><?php echo $product->tax; ?></Сумма>
										<Ставка><?php echo $product->rate; ?></Ставка>											
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
	 </Контейнер>
<?php endforeach ?>  