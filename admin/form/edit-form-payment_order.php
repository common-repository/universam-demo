<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_payment_order extends USAM_Edit_Form_Document
{	
	protected function data_default()
	{
		return ['type' => 'payment_order', 'payment_number' => '', 'counterparty_account_number' => '', 'counterparty_bank_bic' => '', 'payment_recipient_type' => 'company', 'payer_status' => '08', 'period' => '', 'okato' => '', 'kbk' => '', 'tax_info_document_date' => '', 'supplier_bill_id' => ''];
	}
	
	protected function add_document_data(  )
	{	
		unset($this->blocks['manager']);
		$this->placeholder = __('За что платим?', 'usam');
		if( !empty($this->data['tax_info_document_date']) )
			$this->data['tax_info_document_date'] = get_date_from_gmt( $this->data['tax_info_document_date'], "Y-m-d H:i" );	
	}	

	function display_document_properties()
	{	
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Ваша фирма','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.bank_account_id'>
					<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
				</select>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Получатель платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option"><?php $this->section_customers(); ?></div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Тип платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.payment_recipient_type'>
					<option value="company"><?php _e( 'Компания','usam'); ?></option>
					<option value="budget"><?php _e( 'Бюджетная организация','usam'); ?></option>
				</select>
			</div>
		</div>		
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Номер платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.payment_number' maxlength='6'>
			</div>
		</label>	
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Счёт получателя','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.counterparty_account_number' maxlength='20'>
			</div>
		</label>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'БИК банка получателя','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.counterparty_bank_bic' maxlength='9'>
			</div>
		</label>		
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Сумма платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.totalprice'>
			</div>
		</label>			
		<div class ="edit_form__item" v-if="data.payment_recipient_type=='budget'">
			<div class ="edit_form__item_name"><?php _e( 'Код УИН','usam'); ?></div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.supplier_bill_id'>
			</div>
		</div>	
		<div class ="edit_form__item" v-if="data.payment_recipient_type=='budget'">
			<div class ="edit_form__item_name"><?php _e( 'Дата бюджетного документа','usam'); ?></div>
			<div class ="edit_form__item_option">
				<datetime-picker v-model="data.tax_info_document_date"/>
			</div>
		</div>	
		<div class ="edit_form__item" v-if="data.payment_recipient_type=='budget'">
			<div class ="edit_form__item_name"><?php _e( 'КБК','usam'); ?></div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.kbk'>
			</div>
		</div>	
		<div class ="edit_form__item" v-if="data.payment_recipient_type=='budget'">
			<div class ="edit_form__item_name"><?php _e( 'Налоговый период/Код таможенного органа','usam'); ?></div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.period'>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Код ОКАТО/ОКТМО','usam'); ?></div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.okato'>
			</div>
		</div>			
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Статус плательщика','usam'); ?></div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.payer_status'>
			</div>
		</div>
		<?php
    }		
}
?>