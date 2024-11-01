<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Tax extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить налог %s','usam'), $this->data['name'] );
		else
			$title = __('Добавить налог', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
		{
			$_tax = new USAM_Tax( $this->id );
			$this->data = $_tax->get_data();			
		}
		else	
		{
			$this->data = array( 'name' => '', 'description' => '', 'active' => 0, 'sort' => 10, 'type_payer' => '', 'is_in_price' => 0, 'value' => 0, 'setting' => ['locations' => [], 'payments' => [], 'category' => [] ,'brands' => []]);	
		}
	}
	
	function display_left()
	{							
		$this->titlediv( $this->data['name'] );
		$this->add_box_description( $this->data['description'] );			
		usam_add_box( 'usam_terms_settings', __('Условия применения','usam'), [$this, 'terms_settings'] );			
		usam_add_box( 'usam_locations', __('Местоположение','usam'), array( $this, 'selecting_locations' ), $this->data['setting']['locations'] );
    }	
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
		usam_add_box( 'usam_settings', __('Основные параметры','usam'), array( $this, 'display_settings' ) );			
    }
	
	public function terms_settings( ) 
	{		
		$this->checklist_meta_boxs(['category' => $this->data['setting']['category'], 'brands' => $this->data['setting']['brands'], 'selected_gateway' => $this->data['setting']['payments']]); 
	}
		
	function display_settings()
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type_payer'><?php esc_html_e( 'Плательщик', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="tax[type_payer]" id='option_type_payer'>				
						<option value="" <?php selected( $this->data['type_payer'], '') ?> ><?php _e( 'Любой', 'usam'); ?></option>
						<?php				
						$types_payers = usam_get_group_payers();	
						foreach( $types_payers as $value )
						{						
							?>               
							<option value="<?php echo $value['id']; ?>" <?php selected($this->data['type_payer'], $value['id']); ?> ><?php echo $value['name']; ?></option>
							<?php
						}
						?>
					</select>	
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_value'><?php esc_html_e( 'Ставка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_value" name="tax[value]" maxlength = "12" size = "12" value="<?php echo $this->data['value']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_is_in_price'><?php esc_html_e( 'Входит в цену', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="tax[is_in_price]" id='option_is_in_price'>
						<option value="1" <?php selected($this->data['is_in_price'], 1); ?> ><?php _e( 'Да', 'usam'); ?></option>
						<option value="0" <?php selected($this->data['is_in_price'], 0); ?>><?php _e( 'Нет', 'usam'); ?></option>						
					</select>	
				</div>
			</div>
		</div>	
		<?php
    }
}
?>