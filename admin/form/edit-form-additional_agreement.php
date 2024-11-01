<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_additional_agreement extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{		
		$contract = 0;
		if ( empty($this->data['id']) && isset($_GET['contract_id']) )
			$contract = absint($_GET['contract_id']);
		return ['type' => 'additional_agreement', 'closedate' => '', 'document_content' => '', 'contract' => $contract];
	}
	
	protected function add_document_data(  )
	{
		$contract = usam_get_document( $this->data['contract'] );	
		if ( $contract )
		{
			$this->data['bank_account_id'] = $contract['bank_account_id'];
			$this->data['customer_type'] = $contract['customer_type'];
			$this->data['customer_id'] = $contract['customer_id'];
		}
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = $contract;	
		$this->data['document_content'] = usam_get_document_content( $this->id, 'document_content' );		
	}	

	function display_document_properties()
	{
		?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Договор','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="related_document" v-if="data.contract>0" @click="sidebar('contracts')">
					<div class="related_document__document">
						<div class="related_document-title">
							<div class="related_document__document_type_name">№ {{contract.number}}</div>
							<div class="related_document__document_title" v-html="contract.name"></div>
						</div>
						<div class="related_document__document_date">
							<?php esc_html_e( 'Дата создания', 'usam'); ?>: <span class="related_document__date_insert">{{localDate(contract.date_insert,'d.m.Y')}}</span>
						</div>
					</div>
				</div>				
				<a v-else @click="sidebar('contracts')"><?php esc_html_e( 'Выбрать договор', 'usam'); ?></a>				
			</div>
		</div>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Сумма соглашения','usam'); ?>:</div>
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
		add_action('usam_after_form',function() {
			require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-contracts.php' );
		});		
    }
	
	function display_document_footer()
	{
		?>	
		<usam-box :id="'usam_document_attachments'" :title="'<?php _e( 'Файлы договора', 'usam'); ?>'" v-if="data.id>0">
			<template v-slot:body>
				<?php $this->display_attachments(); ?>				
			</template>
		</usam-box>
		<usam-box :id="'usam_document_content'" :title="'<?php _e( 'Содержание договора', 'usam'); ?>'">
			<template v-slot:body>
				<?php 
				wp_editor( $this->data['document_content'], 'document_content', array(
					'textarea_name' => 'document_content',
					'media_buttons' => false,
					'textarea_rows' => 30,	
					'wpautop' => 0,							
					'tinymce' => array(
						'theme_advanced_buttons3' => 'invoicefields,checkoutformfields',
						)
					)
				 ); 
				?>				
			</template>
		</usam-box>
		<?php			
	}
}