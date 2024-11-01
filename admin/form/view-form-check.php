<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_check extends USAM_View_Form_Document
{		
	protected function add_document_data(  )
	{
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['storage'] = usam_get_storage( $this->data['store_id'] );	
		if( $this->js_args['storage'] )
		{
			$location = usam_get_location( $this->js_args['storage']['location_id'] );
			$this->js_args['storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['storage']['id'], 'address');
		}
		$this->add_products_document();
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
		$store_id = usam_get_document_metadata( $this->id, 'store_id' );
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">						
			<?php 
			$this->display_status();
			$this->display_manager_box(	__( 'Продавец','usam'), __( 'Выбрать продавца','usam') );			
			?>
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
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Магазин','usam'); ?>:</div>
				<div class ="view_data__option"><?php 
				$storage = usam_get_storage( $store_id );
				if ( $storage )
					echo $storage['title']; 
				?></div>
			</div>				
			<?php $payment_type = usam_get_document_metadata( $this->id, 'payment_type' ); ?>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Способ оплаты','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_payment_type_name( $payment_type ); ?></div>
			</div>		
			<?php $shift_id = usam_get_document_metadata( $this->id, 'shift_id' );  ?>	
			<?php if ( $shift_id ) {  ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Номер смены','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $shift_id; ?></div>
			</div>	
			<?php } ?>			
		</div>		
		<?php	
	}
}
?>