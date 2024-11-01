<?php
class USAM_Basket_Discount_Rules
{		
	private $current_index;		
	
	public function load( ) 
	{			
		add_action( 'admin_footer', array($this, 'admin_footer') );	
	}
	
	public function admin_footer( ) 
	{
		$this->add_condition_cart_window();
		$this->add_condition_cart_item_window( );
	}
	
	private function get_property_title( $property ) 
	{	
		$properties = ['item_name' => __('Название','usam'), 'item_quantity' => __('Количество','usam'), 'item_price' => __('Цена','usam'), 'item_old_price' => __('Старая цена','usam'), 'item_sku' => __('Артикул','usam'), 'item_barcode' => __('Штрих-код','usam'), 'cart_item_count' => __('Количество видов товаров','usam'), 'item_count_total' => __('Общее количество товаров','usam'), 'subtotal' => __('Сумма товаров','usam'), 'shipping' => __('Стоимость доставки','usam'), 'total_tax' => __('Стоимость налогов','usam'),'discount' => __('Общая скидка','usam'), 'coupons_amount' => __('Скидка по купону','usam'), 'bonuses' => __('Общее количество бонусов','usam'), 'roles' => __('Роль посетителя','usam'), 'weekday' => __('День недели','usam'), 'selected_shipping' => __('Способ доставки','usam'), 'selected_gateway' => __('Способ оплаты','usam'), 'category' => __('Категория товара','usam'), 'brands' => __('Бренд товара','usam'), 'category_sale' => __('Акция магазина','usam'), 'user' => __('Пользователь', 'usam'), 'birthday' => __('День рождение', 'usam'), 'customer' => __('Покупал раньше', 'usam'), 'newcustomer' => __('Новый покупатель', 'usam'), 'location' => __('Местоположение', 'usam'), 'type_price' => __('Тип цены','usam'), 'type_payer' => __('Тип плательщика','usam'), 'subtotal_without_discount' => __('Сумма без товаров на скидке','usam')];			

		if ( isset($properties[$property]) )
			return $properties[$property];		
		elseif ( stristr($property, 'order_property') !== false)
		{	
			static $order_properties = null;
			if ( $order_properties == null )
				$order_properties = usam_get_properties( array( 'type' => 'order', 'fields' => 'id=>data' ) );				
			$id = str_replace("order_property-", "", $property);
			if ( !empty($order_properties[$id]) )			
				return $order_properties[$id]->name." [".$order_properties[$id]->code."]";			
		}
		return '';
	}
	
	
	private function get_term_title( $term_id ) 
	{	
	
	
	}
	
	private function get_wrapper_condition( $id, $html, $type ) 
	{
		?>	
		<div id ="row-<?php echo $id; ?>" class = "condition-block condition-<?php echo $type; ?>" data-id ='<?php echo $id; ?>'>	
			<div class = "condition-wrapper">		
				<?php echo $html; ?>												
			</div>
			<a class="button_delete" href="#"></a>
		</div>
		<?php
	}
	
