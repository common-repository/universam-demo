<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_decree extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'decree', 'document_content' => ''];
	}
	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Приказ №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );
	}	
	
	protected function add_document_data(  )
	{
		$this->tabs = [
			['slug' => 'document_content', 'title' => __('Содержание приказа','usam')],
			['slug' => 'files', 'title' => __('Файлы','usam')." <span class='number_events' v-if='files.length'>{{files.length}}</span>"],
			['slug' => 'change', 'title' => __('Изменения','usam')],			
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];	
		$this->data['document_content'] = usam_get_document_content( $this->id, 'document_content' );	
	}
	
	protected function get_edit()
	{  
		if ( $this->data['status'] == 'subscribe' )
			return false;
		else
			return true;
	}
	
	protected function main_content_cell_1()
	{	
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">			
			<?php 
			$this->display_status();
			$this->display_manager_box(	__( 'Ответственный','usam'), __( 'Выбрать ответственного','usam') );
			?>			
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Ваша фирма','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>			
		</div>		
		<?php	
	}	
	
	protected function main_content_cell_2()
	{	
		?>	
		<h3><?php esc_html_e( 'Участники', 'usam'); ?></h3>
		<div class = "view_data">				
			<?php 
			foreach ( $this->data['contacts'] as $contact )
			{ 					
				?>			
				<div class ="view_data__row">
					<div class ="view_data__name"><a href="<?php echo usam_get_contact_url( $contact->id ); ?>"><?php echo $contact->appeal; ?></a></div>
					<div class ="view_data__option">							
						<?php echo usam_get_contact_metadata($contact->id, 'mobile_phone' );?>
					</div>
				</div>
				<?php 				
			}
			?>
		</div>		
		<?php	
	}	
}
?>