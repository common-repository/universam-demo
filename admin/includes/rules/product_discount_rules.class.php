<?php
class USAM_Product_Discount_Rules
{		
	private $current_index = array();			
	public function load( ) 
	{			
		add_action( 'admin_footer', [$this, 'admin_footer'], 11 );	
	}
	
	public function admin_footer( ) 
	{
		wp_enqueue_style( 'usam-progress-form' );
		$this->add_condition_window();		
		include( USAM_FILE_PATH . '/admin/includes/modal/product_form_importer.php' );
	}
	
	private function get_property_title( $property ) 
	{	
		$properties = array( 'name' => __('Название','usam'), 'sku' => __('Артикул','usam'), 'barcode' => __('Штрихкод','usam'), 'category' => __('Категория товаров','usam'), 'brands' => __('Бренд товаров','usam'), 'category_sale' => __('Акция магазина','usam') );
		if ( isset($properties[$property]) )
			return $properties[$property];	
		elseif ( stristr($property, 'attribute') !== false)
		{			
			$slug = str_replace("attribute-", "", $property);		
			$term = get_term_by( 'slug', $slug, 'usam-product_attributes' );
			if ( !empty($term) )
				$value_title = $term->name;
			else
				$value_title = '('.sprintf( __('Свойство с slug %s удалено','usam'), $slug ).')';					
			return $value_title;		
		}
		return '';
	}
	