	private function display_logic_condition( $id, $logic_operator ) 
	{
		if ( $logic_operator == 'AND' )
		{
			$class_logic = 'condition_logic_and';
			$title_logic = __('И','usam');
			$value_logic = 'AND';
		}
		else
		{
			$class_logic = 'condition_logic_or';
			$title_logic = __('ИЛИ','usam');
			$value_logic = 'OR';
		}
		$name = empty($this->current_index)?"c[$id]":'c['.implode( '][', $this->current_index )."][$id]";
		?>	
		<div id ="row-<?php echo $id; ?>" class = "condition-block condition-logic <?php echo $class_logic; ?>"><span><?php echo $title_logic; ?></span>			
			<input type="hidden" name="<?php echo $name; ?>[logic_operator]" value="<?php echo $value_logic; ?>"/>
		</div>
		<?php
	}
	
	
	private function display_condition_basket( $id, $c ) 
	{		
		$property_title = $this->get_property_title( $c['property'] );
		$logics_title = usam_get_logic_title( $c['logic'] );
		$method = 'controller_get_condition_'.$c['property'];			
		if ( method_exists( $this, $method ) )
		{
			$data = $this->$method( );
			$value_title = $data[$c['value']];	
		}
		else
		{ 
			$value_title = $c['value'];	
			switch( $c['property'] ) 
			{																	
				case 'category': 
				case 'brands': 
				case 'category_sale': 
				case 'cat_rules_purchase': 																
					$taxonomy = 'usam-'.$c['property'];
					$c['value'] = usam_get_term_id_main_site( $c['value'] );
					$term = get_term( $c['value'], $taxonomy );
					if ( !empty($term) )
						$value_title = $term->name;
					else
						$value_title = '('.sprintf( __('Категория с номером %s удалена','usam'), $c['value'] ).')';
				break;		
				case 'location': 				
					$value_title = usam_get_full_locations_name( $c['value'] );			
				break;
				case 'type_payer': 				
					$value_title = usam_get_name_payer( $c['value'] );			
				break;
				case 'location': 				
					usam_edit_data( $new, $this->id, 'usam_types_payers', false );			
				break;
				case 'type_price': 				
					$value_title = usam_get_name_price_by_code( $c['value'] );			
				break;
				case 'selected_shipping': 				
					$delivery = usam_get_delivery_service( $c['value'] );	
					$value_title = $delivery['name']." (".$delivery['id'].")";				
				break;
				case 'selected_gateway': 				
					$gateway = usam_get_payment_gateway( $c['value'] );	
					$value_title = $gateway['name']." (".$gateway['id'].")";						
				break;
				case 'user': 				
					$contact = usam_get_contact( $c['value'], 'user_id' );	
					if ( !empty($contact) )
						$value_title = $contact['appeal']." (".$c['value'].")";
					else
						$value_title = '('.sprintf( __('Пользователь с номером %s удален','usam'), $c['value'] ).')';
				break;
				case 'roles': 				
					$roles = get_editable_roles();	
					$result['notloggedin'] = __('Не вошел в систему','usam');
					foreach ($roles as $role => $info) 
					{
						$result[$role] = translate_user_role( $info['name'] );
					}		
					if ( isset($result[$c['value']] ) )
						$value_title = $result[$c['value']];					
				break;	
				case 'weekday': 				
					$weekday = array( '1' => __('Понедельник','usam'), '2' => __('Вторник','usam'), '3' => __('Среда','usam'), '4' => __('Четверг','usam'), '5' => __('Пятница','usam'), '6' => __('Суббота','usam'), '0' => __('Воскресение','usam') );
					if ( isset($weekday[$c['value']] ) )
						$value_title = $weekday[$c['value']];					
				break;				
			}
		}		
		ob_start();			
		$name = empty($this->current_index)?"c[$id]":'c['.implode( '][', $this->current_index )."][$id]";
		?>																									
		<input type="hidden" name="<?php echo $name; ?>[type]" value="simple"/>
		<input type="hidden" name="<?php echo $name; ?>[property]" value="<?php echo $c['property']; ?>"/>
		<input type="hidden" name="<?php echo $name; ?>[logic]" value="<?php echo $c['logic']; ?>"/>
		<input type="hidden" name="<?php echo $name; ?>[value]" value="<?php echo $c['value']; ?>"/>
		<div class = "expression-wrapper">				
			<div class = "expression property_expression"><?php echo $property_title; ?></div>
			<div class = "expression logics_expression"><?php echo $logics_title; ?></div>
			<div class = "expression value_expression js-copy-clipboard"><?php echo $value_title; ?></div>				
		</div>													
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$this->get_wrapper_condition( $id, $html, 'basket' );
	}	
	
	private function display_condition_basket_products( $id, $c ) 
	{		
		$name = empty($this->current_index)?"c[$id]":'c['.implode( '][', $this->current_index )."][$id]";
		ob_start();	
		?>		
		<input type="hidden" name="<?php echo $name; ?>[type]" value="products"/>		
		<div class = "title_group"><?php _e('Выбрать товары корзины, которые удовлетворяют условиям', 'usam'); ?><a href="#condition_cart_item_window" class = "add_condition_cart_item" id = "usam_modal" data-toggle="modal" data-type="condition_cart_item_window"><?php _e('Добавить условие','usam') ?></a></div>
		<div class = "conditions">
			<?php 			
			if ( !empty($c) )
			{					
				$this->display_rules( $c, $id );			
			}										
			?>
		</div>	
		<?php		
		$html = ob_get_contents();
		ob_end_clean();
		
		$this->get_wrapper_condition( $id, $html, 'basket_products' );	
	}	
	
