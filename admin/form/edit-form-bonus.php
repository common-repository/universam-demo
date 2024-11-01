<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_bonus extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить бонус','usam');
		else
			$title = __('Добавить бонус', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_bonus( $this->id );
		}
		else	
			$this->data = ['id' => 0,  'user_id' => 0, 'order_id' => '', 'payment_order_id' => 0, 'bonus' => '', 'status' => 0, 'type' => 0, 'use_date' => 0];
	}	
	
	function display_settings()
	{		
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Клиент','usam'); ?>:</div>
				<div class ="option user_autocomplete" user_id="<?php echo $this->data['user_id']; ?>">
					<?php 
					$selected = '';
					if ( $this->data['user_id'] )
					{
						$user = get_user_by('id', $this->data['user_id']);	
						$selected = $user->user_nicename;
					}
					?> 
					<autocomplete :selected="'<?php echo $selected; ?>'" @change="change" :request="'users'"></autocomplete>
					<input type="hidden" name="data[user_id]" v-model="user_id"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_order_id'><?php esc_html_e( 'Заказ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_order_id' name="data[order_id]" value="<?php echo $this->data['order_id']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='bonus_number'><?php esc_html_e( 'Количество бонусов', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="bonus_number" name="data[bonus]" value="<?php echo $this->data['bonus']; ?>">
				</div>
			</div>			
		</div>		
		<?php			
	}

	function display_left()
	{			
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
}
?>