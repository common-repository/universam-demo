<?php	
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );	
class USAM_Form_Export extends USAM_Edit_Form
{	
	protected $rule_type = '';	
	protected $vue = true;
	protected $JSON = true;
		
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf( __('Изменить шаблон &laquo;%s&raquo;','usam'), "{{data.name}}" ).'</span><span v-else>'.__('Добавить шаблон экспорта','usam').'</span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) 
	{ 
		return 'import_form';
	}
	
	protected function get_default()
	{ 
		$default = ['id' => 0, 'name' => __('Новый шаблон', 'usam'), 'headings' => 0, 'split_into_files' => '', 'type_import' => 'csv3', 'encoding' => '', 'splitting_array' => '|', 'schedule' => '', 'exchange_option' => '', 'file_data' => '', 'type' => $this->rule_type, 'type_file' => 'exel', 'time' => '00:00', 'start_line' => 0, 'end_line' => 0, 'compare_columns' => [], 'columns' => [], 'orderby' => 'date', 'order' => 'ASC', 'type_price' => '', 'from_day' => '', 'to_day' => '', 'from_price' => '', 'to_price' => '', 'from_stock' => '', 'to_stock' => '', 'from_total_balance' => '', 'to_total_balance' => '', 'contractors' => [], 'from_dateinsert' => '', 'to_dateinsert' => '', 'groups' => [], 'status' => [], 'source' => [], 'from_id' => '', 'to_id' => ''];
		if( $this->rule_type == 'product_export' || $this->rule_type == 'pricelist' )
		{		
			$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
			foreach( $taxonomies as $taxonomy ) 
				$default[$taxonomy] = [];
			
			$default = array_merge(['contractor' => '', 'from_views' => '', 'to_views' => ''], $default );			
			if( $this->rule_type == 'pricelist' )
				$default = array_merge(['file_generation' => '', 'roles' => []], $default );
		}
		elseif( $this->rule_type == 'contact_export' )
		{		
			$default = array_merge(['from_ordercount' => '', 'to_ordercount' => '', 'from_ordersum' => '', 'to_ordersum' => '', 'location' => 0, 'sex' => '', 'from_age' => '', 'to_age' => '', 'post' => ''], $default );	
			
		}
		elseif( $this->rule_type == 'order_export' )
		{		
			$default = array_merge(['order_industry' => '', 'from_ordersum' => '', 'to_ordersum' => '', 'location' => 0, 'from_productcount' => '', 'to_productcount' => ''], $default );	
		}
		elseif( $this->rule_type == 'company_export' )
		{		
			$default = array_merge(['from_ordercount' => '', 'to_ordercount' => '', 'from_ordersum' => '', 'to_ordersum' => '', 'company_industry' => [], 'company_type' => [], 'location' => 0], $default );	
		}
		return $default;
	}	
	
	protected function get_data_tab(  )
	{
		if ( $this->id != null )
		{
			$this->data = usam_get_exchange_rule( $this->id );		
			$metas = usam_get_exchange_rule_metadata( $this->id );
			foreach($metas as $metadata )
			{
				if ( !isset($this->data[$metadata->meta_key]) )
				{
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
					if ( ($metadata->meta_key == 'compare_columns' || $metadata->meta_key == 'compare_columns_value' || $metadata->meta_key == 'columns' || $metadata->meta_key == 'columns2') && !is_array($this->data[$metadata->meta_key]) )
						$this->data[$metadata->meta_key] = [];
				}
			}
		}				
		$default = $this->get_default();
		$this->data = usam_format_data( $default, $this->data );	
		if ( $this->data['headings'] )
			$count = count($this->data['columns']);					
		else
		{
			$count = array_keys($this->data['columns']);				
			$count = array_pop($count);							
		}
		$function = 'usam_get_columns_'.$this->data['type'];	
		if ( function_exists($function) )
		{
			$this->js_args['columns_available'] = $function();	
			array_unshift($this->js_args['columns_available'], '-');
		}
		$columns = [];
		$count = count($this->data['columns']);			
		for ($i=0; $i<=$count; $i++)
		{
			$d = array_keys($this->data['columns']);
			$d = isset($d[$i])?$d[$i]:''; 			
			$tmpkey = array_values($this->data['columns']);
			$tmpkey = isset($tmpkey[$i])?$tmpkey[$i]:''; 		
			$columns[] = ['column' => $d, 'name' => $tmpkey];
		}	
		$this->data['columns'] = $columns;
	}
	
	protected function get_columns_sort()
	{
		return [];		
	}
		