	private function display_group( $rules, $id ) 
	{
		$name = empty($this->current_index)?"c[$id]":'c['.implode( '][', $this->current_index )."][$id]";
		ob_start();				
		?>																									
		<input type="hidden" name="<?php echo $name; ?>[type]" value="group"/>							
		<div class = "title_group"><?php _e('Группа условий', 'usam'); ?><a href="#condition_cart_window" class = "add_condition_cart_item" id = "usam_modal" data-toggle="modal" data-type="condition_cart_window"><?php _e('Добавить условие','usam') ?></a></div>			
		<div class = "conditions">
			<?php $this->display_rules( $rules, $id ); ?>
		</div>																		
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$this->get_wrapper_condition( $id, $html, 'group' );	
	}	

	private function display_group_product( $id, $rules ) 
	{
		$name = empty($this->current_index)?"c[$id]":'c['.implode( '][', $this->current_index )."][$id]";
		ob_start();				
		?>																									
		<input type="hidden" name="<?php echo $name; ?>[type]" value="group"/>							
		<div class = "title_group"><?php _e('Группа условий', 'usam'); ?><a href="#condition_cart_item_window" class = "add_condition_cart_item" id = "usam_modal" data-toggle="modal" data-type="condition_cart_item_window"><?php _e('Добавить условие','usam') ?></a></div>			
		<div class = "conditions">
			<?php $this->display_rules( $rules, $id ); ?>
		</div>																		
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$this->get_wrapper_condition( $id, $html, 'group' );	
	}		
	
	private function display_rules( $rules_group, $box_id = null ) 
	{				
		if ( $box_id !== null )
			$this->current_index[] = $box_id;
		foreach ($rules_group as $id => $c )
		{						
			if ( !isset($c['logic_operator'] ) )
			{				
				switch( $c['type'] ) 
				{																	
					case 'products': 
						$this->display_condition_basket_products( $id, $c['rules'] );
					break;
					case 'group_product':					
						$this->display_group_product( $id, $c['rules'] );
					break;
					case 'group':					
						$this->display_group( $c['rules'], $id );
					break;
					default:
					case 'simple':
						$this->display_condition_basket( $id, $c );
					break;
				}				
			}
			else
			{
				$this->display_logic_condition( $id, $c['logic_operator'] );
			}				
		}	
		$key = array_pop( $this->current_index );		
	}		
	
	public function display( $rules_work_basket ) 
	{				
		$rules_work_basket8 = array( 
								array( 'type' => 'simple', 'property' => 'category', 'logic' => 'equal', 'value' => 3 ),
								array( 'logic_operator' => 'AND', ),
								array( 'type' => 'simple', 'property' => 'brands', 'logic' => 'equal', 'value' => 9 ),
								array( 'logic_operator' => 'AND', ),
								array( 'type' => 'group', 'rules' => array(
												array( 'type' => 'simple', 'property' => 'total_tax', 'logic' => 'equal', 'value' => 3 ),
												array( 'logic_operator' => 'AND', ),
												array( 'type' => 'simple', 'property' => 'weekday', 'logic' => 'equal', 'value' => 3 ),
												array( 'logic_operator' => 'AND', ),
												array( 'type' => 'products', 'rules' => array(												
													array( 'type' => 'simple', 'property' => 'item_name', 'logic' => 'equal', 'value' => 90 ),
													array( 'logic_operator' => 'AND', ),
													array( 'type' => 'simple', 'property' => 'item_sku', 'logic' => 'equal', 'value' => 88 ),
												) ),												
								) ),
			);				
		?>	
		<a href="#condition_cart_window" id = "usam_modal" data-toggle="modal" data-type="condition_cart_window" class="button"><?php _e( 'Добавить условие', 'usam'); ?></a>
		<div class='container_condition'><?php	
			if ( !empty($rules_work_basket) )
			{		
				$this->current_index = array();
				$this->display_rules( $rules_work_basket );
			}
			?></div>
		<?php
	}
	
