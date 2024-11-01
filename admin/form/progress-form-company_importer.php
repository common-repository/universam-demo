<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Form_company_importer extends USAM_Importer
{		
	protected $rule_type = 'company_import';	
	protected function get_columns()
	{
		return usam_get_columns_company_import();
	}
	
	public function get_url()
	{
		return admin_url('admin.php?page=crm&tab=companies&view=table&table=company_import');
	}
	
	public function default_columns()
	{
		?>
		<h4><?php _e( 'Настройки значения по умолчанию', 'usam'); ?></h4>
		<div class ="edit_form">					
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Группа' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select-list @change="rule.groups=$event.id" :lists="groups" :none="' - '"></select-list>
				</div>
			</div>			
		</div>		
		<?php
	}	
}
?>