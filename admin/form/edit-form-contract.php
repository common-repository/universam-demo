<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_contract extends USAM_Edit_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'contract', 'closedate' => '', 'document_content' => ''];
	}
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );	
		$this->data['document_content'] = usam_get_document_content( $this->id, 'document_content' );	
	}	

	function display_document_properties()
	{					
		$this->display_document_counterparties(); ?>
		<label class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Сумма договора','usam'); ?>:</div>
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
		<usam-box :id="'usam_document_attachments'" :title="'<?php _e( 'Файлы договора', 'usam'); ?>'" v-if="data.id>0">
			<template v-slot:body>
				<?php $this->display_attachments(); ?>				
			</template>
		</usam-box>
		<usam-box :id="'usam_document_additional_agreement'" :title="`<?php _e( 'Дополнительные соглашения', 'usam'); ?> <a target='_blank' href='<?php echo add_query_arg(['form_name' => 'additional_agreement', 'id' => 0]); ?>&contract_id=`+data.id+`'><?php _e( 'Добавить', 'usam'); ?></a>`" v-if="data.id>0">
			<template v-slot:body>
				<div class="related_documents related_documents_row">
					<div class="related_document" v-for="(document, i) in agreements">
						<a class="related_document__document" target='_blank' :href="'<?php echo add_query_arg(['form_name' => 'additional_agreement', 'form' => 'view']); ?>&id='+data.id">
							<div class="related_document-title">
								<div class="related_document__document_type_name">№ {{document.number}}</div>
								<div class="related_document__document_title" v-html="document.name"></div>
							</div>
							<div class="related_document__document_date">
								<?php esc_html_e( 'Дата создания', 'usam'); ?>: <span class="related_document__date_insert">{{localDate(document.date_insert,'d.m.Y')}}</span>
							</div>
						</a>
					</div>	
				</div>
			</template>
		</usam-box>
		<usam-box :id="'usam_document_content'" :title="'<?php _e( 'Содержание', 'usam'); ?>'">
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
?>