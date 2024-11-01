<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_suggestion extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf( __('Предложение №%s от %s %s','usam'), $this->data['number'], '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_date( $this->data['date_insert'], "d.m.Y" ).'</span>', "&#171;".$this->data['name']."&#187;" );
	}	
	
	protected function data_default()
	{
		return ['type' => 'suggestion', 'closedate' => date( "Y-m-d H:i:s"), 'conditions' => ''];
	}	
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );	
		$this->add_products_document();
	}
}
?>