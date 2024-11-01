<?php			
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_shipped extends USAM_View_Form
{
	protected $ribbon = true;	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Отгрузка №%s от %s','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ) );
	}	
		
	protected function get_data_tab(  )
	{					
		$this->data = usam_get_shipped_document( $this->id );		
		if ( $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		}
		$metas = usam_get_shipped_document_metadata( $this->id );
		foreach($metas as $metadata )
			if ( !isset($this->data[$metadata->meta_key]) )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		$this->tabs = [
			['slug' => 'products_document', 'title' => __('Товары','usam')],
		//	['slug' => 'change', 'title' => __('Изменения','usam')],			
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];		
	}
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'shipped', 'view' );
	}

	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null )
		{						
			if( !wp_is_mobile() )
				$this->display_printed_form('shipped');			
			if ( usam_check_document_access( $this->data, 'shipped', 'edit' ) )
			{	
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php	
			}
			if ( current_user_can( 'delete_shipped' ) )
				$this->delete_button();	
		}
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
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
			<?php $this->display_form_status( 'shipped' ); ?>				
				<?php
				if ( !empty($this->data['storage_pickup']) )
				{
					?>
					<div class ="view_data__row">
						<div class ="view_data__name"><?php _e( 'Офис получения','usam'); ?>:</div>
						<div class ="view_data__option">
							<?php 					
							$storage = usam_get_storage( $this->data['storage_pickup'] ); 
							if ( $storage )										
							{						
								$location = usam_get_location( $storage['location_id'] );
								$city = isset($location['name'])?htmlspecialchars($location['name'])." ":'';
								$storage_pickup_phone = usam_get_storage_metadata( $storage['id'], 'phone');
								$storage_pickup_schedule = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'schedule'));
								$storage_pickup_address = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));
								?>									
								<div class='crm_customer'>
									<a href='<?php echo admin_url("admin.php?page=storage&tab=storage&table=storage&form=edit&form_name=storage&id=".$this->data['storage_pickup']); ?>'><?php echo $storage['title']; ?></a>
									<div class='crm_customer__info'>
										<div class='crm_customer__info_rows'>
											<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_code"><?php _e("Код","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage['code']; ?></div></div>
											<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_address"><?php _e("Адрес","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_address; ?></div></div>
											<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_phone"><?php _e("т.","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_phone; ?></div></div>
											<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_schedule"><?php _e("График работы","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_schedule; ?></div></div>
										</div>
									</div>
								</div>
								<?php 
							}		
							else
								_e('Выбранный склад не существует', 'usam');
							?>		
						</div>
					</div>
				<?php } ?>			
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость доставки','usam'); ?>:</div>
				<div class ="view_data__option"><span class="document_totalprice"><?php echo $this->currency_display($this->data['totalprice']); ?></span></div>
			</div>	
			<div class ="view_data__row" v-if="data.tax_id">
				<div class ="view_data__name" v-html="data.tax_name+':'"></div>
				<div class ="view_data__option" v-html="data.tax_value"></div>
			</div>
			<div class ="view_data__row" v-if="data.exchange!==false">
				<div class ="view_data__name"><?php esc_html_e( 'Статус выгрузки', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<span class='item_status_valid item_status' v-if="data.exchange==1"><?php _e( 'выгружен', 'usam'); ?></span>
					<span class='item_status_attention item_status' v-else><?php _e( 'не выгружен', 'usam'); ?></span>						
				</div>	
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Склад списания', 'usam'); ?>:</div>
				<div class ="view_data__option">					
					<?php
						if ( empty($this->data['storage']) )
							_e( 'Не выбрано', 'usam');
						else
						{
							$storage = usam_get_storage( $this->data['storage'] ); 
							echo isset($storage['title'])?$storage['title']:''; 
						}
					?>
				</div>
			</div>	
			<div class ="view_data__row" v-html="data.readiness_date">
				<div class ="view_data__name"><?php _e( 'Дата сборки','usam'); ?>:</div>
				<div class ="view_data__option">{{localDate(data.readiness_date,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
			</div>			
		</div>		
		<?php	
	}
	
	protected function main_content_cell_2()
	{	
		$delivery_problem = usam_get_shipped_document_metadata( $this->id, 'delivery_problem' );
		$date_delivery = usam_get_shipped_document_metadata( $this->id, 'date_delivery' );
		$courier_delivery = usam_get_shipped_document_metadata( $this->id, 'courier_delivery' );		
		?>	
		<h3><?php esc_html_e( 'Получение', 'usam'); ?></h3>
		<div class = "view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Способ доставки','usam'); ?>:</div>
				<div class ="view_data__option" v-html="data.name"></div>
			</div>
			<div class ="view_data__row" v-if="data.track_id">
				<div class ="view_data__name"><?php _e( 'Номер отслеживания','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php 
					$delivery = usam_get_delivery_service( $this->data['method'] );									
					if ( $this->data['method'] != '' && !empty($delivery['handler']) ) { ?>	
						<a title='<?php _e( 'Посмотреть историю почтового отправления', 'usam'); ?>' href='<?php echo add_query_arg(['form' => 'view', 'id' => $this->id, 'form_name' => 'tracking']); ?>'><?php echo $this->data['track_id']; ?></a>
					<?php } else { ?>
						<?php echo $this->data['track_id']; ?>
					<?php } ?>					
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Курьер','usam'); ?>:</div>
				<div class ="view_data__option" v-if="data.courier>0">
					<?php						
					$contact = usam_get_contact( $this->data['courier'], 'user_id' );						
					echo !empty($contact)?$contact['appeal']:'';
					?>
				</div>
				<div class ="view_data__option" v-else><?php _e( 'Не указан','usam'); ?></div>		
			</div>					
			<div class ="view_data__row" v-if="data.courier && data.date_delivery">
				<div class ="view_data__name"><?php _e( 'Дата и время доставки','usam'); ?>:</div>
				<div class ="view_data__option">{{localDate(data.date_delivery,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
			</div>	
			<div class ="view_data__row" v-if="data.courier && data.note">
				<div class ="view_data__name"><?php _e( 'Указания курьеру','usam'); ?>:</div>
				<div class ="view_data__option" v-html="data.note.replace(/\n/g,'<br>')"></div>
			</div>			
		</div>			
		<?php
	}
	
	function display_tab_related_documents()
	{	
		$this->display_related_documents('shipped');
	}
	
	public function display_tab_products_document( )
	{		
		$this->register_modules_products();
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-shipped.php' );
	}
}
?>