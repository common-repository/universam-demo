<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_order_contractor extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf('%s №%s %s. %s', usam_get_document_name($this->data['type']), $this->data['number'], '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_date( $this->data['date_insert'], "d.m.Y" ).'</span>', "&#171;".$this->data['name']."&#187;" );
	}	
	
	protected function data_default()
	{
		return ['type' => 'order_contractor', 'contract' => 0, 'customer_type' => 'company'];
	}
	
	protected function add_document_data(  )
	{	
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );		
		$this->js_args['company'] = usam_get_company( $this->data['customer_id'] );	
		if ( $this->js_args['company'] )
		{
			$this->js_args['company']['logo'] = usam_get_company_logo( $this->data['customer_id'] );
			$this->js_args['company']['url'] = usam_get_company_url( $this->data['customer_id'] );	
		}
		$this->add_products_document();
	}
	
	protected function main_content_cell_2()
	{			
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
				<div class ="view_data__name"><?php _e( 'Покупатель','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Поставщик','usam'); ?>:</div>
				<div class ="view_data__option"><?php $this->display_customer( $this->data['customer_id'], $this->data['customer_type'] ); ?></div>
			</div>		
		</div>		
		<?php	
	}	
}
?>