	public function display_settings() 
	{		
		?>
		 <div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_file'><?php esc_html_e( 'Тип файла выгрузки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="type_file" v-model="data.type_file">				
						<?php
						foreach (usam_get_types_file_exchange() as $key => $type_file)
						{
							?><option value='<?php echo $key ?>' <?php selected($this->data['type_file'], $key); ?>><?php echo $type_file['title'] ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='splitting_arrays'><?php esc_html_e( 'Разделение массивов', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><input id='splitting_arrays' type="text" v-model="data.splitting_array"></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='headings'><?php esc_html_e( 'Заголовки столбцов', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
						<select id="headings" v-model="data.headings">												
						<option value='0'><?php _e('Не добавлять','usam') ?></option>	
						<option value='1'><?php _e('Добавлять коды столбцов','usam') ?></option>			
						<option value='2'><?php _e('Добавлять названия столбцов','usam') ?></option>							
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_orderby'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="orderby" id="option_orderby" v-model="data.orderby">				
						<?php 					
						foreach ($this->get_columns_sort() as $key => $title) 			
						{
							?><option value='<?php echo $key; ?>'><?php echo $title; ?></option>	<?php 
						}
						?>						
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_order'><?php esc_html_e( 'Направление сортировки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id='option_order' v-model="data.order">				
						<option value='ASC'><?php _e('По порядку','usam') ?></option>	
						<option value='DESC'><?php _e('Обратный порядок','usam') ?></option>						
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Разбить на несколько файлов', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select v-model="data.split_into_files">
						<option value=''><?php _e('Не разбивать', 'usam'); ?></option>
						<?php				 
						$possible_columns = $this->get_possible_columns();						
						foreach( $possible_columns as $key => $title ) 			
						{			
							?><option value='<?php echo $key; ?>'><?php printf( __('По колонке - %s','usam'), $title); ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<?php
	}
	
	public function get_columns() 
	{
		return (array)usam_get_exchange_rule_metadata( $this->id, 'columns' );
	}	
	
	protected function get_possible_columns() 
	{
		$function = 'usam_get_columns_'.$this->rule_type;
		$possible_columns = [];
		if ( function_exists($function) )
			$possible_columns = $function();
		return $possible_columns;
	}
		
	public function display_products_selection() 
	{		
		?>
		 <div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_day'><?php esc_html_e('Товары, добавленные за указанные дни', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="number" id="from_day" v-model="data.from_day" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> -
					<input type="number" v-model="data.to_day" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_price'><?php esc_html_e( 'Тип цены', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select-list @change="data.type_price=$event.id" :lists="prices" :selected="data.type_price"></select-list>	
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_price'><?php esc_html_e( 'Цена', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="from_price" v-model="data.from_price" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> -
					<input type="text" v-model="data.to_price" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_stock'><?php esc_html_e( 'Доступный остаток', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.from_stock" id="from_stock" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="text" v-model="data.to_stock" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_total_balance'><?php esc_html_e( 'Общий остаток', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" v-model="data.from_total_balance" id="from_total_balance" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="text" v-model="data.to_total_balance" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_views'><?php esc_html_e( 'Просмотры', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="number" v-model="data.from_views" id="from_views" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="number" v-model="data.to_views" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>	
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Поставщики товара','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.contractor=$event.id" :multiple='1' :lists="contractors" :selected="data.contractor"></select-list>	
				</div>
			</div>
			<div class ="edit_form__item" v-for="(item, k) in taxonomies" v-if="terms[item.name] !== undefined">
				<div class ="edit_form__item_name">{{item.label}}:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data[item.name]=$event.id" :multiple='1' :lists="terms[item.name]" :selected="data[item.name]"></select-list>	
				</div>
			</div>
		</div>	
	   <?php   
	}
			
	public function display_filter() { }	
	public function display_automation() { }

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
		usam_add_box( 'usam_product_select', __('Настройки выбора','usam'), array( $this, 'display_filter' ));	
		$this->display_columns();
    }		
	
	function display_columns()
	{			
		?>
		<usam-box :id="'usam_product_exporter_columns'" :handle="false" :title="'<?php _e( 'Настройки колонок', 'usam'); ?>'">
			<template v-slot:body>
				<level-table :lists='data.columns'>				
					<template v-slot:thead="slotProps">
						<th class="column_number"></th>	
						<th><?php _e( 'Свойство', 'usam'); ?></th>				
						<th class="column_name" v-show="data.headings!=0"><?php _e( 'Название колонки', 'usam'); ?></th>	
						<th class="column_actions"></th>
					</template>			
					<template v-slot:tbody="slotProps">
						<td class="column_number">{{slotProps.k+1}}</td>
						<td class="column-code">					
							<div class="level_table_row">
								<select-list @change="slotProps.row.column=$event.id; slotProps.row.name=$event.id" :lists="columns_available" :selected="slotProps.row.column"></select-list>
							</div>
						</td>
						<td class='column_name' v-show="data.headings!=0">									
							<input type="text" v-model="slotProps.row.name" class="input-text" placeholder='<?php _e('Название колонки в файле', 'usam'); ?>'/>
						</td>					
						<td class="column_actions">		
							<?php echo usam_get_system_svg_icon("drag", ["draggable" => "true"]); ?>
							<?php echo usam_get_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
							<?php echo usam_get_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>										
						</td>
					</template>
				</level-table>
			</template>
		</usam-box>	
		<?php 	
	}	

	function display_right()
	{			
		usam_add_box( 'usam_automation', __('Автоматизация','usam'), array( $this, 'display_automation' ) );	
	}
	
	
	protected function toolbar_buttons9( ) 
	{ 		
		?>		
		<div class="action_buttons__button" v-if="data.id>0"><a @click="download" class="button"><?php _e('Скачать','usam'); ?></a></div>
		<div v-if="data.id>0">
			<?php
			$links[] = ['action' => 'deleteItem', 'title' => esc_html__('Удалить', 'usam'), 'capability' => 'delete_'.$this->data['type']];				
			$this->display_form_actions( $links );
			?>
		</div>
		<?php
	}	
	
	protected function get_toolbar_buttons( ) 
	{ 		
		$links = [					
			['vue' => ["@click='download'"], 'primary' => false, 'name' => __('Скачать','usam')],
			['vue' => ["@click='saveForm'"], 'primary' => true, 'name' => $this->title_save_button()],
		];
		return $links;
	}
}
?>