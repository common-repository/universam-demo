<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_invoice_payment extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'invoice_payment', 'closedate' => date( "Y-m-d H:i:s"), 'contract' => 0, 'conditions' => ''];
	}	
	
	protected function add_document_data(  )
	{
		$this->tabs = [
			array( 'slug' => 'matching', 'title' => __('Согласование','usam') ),
			array( 'slug' => 'change', 'title' => __('Изменения','usam') ),
			array( 'slug' => 'related_documents', 'title' => __('Документы','usam') ),
		];
		$this->data['your_decision'] = 'not';	
		$contact_id = usam_get_contact_id();	
		foreach ( $this->data['contacts'] as $contact )
		{
			if ( $contact->id == $contact_id )
			{
				$this->data['your_decision'] = usam_get_document_metadata($this->id, 'matching_'.$contact->id);  
				break;
			}
		}
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );	
	}
	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Счет за %s','usam'), mb_strtolower($this->data['name']) );
	}	
	
	protected function main_content_cell_1()
	{	
		$reason_payment = usam_get_document_metadata($this->id, 'reason_payment'); 
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">						
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Номер счета','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['number']; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Дата','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_insert'], "d.m.Y" ); ?></div>
			</div>
			<?php 
			$this->display_status();
			$this->display_manager_box(	__( 'Инициатор','usam'), __( 'Выбрать инициатора','usam') );
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
			<?php $this->display_groups(); 	?>
		</div>		
		<?php	
	}
	
	protected function main_content_cell_2()
	{			
		$currency = usam_get_document_metadata($this->id, 'currency');
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo usam_currency_display( $this->data['totalprice'], ['currency' => $currency]); ?></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Плательщик','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Продавец','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php 							
					$display_text = __('Еще не выбрано','usam');
					if ( $this->data['customer_type'] == 'contact' )
					{
						$contact = usam_get_contact( $this->data['customer_id'] );			
						if ( !empty($contact) )	
						{	
							$display_text = "<a href='".usam_get_contact_url( $this->data['customer_id'] )."' target='_blank'>".$contact['appeal']."</a>";	
						}
					}
					else
					{								
						$company = usam_get_company( $this->data['customer_id'] );			
						if ( !empty($company) )	
						{	
							$display_text = "<a href='".usam_get_company_url( $this->data['customer_id'] )."' target='_blank'>".$company['name']."</a>";	
						}
					}
					echo $display_text; ?>
				</div>
			</div>		
		</div>		
		<?php	
	}
		
	function display_tab_matching()
	{ 
		$contact_id = usam_get_contact_id();
		?>	
		<h3><?php esc_html_e( 'Счет на оплату', 'usam'); ?></h3>		
		<?php $this->display_attachments(); ?>			
		<div class="matching_document" v-if="yourDecision!=='not'">				
			<div class="matching_document_result">	
				<div class="matching_document__title" v-if="yourDecision=='approve'"><?php esc_html_e( 'Счет вами утвержден', 'usam'); ?></div>
				<div class="matching_document__title" v-if="yourDecision=='declained'"><?php esc_html_e( 'Счет вами отклонен', 'usam'); ?></div>
			</div>
			<div class="matching_document__box" v-if="!yourDecision">
				<div class="matching_document__title"><?php echo empty($matching_document) ? esc_html__('Требуется ваше решение', 'usam') : esc_html__('Изменить решение', 'usam'); ?></div>
				<div class="matching_document__buttons">					
					<div class="matching_document__button background_green"><a class="color_green" @click="approve"><?php esc_html_e( 'Утвердить', 'usam'); ?></a></div>
					<div class="matching_document__button background_brown"><a class="color_brown" @click="doNotApprove"><?php esc_html_e( 'Отклонить счет', 'usam'); ?></a></div>
				</div>
			</div>						
		</div>
		<div class = "view_data matching_document_lists">				
			<?php 
			foreach ( $this->data['contacts'] as $contact )
			{ 					
				?>			
				<div class ="view_data__row">
					<div class ="view_data__name"><a href="<?php echo usam_get_contact_url( $contact->id ); ?>"><?php echo $contact->appeal; ?></a></div>
					<div class ="view_data__option">							
						<?php 
						$matching = usam_get_document_metadata($this->id, 'matching_'.$contact->id); 
						switch ( $matching ) 
						{		
							case 'approve' :
								?><span class="matching_document_status background_green color_green"><?php esc_html_e( 'Согласовано', 'usam'); ?></span><?php
							break;		
							case 'declained' :
								?><span class="matching_document_status background_brown color_brown"><?php esc_html_e( 'Отклонено', 'usam'); ?></span><?php
							break;	
							default:
								?><span class="matching_document_status background_blue color_blue"><?php esc_html_e( 'На рассмотрении', 'usam'); ?></span><?php
							break;
						}							
						?>
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