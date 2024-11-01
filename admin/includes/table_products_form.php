<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_form.php' );
//Vue таблица товаров		
class USAM_Table_Products_Form extends USAM_Table_Form
{	
	public $products = [];
	public function select_product_buttons()
	{	
		?>
		<button v-if="!edit && abilityChange" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить товар', 'usam'); ?></button>
		<div v-if="edit && abilityChange && !edit_form" class="select_product__buttons">			
			<button type="button" class="button button-primary" @click="saveElement"><?php _e( 'Сохранить', 'usam'); ?></button>
			<button type="button" class="button" @click="edit=!edit"><?php _e( 'Отменить', 'usam'); ?></button>
		</div>
		<?php
	}
	
	public function display( $columns, $products = [] )
	{
		$this->products = $products;
		?>
		
		<div id="add_items_<?php echo $this->type; ?>" v-show="pLoaded" class="select_product" table_type="<?php echo $this->type; ?>">
			<div id ="table_container" class = 'table_products_container'>
				<table class="usam_list_table table_products">
					<thead>
						<tr>
							<th scope="col" class="manage-column" :class="'column-'+column.id" v-for="column in table_columns">
								<span v-if="column.id=='tools'" class="dashicons dashicons-list-view open_columns_tools" @click="columns_tools=!columns_tools"></span>
								<span v-else-if="column.id=='discount'">{{column.name}} <input v-if="edit && abilityChange" size="4" type="text" v-model="product_discounts" @blur="changeDiscount(product_discounts)"></span>
								<span v-else-if="column.id=='old_price'">{{column.name}} <input v-if="edit && abilityChange" size="4" type="text" v-model="add_sum" @blur="charge_price(add_sum)"></span>
								<span v-html="column.name" v-else></span>
							</th>
						</tr>
						<tr v-if="columns_tools" class="table_products__columns_tools_row">
							<td :colspan = 'table_columns.length'>		
								<div class="table_products__columns_tools">						
									<label v-for="(name, c) in settingsTables.column_names">
										<input type="checkbox" v-model="settingsTables.user_columns" :value="c">{{name}}
									</label>
								</div>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr class = "items_empty" v-if="products.length==0">
							<td :colspan = 'table_columns.length'><?php _e( 'Нет товаров', 'usam'); ?></td>
						</tr>
						<?php $this->display_body_table(); ?>				
					</tbody>
					<tfoot>
						<?php $this->display_total_table(); ?>	
					</tfoot>			
				</table>							
			</div>
			<div class="add_items" v-show="edit && abilityChange">
				<div class="add_items__options">				
					<?php
					if ( $this->add_items_table ) 
					{
						?><button type="button" class="button open_table" @click="tableShow=!tableShow"><span class="dashicons dashicons-list-view"></span></button><?php 
					} ?>
					<div class="add_items__add" v-if="!tableShow">	
						<autocomplete @change="selectElement" :selected="searchItem" :query="{status:['publish','draft']}" :request="'products'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
					</div>
					<button v-if="!tableShow" @click="addElement" type="button" class="button"><?php _e('Добавить', 'usam'); ?></button>
					<button v-if="!tableShow" @click="importData=!importData" type="button" class="button import_data"><?php _e( 'Импорт товаров', 'usam'); ?></button>
					<button v-if="products.length" @click="deleteElements" type="button" class="button"><?php _e( 'Очистить', 'usam'); ?></button>
				</div>		
				<?php
				if ( $this->add_items_table ) 
				{
					?>
					<div class="add_items_table" v-show="tableShow">	
						<?php require ( USAM_FILE_PATH.'/admin/templates/vue-templates/table-selection-products.php' ); ?>
					</div>
					<?php 
					add_action('admin_footer', function(){
						require_once( USAM_FILE_PATH.'/admin/templates/vue-templates/paginated-list.php' );
						require_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-viewer.php' );					
					});	
					?>				
				<?php } ?>
			</div>
			<div class='product_importer' v-show="importData">		
				<?php 
				require_once( USAM_FILE_PATH . '/admin/includes/product/product_form_importer.php' );
				$progress_form = new USAM_Product_Form_Importer( );
				$progress_form->display();
				?>
			</div>
		</div>		
		<?php 			
		$this->set_js_data( $columns );
	}
		
	public function set_js_data( $columns )
	{	
		self::$table_form[$this->type] = ['user_columns' => $this->get_user_columns(), 'columns' => $columns, 'column_names' => usam_get_columns_product_table(), 'products' => $this->products];
		$this->print_js_data();	
	}
}