	private function get_condition_basket( $id, $c ) 
	{	
		if ( !isset($c['property']) || !isset($c['property']) || !isset($c['property']))
			return false;		
		
		$property = $c['property'];	
		$logic = $c['logic'];	
		$value = $c['value'];
		
		$condition = array( 'type' => 'simple', 'property' => $property, 'logic' => $logic, 'value' => $value );		
		return $condition;
	}
	
	private function controller_get_condition_products( $id, $condition ) 
	{			
		$rules = array();
		foreach ( $condition as $id => $c )
		{
			if ( !isset($c['logic_operator'] ) )
			{	
				switch( $c['type'] ) 
				{				
					case 'group':
						$result = $this->get_condition_group( $id, $c );
					break;
					default:
					case 'simple':
						$result = $this->get_condition_basket( $id, $c );
					break;
				}	
				if ( $result !== false )
					$rules[] = $result;
			}
			else
			{
				$rules[] = $this->get_logic_condition( $c );
			}	
		}
		$new_conditions = array( 'type' => 'products', 'rules' => array() );
		return $new_conditions;
	}
	
	private function get_condition_group( $id, $condition, $type ) 
	{	
		$rules = $this->get_rules( $condition );
		$condition = array( 'type' => $type, 'rules' => $rules );
		
		return $condition;
	}

	private function get_logic_condition( $logic_operator = 'AND' ) 
	{	
		return array( 'logic_operator' => $logic_operator );		
	}
	
	private function get_rules( $conditions ) 
	{	
		$structured_conditions = array();		
		foreach ( $conditions as $id => $c )
		{								
			if ( !is_numeric( $id ) )
				continue;
			
			$structured_conditions[$id] = $c;
		}		
		$new_conditions = array();	
	
		end($structured_conditions);
		$key_end = key($structured_conditions);
		
		foreach ( $structured_conditions as $id => $c )
		{						
			if ( !isset($c['logic_operator'] ) )
			{				
				if ( !isset($c['type']) )
					continue;
				
				switch( $c['type'] ) 
				{																	
					case 'products': 	
						$result = $this->get_rules( $c );	
						if ( $result !== false )
							$result = array( 'type' => 'products', 'rules' => $result );
					break;
					case 'group_product':
					case 'group':							
						$result = $this->get_condition_group( $id, $c, $c['type'] );				
					break;
					default:
					case 'simple':
						$result = $this->get_condition_basket( $id, $c );
					break;
				}	
				if ( $result !== false )
					$new_conditions[] = $result;
			}
			else
			{	
				if ( count($new_conditions) % 2 && $key_end != $id )
					$new_conditions[] = $this->get_logic_condition( $c['logic_operator'] );
			}				
		}	
		return $new_conditions;
	}	
	
	
		// Вернуть правила условий корзины
	public function get_rules_basket_conditions( ) 
	{				
		$conditions = isset($_POST['c'])?$_POST['c']:array();			
		$new_conditions = $this->get_rules( $conditions );		
		return $new_conditions;
	}
	
	private function get_selectlist( $id, $properties, $selected ='' ) 
	{ 
		?>		
		<div id="select">
			<select id="select_<?php echo $id; ?>" style='width:100%'>
				<?php										
				foreach ( $properties as $key => $value )
				{					
					?>			
					<option value="<?php echo $key; ?>"<?php selected($key, $selected); ?> class = "show_help"><?php echo $value; ?></option>		
					<?php
				}				
				?>
			</select>
		</div>  
		<?php
	}
	
	private function display_properties( $properties, $selected ='' ) 
	{ 
		?>
		<div id="properties" >  
			<h3><?php _e('Свойства', 'usam')?></h3>
			<?php $this->get_selectlist('property', $properties, $selected); ?>
		</div>		
		<?php
	}
	
