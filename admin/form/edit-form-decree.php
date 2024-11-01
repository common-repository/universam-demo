<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_decree extends USAM_Edit_Form_Document
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Приказ №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );
		else
			$title = __('Добавить приказ', 'usam');	
		return $title;
	}
	
	protected function data_default()
	{
		return ['type' => 'decree', 'document_content' => ''];
	}
	
	protected function add_document_data(  )
	{
		$this->blocks['contacts'] = __( 'Участники', 'usam');
		$this->data['document_content'] = usam_get_document_content( $this->id, 'document_content' );	
	}	
	
	function display_document_footer()
	{
		?>	
		<usam-box :id="'usam_document_attachments'" :title="'<?php _e( 'Файлы приказа', 'usam'); ?>'" v-if="data.id>0">
			<template v-slot:body>
				<?php $this->display_attachments(); ?>				
			</template>
		</usam-box>
		<usam-box :id="'usam_document_content'" :title="'<?php _e( 'Содержание приказа', 'usam'); ?>'">
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