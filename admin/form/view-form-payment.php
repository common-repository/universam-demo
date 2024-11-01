<?php
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_payment extends USAM_View_Form
{		
	protected function get_title_tab()
	{ 	
		return sprintf( __('Оплата №%s от %s','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ) );
	}		
		
	protected function get_data_tab(  )
	{					
		$this->data = usam_get_payment_document( $this->id );
		if ( $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		}
		$this->tabs = [
		//	['slug' => 'change', 'title' => __('Изменения','usam')],			
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];		
	}	
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'payment', 'view' );
	}
	
	function currency_display( $price ) 
	{			
		return usam_get_formatted_price( $price, ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false]);		
	}
	
	protected function main_content_cell_1()
	{	
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">			
			<?php $this->display_form_status( $this->data['type'] ); ?>
			<?php if ( $this->data['transactid'] ){ ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'Номер транзакции','usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $this->data['transactid']; ?></div>
				</div>	
			<?php } ?>		
			<?php if ( $this->data['date_payed'] ){ ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'Дата оплаты','usam'); ?>:</div>
					<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_payed'] ); ?></div>
				</div>	
			<?php } ?>
			<?php if ( $this->data['external_document'] ){ ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'Внешний документ','usam'); ?>:</div>
					<div class ="view_data__option">
						<span class="document_totalprice"><?php echo $this->data['external_document']; ?></span>
					</div>
				</div>	
			<?php } ?>
		</div>		
		<?php	
	}
	
	protected function main_content_cell_2()
	{			
		$payment_types = usam_get_payment_types();
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Сумма','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Варианты оплаты','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo isset($payment_types[$this->data['payment_type']])?$payment_types[$this->data['payment_type']]:''; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Способ оплаты','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['name']; ?></div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Расчетный счет','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
		</div>		
		<?php	
	}
		
	function display_tab_related_documents()
	{	
		$this->display_related_documents('payment');
	}	
}
?>