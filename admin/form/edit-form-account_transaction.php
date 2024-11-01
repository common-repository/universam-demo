<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_account_transaction extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf(__('Изменить транзакцию по счету &#8220;%s&#8221;','usam'), $this->data['account_id']);
		else
			$title = __('Добавить транзакцию', 'usam');	
		return $title;
	}
		
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
			$this->data = usam_get_account_transaction( $this->id );
		else	
			$this->data = array( 'account_id' => '', 'description' => '', 'sum' => '0.00', 'type_transaction' => 0, 'order_id' => '' ); 
	}	

	function display_settings()
	{		
		?>
		<div class="edit_form">
			<?php if ( $this->id === null ) { ?>			
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='type_transaction'><?php esc_html_e( 'Тип транзакции', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select name = "type_transaction" id="type_transaction">							
							<option value='0' <?php selected($this->data['type_transaction'], 0); ?>><?php _e('Пополнение', 'usam'); ?></option>
							<option value='1' <?php selected($this->data['type_transaction'], 1); ?>><?php _e('Списание', 'usam'); ?></option>
						</select>	
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Номер счета', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<?php $url = add_query_arg(['action' => 'autocomplete', 'get' => 'customer_account', 'security' => wp_create_nonce('customer_account')], admin_url( 'admin-ajax.php', 'relative' )); ?>
						<input type="text" id='option_code' required name="account_id" autocomplete="off" class='js-autocomplete' value="<?php echo $this->data['account_id']; ?>" data-url='<?php echo $url; ?>'/>
					</div>
				</div>	
			<?php } ?>						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sum'><?php esc_html_e( 'Сумма', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_sum" name="sum" value="<?php echo $this->data['sum']; ?>" maxlength="5"/>
				</div>
			</div>			
		</div>		
		<?php			
	}

	function display_left()
	{			
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );
		$this->add_box_description( $this->data['description'] );			
    }
}
?>