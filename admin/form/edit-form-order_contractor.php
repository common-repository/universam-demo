<?php			
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document.php' );
class USAM_Form_order_contractor extends USAM_Edit_Form_Document
{		
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

	function display_document_properties()
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
			<div class ="edit_form__item_name"><?php _e( 'Поставщик','usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<div class="user_block" v-if="data.customer_id>0" @click="sidebar('companies')">
					<div class='user_foto'><a class='image_container usam_foto'><img :src='company.logo'></a></div>	
					<a class='user_name':href="company.url" v-html="company.name"></a>
				</div>				
				<a v-else @click="sidebar('companies')"><?php esc_html_e( 'Выбрать поставщика', 'usam'); ?></a>
			</div>
		</div>		
		<?php
		$this->display_document_contract();
		add_action('usam_after_form',function() {
			include( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-companies.php' );
		});
		usam_vue_module('list-table');
    }
	
	function display_document_footer()
	{
		$this->register_modules_products();
		?>
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-document.php' ); ?>
			</template>
		</usam-box>
		<?php
		
	}
}
?>