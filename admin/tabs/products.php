<?php
class USAM_Tab_products extends USAM_Tab
{	
	protected $views = ['table', 'simple'];		
	public function get_title_tab()
	{		
		return __('Управление ценой', 'usam');
	}			
	
	public function display() 
	{
		$code_price = usam_get_manager_type_price();		
		?>
		<div id="mass_price_change" class ="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php _e('Тип цены','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select-list :search='1' @change="selected.type_price=$event.id" :lists="options.code_price" :selected="'<?php echo $code_price; ?>'" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
				</div>
			</div>			
			<?php 
			foreach( ['category', 'brands', 'category_sale', 'catalog'] as $tax_slug )
			{		
				$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
				?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php echo $tax_obj->labels->menu_name; ?>:</div>
					<div class ="edit_form__item_option">
						<select-list :search='1' @change="selected['<?php echo $tax_slug; ?>']=$event.id" :lists="options.<?php echo $tax_slug; ?>" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
					</div>
				</div>	
				<?php
			} 
			?>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php _e( 'Процент изменения цены','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' v-model='selected.markup' value='' style="width:100px;" />
					<select v-model="selected.operation" class="select_type_md">				
						<option value='+'><?php _e('Увеличить', 'usam'); ?></option>			
						<option value='-'><?php _e('Уменьшить', 'usam'); ?></option>						
					</select>					
				</div>
			</div>			
			<div class ="edit_form__item">				
				<div class ="edit_form__item_name"></div>
				<div class ="edit_form__item_option">
					<button class="button" @click="change"><?php _e('Изменить цену товара','usam'); ?></button>
				</div>
			</div>				
		</div>
		<?php		
	}
}
