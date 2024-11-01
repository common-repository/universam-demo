<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_additional_agreement extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'additional_agreement', 'closedate' => '', 'document_content' => '', 'contract' => 0];
	}
	
	protected function add_document_data(  )
	{
		$this->tabs = [
			['slug' => 'document_content', 'title' => __('Содержание','usam')],
			['slug' => 'files', 'title' => __('Файлы','usam')." <span class='number_events' v-if='files.length'>{{files.length}}</span>"],
			['slug' => 'change', 'title' => __('Изменения','usam')],
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];
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
	
	protected function main_content_cell_1()
	{	
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
		$document = usam_get_document( $contract_id );
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">			
			<?php 
			$this->display_status();
			$this->display_manager_box();			
			?>
			<?php if ( !empty($document) ) { ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Договор','usam'); ?>:</div>
				<div class ="view_data__option"><a href='<?php echo usam_get_document_url( $document ); ?>' target='_blank'><?php printf( __('№%s от %s','usam'), $document['number'], usam_local_date( $document['date_insert'], "d.m.Y" ) ); ?></a></div>
			</div>	
			<?php } ?>					
		</div>		
		<?php	
	}
}
?>