	private function get_wrapper_condition( $id, $html, $type ) 
	{
		?>	
		<div id ="row-<?php echo $id; ?>" class = "condition-block condition-<?php echo $type; ?>">	
			<div class = "condition-wrapper"><?php echo $html; ?></div>
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
	
	
	private function get_html_block_simple( $id, $c ) 
	{		
		$property_title = $this->get_property_title( $c['property'] );
		$logics_title = usam_get_logic_title( $c['logic'] );
		$method = 'get_html_block_'.$c['property'];			
		if ( method_exists( $this, $method ) )
		{
			$data = $this->$method( );
			$value_title = $data[$c['value']];	
		}
		else
		{ 
			switch( $c['property'] ) 
			{																	
				case 'category': 
				case 'brands': 
				case 'category_sale': 																		
					$taxonomy = 'usam-'.$c['property'];
					$c['value'] = usam_get_term_id_main_site( $c['value'] );
					$term = get_term( $c['value'], $taxonomy );
					if ( !empty($term) )
						$value_title = $term->name;
					else
						$value_title = sprintf( __('Категория с номером %s удалена','usam'), $c['value'] );
				break;		
				case 'sku': 		
				case 'barcode': 				
					$product_id = usam_get_product_id_by_meta( $c['property'], $c['value'] );
					if ( $product_id )					
						$value_title = '<a href="'.usam_product_url( $product_id ).'">'.get_the_title( $product_id ).'</a>';			
					else
						$value_title = sprintf( __('Товар с артикулом %s не найден','usam'), $c['value'] );
				break;					
				case 'location': 				
					$value_title = usam_get_full_locations_name( $c['value'] );			
				break;
				default:
					$value_title = '';		                
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
			<div class = "expression value_expression"><span class="js-copy-clipboard"><?php echo $c['value']; ?></span><span class = "title_value_expression"><?php echo $value_title; ?></span></div>				
		</div>													
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$this->get_wrapper_condition( $id, $html, 'basket' );
	}		
	
	private function display_group( $id, $rules ) 
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
					case 'group':					
						$this->display_group( $id, $c['rules'] );
					break;
					default:
					case 'simple':
						$this->get_html_block_simple( $id, $c );
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
		?>	
		<div class='condition_buttons'>
			<a href="#condition_cart_window" id = "usam_modal" data-toggle="modal" data-type="condition_cart_window" class="button"><?php _e( 'Добавить условие', 'usam'); ?></a>
			<a href="#" id="import_products" class="button"><?php _e( 'Импорт товаров', 'usam'); ?></a>
		</div>
		<div class='container_condition'><?php				
			if ( !empty($rules_work_basket) )
			{		
				$this->current_index = array();
				$this->display_rules( $rules_work_basket );
			}
			?></div>
		<?php
	}
	
	private function get_condition_simple( $id, $c ) 
	{	
		if ( !isset($c['property']) || !isset($c['property']) || !isset($c['property']))
			return false;		
		
		$property = $c['property'];	
		$logic = $c['logic'];	
		$value = $c['value'];
		
		$condition = array( 'type' => 'simple', 'property' => $property, 'logic' => $logic, 'value' => $value );		
		return $condition;
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
					case 'group':							
						$result = $this->get_condition_group( $id, $c, $c['type'] );				
					break;
					default:
					case 'simple':
						$result = $this->get_condition_simple( $id, $c );
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
										'contains' => array( 'property' => 'name', 'title' => __('содержит', 'usam') ),
										'not_contain' => array( 'property' => 'name', 'title' => __('не содержит', 'usam') ),
										'begins' => array( 'property' => 'name', 'title' => __('начинается с', 'usam') ),
										'ends' => array( 'property' => 'name', 'title' => __('заканчивается на', 'usam') ),										
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
		
	public function add_condition_window() 
	{	
		ob_start();		
		$properties = array( 'group' => __('Группа условий','usam'), 'product' => __('Товар','usam'), 'product_attributes' => __('Свойства товаров','usam'), 'terms' => __('Термины','usam') );				
		?>	
		<div class='modal-body'>		
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
					<tr id = "product">
						<td>						
							<div class="header">  
								<h2><?php _e('Товар', 'usam')?></h2>
								<p><?php _e('Правило проверяет свойства товара на соответствие условиям, и выберет те которые соответствуют. Скидка не буден расчитана для товаров которые не соответствуют условиям.', 'usam') ?></p> 
							</div>
							<div class="column1">  
								<?php
								$properties = array( 'name' => __('Название','usam'), 'sku' => __('Артикул','usam'), 'barcode' => __('Штрихкод','usam'));	
								$this->display_properties( $properties );
								?>
							</div>
							<div class="column2"><?php $this->display_logic( array( 'equal', 'not_equal', 'greater', 'less', 'eg', 'el', 'contains', 'not_contain', 'begins', 'ends')) ?></div>
							<div class="column3">
								<h3><?php _e('Значение', 'usam')?></h3>
								<div><input type="text" id = "property_value" name="value" value=""/></div>             
							</div>								 
						</td>
					</tr>				
					<tr id = "terms">
						<td>						
							<div class="header">  
								<h2><?php _e('Термины', 'usam')?></h2>
								<p><?php _e('Правило проверяет термины на соответствие условиям.', 'usam') ?></p> 
							</div>
							<div class="column1">
								<?php
								$properties = array( 'category' => __('Категория','usam'), 'brands' => __('Бренд','usam'), 'category_sale' => __('Акция магазина','usam'));	
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
					<tr id = "product_attributes">
						<td>						
							<div class="header">  
								<h2><?php _e('Свойства товаров', 'usam')?></h2>
								<p><?php _e('Правило проверяет свойство товаров на соответствие условиям.', 'usam') ?></p> 
							</div>
							<div class="column1">  
								<?php
								$terms = get_terms( array( 'hide_empty' => 0, 'taxonomy' => 'usam-product_attributes', 'orderby' => 'name' ) );
								$properties = array();
								foreach ($terms as $term)	
								{									
									$properties['attribute-'.$term->slug] = $term->name;
								}
								$this->display_properties( $properties );
								?>
							</div>
							<div class="column2"><?php $this->display_logic( array( 'equal', 'not_equal', 'greater', 'less', 'eg', 'el', 'contains', 'not_contain', 'begins', 'ends')) ?></div>
							<div class="column3">
								<h3><?php _e('Значение', 'usam')?></h3>
								<div><input type="text" id = "property_value" name="value" value=""/></div>             
							</div>	
						</td>
					</tr>						
				</table>
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
	
	public function display_meta_box_group($group) 
	{
		$edit = new USAM_Edit_Form();
		$edit->display_meta_box_group($group);
	}	
}
?>