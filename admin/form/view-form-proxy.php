<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_proxy extends USAM_View_Form_Document
{		
	protected function get_title_tab()
	{ 	
		return sprintf( __('Доверенность №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );	
	}	
	
	protected function data_default()
	{
		return ['type' => 'proxy', 'customer_type' => 'company', 'contract' => 0, 'closedate' => date( "Y-m-d H:i:s", strtotime('+5 days'))];
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
				<div class ="view_data__name"><?php _e( 'Стоимость','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Клиент','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Получение от','usam'); ?>:</div>
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
	
	public function display_tab_products_document( )
	{		
		require_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-proxy.php' );
	}
}
?>