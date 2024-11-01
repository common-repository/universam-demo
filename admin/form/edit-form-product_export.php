<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/export-form.php' );
class USAM_Form_product_export extends USAM_Form_Export
{
	protected $rule_type = 'product_export';
	protected function get_columns_sort() 
	{			
		return ['date' => __('По дате','usam'), 'post_modified' => __('По дате изменения','usam'), 'post_title' => __('По названию','usam'), 'id' => __('По номеру','usam'), 'post_author' => __('По автору','usam'), 'menu_order' => __('По ручной сортировке','usam'), 'rand' => __('Случайно','usam'), 'views' => __('По просмотрам','usam'), 'rating' => __('По рейтингу','usam'), 'price' => __('По цене','usam'), 'stock' => __('По остаткам','usam')];
	}
	
	function display_left()
	{					
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<?php $this->display_settings(); ?>
		</div>	
		<?php
		usam_add_box( 'usam_product_select', __('Настройки выбора товаров','usam'), [$this, 'display_products_selection']);
		$this->display_columns();
    }		
}
?>