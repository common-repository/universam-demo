<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_reconciliation_act extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'reconciliation_act', 'contract' => '', 'start_date' => '', 'end_date' => ''];
	}
	
	protected function add_document_data(  )
	{
		$this->tabs = [
			['slug' => 'subordinate_documents', 'title' => __('Документы сверки','usam')],			
			['slug' => 'change', 'title' => __('Изменения','usam')],
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i:s" );		
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i:s" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );
	}
	
	protected function get_edit()
	{  
		if ( $this->data['status'] == 'paid' )
			return false;
		else
			return true;
	}
	
	protected function main_content_cell_1()
	{	
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
		$start_date = usam_get_document_metadata( $this->id, 'start_date' );
		$end_date = usam_get_document_metadata( $this->id, 'end_date' );
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">						
			<?php 
			$this->display_status();
			$this->display_manager_box();			
			?>				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Дата начала', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $start_date, 'd.m.Y' ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Конец периода', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $end_date, 'd.m.Y' ); ?></div>
			</div>	
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
	
	protected function main_content_cell_2()
	{			
		$end_date = usam_get_document_metadata( $this->id, 'end_date' );
		$end_date = usam_local_date( $end_date, 'd.m.Y' );
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Оборот','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Продавец','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'], true, false ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Покупатель','usam'); ?>:</div>
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
		$end_balance = (float)usam_get_document_metadata($this->id, 'end_balance' );	
		$display_text = '';
		if ( $this->data['customer_type'] == 'contact' )
		{
			$contact = usam_get_contact( $this->data['customer_id'] );			
			if ( !empty($contact) )	
				$display_text = "<a href='".usam_get_contact_url( $this->data['customer_id'] )."' target='_blank'>".$contact['appeal']."</a>";
		}
		else
		{								
			$company = usam_get_company( $this->data['customer_id'] );			
			if ( !empty($company) )	
				$display_text = "<a href='".usam_get_company_url( $this->data['customer_id'] )."' target='_blank'>".$company['name']."</a>";
		}		
		if ( $end_balance > 0 )
			printf( __('На %s задолженность %s в пользу %s', 'usam'), $end_date, $this->currency_display( $end_balance ), $display_text); 
		elseif ( $end_balance == 0 )
			printf( __('На %s задолженность отсутствует', 'usam'), $end_date); 
		else
		{
			printf( __('На %s задолженность %s в пользу %s', 'usam'), $end_date, $this->currency_display( abs($end_balance) ), usam_get_display_company_by_acc_number( $this->data['bank_account_id'], false, false )); 
		}		
	}
		
	function display_tab_subordinate_documents()
	{ 	
		$start_date = usam_get_document_metadata($this->id, 'start_date'); 	
		$start_date = $start_date?usam_local_date( $start_date, get_option( 'date_format', 'Y/m/d' ) ):'';
		$end_date = usam_get_document_metadata($this->id, 'end_date' ); 
		$end_date = $end_date?usam_local_date( $end_date, get_option( 'date_format', 'Y/m/d' ) ):'';
		$start_balance = usam_get_document_metadata($this->id, 'start_balance' );
		$end_balance = (float)usam_get_document_metadata($this->id, 'end_balance' );		
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="column-n">№</td>
					<td><?php _e( 'Наименование операции, документы', 'usam') ?></td>
					<td><?php _e( 'Дебет', 'usam') ?></td>
					<td><?php _e( 'Кредит', 'usam') ?></td>				
				</tr>
			</thead>
			<tbody>			
				<tr class="results_line">
					<td></td>
					<td><?php _e( 'Сальдо', 'usam') ?></td>
					<td><?php echo $this->currency_display( $start_balance ); ?></td>
					<td></td>
				</tr>
				<?php 
				require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
				$v = apply_filters('usam_reconciliation_documents', 'act' );
				$documents = usam_get_documents(['child_document' => ['id' => $this->id, 'type' => $this->data['type'], 'link_type' => 'subordinate'], 'orderby' => 'date_insert']);
				$i = 0;
				$sum1 = 0;
				$sum2 = 0;
				foreach( $documents as $document ) 
				{ 
					$detail = usam_get_details_document( $document->type );
					$i++;					
					?>
					<tr>
						<td class="column-n"><?php echo $i; ?></td>
						<td>
							<p style="margin:0 0 2px 0"><?php echo $detail['single_name']; ?></p>
							<p style="margin:0"><a href="<?php echo usam_get_document_url( $document ); ?>"><?php echo '№'.$document->number.' '.usam_local_date($document->date_insert, "d.m.Y"); ?></a></p>
						</td>
						<?php if ( $v == 'payment_received' ) { ?>	
							<?php if ( $document->type != 'payment_received' ) { ?>						
								<?php $sum1 += $document->totalprice; ?>
								<td><?php echo $this->currency_display( $document->totalprice ); ?></td>
								<td></td>
							<?php } else { ?>
								<?php $sum2 += $document->totalprice; ?>
								<td></td>
								<td><?php echo $this->currency_display( $document->totalprice ); ?></td>
							<?php } ?>
						<?php } else { ?>
							<?php if ( $document->type == 'act' ) { ?>						
								<?php $sum1 += $document->totalprice; ?>
								<td><?php echo $this->currency_display( $document->totalprice ); ?></td>
								<td></td>
							<?php } else { ?>
								<?php $sum2 += $document->totalprice; ?>
								<td></td>
								<td><?php echo $this->currency_display( $document->totalprice ); ?></td>
							<?php } ?>
						<?php } ?>
					</tr>
				<?php } ?>
				<tr class="results_line">
					<th></th>
					<th>
						<p style="margin:0 0 2px 0"><?php _e( 'Обороты за период с', 'usam') ?></p>
						<p style="margin:0"><?php printf( __( '%s по %s', 'usam'), $start_date, $end_date) ?></p>
					</th>
					<th><?php echo $this->currency_display( $sum1 ); ?></th>
					<th><?php echo $this->currency_display( $sum2 ); ?></th>
				</tr>
			</tbody>
			<tfoot>				
				<tr>
					<td></td>
					<td><?php _e( 'Сальдо', 'usam') ?></td>
					<?php if ( $end_balance < 0 ) { ?>						
						<td><?php echo $this->currency_display( abs($end_balance) ); ?></td>
						<td></td>
					<?php } else { ?>					
						<td></td>
						<td><?php echo $this->currency_display( abs($end_balance) ); ?></td>
					<?php } ?>	
				</tr>
			</tfoot>
		</table>
		<?php 		
	}
}
?>