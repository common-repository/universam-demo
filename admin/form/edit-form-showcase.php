<?php		
require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_showcase extends USAM_Edit_Form
{
	protected $vue = true;
	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.__('Изменить витрину','usam').'</span><span v-else>'.__('Добавить витрину','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{			
		$default = ['id' => 0, 'name' => '', 'domain' => '', 'login' => '', 'access_token' => '', 'products' => 0, 'number_products' => 0, 'status' => 'disabled', 'settings' => ['contractors' => '']]; 
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
		foreach( $taxonomies as $taxonomy ) 
			$default['settings'][$taxonomy] = [];
		if ( $this->id !== null )
			$this->data = usam_get_showcase( $this->id );			
		$this->data = usam_format_data( $default, $this->data );				
	}
	
	protected function get_main_actions()
	{
		$actions = [];
		$actions[] = ['action' => 'checkAvailableProducts', 'title' => __('Пометить товары для выгрузки', 'usam'), 'if' => 'data.products>0'];
		$actions[] = ['action' => 'removeLink', 'title' => __('Удалить связь с витриной', 'usam'), 'if' => 'data.id>0'];
		$actions[] = ['action' => 'updatePrices', 'title' => __('Обновить цены в витринах', 'usam'), 'if' => 'data.products>0'];
		$actions[] = ['action' => 'synchronizationProducts', 'title' => __('Синхранизировать товары в витринах', 'usam'), 'if' => 'data.products>0'];		
		$actions[] = ['action' => 'deleteSynchronizationProducts', 'title' => __('Удалить не синхранизированые товары в витрине', 'usam'), 'if' => 'data.products>0'];
		$actions[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'class' => 'delete', 'if' => 'data.id>0'];
		return $actions;
	}

	function display_left()
	{			
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<div class="edit_form">
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Домен', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" v-model="data.domain">
					</div>
				</label>				
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Логин', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" v-model="data.login">
					</div>
				</label>
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Токен', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="password" v-model="data.access_token">
					</div>
				</label>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<?php $statuses = usam_get_statuses_showcase(); ?>
						<select name = "status" v-model="data.status">							
							<?php				
							foreach ( $statuses as $key => $name ) 
							{					
								?><option value='<?php echo $key; ?>' <?php selected($this->data['status'], $key); ?>><?php echo $name; ?></option><?php
							}
							?>
						</select>	
					</div>
				</div>			
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Авто передача товара', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<selector v-model="data.products"></selector>	
					</div>
				</div>	
			</div>	
		</div>
		<usam-box :id="'usam_products_settings'" :handle="false" :title="'<?php _e( 'Выгружать следующие товары', 'usam'); ?>'" v-show="data.products">
			<template v-slot:body>		
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Поставщики товара','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.settings.contractors=$event.id" :multiple='1' :lists="contractors" :selected="data.settings.contractors"></select-list>	
						</div>
					</div>
					<div class ="edit_form__item" v-for="(item, k) in taxonomies" v-if="terms[item.name] !== undefined">
						<div class ="edit_form__item_name">{{item.label}}:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.settings[item.name]=$event.id" :multiple='1' :lists="terms[item.name]" :selected="data.settings[item.name]"></select-list>	
						</div>
					</div>
				</div>	
			</template>
		</usam-box>	
		<?php 
    }
}
?>