	private function display_logic( $logics_display, $selected = '' ) 
	{		
		?>
		<div id="logics">
			<h3><?php _e('Логика', 'usam')?></h3>
			<div id="Radio">
				<?php
				$logics = array( 'equal' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('равно', 'usam') ),
										'not_equal' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('не равно', 'usam') ),
										'greater' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('больше', 'usam') ),
										'less' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('меньше', 'usam') ),
										'eg' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('больше либо равно', 'usam') ),
										'el' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,item_count_total,subtotal', 'title' => __('меньше либо равно', 'usam') ),	
										'contains' => array( 'property' => 'item_name', 'title' => __('содержит', 'usam') ),
										'not_contain' => array( 'property' => 'item_name', 'title' => __('не содержит', 'usam') ),
										'begins' => array( 'property' => 'item_name', 'title' => __('начинается с', 'usam') ),
										'ends' => array( 'property' => 'item_name', 'title' => __('заканчивается на', 'usam') ),										
									);						
				$properties = array();
				foreach ($logics_display as $key)	
				{	
					if ( isset($logics[$key]) )
					{
						$properties[$key] = $logics[$key]['title'];
					}
				}
				$this->get_selectlist('logic', $properties, $selected);				
				?>										
			</div> 
		</div> 
		<?php	
	}
	
	private function display_help( $help ) 
	{
		?>
		<div id="help_container">
			<?php	
			foreach ($help as $key => $value)	
			{
				?>
				<div class="help-<?php echo $key; ?> help">
					<h4><?php echo $value['title']; ?><span> - <?php _e('объяснение', 'usam')?></span></h4>
					<p><?php echo $value['explanation']; ?></p>
				</div>
				<?php		
			}
			?>	
		</div>	
		<?php				
	}
	
	
	public function add_condition_cart_window() 
	{	
		ob_start();		
		$properties = array( 'group' => __('Группа условий','usam'), 'products' => __('Выбор товаров','usam'), 'cart' => __('Корзина','usam'), 'cart_terms' => __('Термины корзины','usam'), 'selected_gateway' => __('Метод оплаты','usam'), 'selected_shipping' => __('Метод доставки','usam'), 'roles' => __('Роли покупателя','usam'), 'user' => __('Пользователь','usam'), 'user_data' => __('Данные покупателя','usam'), 'order_property' => __('Оформление заказа','usam'), 'weekday' => __('День недели','usam'), 'type_price' => __('Типы цен','usam'), 'type_payer' => __('Типы плательщиков','usam'), 'location' => __('Местоположения','usam') );				
		?>	
		<div class='modal-body'>
			<div class='modal-scroll'>
				<select id="type_properties" name="type_properties">
					<?php
					foreach($properties as $key => $title)
					{
						?>
						<option value="<?php echo $key; ?>"><?php echo $title; ?></option>
						<?php 
					}
					?>
				</select>
				<div class="condition_group container_containing_column3">	
					<table class = "usam_box_column3">
						<tr id = "group">
							<td>						
								<div class="header">  
									<h2><?php _e('Группа условий', 'usam')?></h2>
									<p><?php _e('Добавьте группу условий, чтобы сгрупировать условия', 'usam') ?></p> 
								</div>
								<div class="column1">
								   
								</div>									 
							</td>
						</tr>		
						<tr id = "products">
							<td>						
								<div class="header">  
									<h2><?php _e('Товары корзины', 'usam')?></h2>
									<p><?php _e('Добавте это блок чтобы вы могли добавлять условия по товарам в корзине.', 'usam') ?></p> 
								</div>
								<div class="column1">
								   
								</div>								
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>			 
							</td>
						</tr>	
						<tr id = "cart">
							<td>						
								<div class="header">  
									<h2><?php _e('Корзина', 'usam')?></h2>
									<p><?php _e('Правило проверяет свойства корзины на соответствие условиям.', 'usam') ?></p> 
								</div>
								<div class="column1" >  
									<?php 
									$properties = array( 'cart_item_count' => __('Количество видов товаров','usam'), 'item_count_total' => __('Общее количество товаров','usam'), 'subtotal' => __('Сумма товаров','usam'), 'subtotal_without_discount' => __('Сумма без товаров на скидке','usam'), 'shipping' => __('Стоимость доставки','usam'), 'total_tax' => __('Стоимость налогов','usam'),'discount' => __('Общая скидка','usam'), 'coupons_amount' => __('Скидка по купону','usam'), 'bonuses' => __('Общее количество бонусов','usam') );	
									$this->display_properties( $properties ); 
									?>
								</div>						
								<div class="column2">
									<?php $this->display_logic( array( 'equal', 'not_equal', 'greater', 'less', 'eg', 'el')) ?>
								</div>
								<div class="column3">
									<h3><?php _e('Значение', 'usam')?></h3>
									<div>
										<input type="text" id = "property_value" name="value" value=""/>
									</div>            
								</div>
								<div class="usam_help_wrapper">
									<?php	
									$help = array( 
		'total_price' => array('title' => 'Общая стоимость', 'explanation' => 'Проверит общую стоимость в корзины с учетом скидок, доставок и налогов на соответствие.',),
		'shipping' => array('title' => 'Стоимость доставки', 'explanation' => 'Проверит стоимость доставки корзины на соответствие.',),
		'total_tax' => array('title' => 'Стоимость налогов', 'explanation' => 'Проверит стоимость налогов корзины на соответствие.',),
		'discount' => array('title' => 'Общая скидка', 'explanation' => 'Проверит общую скидку корзины на соответствие.',),
		'coupons_amount' => array('title' => 'Скидка по купону', 'explanation' => 'Проверит скидку по купону, которую ввел покупатель в корзине, на соответствие.',),	
		'bonuses' => array('title' => 'Используемые бонусы', 'explanation' => 'Проверит бонусы, которые покупатель использовал в корзине, на соответствие.',),
		'cart_item_count' => array('title' => 'Общее количество', 'explanation' => 'Проверит общее количество товаров корзины на соответствие.',),
		'subtotal' => array('title' => 'Итоговая сумма', 'explanation' => 'Проверит стоимость товаров корзины на соответствие.',),					
									);
									$this->display_help( $help ); ?>	
								</div>								
							</td>
						</tr>		
						<tr id = "cart_terms">
							<td>
								<div class="column1">
									<?php
									$properties = array( 'category' => __('Категории','usam'), 'brands' => __('Бренды','usam'), 'category_sale' => __('Акции магазина','usam'));	
									$this->display_properties( $properties );
									?>
								</div>
								<div class="column2">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>		
								<div class="column3">						
									<div id="all_taxonomy">	
										<?php 
										$this->display_meta_box_group( 'category' ); 
										$this->display_meta_box_group( 'brands' );
										$this->display_meta_box_group( 'category_sale' );										
										?>			
									</div>
								</div>
							</td>
						</tr>						
						<tr id = "roles">
							<td>						
								<div class="header">  
									<h2><?php _e('Роли пользователей', 'usam')?></h2>
									<p><?php _e('Правило проверяет роли пользователей на соответствие условиям.', 'usam') ?></p> 
								</div>
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal' )) ?>							            
								</div>							
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'roles' ); ?>
								</div>													
							</td>
						</tr>			
						<tr id = "user_data">
							<td>						
								<div class="header">  
									<h2><?php _e('Данные покупателя', 'usam')?></h2>
									<p><?php _e('Правило проверяет данные покупателя на соответствие условиям.', 'usam') ?></p> 
								</div>
								<div class="column1" >  
									<?php
									$properties = ['birthday' => __('День рождения','usam'), 'newcustomer' => __('Новый покупатель','usam'), 'customer' => __('Покупал раньше','usam')];	
									$this->display_properties( $properties );
									?>   
								</div>	
							</td>
						</tr>
						<tr id = "user">
							<td>	
								<div class="column1">
									<div id="properties" >  
										<h3><?php _e('Пользователи', 'usam')?></h3>
										<?php
											$t = new USAM_Autocomplete_Forms();		
											$t->get_form_user( );
										?>   
									</div>	
								</div>	
								<div class="column2">
									<?php $this->display_logic(['equal', 'not_equal']) ?>
								</div>									
							</td>
						</tr>
						<tr id = "location">
							<td>	
								<div class="column1" >  
									<div id="properties" >  
										<h3><?php _e('Местоположение', 'usam')?></h3>
										<?php
											$t = new USAM_Autocomplete_Forms();		
											$t->get_form_position_location( );
										?>   
									</div>							
								</div>	
								<div class="column2">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>								
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		//'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		//'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>				 
							</td>
						</tr>
						<tr id = "order_property">
							<td>	
								<div class="column1" >  
									<?php
									$list_properties = usam_get_properties( array('type' => 'order') );	
									$properties = array();
									foreach ($list_properties as $key => $value)	
									{	
										$properties['order_property-'.$value->code] = $value->name." [".$value->code."]";
									}
									$this->display_properties( $properties );
									?>							
								</div>						
								<div class="column2">
									<?php $this->display_logic( array( 'equal', 'not_equal', 'greater', 'less', 'eg', 'el')) ?>
								</div>
								<div class="column3">
									<h3><?php _e('Значение', 'usam')?></h3>
									<div>
										<input type="text" id = "property_value" name="value" value=""/>
									</div>            
								</div>
								<div class="usam_help_wrapper">
									<?php	
									$help = array( 
		'total_price' => array('title' => 'Общая стоимость', 'explanation' => 'Проверит общую стоимость в корзины с учетом скидок, доставок и налогов на соответствие.',),
		'shipping' => array('title' => 'Стоимость доставки', 'explanation' => 'Проверит стоимость доставки корзины на соответствие.',),
		'total_tax' => array('title' => 'Стоимость налогов', 'explanation' => 'Проверит стоимость налогов корзины на соответствие.',),
		'discount' => array('title' => 'Общая скидка', 'explanation' => 'Проверит общую скидку корзины на соответствие.',),
		'coupons_amount' => array('title' => 'Скидка по купону', 'explanation' => 'Проверит скидку по купону, которую ввел покупатель в корзине, на соответствие.',),	
		'bonuses' => array('title' => 'Используемые бонусы', 'explanation' => 'Проверит бонусы, которые покупатель использовал в корзине, на соответствие.',),
		'cart_item_count' => array('title' => 'Общее количество', 'explanation' => 'Проверит общее количество товаров корзины на соответствие.',),
		'subtotal' => array('title' => 'Итоговая сумма', 'explanation' => 'Проверит стоимость товаров корзины на соответствие.',),					
									);
									$this->display_help( $help ); ?>	
								</div>								
							</td>
						</tr>		
						<tr id = "weekday">
							<td>													
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'weekday' ); ?>
								</div>							
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>			 
							</td>
						</tr>
						<tr id = "type_price">
							<td>						
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>							
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'type_prices' ); ?>							
								</div>
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>	 
							</td>
						</tr>
						<tr id = "type_payer">
							<td>							
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>		
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'types_payers' ); ?>
								</div>
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>	 
							</td>
						</tr>				
						<tr id = "selected_gateway">
							<td>							
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>
								</div>								
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'selected_gateway' ); ?>
								</div>
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>	 
							</td>
						</tr>
						<tr id = "selected_shipping">
							<td>						
								<div class="column1">
									<?php $this->display_logic( array( 'equal', 'not_equal')) ?>      
								</div>
								<div id="all_taxonomy">	
									<?php $this->display_meta_box_group( 'selected_shipping' ); ?>							
								</div>
								<div class="usam_help_wrapper">
									<?php										
									$help = array( 
		'equal' => array('title' => 'Равно', 'explanation' => 'Правило выполнится если способ доставки будет равен выбранному.',),
		'not_equal' => array('title' => 'Не равно', 'explanation' => 'Правило выполнится если способ доставки будет не равен выбранному.',),
									);
									$this->display_help( $help ); ?>	
								</div>		 
							</td>
						</tr>
					</table>
				</div>	
			</div>			
			<div class="modal__buttons">
				<button id = "save_action" type="button" class="button-primary button"><?php _e( 'Добавить', 'usam'); ?></button>				
				<button type="button" class="button" data-dismiss="modal" aria-hidden="true"><?php _e( 'Отменить', 'usam'); ?></button>
			</div>
		</div>	
		<?php
		$html = ob_get_contents();
		ob_end_clean();	
		echo usam_get_modal_window( __('Добавить условие', 'usam'), 'condition_cart_window', $html );	
	}
	
	
	public function add_condition_cart_item_window() 
	{	
		ob_start();	
		$properties = ['product' => __('Товар','usam'), 'terms' => __('Термин','usam'), 'group_product' => __('Группа условий','usam')];				
		?>	
		<div class='modal-body'>	
			<div class='basket_discount_rule'>		
				<select id="type_properties" name="type_properties">
					<?php
					foreach($properties as $key => $title)
					{
						?>
						<option value="<?php echo $key; ?>"><?php echo $title; ?></option>
						<?php 
					}
					?>
				</select>
				<div class="condition_group container_containing_column3">	
					<table class = "usam_box_column3">
						<tr id = "product">
							<td>						
								<div class="header">  
									<h2><?php _e('Товар', 'usam')?></h2>
									<p><?php _e('Правило проверяет свойства товара на соответствие условиям, и выберет те которые соответствуют. Скидка не буден расчитана для товаров которые не соответствуют условиям.', 'usam') ?></p> 
								</div>
								<div class="column1">  
									<?php
									$properties = ['item_name' => __('Название','usam'), 'item_quantity' => __('Количество','usam'), 'item_price' => __('Цена','usam'), 'item_old_price' => __('Старая цена','usam'), 'item_sku' => __('Артикул','usam'), 'item_barcode' => __('Штрихкод','usam')];	
									$this->display_properties( $properties );
									?>
								</div>
								<div class="column2"><?php $this->display_logic( array( 'equal', 'not_equal', 'greater', 'less', 'eg', 'el', 'contains', 'not_contain', 'begins', 'ends')) ?></div>
								<div class="column3">
									<h3><?php _e('Значение', 'usam')?></h3>
									<div><input type="text" id = "property_value" name="value" value=""/></div>             
								</div>
								<div class="usam_help_wrapper">
									<?php									
									$help = array( 
		'item_name' => array('title' => 'Название товара', 'explanation' => 'Проверит название товаров в корзине на соответствие. Если выбрано равно, то название товара должно быть строго равно введенному значению. Если выбрано содержит, то в названии товара должен быть текст который соответствует введенному значению.',),
		'item_quantity' => array('title' => 'Количество товара', 'explanation' => 'Проверит количество товаров в корзине на соответствие.',),	
		'item_price' => array('title' => 'Цена товара', 'explanation' => 'Проверит цену товаров в корзине на соответствие.',),
		'item_old_price' => array('title' => 'Количество товара', 'explanation' => 'Проверит цену товаров в корзине на соответствие. Если вы не хотите, чтобы скидка распостранялась на товары, на которые уже есть скидка, установите логику в равно, а в значение поставьте 0',),					
									);							
									$this->display_help( $help ); ?>	
								</div>				 
							</td>
						</tr>
						<tr id = "terms">
							<td>						
								<div class="column1">
									<?php
									$properties = array( 'category' => __('Категория','usam'), 'brands' => __('Бренд','usam'), 'category_sale' => __('Акция магазина','usam'));	
									$this->display_properties( $properties );
									?>
								</div>
								<div class="column2"><?php $this->display_logic( array( 'equal', 'not_equal' )) ?></div>
								<div class="column3">
									<div id="all_taxonomy">	
										<?php  							
										$this->display_meta_box_group( 'category' ); 
										$this->display_meta_box_group( 'brands' );
										$this->display_meta_box_group( 'category_sale' );										
										?>	
									</div>	
								</div>
							</td>
						</tr>
						<tr id = "group_product">
							<td>						
								<div class="header">  
									<h2><?php _e('Группа условий', 'usam')?></h2>
									<p><?php _e('Добавьте группу условий, чтобы сгрупировать условия', 'usam') ?></p> 
								</div>
								<div class="column1">
								   
								</div>									 
							</td>
						</tr>						
					</table>
				</div>	
			</div>			
			<div class="modal__buttons">
				<button id = "save_action" type="button" class="button-primary button"><?php _e( 'Добавить', 'usam'); ?></button>				
				<button type="button" class="button" data-dismiss="modal" aria-hidden="true"><?php _e( 'Отменить', 'usam'); ?></button>
			</div>	
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();	
		echo usam_get_modal_window( __('Добавить условие', 'usam'), 'condition_cart_item_window', $html );	
	}
	
	public function display_meta_box_group($group) 
	{
		$edit = new USAM_Edit_Form();
		$edit->display_meta_box_group($group);
	}	
}
?>