<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-property_group.php' );
class USAM_Form_order_property_group extends USAM_Form_Property_Groups
{		
	protected $group_type = 'order';
	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить группу свойств','usam');
		else
			$title = __('Добавить группу свойств', 'usam');	
		return $title;
	}	
	
	public function extra_options( )
	{			
		$type_payer_ids = usam_get_array_metadata($this->id, 'property_group', 'type_payer');
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_field_type_payer'><?php esc_html_e( 'Тип плательщика', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "type_payer[]" id="order_field_type_payer" multiple>						
						<?php				
						$types_payers = usam_get_group_payers();
						foreach( $types_payers as $value )
						{						
							?><option value="<?php echo $value['id']; ?>" <?php selected( in_array($value['id'], $type_payer_ids) ) ?> ><?php echo $value['name']; ?></option><?php
						}
						?>
					</select>	
				</div>
			</div>			
		</div>
      <?php
	}   
	
	function display_left()
	{		
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_property_group', __('Параметры','usam'), array( $this, 'property_group_settings' ) );				
		usam_add_box( 'usam_property_group_payers', __('Дополнительные параметры','usam'), array( $this, 'extra_options' ) );				
    }	
}
?>