<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_invoice extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf('%s №%s %s. %s', usam_get_document_name('invoice'), $this->data['number'], '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_date( $this->data['date_insert'], "d.m.Y" ).'</span>', "&#171;".$this->data['name']."&#187;" );
	}

	protected function data_default()
	{
		return ['type' => 'invoice', 'closedate' => date("Y-m-d H:i:s", strtotime('+5 days')), 'contract' => 0, 'conditions' => ''];
	}
	
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );		
		$this->add_products_document();
	}
	
	protected function get_edit()
	{  
		if ( $this->data['status'] == 'paid' )
			return false;
		else
			return true;
	}	
	
	protected function get_document_toolbar_buttons()
	{  
		return [['action' => 'addAct', 'title' => esc_html__('Сделать акт', 'usam'), 'capability' => 'add_act']];	
	}	
	
	protected function main_content_cell_1()
	{			
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">						
			<?php 
			$this->display_status();
			$this->display_manager_box();	
			?>
			<?php if ( !empty($this->data['closedate']) ) { ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Срок оплаты','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo empty($this->data['closedate'])?'':date_i18n("d.m.Y H:i", strtotime(get_date_from_gmt($this->data['closedate'], "Y-m-d H:i:s"))); ?>
				</div>
			</div>		
			<?php } ?>	
			<?php if ( $contract_id ) { 
				$contract_document = usam_get_document( $contract_id );
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'Оплата по договору','usam'); ?>:</div>
					<div class ="view_data__option">
						<?php echo $contract_document['name']." ".__("от","usam")." ".usam_local_date($contract_document['date_insert'], 'd.m.Y'); ?>
					</div>
				</div>		
			<?php } ?>
			<?php $this->display_groups( admin_url("admin.php?page=crm&tab=invoice") ); ?>			
		</div>				
		<?php	
	}
}
?>