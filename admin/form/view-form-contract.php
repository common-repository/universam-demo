<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_contract extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'contract', 'closedate' => '', 'document_content' => ''];
	}
	
	protected function get_title_tab()
	{ 	
		return sprintf('%s №%s %s. %s', usam_get_document_name('contract'), $this->data['number'], '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_date( $this->data['date_insert'], "d.m.Y" ).'</span>', "&#171;".$this->data['name']."&#187;" );
	}	
	
	protected function add_document_data(  )
	{
		$this->tabs = array( 	
			array( 'slug' => 'document_content', 'title' => __('Содержание договора','usam') ),
			array( 'slug' => 'additional_agreements', 'title' => __('Дополнительные соглашения','usam') ),
			['slug' => 'files', 'title' => __('Файлы','usam')." <span class='number_events' v-if='files.length'>{{files.length}}</span>"], 
			array( 'slug' => 'change', 'title' => __('Изменения','usam') ),
			array( 'slug' => 'related_documents', 'title' => __('Документы','usam') ),
		);		
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );	
		$this->data['document_content'] = usam_get_document_content( $this->id, 'document_content' );	
	}	
	
	public function display_tab_additional_agreements( )
	{		
		$this->list_table( 'additional_agreements' );		
	}
}
?>