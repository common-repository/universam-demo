<?php	
require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_product_day extends USAM_Edit_Form
{		
	protected $vue = true;
	protected $JSON = true;	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf(__('Изменить программу &laquo;%s&raquo;','usam'), '{{data.name}}' ).'</span><span v-else>'.__('Добавить программу','usam').'</span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{						
		$this->display_toolbar_buttons();
		$this->main_actions_button();
	}	
	
	protected function get_data_tab(  )
	{	
		$default = ['id' => 0, 'name' => '', 'description' => '', 'active' => 0, 'refill' => 0, 'type_prices' => [], 'start_date' => '', 'end_date' => '', 'conditions' => ['pricemin' => 0, 'pricemax' => 0, 'minstock' => 0, 'c' => 10, 'value' => 10], 'products' => []];
		$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');		
		foreach($taxonomies as $taxonomy)
			$default['conditions'][$taxonomy->name] = [];
		if ( $this->id != null )
		{
			$this->data = usam_get_data($this->id, 'usam_product_day_rules');	
			$this->data['products'] = usam_get_products_day(['rule_id' => $this->id, 'status' => [0, 1]] );
			foreach($this->data['products'] as $k => $product)
			{
				$this->data['products'][$k]->post_title = get_the_title($product->product_id);
				$this->data['products'][$k]->small_image = usam_get_product_thumbnail_src($product->product_id);
				$this->data['products'][$k]->url = get_permalink( $product->product_id );
				$this->data['products'][$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
			}
		}
		$this->data = usam_format_data( $default, $this->data );		
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i" );	
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i" );
		$this->register_modules_products();
	}	
	  	
	function display_left()
	{	
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Период действия', 'usam'); ?>:</div>
					<div class ="edit_form__item_option date_intervals">
						<datetime-picker v-model="data.start_date"></datetime-picker> - <datetime-picker v-model="data.end_date"></datetime-picker>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label><?php esc_html_e( 'Пополнять очередь', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<label><input type='radio' value='1' name='refill' v-model="data.refill"/> <?php _e( 'Да', 'usam');  ?></label> &nbsp;
						<label><input type='radio' value='0' name='refill' v-model="data.refill"/> <?php _e( 'Нет', 'usam');  ?></label>
					</div>
				</div>			
			</div>		
		</div>
		<usam-box :id="'usam_auto_fill'" :handle="false" :title="'<?php _e( 'Автоматическое заполнение', 'usam'); ?>'" v-show="data.refill==1">
			<template v-slot:body>		
				<div class="edit_form">
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Диапазон цен', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' class='interval' name='pricemin' v-model="data.conditions.pricemin"> - 
							<input type='text' class='interval' name='pricemax' v-model="data.conditions.pricemax">
						</div>
					</label>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Минимальный остаток', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' class='text' size='10' name='minstock' v-model="data.conditions.minstock">
						</div>
					</label>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Количество в очереди', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' class='text' size='10' name='c' v-model="data.conditions.c">
						</div>
					</label>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Установить скидку', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type='text' class='text' size='10' name='value' v-model="data.conditions.value">
						</div>
					</label>	
					<div class ="edit_form__item">
						<div class ="edit_form__title"><label><?php esc_html_e( 'Выберите из каких групп выбирать товары', 'usam'); ?></label></div>				
					</div>	
				</div>
				<div class="edit_form" v-for="(item, k) in taxonomies" v-if="terms[item.name] !== undefined">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name">{{item.label}}:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.conditions[item.name]=$event.id" :multiple='1' :lists="terms[item.name]" :selected="data.conditions[item.name]"></select-list>	
						</div>
					</div>
				</div>	
			</template>
		</usam-box>	
		<usam-box :id="'usam_document_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<?php include( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-product_day.php' ); ?>
			</template>
		</usam-box>
		<?php
    }	
	
	function display_right()
	{			
		?>
		<usam-box :id="'usam_status_active'" :title="'<?php _e( 'Статус активности', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked v-model="data.active" :text="'<?php _e('Активно', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<usam-box :id="'usam_prices'" :title="'<?php _e( 'Типы цен', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<select-list @change="data.type_prices=$event.id" :multiple='1' :lists="prices" :selected="data.type_prices"></select-list>	
			</template>
		</usam-box>
		<?php	
    }
}
?>