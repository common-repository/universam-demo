<?php		
require_once( USAM_FILE_PATH .'/admin/includes/form/export-form.php' );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
class USAM_Form_Pricelist extends USAM_Form_Export
{		
	protected $rule_type = 'pricelist';
	protected function get_columns_sort() 
	{
		return ['date' => __('По дате','usam'), 'post_modified' => __('По дате изменения','usam'), 'post_title' => __('По названию','usam'), 'id' => __('По номеру','usam'), 'post_author' => __('По автору','usam'), 'menu_order' => __('По ручной сортировке','usam'), 'rand' => __('Случайно','usam'), 'views' => __('По просмотрам','usam'), 'rating' => __('По рейтингу','usam'), 'price' => __('По цене','usam'), 'stock' => __('По остаткам','usam')];
	}
	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf( __('Изменить прайс-лист № %s','usam'), "{{data.id}}" ).'</span><span v-else>'.__('Добавить прайс-лист','usam').'</span>';
	}
		
	public function get_columns() 
	{
		$columns = usam_get_exchange_rule_metadata( $this->id, 'columns' );
		if ( !$columns )
			$columns = ['exel_image' => __('Картинка','usam'), 'post_title' => '', 'sku' => '', 'price_tp_1' => __('Цена','usam')];
		return $columns;
	}	
	
	public function display_pricelist_settings() 
	{
		$file_generations = array( '' => __('При запросе клиентом', 'usam'), 'day' => __('Каждый день вечером', 'usam') );
		$rules = usam_get_exchange_rules( array('type' => 'product_import') );
		foreach ($rules as $rule) 		
		{
			$file_generations['rule_'.$rule->id] = __('По завершению правила импорта', 'usam').' - '.$rule->name;
		}
		?>
		 <div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='file_generation'><?php esc_html_e('Принцип генерации файла', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="file_generation" v-model="data.file_generation">				
						<?php
						foreach ($file_generations as $key => $title)
						{
							?><option value='<?php echo $key ?>'><?php echo $title ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
		</div>	
	   <?php   
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
		usam_add_box( 'usam_pricelist_settings', __('Настройка обработки','usam'), array( $this, 'display_pricelist_settings' ));	
		usam_add_box( 'usam_product_select', __('Настройки выбора товаров','usam'), array( $this, 'display_products_selection' ));
		$this->display_columns();	
    }
	
	function display_right()
	{	
		?>
		<usam-box :id="'usam_status_active'" :title="'<?php _e( 'Статус активности', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked :value="data.schedule==0?0:1" @input="data.schedule=$event?1:0" :text="'<?php _e('Активно', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<usam-box :id="'usam_product_exporter_columns'" :handle="false" :title="'<?php _e( 'Роли пользователей', 'usam'); ?>'">
			<template v-slot:body>
				<select-list @change="data.roles=$event.id" :multiple='1' :lists="roles" :selected="data.roles"></select-list>	
			</template>
		</usam-box>	
		<?php 	
	}	
}
?>