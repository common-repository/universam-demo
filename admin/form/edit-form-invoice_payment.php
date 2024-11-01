<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_invoice_payment extends USAM_Edit_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf( __('Счет за %s','usam'), '<span v-html="data.name"></span>').'</span><span v-else>'.sprintf('Добавить %s', mb_strtolower(usam_get_document_name($this->data['type'])) ).'</span>';
	}
	
	protected function data_default()
	{
		return ['type' => 'invoice_payment', 'closedate' => date( "Y-m-d H:i:s"), 'contract' => 0, 'conditions' => ''];
	}
	
	protected function add_document_data(  )
	{
		$this->placeholder = __('За что платим', 'usam');
		$this->blocks['contacts'] = __( 'Подписи от', 'usam');
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );	
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
			<div class ="edit_form__item_name"><?php _e( 'Продавец','usam'); ?>:</div>
			<div class ="edit_form__item_option"><?php $this->section_customers(); ?></div>
		</div>
		<?php 		
		$this->display_document_contract(); ?>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Сумма к оплате','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.totalprice'>
			</div>
		</label>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Срок','usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<datetime-picker v-model="data.closedate"/>
			</div>
		</div>
		<?php		
    }
	
	function display_document_footer()
	{
		?>		
		<usam-box :id="'usam_document_attachments'" :title="'<?php _e( 'Счет на оплату', 'usam'); ?>'">
			<template v-slot:body>
				<?php $this->display_attachments(); ?>				
			</template>
		</usam-box>
		<?php
		
	}
}
?>