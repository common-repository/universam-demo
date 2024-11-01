<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/import-form.php' );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
class USAM_Form_contact_import extends USAM_Import_Form
{		
	protected $rule_type = 'contact_import';	
	
	function display_default_values()
	{
		?>
		<div class='edit_form'>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Группа' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select-list @change="data.groups=$event.id" :lists="groups" :none="' - '" :selected="data.groups"></select-list>
					<input type="hidden" name="groups" v-model="data.groups">
				</div>
			</div>		
		</div>
		<?php
	}			
}
?>