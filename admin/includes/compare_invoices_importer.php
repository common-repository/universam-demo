<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Compare_Invoices_Rule_Importer extends USAM_Importer
{			
	protected $rule_type = 'order_import';	
	public function get_steps()
	{
		return ['file' => __('Выбор файла', 'usam'), 'columns' => __('Назначение столбцов', 'usam'), 'finish' => __('Сравнение', 'usam')];
	}
	
	protected function get_columns()
	{
		return ['sku' => __('Артикул', 'usam'), 'barcode' => __('Штрих-код', 'usam')];
	}
	
	public function get_id()
	{
		return 'compare_invoices_importer';
	}
		
	public function process_file()
	{
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
		$rules = usam_get_exchange_rules(['type' => $this->rule_type]);			
		?>
		<h3><?php _e('Выбор файла' , 'usam'); ?></h3>
		<div class='edit_form'>	
			<?php $this->file_selection(); ?>
			<?php if ( $this->template && !empty($rules) ) {  ?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='template_id'><?php esc_html_e( 'Шаблон', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model="template_id" id="template_id">				
							<option value='0'><?php esc_html_e( 'Не использовать шаблон', 'usam'); ?></option>	
							<?php													
							foreach ($rules as $rule) 			
							{
								?><option value='<?php echo $rule->id ?>'><?php echo $rule->name; ?></option><?php
							}
							?>
						</select>
					</div>
				</div>
			<?php } ?>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='customer_id'><?php esc_html_e( 'Клиент', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php
						$orders = usam_get_orders(['status' => 'job_dispatched']);
						$contact_ids = [];
						$company_ids = [];						
						$lists = [];
						foreach ($orders as $order) 			
						{						
							if ( $order->company_id )
								$company_ids[] = $order->company_id;
							elseif ( $order->contact_id )
								$contact_ids[] = $order->contact_id;
						}
						if ( $company_ids )
						{
							$companies = usam_get_companies(['include' => $company_ids, 'orderby' => 'name']);							
							foreach ($companies as $company)
							{
								$lists[] = ['id' => 'companies-'.$company->id, 'name' => $company->name];
							}
						}
						if ( $contact_ids )
						{
							$contacts = usam_get_contacts(['include' => $contact_ids, 'orderby' => 'appeal']);
							foreach ($contacts as $contact) 			
							{
								$lists[] = ['id' => 'contacts-'.$contact->id, 'name' => $contact->appeal];
							}
						}
					?>
					<select-list @change="change_customer" :lists='<?php echo json_encode( $lists ); ?>' :search='1'></select-list>
				</div>
			</div>			
		</div>			
		<div class='actions'>	
			<button class="button button-primary" @click="next_step"><?php esc_html_e( 'Продолжить', 'usam'); ?></button>
		</div>
		<?php 
	}
		
	public function process_finish()
	{
		?>	
		<div class='comparison_results'>
			<div class='comparison_results__found'>
				<div class="comparison_results__title"><?php _e('Найденные в накладных товары' , 'usam'); ?></div>
				<div class="comparison_results__product" v-for="(product, k) in found">
					<div class="comparison_results__product_image"><img :src="product.image"></div>
					<div class="comparison_results__product_name"><a :href="product.url" v-html="product.name"></a></div>
					<div class="comparison_results__product_quantity" v-html="product.quantity_unit_measure"></div>					
				</div>
			</div>
			<div class='compare_invoices__not_found'>
				<div class="comparison_results__title"><?php _e('Не найденные в накладных товары' , 'usam'); ?></div>
				<div class="comparison_results__product" v-for="(product, k) in not_found">
					<div class="comparison_results__product_name" v-html="product.name"></div>
					<div class="comparison_results__product_quantity" v-html="product.quantity_unit_measure"></div>		
				</div>
			</div>
		</div>		
		<?php 
	}
}
?>