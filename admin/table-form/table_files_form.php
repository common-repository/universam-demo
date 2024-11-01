<?php
require_once( USAM_FILE_PATH .'/admin/includes/table_form.php' );		
class USAM_Table_Files_Form extends USAM_Table_Form
{		
	protected $add_items_table = false;
	public function select_buttons( )
	{	
		?>
		<button v-if="!edit && abilityChange" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить или удалить файл', 'usam'); ?></button>
		<div v-if="edit && abilityChange && !edit_form" class="select_product__buttons">			
			<button type="button" class="button button-primary" @click="saveElement"><?php _e( 'Сохранить', 'usam'); ?></button>
			<button type="button" class="button" @click="edit=!edit"><?php _e( 'Отменить', 'usam'); ?></button>
		</div>
		<?php
	}
	
	public function display( $columns )
	{		
		?>
		<div id="add_items_<?php echo $this->type; ?>" v-show="pLoaded" class="select_product" table_type="<?php echo $this->type; ?>">
			<?php  $this->display_product_table( $columns ); ?>
			<div class="add_items" v-if="edit && abilityChange">
				<div class="add_items__options">				
					<?php
					if ( $this->add_items_table )
					{
						?><button type="button" class="button open_table" @click="tableShow=!tableShow"><span class="dashicons dashicons-list-view"></span></button><?php 
					} ?>
					<div class="add_items__add">	
						<autocomplete @change="selectElement" :request="'companies'" :selected="searchItem" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>	
					</div>
				</div>					
			</div>			
		</div>		
		<?php
		$this->set_js_data( $columns );
	}
		
	public function display_product_table( $columns )
	{		
		?>
		<div id ="table_container" class = 'table_products_container'>
			<table class="usam_list_table table_products">
				<thead>
					<tr>
						<th scope="col" class="manage-column" :class="'column-'+column.id" v-for="column in table_columns">
							<span v-if="column.id=='tools'" class="dashicons dashicons-list-view open_columns_tools" @click="columns_tools=!columns_tools"></span>
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
					<?php $this->display_items_empty(); ?>			
					<?php $this->display_body_table(); ?>
				</tbody>
				<tfoot>
					<?php $this->display_total_table(); ?>	
				</tfoot>			
			</table>							
		</div>		
		<?php
	}	
	
	protected function display_body_table()
	{		
		?>
		<tr v-if="items.length" v-for="(item, k) in items">
			<td class="column-n">{{k+1}}</td>
			<td class="column-title">
				<div class="user_block">
					<a :href="item.url" v-if="item.logo" class="usam_foto image_container">
						<img src="item.logo"></a>
					<div>
						<a :href="item.url" v-html="item.name"></a>
					</div>
				</div>	
			</td>				
			<td class="column-delete">					
				<a class="action_delete" href="" v-if="edit && abilityChange" @click="delElement($event, k)"></a>
			</td>	
		</tr>
		<?php		
	}
}