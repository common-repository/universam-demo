<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_reconciliation_act extends USAM_Edit_Form_Document
{			
	protected function data_default()
	{
		return ['type' => 'reconciliation_act', 'contract' => '', 'start_date' => '', 'end_date' => ''];
	}
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i:s" );		
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i:s" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );
	}	
	
	protected function title_save_button( ) 
	{ 
		_e('Сформировать','usam');
	}
	
	function display_document_properties()
	{	
		$this->display_document_counterparties();
		$this->display_document_contract();
		?>					
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Период','usam'); ?>:</div>
			<div class ="edit_form__item_option edit_form__item_group">					
				<date-picker v-model="data.start_date"></date-picker> - <date-picker v-model="data.end_date"></date-picker>
			</div>
		</div>
		<?php
    }	
}
?>