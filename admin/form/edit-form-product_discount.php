<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-discount.php' );
class USAM_Form_product_discount extends USAM_Form_Rule_Discount
{		
	function box_action( )
	{			
		?>		
		<div class="edit_form">		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='rule_discount'><?php esc_html_e( 'Сумма скидки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="rule_discount" type="text" name="discount" autocomplete="off" style="width:100px;" value="<?php echo $this->data['discount']; ?>">	
					<?php echo usam_get_select_type_md( $this->data['dtype'], array( 'name' => "dtype" ) ); ?>							
				</div>
			</div>	
		</div>		
		<?php
	}		
	
	public function label_product( )
	{
		$label_name = usam_get_discount_rule_metadata($this->id, 'label_name');
		$label_color = usam_get_discount_rule_metadata($this->id, 'label_color');
		?>		
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_label_name'><?php esc_html_e( 'Название стикера', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" autocomplete="off" id="option_label_name" value="<?php echo $label_name; ?>" name="label_name"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_label_color'><?php esc_html_e( 'Цвет стикера', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" autocomplete="off" id="option_label_color" value="<?php echo $label_color; ?>" name="label_color"/>
				</div>
			</div>
		</div>		
		<?php
	}

	function display_left()
	{	
		$this->titlediv( $this->data['name'] );		
		$this->add_box_description( $this->data['description'] );
		usam_add_box( 'usam_options', __('Настройки правила','usam'), array( $this, 'box_options' ) );
		usam_add_box( 'usam_label_product', __('Метка на товар в каталоге','usam'), array( $this, 'label_product' ) );
		usam_add_box( 'usam_execute_actions', __('Установить скидку','usam'), array( $this, 'box_action' ) );	
		usam_add_box( 'usam_condition', __('Условия выполнения правила','usam'), array( $this, 'display_product_discount_rules' ) );	
    }	
}
?>