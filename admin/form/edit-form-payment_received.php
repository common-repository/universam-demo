<?php			
require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_payment_received extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'payment_received', 'payment_number' => '', 'counterparty_account_number' => '', 'counterparty_bank_bic' => '', 'payment_recipient_type' => 'company', 'payer_status' => '08', 'period' => '', 'okato' => '', 'kbk' => '', 'tax_info_document_date' => '', 'supplier_bill_id' => ''];
	}
	
	protected function add_document_data(  )
	{
		$this->placeholder = __('Назначение платежа', 'usam');		
		if( !empty($this->data['tax_info_document_date']) )
			$this->data['tax_info_document_date'] = get_date_from_gmt( $this->data['tax_info_document_date'], "Y-m-d H:i" );
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );			
	}
		
	function display_document_properties()
	{				
		?>				
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Тип платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.payment_recipient_type'>
					<option value="company"><?php _e( 'Компания','usam'); ?></option>
					<option value="budget"><?php _e( 'Бюджетная организация','usam'); ?></option>
				</select>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Получатель платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.bank_account_id'>
					<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
				</select>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Плательщик','usam'); ?>:</div>
			<div class ="edit_form__item_option"><?php $this->section_customers(); ?></div>
		</div>
		<?php $this->display_document_contract(); ?>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Сумма платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.totalprice'>
			</div>
		</label>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Номер платежа','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<input type='text' v-model='data.payment_number'>
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
	
	function display_document_footer()
	{
		?>		
		<usam-box :id="'usam_payment_on_account'" :title="'<?php _e( 'Оплата счетов', 'usam'); ?>'">
			<template v-slot:body>
			<?php 
				$checklist = [];
				$parent_document_ids = [];
				if( $this->data['id'] )
				{
					$documents = usam_get_documents(['type' => 'invoice', 'customer_type' => $this->data['customer_type'], 'customer_id' => $this->data['customer_id'], 'status' => ['draft', 'sent'], 'conditions' => ['key' => 'sum', 'compare' => '>=', 'value' => $this->data['totalprice']]]);	
					$parent_documents = usam_get_documents(['parent_document' => ['id' => $this->data['id'], 'type' => $this->data['type']]]);		
					$documents = array_merge( $parent_documents, $documents );									
					foreach( $documents as $document )
					{
						$checklist[$document->id] = sprintf( __('%s №%s от %s на сумму %s','usam'), $document->name, $document->number, usam_local_date($document->date_insert, "d.m.Y"), $this->currency_display($document->totalprice) );
					} 					
					foreach( $parent_documents as $document )
					{
						$parent_document_ids[] = $document->id;
					}
				}
				?>
				<div class="categorydiv">
					<div class="tabs-panel">
						<ul id="groups_checklist" class="categorychecklist form-no-clear">
							<?php echo usam_get_checklist( 'document_ids', $checklist, $parent_document_ids ); ?>
						</ul>
					</div>							
				</div>			
			</template>
		</usam-box>
		<?php
		
	}
}
?>