<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );		
class USAM_Edit_Form_Document extends USAM_Edit_Form
{	
	protected $placeholder;
	protected $block_contacts = true;
	protected $vue = true;
	protected $blocks = [];
	
	protected function get_title_tab()
	{ 
		return '<span v-if="data.id">'.sprintf('%s № %s %s', usam_get_document_name($this->data['type']), '<span class="number">{{data.number}}</span>', '<span class="subtitle_date">'.__('от','usam').' {{localDate(data.date_insert,"'.get_option('date_format', 'Y/m/j').'")}}</span>' ).'</span><span v-else>'.sprintf('Добавить %s', mb_strtolower(usam_get_document_name($this->data['type'])) ).'</span>';
	}
	
	protected function data_default()
	{	
		return [];
	}
	
	protected function get_data_tab()
	{		
		$this->placeholder = __('Название', 'usam');
		$this->blocks = ['manager' => __( 'Ответственный', 'usam'), 'contacts' => __( 'Контакты', 'usam')];
		$user_id = get_current_user_id();
		$standart_default = ['id' => 0, 'name' => '', 'bank_account_id' => get_option('usam_shop_company'), 'manager_id' => $user_id, 'type_price' => '', 'totalprice' => 0, 'customer_id' => 0, 'customer_type' => 'company', 'status' => 'draft', 'date_insert' => date( "Y-m-d H:i:s"), 'note' => '', 'external_document_date' => '', 'external_document' => '', 'groups' => []];
		if ( !empty($_GET['contact']) )
		{
			$standart_default['customer_id'] = absint($_GET['contact']);
			$standart_default['customer_type'] = 'contact';				
		}
		elseif ( !empty($_GET['company']) )
		{
			$standart_default['customer_id'] = absint($_GET['company']);
			$standart_default['customer_type'] = 'company';				
		}
		$default = array_merge( $standart_default, $this->data_default() );
		if ( $this->id != null )
		{
			$this->data = usam_get_document( $this->id );	
			$this->data = array_merge( $default, $this->data );			
			if ( $this->viewing_not_allowed() )
			{
				$this->data = [];
				return;
			}
			$metas = usam_get_document_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		}
		else		
			$this->data = array_merge( $default, $this->data );
		if( isset($this->data['conditions']) )
			$this->data['conditions'] = usam_get_document_content($this->id, 'conditions');			
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );
		if( !empty($this->data['external_document_date']) )
			$this->data['external_document_date'] = get_date_from_gmt( $this->data['external_document_date'], "Y-m-d H:i" );
		$this->js_args['manager'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );	
		}		
		$this->js_args['contact'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		if( $this->data['customer_type'] == 'contact' )
		{
			$contact = usam_get_contact( $this->data['customer_id'] );
			if( $contact )
			{
				$this->js_args['contact'] = $contact;
				$this->js_args['contact']['foto'] = usam_get_contact_foto( $contact['id'] );
				$this->js_args['contact']['url'] = usam_get_contact_url( $contact['id'] );	
			}
		}		
		if ( empty($this->data['number']) )
			$this->data['number'] = usam_get_document_number( $this->data['type'] );
		$this->add_document_data();
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
	}
	
	protected function viewing_not_allowed()
	{
		return !usam_check_document_access( $this->data, $this->data['type'], 'view' );
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) 
	{
		return 'edit_form_document';
	}
	
	public function section_customers( )
	{		
		$display_contact = '';
		$display_company = '';
		if( $this->data['customer_type'] == 'contact' )
		{
			$contact = usam_get_contact( $this->data['customer_id'] );		
			$display_contact = !empty($contact)?$contact['appeal']:'';	
		}
		else
		{
			$company = usam_get_company( $this->data['customer_id'] );
			$display_company = !empty($company)?$company['name']:'';	
		}
		?>
		<div class ="counterparty">		
			<div class="select_customer_type">							
				<select v-model="data.customer_type">
					<option value='contact'><?php _e('Контакт','usam'); ?></option>	
					<option value='company'><?php _e('Компания','usam'); ?></option>	
				</select>
			</div>				
			<autocomplete v-if="data.customer_type=='contact'" :selected="'<?php echo htmlspecialchars($display_contact, ENT_QUOTES); ?>'" @change="data.customer_id=$event.id" :request="'contacts'"></autocomplete>
			<autocomplete v-else :selected="'<?php echo htmlspecialchars($display_company); ?>'" @change="data.customer_id=$event.id" :request="'companies'"></autocomplete>
		</div> 	
		<?php
	}	
	
	function currency_display( $price ) 
	{			
		return usam_get_formatted_price( $price, ['type_price' => $this->data['type_price']] );
	}	
	
	public function display_document_content( ) 
	{
		$document_content = usam_get_document_content( $this->id, 'document_content' );
		if ( $this->change )			
			wp_editor( $document_content, 'document_content', array(
				'textarea_name' => 'document_content',
				'media_buttons' => false,
				'textarea_rows' => 30,	
				'wpautop' => 0,							
				'tinymce' => array(
					'theme_advanced_buttons3' => 'invoicefields,checkoutformfields',
					)
				)
			 ); 
		else
		{
			echo $document_content;			
		}
	}
	
	protected function get_toolbar_buttons( ) 
	{ 
		return [];
	}
	
	protected function toolbar_buttons( ) 
	{ 		
		if( !wp_is_mobile() )
			$this->display_printed_form( $this->data['type'] );
		$this->display_toolbar_buttons();
		if ( usam_check_document_access( $this->data, $this->data['type'], 'edit' ) )
		{
			?><button type="button" class="button button-primary action_buttons__button" @click="saveForm"><?php echo $this->title_save_button(); ?></button><?php	
		}
		?>		
		<div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view']); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div>
		<div v-if="data.id>0">
			<?php
			$links[] = ['action' => 'deleteItem', 'title' => esc_html__('Удалить', 'usam'), 'capability' => 'delete_'.$this->data['type']];				
			$this->display_form_actions( $links );
			?>
		</div>
		<?php
	}	
	
	public function display_document_properties( ) { } 	
	public function display_document_footer( ) { } 	
	
	function display_left()
	{						
		?>		
		<div class='event_form_head'>			
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php echo $this->placeholder; ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<div class="edit_form">
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Номер','usam'); ?>:</div>
					<div class ="edit_form__item_option">					
						<input type='text' v-model="data.number">
					</div>
				</label>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Дата','usam'); ?>:</div>
					<div class ="edit_form__item_option">					
						<datetime-picker v-model="data.date_insert"/>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select v-model='data.status'>
							<option v-for="status in statuses" v-if="status.type==data.type && (status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
						</select>
					</div>
				</div>							
				<?php $this->display_document_properties(); ?>	
				<a class ="edit_form__margin" v-if="!externalDocument" @click="externalDocument=!externalDocument"><?php esc_html_e( 'Внешний документ', 'usam'); ?></a>
				<label class ="edit_form__item" v-if="externalDocument">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Номер внешнего документа', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type='text' v-model="data.external_document">
					</div>
				</label>
				<div class ="edit_form__item" v-if="externalDocument">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Дата внешнего документа', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">				
						<datetime-picker v-model="data.external_document_date"/>
					</div>
				</div>
			</div>
		</div>		
		<?php	
		$this->display_document_footer();			
    }
	
	function display_document_counterparties()
	{						
		?>				
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Ваша фирма','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model='data.bank_account_id'>
					<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
				</select>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Контрагент','usam'); ?>:</div>
			<div class ="edit_form__item_option"><?php $this->section_customers(); ?></div>
		</div>
		<?php		
    }
	
	function display_document_contract()
	{						
		?>				
		<div class ="edit_form__item" v-if="data.customer_id>0">
			<div class ="edit_form__item_name"><?php _e( 'Договор','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="related_document" v-if="data.contract>0" @click="sidebar('contracts')">
					<div class="related_document__document">
						<div class="related_document-title">
							<div class="related_document__document_type_name">№ {{contract.number}}</div>
							<div class="related_document__document_title" v-html="contract.name"></div>
						</div>
						<div class="related_document__document_date">
							<?php esc_html_e( 'Дата создания', 'usam'); ?>: <span class="related_document__date_insert">{{localDate(contract.date_insert,'d.m.Y')}}</span>
						</div>
					</div>
				</div>				
				<a v-else @click="sidebar('contracts')"><?php esc_html_e( 'Выбрать договор', 'usam'); ?></a>				
			</div>
		</div>		
		<?php
		add_action('usam_after_form',function() {
			require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-contracts.php' );
		});
		usam_vue_module('list-table');		
    }	
		
	function display_right()
	{			
		?>
		<usam-box :id="'usam_document_notes'" :title="'<?php _e( 'Примечание менеджера', 'usam'); ?>'">
			<template v-slot:body>
				<textarea class="width100" rows='3' cols='40' maxlength='255' v-model="data.note"></textarea>
			</template>
		</usam-box>		
		<?php
		if( isset($this->blocks['manager']) )
		{	
			?>
			<usam-box :id="'managers'" :handle="false" :title="'<?php echo $this->blocks['manager']; ?>'">
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
			</usam-box>	
			<?php
		}
		if( isset($this->blocks['contacts']) )
		{	
			?>
			<usam-box :id="'contacts'" :title="'<?php echo $this->blocks['contacts']; ?>'">
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-contacts.php' ); ?>
			</usam-box>
			<?php	
		}
		?>
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>		
		<?php
	}
}
?>