<?php		
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_View_Form_Document extends USAM_View_Form
{		
	protected $ribbon = true;	
	protected function data_default()
	{
		return [];
	}
	
	protected function get_data_tab(  )
	{					
		$this->data = usam_get_document( $this->id );
		if( empty($this->data) || $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		} 
		$user_id = get_current_user_id();
		$standart_default = ['id' => 0, 'name' => '', 'bank_account_id' => get_option('usam_shop_company'), 'manager_id' => $user_id, 'type_price' => '', 'totalprice' => 0, 'customer_id' => 0, 'customer_type' => 'company', 'status' => 'draft', 'date_insert' => date( "Y-m-d H:i:s"), 'note' => '', 'external_document_date' => '', 'external_document' => '', 'groups' => []];
		$default = array_merge( $standart_default, $this->data_default() );
		$this->data = array_merge( $default, $this->data );
		$this->data['contacts'] = usam_get_contacts(['document_ids' => $this->id, 'source' => 'all']);		
		$this->tabs = [ 
			['slug' => 'products_document', 'title' => __('Товары','usam')],			
			['slug' => 'change', 'title' => __('Изменения','usam')],
			['slug' => 'related_documents', 'title' => __('Документы','usam')],
		];
		$this->add_document_data();		
		$this->header_title = __('Описание', 'usam');
		$this->header_content = usam_get_document_metadata($this->id, 'note');		
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );	
		}		
		if ( $this->data['status'] == 'approved' )
			$this->change = false;				
	}	
	
	protected function add_products_document()
	{
		$this->data['product_taxes'] = usam_get_document_product_taxes( $this->data['id'] ); 		
		foreach($this->data['product_taxes'] as $k => $product)
			$this->data['product_taxes'][$k]->name = stripcslashes($product->name);			
		$this->data['products'] = usam_get_products_document( $this->data['id'] );		
		foreach($this->data['products'] as $k => $product)
		{
			$this->data['products'][$k]->name = stripcslashes($product->name);
			$this->data['products'][$k]->small_image = usam_get_product_thumbnail_src($product->product_id);
			$this->data['products'][$k]->url = get_permalink( $product->product_id );
			$this->data['products'][$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
		}
		$this->register_modules_products();		
	}
	
	protected function viewing_not_allowed()
	{	
		return !usam_check_document_access( $this->data, $this->data['type'], 'view' );
	}
		
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_title_tab()
	{ 	
		return sprintf('%s № %s %s', usam_get_document_name($this->data['type']), '<span class="number">'.$this->data['number'].'</span>', '<span class="subtitle_date">'.__('от','usam').' '.usam_local_date($this->data['date_insert'], "d.m.Y").'</span>' );
	}
	
	protected function get_edit()
	{
		return true;
	}
	
	protected function form_class( ) 
	{ 
		return 'view_form_document';
	}
	
	protected function get_document_toolbar_buttons( ) 
	{ 
		return [];
	}
			
	protected function toolbar_buttons( ) 
	{ 
		if ( $this->id != null )
		{	
			if ( usam_check_document_access( $this->data, $this->data['type'], 'edit' ) )
			{
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php
			}	
			if( !wp_is_mobile() )
			{ 
				$this->display_printed_form( $this->data['type'] );
				$this->display_toolbar_buttons();				
			}					
			$links = $this->get_document_toolbar_buttons();
			$links[] = ['action' => 'deleteItem', 'title' => esc_html__('Удалить', 'usam'), 'class' => 'delete', 'capability' => 'delete_'.$this->data['type']];				
			$this->display_form_actions( $links );
		}
	}
	
	protected function ability_to_delete( )
	{
		return $this->data['status'] == 'draft' && current_user_can('delete_'.$this->data['type']) || current_user_can('delete_any_'.$this->data['type'])? true : false;
	}
		
	function currency_display( $price ) 
	{			
		if ( $this->data['type_price'] )
			return usam_get_formatted_price( $price, ['type_price' => $this->data['type_price']]);
		else
			return usam_get_formatted_price( $price, ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false]);		
	}

	protected function display_status()
	{	
		?>
		<div class ="view_data__row">
			<div class ="view_data__name"><?php _e( 'Статус','usam'); ?>:</div>
			<div class ="view_data__option"><?php 
				if ( current_user_can('edit_'.$this->data['type']) )
				{
					?>
					<select v-model='data.status'>
						<option v-for="status in statuses" v-if="(status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
					</select>						
					<?php
				}
				else
				{
					echo usam_get_object_status_name( $this->data['status'], $this->data['type'] );
				}
				?>
			</div>
		</div>	
		<?php
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
				<div class ="view_data__name"><?php _e( 'Продавец','usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_display_company_by_acc_number( $this->data['bank_account_id'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Покупатель','usam'); ?>:</div>
				<div class ="view_data__option"><?php $this->display_customer( $this->data['customer_id'], $this->data['customer_type'] ); ?></div>
			</div>		
		</div>		
		<?php	
	}	
	
	public function display_tab_files( )
	{		
		$this->display_attachments();
	}
		
	public function display_tab_products_document( )
	{		
		require_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-document.php' );
	}
	
	function display_tab_document_content()
	{ 
		$document_content = usam_get_document_content( $this->id, 'document_content' );		
		?>
		<div class="document_content"><?php echo $document_content; ?></div>
		<?php
	}	

	function display_tab_related_documents()
	{	
		$this->display_related_documents( $this->data['type'] );
	}
}