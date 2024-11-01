<div id="bulk_actions_system_product_attribute" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Изменить системные свойства у товаров','usam'); ?></div>
	</div>
	<?php 
	global $wp_post_statuses;
	$statuses = get_post_stati( 0, 'names' );
	$prices = usam_get_prices(['type' => 'all']);	
	$out = "
	<div class='modal-body'>
		<div class='product_bulk_actions'>
			<div class='colum1'>
				<div class='title'>".__('Свойства товара','usam')."</div>
				<div class='product_attributes modal-scroll'>
					<div class='system_characteristics edit_form'>
						<div class='edit_form__item'>			
							<div class='edit_form__item_name'><label for='virtual_product_attribute'>".__('Тип товара','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='virtual_product_attribute'>					
									<option value=''>".__('Выберите', 'usam')."</option>";
									foreach ( usam_get_types_products_sold() as $key => $type )
									{
										$out .= "<option value='$key'>".$type['single']."</option>";
									}
								$out .= "</select>	
							</div>	
						</div>	
						<div class='edit_form__item'>			
							<div class='edit_form__item_name'><label for='virtual_product_attribute'>".__('Дата товара','usam').":</label></div>	
							<div class='edit_form__item_option'>
								".usam_get_display_datetime_picker( 'insert', '', array( 'date' ) )."
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='not_limited_attribute'>".__('Запас не ограничен','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='not_limited_attribute'>
									<option value=''>".__('Выберите', 'usam')."</option>
									<option value='1'>".__('Да', 'usam')."</option>
								</select>	
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='under_order_attribute'>".__('Под заказ','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='under_order_attribute'>
									<option value=''>".__('Выберите', 'usam')."</option>
									<option value='0'>".__('Нет', 'usam')."</option>
									<option value='1'>".__('Да', 'usam')."</option>
								</select>	
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='sticky_product'>".__('Избранное','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='sticky_product'>
									<option value=''>".__('Выберите', 'usam')."</option>
									<option value='1'>".__('Да', 'usam')."</option>
									<option value='0'>".__('Нет', 'usam')."</option>
								</select>	
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='unit_attribute'>".__('Коэффициент измерения','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='8' type='text' id='unit_attribute' value=''>
							</div>	
						</div>
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='unit_attribute'>".__('Единица измерения','usam').":</label></div>	
							<div class='edit_form__item_option'>											
								<select id='unit_measure_attribute'>										
									<option value=''>".__('Выберите', 'usam')."</option>";
									foreach ( usam_get_list_units() as $unit )
										$out .= "<option value='".$unit['code']."'>".$unit['title']."</option>";								
								$out .= "</select>	
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='status_product'>".__('Статус товара','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='status_product'>										
									<option value=''>".__('Выберите', 'usam')."</option>";									
									foreach ( $statuses as $status )
										$out .= "<option value='$status'>".$wp_post_statuses[$status]->label."</option>";								
								$out .= "</select>		
							</div>	
						</div>";
						$companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor', 'orderby' => 'name']);
						$out .= "<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='contractor'>".__('Поставщик товара','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='contractor'>										
									<option value=''>".__('Выберите', 'usam')."</option>";									
									foreach ( $companies as $company )
										$out .= "<option value='$company->id'>".$company->name."</option>";								
								$out .= "</select>		
							</div>	
						</div>					
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_views'>".__('Количество просмотров','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='10' type='text' id='product_views' value=''>
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_weight'>".__('Вес','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='10' type='text' id='product_weight' value=''>
							</div>	
						</div>			
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_length'>".__('Длина','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='10' type='text' id='product_length' value=''>
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_width'>".__('Ширина','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='10' type='text' id='product_width' value=''>
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_height'>".__('Высота','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='10' type='text' id='product_height' value=''>
							</div>	
						</div>	
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='bonus_code_price'>".__('Бонусы для цены','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='bonus_code_price'>										
									<option value=''>".__('Выберите', 'usam')."</option>";									
									foreach ( $prices as $price )
										$out .= "<option value='".$price['code']."'>".$price['title']."</option>";								
								$out .= "</select>		
							</div>	
						</div>						
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='bonus_value'>".__('Количество бонусов','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<input size='8' type='text' id='bonus_value' value=''>
							</div>	
						</div>						
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='bonus_type'>".__('Тип расчета бонусов','usam').":</label></div>	
							<div class='edit_form__item_option'>
								<select id='bonus_type'>										
									<option value=''>".__('Выберите', 'usam')."</option>	
									<option value='p'>".__('В процентах', 'usam')."</option>
									<option value='f'>".__('Фиксированные', 'usam')."</option>							
								</select>	
							</div>	
						</div>							
					</div>
				</div>	
			</div>
			<div class='colum2'><div class='title'><strong>".__('Товары','usam')."</strong></div><div class='products modal-scroll'>".__('У товаров с учетом выбранных фильтров','usam')."</div></div>
		</div>
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'>".__('Сохранить', 'usam')."</button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'>".__('Закрыть', 'usam')."</button>
		</div>
	</div>";
	echo $out;	
?>
</div>	