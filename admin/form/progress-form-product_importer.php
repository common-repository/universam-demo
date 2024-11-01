<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Form_product_importer extends USAM_Importer
{		
	protected $rule_type = 'product_import';
	protected function get_columns()
	{
		return usam_get_columns_product_import();
	}
	
	public function get_url()
	{
		return admin_url('admin.php?page=exchange&tab=product_importer');
	}
	
	public function default_columns()
	{
		?>
		<h4><?php _e( 'Настройки значения по умолчанию', 'usam'); ?></h4>
		<div class ="edit_form">					
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Статус товара' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select v-model='rule.post_status'>						
						<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
						<?php
						foreach ( get_post_stati(['show_in_admin_status_list' => true], 'objects') as $key => $status ) 
						{										
							?><option value='<?php echo $key; ?>'><?php echo $status->label; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Поставщик товара' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select-list @change="rule.contractor=$event.id" :lists="default_columns.companies" :search='1'></select-list>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Категория товара' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select-list @change="rule.product_category=$event.id" :lists="default_columns.category" :search='1'></select-list>
				</div>
			</div>	
		</div>		
		<?php
	}	
}
?>