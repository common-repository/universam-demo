<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_payment_order extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf( __('Платежное поручение №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );	
	}	
	
	protected function data_default()
	{
		return ['type' => 'payment_order', 'payment_number' => '', 'counterparty_account_number' => '', 'counterparty_bank_bic' => '', 'payment_recipient_type' => 'company', 'payer_status' => '08', 'period' => '', 'okato' => '', 'kbk' => '', 'tax_info_document_date' => '', 'supplier_bill_id' => ''];
	}
	
	protected function add_document_data(  )
	{	
		$this->tabs = [
			['slug' => 'data', 'title' => __('Данные','usam')],
			['slug' => 'change', 'title' => __('Изменения','usam')],
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];
		if( !empty($this->data['tax_info_document_date']) )
			$this->data['tax_info_document_date'] = get_date_from_gmt( $this->data['tax_info_document_date'], "Y-m-d H:i" );	
	}	
	
	protected function get_edit()
	{  
		if ( $this->data['status'] == 'approved' )
			return false;
		else
			return true;
	}
	
	protected function main_content_cell_2()
	{			
		$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 		
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Сумма платежа','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Получатель платежа','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Плательщик','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php 							
					$display_text = __('Еще не выбрано','usam');
					$company = usam_get_company( $this->data['customer_id'] );			
					if ( !empty($company) )	
					{	
						$display_text = "<a href='".usam_get_company_url( $this->data['customer_id'] )."' target='_blank'>".$company['name']."</a>";
					}
					echo $display_text; ?>
				</div>
			</div>	
			<?php 
			if ( $contract_id )
			{	?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'По договору','usam'); ?>:</div>
					<div class ="view_data__option">
						<?php 
							$document = usam_get_document( $contract_id ); 
							echo $document['name'];
						?>
					</div>
				</div>		
			<?php }	?>			
		</div>		
		<?php	
	}
	
	protected function display_tab_data()
	{	
		?>
		<div class = "view_data">			
			<div class ="view_data__row" v-if="data.supplier_bill_id">
				<div class ="view_data__name"><?php _e( 'Код УИН','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice" v-html="data.supplier_bill_id"></span>
				</div>
			</div>
			<div class ="view_data__row" v-if="data.kbk">
				<div class ="view_data__name"><?php _e( 'КБК','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice" v-html="data.kbk"></span>
				</div>
			</div>
			<div class ="view_data__row" v-if="data.okato">
				<div class ="view_data__name"><?php _e( 'Код ОКАТО/ОКТМО','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice" v-html="data.okato"></span>
				</div>
			</div>
		</div>
		<?php	
	}	
}
?>