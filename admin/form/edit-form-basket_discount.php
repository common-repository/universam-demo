<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-discount.php' );
class USAM_Form_basket_discount extends USAM_Form_Rule_Discount
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило расчета скидок %s','usam'), $this->data['name'] );
		else
			$title = __('Добавить правило расчета скидок', 'usam');	
		return $title;
	}
		
	function box_action( )
	{			
		$discount_cart_type = ['p' => esc_html__('Изменить цену товара', 'usam'), 'b' => esc_html__('Добавить бонусы', 'usam'), 's' => esc_html__('Изменить доставку', 'usam'), 'g' => esc_html__('Добавить подарок', 'usam'), 'gift_choice' => esc_html__('Пользователь выбирает подарок из указанных ниже', 'usam'), 'gift_one_choice' => esc_html__('Пользователь выбирает один подарок из указанных ниже', 'usam')];
		$perform_action = usam_get_discount_rule_metadata( $this->id, 'perform_action');
		$currency = usam_get_currency_sign();
		?>		
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='discount_cart_type'><?php esc_html_e( 'Выполнить действие', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="discount_cart_type" name="perform_action">
						<?php 
						foreach ( $discount_cart_type as $key => $title ) 
						{
							?><option value="<?php echo $key; ?>" <?php selected( $perform_action, $key) ?>><?php echo $title; ?></option><?php 
						}
						?>
					</select>
				</div>
			</div>	
			<div id = "discount_cart-value" class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='discount_cart_value'><?php esc_html_e( 'Скидка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span><input type="text" style="width:100px;" value="<?php echo $this->data['discount']; ?>" name="discount" id="discount_cart_value" autocomplete="off"/></span>
					<select class="ruleprops" name="dtype" style="width:300px;">
						<option value="f" <?php selected($this->data['dtype'],'f'); ?>><?php echo esc_html( $currency ) ?></option>		
						<option value="p" <?php selected($this->data['dtype'],'p'); ?>>%</option>
					</select>
				</div>
			</div>					
		</div>			
		<?php
		$this->table_products_add_button(['n' => __('№', 'usam'), 'title' => __('Товары', 'usam'), 'delete' => '' ], 'gift' );
	}		
		
	function display_left()
	{		
		$this->titlediv( $this->data['name'] );		
		$this->add_box_description( $this->data['description'] );	
	
		usam_add_box( 'usam_options', __('Основные настройки','usam'), array( $this, 'box_options' ) );		
		usam_add_box( 'usam_execute_actions', __('Выполнить действия','usam'), array( $this, 'box_action' ) );	
		
		$conditions = usam_get_discount_rule_metadata( $this->id, 'conditions');
		usam_add_box( 'usam_condition', __('Условия выполнения правила','usam'), array( $this, 'display_rules_work_basket' ), $conditions );	
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>