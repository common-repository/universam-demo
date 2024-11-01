<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );		
class USAM_Form_Payment extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 			
		if ( empty($this->data) )	
		{
			return __('Документ не существует', 'usam');				
		}			
		if ( $this->id != null )
			return sprintf('%s № %s %s', usam_get_document_name('payment'), '<span class="number">'.$this->data['number'].'</span>', '<span class="subtitle_date">'.__('от','usam').' '.usam_local_date($this->data['date_insert'], "d.m.Y").'</span>' );
		else
			return __('Добавить оплату', 'usam');
	}	
	
	protected function get_data_tab(  )
	{				
		if ( $this->id )
		{ 
			$this->data = usam_get_payment_document( $this->id );				
			if ( empty($this->data) )	
			{
				$this->not_exist = true;
				return false;
			}	
			if ( $this->viewing_not_allowed() )
			{
				$this->data = [];
				return;
			}
		}
		else
		{
			$this->id = 0;							
			$this->data = ['id' => 0, 'date_insert' => '', 'date_payed' => '', 'bank_account_id' => '', 'name' => '', 'number' => '', 'document_id' => 0, 'gateway_id' => '', 'sum' => '', 'status' => '', 'transactid' => '', 'external_document' => '', 'payment_type' => 0];			
		}	
	}	
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'payment', 'view' );
	}
	
	protected function get_toolbar_buttons( ) 
	{ 
		$links = [
			['name' => __('Посмотреть','usam'), 'action_url' => usam_get_document_url( $this->id, 'payment' ), 'display' => 'not_null', 'capability' => 'view_payment'], 
		];
		return $links;
	}
			
	private function disabled() 
	{ 
		echo $this->id?'disabled = "disabled"':'';
	}	
	
	public function display_document_metabox() 
	{    	
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Номер документа', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="_payment[number]" value="<?php echo $this->data['number']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Дата создания', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php usam_display_date_picker( 'insert', $this->data['date_insert'] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Расчетный счет', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">				
					<?php usam_select_bank_accounts( $this->data['bank_account_id'], array('name' => "_payment[bank_account_id]") ) ?>
				</div>
			</div>					
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php _e( 'Номер внешнего документа', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input maxlength = "50" type="text" name="_payment[external_document]" value="<?php echo $this->data['external_document']; ?>">
				</div>
			</div>			
		</div>
		<?php
	}	
	
	public function display_document_payment() 
	{    	
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Варианты оплаты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $payment_types = usam_get_payment_types(); ?>
					<select name="_payment[payment_type]">
						<?php												
						foreach ( $payment_types as $key => $payment_type ) 
						{
							?><option value='<?php echo $key; ?>' <?php selected($this->data['payment_type'],$key); ?>><?php echo $payment_type; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Способ оплаты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_payment_gateway_dropdown( $this->data['gateway_id'], array('name' => "_payment[gateway_id]") ) ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Стоимость', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input size = "10" maxlength = "10" type="text" name="_payment[sum]" required value="<?php echo $this->data['sum']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php echo usam_get_status_dropdown('payment', $this->data['status'], ['name' => "_payment[status]", 'id' => "usam-payment_status"]); ?>
				</div>
			</div>
			<?php if ( $this->data['transactid'] ){ ?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label><?php _e( 'Номер транзакции', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input maxlength = "50" type="text" name="_payment[transactid]" value="<?php echo $this->data['transactid']; ?>">
					</div>
				</div>
			<?php } ?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php _e( 'Дата оплаты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'date_payed-'.$this->id, $this->data['date_payed'] ); ?>
				</div>
			</div>		
		</div>
		<?php
	}
			
	function display_left()
	{			
		usam_add_box( 'usam_payment_general_information', __('Общая информация', 'usam'), array( $this, 'display_document_metabox' ));
		usam_add_box( 'usam_document_payment', __('Оплата', 'usam'), array( $this, 'display_document_payment' ));	
	}	
}
?>