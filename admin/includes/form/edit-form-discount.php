<?php	
require_once( USAM_FILE_PATH . '/admin/includes/rules/product_discount_rules.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Rule_Discount extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить правило скидок', 'usam');			
		return $title;
	}
		
	protected function get_data_tab(  )
	{					
		if ( $this->id != null )
			$this->data = usam_get_discount_rule( $this->id ); 
		else
			$this->data = ['name' => '', 'description' => '', 'active' => 0, 'term_slug' => '', 'priority' => 100, 'code' => '', 'end' => 0, 'discount' => 0, 'dtype' => 'p', 'start_date' => '', 'end_date' => ''];
	}	
	
	function box_options( )
	{			
		?>		
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'], 'hour' ); ?> - <?php usam_display_datetime_picker('end', $this->data['end_date'],  'hour' ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_priority'><?php esc_html_e( 'Приоритет', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id ="option_priority" value="<?php echo $this->data['priority']; ?>" autocomplete="off" name="priority"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_end'><?php esc_html_e( 'Прекратить дальнейшее выполнение правил', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="checkbox" id ="option_end" value="1" <?php checked($this->data['end'],1) ?> name="end"/>
				</div>
			</div>
			<?php if ( usam_check_current_user_role( 'administrator' ) ) { ?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Внешний код', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type="text" id ="option_code" value="<?php echo $this->data['code']; ?>" autocomplete="off" name="code"/>
					</div>
				</div>	
			<?php } ?>			
		</div>		
		<?php
	}	
	
	public function link_to_stock( )
	{        
		?>
		<div class="edit_form">				
			<div class ="edit_form__item">
				<select class="chzn-select" name = "term_slug" style="100%">
					<option value="0"><?php _e('Не привязывать','usam'); ?></option>
					<?php	
					$terms = get_terms( array('orderby' => 'name', 'taxonomy' => 'usam-category_sale', 'hide_empty' => 0) );
					foreach( $terms as $term )
					{						
						?><option value="<?php echo $term->slug; ?>" <?php selected($term->slug, $this->data['term_slug']) ?> ><?php echo $term->name; ?></option><?php
					}
					?>
				</select>	
			</div>
		</div>		
		<?php
	} 
					  
    public function selecting_type_prices( )
	{        
		$type_prices = usam_get_discount_rule_metadata( $this->id, 'type_prices');
		$this->display_meta_box_group( 'type_prices', $type_prices );		
	}       
	
	public function display_product_discount_rules( ) 
	{		
		USAM_Admin_Assets::basket_conditions();
		$conditions = usam_get_discount_rule_metadata( $this->id, 'conditions');
		$product_discount_rules = new USAM_Product_Discount_Rules( );
		$product_discount_rules->load();
		$product_discount_rules->display( $conditions );	
	}
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );				
		usam_add_box( 'usam_link_to_stock', __('Привязать к акции','usam'), array( $this, 'link_to_stock' ) );			
		usam_add_box( 'usam_prices', __('Цены, на которые установить','usam'), array( $this, 'selecting_type_prices' ) );		
    }
}
?>