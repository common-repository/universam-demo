<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-document.php' );
class USAM_Form_movement extends USAM_View_Form_Document
{		
	protected function data_default()
	{
		return ['type' => 'movement', 'from_storage' => '', 'for_storage' => '', 'name' => __( 'Новое перемещение', 'usam')];
	}
	
	protected function add_document_data(  )
	{	
		$this->tabs = [
			array( 'slug' => 'products_document', 'title' => __('Товары','usam') ),			
			array( 'slug' => 'change', 'title' => __('Изменения','usam') ),
			array( 'slug' => 'related_documents', 'title' => __('Документы','usam') ),
		];
		$this->js_args['from_storage'] = usam_get_storage( $this->data['from_storage'] );	
		if( $this->js_args['from_storage'] )
		{
			$location = usam_get_location( $this->js_args['from_storage']['location_id'] );
			$this->js_args['from_storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['from_storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['from_storage']['id'], 'address');
		}
		$this->js_args['for_storage'] = usam_get_storage( $this->data['for_storage'] );	
		if( $this->js_args['for_storage'] )
		{
			$location = usam_get_location( $this->js_args['for_storage']['location_id'] );
			$this->js_args['for_storage']['city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
			$this->js_args['for_storage']['address'] = (string)usam_get_storage_metadata( $this->js_args['for_storage']['id'], 'address');
		}
		$this->add_products_document();
	}
	
	protected function main_content_cell_1()
	{	
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">			
			<?php 
			$this->display_status();
			$this->display_manager_box();
			$this->display_groups();
			?>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span>
				</div>
			</div>	
		</div>				
		<?php	
	}
		
	protected function main_content_cell_2()
	{	
		?>	
		<h3><?php esc_html_e( 'Движение', 'usam'); ?></h3>
		<div class = "view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Фирма','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php 		
					$company = usam_get_company( $this->data['customer_id'] );			
					if ( !empty($company) )	
					{	
						echo "<a href='".usam_get_company_url( $this->data['customer_id'] )."' target='_blank'>".$company['name']."</a>";
					}
					?>
				</div>
			</div>		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Со склада','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php $storage_id = usam_get_document_metadata($this->id, 'from_storage'); ?>
					<?php echo usam_get_store_field( $storage_id, 'title' ) ?>
				</div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'На склад','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php $storage_id = usam_get_document_metadata($this->id, 'for_storage'); ?>
					<?php echo usam_get_store_field( $storage_id, 'title' ) ?>
				</div>
			</div>	
		</div>		
		<?php	
	}	
	
	public function display_tab_products_document( )
	{		
		require_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-movement.php' );
	}
}
?>