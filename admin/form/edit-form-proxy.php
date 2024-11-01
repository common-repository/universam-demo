<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_proxy extends USAM_Edit_Form_Document
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Доверенность №%s от %s (%s)','usam'), $this->data['number'], usam_local_date( $this->data['date_insert'], "d.m.Y" ), $this->data['name'] );
		else
			$title = __('Добавить доверенность', 'usam');	
		return $title;
	}
	
	protected function data_default()
	{
		return ['type' => 'proxy', 'customer_type' => 'company', 'contract' => 0, 'closedate' => date( "Y-m-d H:i:s", strtotime('+5 days'))];
	}
	
	protected function add_document_data(  )
	{
		$this->blocks['manager'] = __( 'Получатель', 'usam');	
		unset($this->blocks['contacts']);
		if( !empty($this->data['closedate']) )
			$this->data['closedate'] = get_date_from_gmt( $this->data['closedate'], "Y-m-d H:i" );		
		$this->js_args['contract'] = usam_get_document( $this->data['contract'] );			
		$this->add_products_document();
	}	
	
	function display_document_properties()
	{	
		$company = usam_get_company( $this->data['customer_id'] );
		$display_company = !empty($company)?$company['name']:'';			
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
			<div class ="edit_form__item_name"><?php _e( 'Получение у компании','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<autocomplete :selected="'<?php echo htmlspecialchars($display_company); ?>'" @change="data.customer_id=$event.id" :request="'companies'"></autocomplete>
			</div>
		</div>	
		<?php $this->display_document_contract(); ?>					
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e( 'Срок','usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<datetime-picker v-model="data.closedate"/>
			</div>
		</div>
		<?php
    }
	
	function display_document_footer()
	{
		$this->register_modules_products();
		?>
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-proxy.php' ); ?>
			</template>
		</usam-box>
		<?php
		
	}
}