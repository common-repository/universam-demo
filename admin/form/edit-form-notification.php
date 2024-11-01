<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_notification extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить уведомление &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить уведомление', 'usam');	
		return $title;		
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )							
		{
			$this->data = usam_get_data( $this->id, 'usam_notifications' );			
			if ( !empty($this->data['contacts']) )
				$this->data['contacts'] = usam_get_contacts(['include' => $this->data['contacts'], 'source' => 'all', 'orderby' => 'name', 'cache_meta' => true, 'cache_results' => true]);		
			else
				$this->data['contacts'] = array();
		}
		else
			$this->data = array( 'name' => '', 'active' => 0, 'email' => '', 'phone' => '', 'messenger' => '', 'events' => array( ), 'contacts' => array() );		
	}	
		
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );			
		usam_add_box( 'usam_events', __('О чем уведомлять', 'usam'), array( $this, 'display_events' ) );	
		usam_add_box( 'usam_where_to_report', __('Куда уведомить', 'usam'), array( $this, 'display_where_to_report' ) );	
    }	
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
		$title = __("Контакты", "usam")." <a data-modal='select_contacts' data-screen='contacts' data-list='contact'  class='js-modal'>".__('Добавить','usam')."</a>";		
		usam_add_box( 'usam_document_contacts', $title, array( $this, 'display_select_contacts' ) );		
    }
	
	public function display_events() 
	{		
		?>
		<div id = "feedback" class = "usam_tabs usam_tabs_style2">
			<div class = "header_tab">
				<a class = "tab" href="#order"><?php _e( 'Заказ', 'usam'); ?></a>
				<a class = "tab" href="#shipped_document"><?php _e( 'Отгрузки', 'usam'); ?></a>
				<a class = "tab" href="#feedback"><?php _e( 'Обращения', 'usam'); ?></a>		
				<a class = "tab" href="#email"><?php _e( 'Письма', 'usam'); ?></a>	
				<a class = "tab" href="#chat"><?php _e( 'Чат', 'usam'); ?></a>				
				<a class = "tab" href="#low_stock"><?php _e( 'Низкий запас', 'usam'); ?></a>	
				<a class = "tab" href="#no_stock"><?php _e( 'Нет в наличии', 'usam'); ?></a>
				<a class = "tab" href="#crm"><?php _e( 'Событие в CRM', 'usam'); ?></a>
			</div>	
			<div class = "countent_tabs">					
				<?php 
				$this->print_tab( 'order', esc_html__('Сообщать о новых заказах', 'usam') ); 
				$this->print_tab( 'shipped_document', esc_html__('Сообщать о новых отгрузках курьерам', 'usam') ); 
				$this->print_tab( 'feedback', esc_html__('Сообщать об обращениях через веб-формы', 'usam') ); 
				$this->print_tab( 'email', esc_html__('Сообщать о новых письмах', 'usam') ); 
				$this->print_tab( 'chat', esc_html__('Сообщать об обращениях через чат', 'usam') ); 
				$this->print_tab( 'low_stock', esc_html__('Сообщать о малом запасе товара', 'usam') ); 
				$this->print_tab( 'no_stock', esc_html__('Сообщать об отсутствии товара на вашем сайте', 'usam') ); 			
				$this->print_tab( 'crm', esc_html__('Сообщать когда меня добавят в задание, собрание, проект или документ', 'usam') ); 					
				?>							
			</div>
		</div>
	<?php 
	}
	
	function print_tab( $type_message, $title ) 
	{		
		$events = !empty($this->data['events'][$type_message])?$this->data['events'][$type_message]:array('email' => 0, 'sms' => 0, 'messenger' => 0, 'condition' => array() );
		
		$email_checked = !empty($events['email'])?1:0;
		$sms_checked = !empty($events['sms'])?1:0;
		$messenger_checked = !empty($events['messenger'])?1:0;		
		?>
		<div id = "<?php echo $type_message; ?>" class="tab">	
			<h4 class="form_group"><?php echo $title; ?></h3>	
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='notification_events_<?php echo $type_message; ?>'><?php esc_html_e( 'На электронную почту', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='hidden' name='events[<?php echo $type_message; ?>][email]' value='0'>
						<input id="notification_events_<?php echo $type_message; ?>" value='1' <?php checked( '1', $email_checked ); ?> type='checkbox' name='events[<?php echo $type_message; ?>][email]'>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='notification_events_<?php echo $type_message; ?>'><?php esc_html_e( 'В СМС', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='hidden' name='events[<?php echo $type_message; ?>][sms]' value='0'>
						<input id="notification_events_<?php echo $type_message; ?>" value='1' <?php checked( '1', $sms_checked ); ?> type='checkbox' name='events[<?php echo $type_message; ?>][sms]'>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='notification_events_<?php echo $type_message; ?>'><?php esc_html_e( 'В мессенжер', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type='hidden' name='events[<?php echo $type_message; ?>][messenger]' value='0'>
						<input id="notification_events_<?php echo $type_message; ?>" value='1' <?php checked( '1', $messenger_checked ); ?> type='checkbox' name='events[<?php echo $type_message; ?>][messenger]'>
					</div>
				</div>
			</div>
			<?php 						
			$conditions = !empty($events['conditions'])?$events['conditions']:array();
			switch ( $type_message ) 
			{
				case 'order' :
					if ( empty($conditions) )
						$conditions = array( 'prices' => array( '' ) );
					$this->display_conditions( $type_message, $conditions );		
				break;
				case 'feedback' :					
					if ( empty($conditions) )
						$conditions =  array( 'sales_area' => array( '' ) );
				//	$this->display_conditions( $type_message, $conditions );					
				break;				
				case 'low_stock' :
					if ( empty($conditions) )
						$conditions = array( 'category' => array( '' ) );						
				
					$stock = !empty($events['stock'])?$events['stock']:10;
					?>
					<p><label><?php _e('Запас от','usam'); ?>: <input type='text' name='events[<?php echo $type_message; ?>][stock]' value='<?php echo $stock; ?>'></label></p>								
					<?php	
					$this->display_conditions( $type_message, $conditions );							
				break;
				case 'no_stock' :							
					if ( empty($conditions) )
						$conditions = array( 'category' => array( '' ) );	
					$this->display_conditions( $type_message, $conditions );						
				break;
			}			
			?>
		</div>		
		<?php
	}
	
	public function display_conditions( $type, $conditions )
	{			
		?>		
		<h4 class="form_group"><?php _e('Условия','usam'); ?></h3>				
		<div class="usam_table_container condition">
			<table class = "table_rate">
				<thead>
					<tr>
						<th><?php _e('Что проверить', 'usam'); ?></th>
						<th><?php _e('Значение', 'usam'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>				
					<?php if ( !empty( $conditions ) ): ?>
						<?php				
							foreach( $conditions as $id => $condition )
							{
								foreach( $condition as $value )
									$this->output_row_conditions( $type, $id, $value );							
							}
						?>
					<?php else: ?>
						<?php $this->output_row_conditions( $type ); ?>
					<?php endif ?>
				</tbody>
			</table>
		</div>		
        <?php
	}     

	private function output_row_conditions( $type, $id = '', $value = '' ) 
	{							
		?>
		<tr>
			<td class="column_name center">
				<div class="condition_type">					
					<select id ="check_type" name="conditions[<?php echo $type; ?>][type][]">
						<?php
						switch ( $type ) 
						{
							case 'order' :							
								?>	
								<option value="prices" <?php selected( $id, 'prices'); ?>><?php esc_html_e( 'Типы цен', 'usam'); ?></option>	
							<?php
							break;
							case 'feedback' :								
								?>	
								<option value="sales_area" <?php selected( $id, 'sales_area'); ?>><?php esc_html_e( 'Мультирегиональность', 'usam'); ?></option>	
							<?php
							break;
							case 'low_stock' :	
							case 'no_stock' :									
								?>	
								<option value="brands" <?php selected( $id, 'brands'); ?>><?php echo esc_html__('Бренд', 'usam'); ?></option>		
								<option value="category" <?php selected($id, 'category'); ?>><?php echo esc_html__('Категория товара', 'usam'); ?></option>	
								<option value="category_sale" <?php selected($id, 'category_sale'); ?>><?php echo esc_html__('Акция магазина', 'usam'); ?></option>
							<?php
							break;
						}
						?>								
					</select>				
				</div>					
			</td>				
			<td class="td_condition_value">							
				<?php
				switch ( $type ) 
				{
					case 'order' :	
						?>				
						<div id="check_price" class="check_blok <?php echo $id=='prices'?'show':'hidden'; ?>">	
							<select class ="condition_value" name="conditions[<?php echo $type; ?>][value][]" <?php echo $id=='prices'?'':'disabled = "disabled"'; ?>>
								<option value=""<?php selected($value, ''); ?>><?php esc_html_e( 'Все цены', 'usam'); ?></option>
								<?php 
								$prices = usam_get_prices( array('type' => 'R') );		
								foreach ($prices as $price)	
								{									
									?>
									<option value="<?php echo $price['code']; ?>"<?php selected($value, $price['code']); ?>><?php echo $price['title']; ?></option>
									<?php
								}	
								?>	
							</select>	
						</div>
					<?php
					break;
					case 'feedback' :	
						?>			
						<div id="check_sales_area" class="check_blok <?php echo $id=='sales_area'?'show':'hidden'; ?>">	
							<select class ="condition_value" name="conditions[<?php echo $type; ?>][value][]" <?php echo $id=='sales_area'?'':'disabled = "disabled"'; ?>>
								<option value=""<?php selected($value, ''); ?>><?php esc_html_e( 'Все зоны', 'usam'); ?></option>
								<?php 
								$sales_area = usam_get_sales_areas();
								foreach ($sales_area as $area)	
								{									
									?>
									<option value="<?php echo $area['id']; ?>"<?php selected($value, $area['id']); ?>><?php echo $area['name']; ?></option>
									<?php
								}	
								?>	
							</select>	
						</div>
					<?php
					break;
					case 'low_stock' :	
					case 'no_stock' :	
						?>	
						<div id="check_brands" class="check_blok <?php echo $id=='brands'?'show':'hidden'; ?>">	
							<select class ="condition_value" name="conditions[<?php echo $type; ?>][value][]" <?php echo $id=='brands'?'':'disabled = "disabled"'; ?>>								
							<?php 
							$terms = get_terms( array( 'taxonomy' => 'usam-brands', 'hide_empty' => 0 ));	
							foreach ($terms as $term)	
							{									
								?><option value="<?php echo $term->term_id; ?>"<?php selected($value, $term->term_id); ?>><?php echo $term->name; ?></option><?php
							}	
							?>	
							</select>	
						</div>
						<div id="check_category" class="check_blok <?php echo $id=='category'?'show':'hidden'; ?>">	
							<select class ="condition_value" name="conditions[<?php echo $type; ?>][value][]" <?php echo $id=='category'?'':'disabled = "disabled"'; ?>>
							<option value=""<?php selected($value, ''); ?>><?php esc_html_e( 'Все категории', 'usam'); ?></option>
							<?php 
							$terms = get_terms( array('taxonomy' => 'usam-category', 'hide_empty' => 0 ));	
							foreach ($terms as $term)	
							{									
								?><option value="<?php echo $term->term_id; ?>"<?php selected($value, $term->term_id); ?>><?php echo $term->name; ?></option><?php
							}	
							?>	
							</select>	
						</div>
						<div id="check_category_sale" class="check_blok <?php echo $id=='category_sale'?'show':'hidden'; ?>">	
							<select class ="condition_value" name="conditions[<?php echo $type; ?>][value][]" <?php echo $id=='category_sale'?'':'disabled = "disabled"'; ?>>
							<?php 
							$terms = get_terms( array( 'taxonomy' => 'usam-category_sale', 'hide_empty' => 0 ));	
							foreach ($terms as $term)	
							{									
								?><option value="<?php echo $term->term_id; ?>"<?php selected($value, $term->term_id); ?>><?php echo $term->name; ?></option><?php
							}	
							?>	
							</select>	
						</div>		
					<?php
					break;
				}
				?>						
			</td>
			<td class="column_actions">	
				<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
					?>
			</td>
		</tr>
		<?php
	}	
	
	public function display_where_to_report() 
	{		
		$properties = usam_get_properties( array('type' => 'contact') );
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_email'><?php esc_html_e( 'Электронная почта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select class ="option_email" name="email">
						<?php 	
						foreach ($properties as $property)	
						{									
							if ( $property->field_type == 'email' )
							{
								?><option value="<?php echo $property->code; ?>"<?php selected($property->code, $this->data['email']); ?>><?php echo $property->name; ?></option><?php
							}
						}	
						?>	
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_phone'><?php esc_html_e( 'Телефон', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select class ="option_phone" name="phone">
						<?php 	
						foreach ($properties as $property)	
						{									
							if ( $property->field_type == 'mobile_phone' )
							{
								?><option value="<?php echo $property->code; ?>"<?php selected($property->code, $this->data['phone']); ?>><?php echo $property->name; ?></option><?php
							}
						}	
						?>	
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_messengers'><?php esc_html_e( 'Мессенджер', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select class ="option_messengers" name="messenger">
						<?php 
						foreach ($properties as $property)	
						{									
							if ( $property->group == 'social_networks_id' )
							{
								?><option value="<?php echo $property->code; ?>"<?php selected($property->code, $this->data['messenger']); ?>><?php echo $property->name; ?></option><?php
							}
						}	
						?>	
					</select>
				</div>
			</div>
		</div>
		<?php
	}			
}
?>