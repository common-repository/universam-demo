<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Creating_Rule_Coupon extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило &#171;%s&#187','usam'), $this->data['title'] );
		else
			$title = __('Добавить правило', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_coupons_roles');
		else
			$this->data = ['active' => 0, 'title' => __('Новое правило', 'usam'), 'rule_type' => '', 'discount' => '', 'discount_type' => 0, 'day' => 7, 'user_id' => 0,'totalprice' => '', 'percentage_of_use' => '', 'roles' => [], 'sales_area' => [], 'subject' => '', 'message' => '', 'sms_message' => '', 'format' => 'U*********', 'type_format' => 'n'];
	}	
			
	function display_message(  )
	{			
		usam_list_order_shortcode();
		?>			
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="option_subject"><?php esc_html_e( 'Тема письма', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_subject" class = "subject" name="subject" type='text' value='<?php echo esc_attr($this->data['subject']); ?>' />
				</div>
			</div>
			<div>
			<?php                  
			wp_editor(stripslashes(str_replace('\\&quot;','',$this->data['message'])), 'coupons_roles_message', array(
				'textarea_name' => 'message',
				'media_buttons' => false,
				'textarea_rows' => 10,
				'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
				)	
			);
			?>     
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="sms"><?php esc_html_e( 'СМС сообщение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="sms"  class="sms_message" rows="8" name="sms_message"><?php echo esc_textarea( $this->data['sms_message'] ); ?></textarea>
					<div id="characters" class="character"><?php echo mb_strlen($this->data['sms_message']); ?></div>
				</div>
			</div>
		</div>	
		<?php	
	}	
	
	public function display_conditions_creating_coupon( ) 
	{			
		$this->checklist_meta_boxs(['roles' => $this->data['roles'], 'sales_area' => $this->data['sales_area']]); 
	}	
	
	function display_left()
	{					
		$this->titlediv( $this->data['title'] );					
		
		if ( $this->id != null )
			$coupon = usam_get_coupon( $this->data['coupon_id'] );
		else			
			$coupon = array( 'max_is_used' => 1, 'amount_bonuses_author' => '' );	
		
		$conditions = usam_get_coupon_metadata( $this->id, 'conditions' );
		
		usam_add_box( 'usam_coupon_data_settings', __('Параметры купона','usam'), array( $this, 'coupon_data_settings' ) );	
		usam_add_box( 'usam_condition', __('Условия использования','usam'), array( $this, 'display_rules_work_basket' ), $conditions );	
		usam_add_box( 'usam_conditions_creating_coupon', __('Условия создания купона','usam'), array( $this, 'display_conditions_creating_coupon' ) );		
		usam_add_box( 'usam_options', __('Сообщение клиентам о купоне','usam'), array( $this, 'display_message' ) );			
    }		
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>