<?php	
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );	
class USAM_Import_Form extends USAM_Edit_Form
{	
	protected $rule_type = '';	
	protected $vue = true;
	protected $JSON = true;
	protected $folders = [];
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) 
	{ 
		return 'import_form';
	}
	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf( __('Изменить шаблон &laquo;%s&raquo;','usam'), "{{data.name}}" ).'</span><span v-else>'.__('Добавить шаблон','usam').'</span>';
	}
		
	protected function get_data_tab()
	{	
		$default = ['id' => 0, 'name' => __('Новый шаблон', 'usam'), 'headings' => 0, 'type_import' => '', 'encoding' => '', 'splitting_array' => '|', 'schedule' => '', 'exchange_option' => '', 'file_data' => '', 'type' => $this->rule_type, 'type_file' => 'csv3', 'time' => '00:00', 'start_line' => 0, 'end_line' => 0, 'delete_file' => 1, 'columns' => [], 'columns2' => [], 'exception' => [], 'to_email' => '', 'subject' => '', 'groups' => ''];
		if( $this->rule_type == 'product_import' )
		{		
			$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
			foreach( $taxonomies as $taxonomy ) 
				$default[$taxonomy] = 0;
			$default['post_status'] = '';
			$default['contractor'] = 0;			
			$default['user_id'] = 0;
			$default['change_stock'] = 0;
			$default['product_views'] = 0;
			$default['change_price'] = 0;
			$default['change_price2'] = 0;
			$default['selection_raw_data'] = 'all';
			$default['not_updated_products_status'] = '';
			$default['not_updated_products_stock'] = 0;			
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
		if ( $this->id != null )
		{
			$this->data = usam_get_exchange_rule( $this->id );
			$metas = usam_get_exchange_rule_metadata( $this->id );
			foreach($metas as $metadata )
			{
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
				if ( ($metadata->meta_key == 'exception' || $metadata->meta_key == 'columns' || $metadata->meta_key == 'columns2') && !is_array($this->data[$metadata->meta_key]) )
					$this->data[$metadata->meta_key] = [];
			}			
		}			
		$this->data = usam_format_data( $default, $this->data );	
		if ( $this->data['headings'] )
			$count = count($this->data['columns']);		
		else
		{
			$keys = array_keys($this->data['columns']);	
			$count = end($keys);
			if( !is_numeric($count) )
				$count = count($this->data['columns']);	
		}
		$function = 'usam_get_columns_'.$this->data['type'];	
		if ( function_exists($function) )
		{
			$this->js_args['columns_available'] = $function();	
			array_unshift($this->js_args['columns_available'], '-');
		}
		$columns = [];			
		for ($i=0; $i<=$count; $i++)
		{
			if( $this->data['headings'] )
			{
				$d = array_keys($this->data['columns']);
				$d = isset($d[$i])?$d[$i]:''; 
			}
			else
				$d = !empty($this->data['columns'][$i])?$this->data['columns'][$i]:'';
			if( $this->data['headings'] )
			{
				$keys = array_keys($this->data['columns2']);
				$d2 = isset($keys[$i])?$this->data['columns2'][$keys[$i]]:''; 
			}
			else
				$d2 = !empty($this->data['columns2'][$i])?$this->data['columns2'][$i]:''; 
			if ( !isset($this->js_args['columns_available'][$d2]) )
				$d2 = '';
			if ( $this->data['headings'] )
			{
				$tmpkey = array_values($this->data['columns']);
				$tmpkey = isset($tmpkey[$i])?$tmpkey[$i]:''; 
			}
			else
				$tmpkey = empty($this->data['columns'][$i])?'':$this->data['columns'][$i]; 				
						
			if( $this->data['headings'] )
			{
				$keys = array_keys($this->data['exception']);			
				$exception = isset($keys[$i])?$this->data['exception'][$keys[$i]]:[]; 			
			}
			else
				$exception = !empty($this->data['exception'][$i])?$this->data['exception'][$i]:[];	
			$exception = array_merge( ['comparison' => '', 'value' => ''], $exception);	
			$columns[] = array_merge(  ['exception' => $exception], ['column' => $d, 'column2' => $d2, 'name' => $tmpkey] );
		}		
		$this->data['columns'] = $columns;			
		$this->js_args['folder'] = ['id' => '', 'name' => ''];	
		if ( $this->data['exchange_option'] == 'folder' )
		{
			if ( $this->data['file_data'] )
			{
				$folder = usam_get_folder( $this->data['file_data'] );
				if ( !empty($folder) )				
					$this->js_args['folder'] = $folder;			
			}
		}		
		$this->js_args['column_conditions'] = usam_get_conditions();
	}

	protected function get_columns()
	{
		return array();
	}	
	
	public function display_settings() 
	{
		$name_columns = [0 => __('Ручное определение', 'usam'), 1 => __('Содержит название колонок', 'usam')];
		?>
		 <div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_import'><?php esc_html_e( 'Вариант импорта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='type_import' id='type_import' v-model="data.type_import">						
						<option value=''><?php _e( 'Обновлять или создавать'  , 'usam'); ?></option>							
						<option value='update'><?php _e( 'Только обновить'  , 'usam'); ?></option>
						<option value='insert'><?php _e( 'Только создать', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_file'><?php esc_html_e( 'Тип файла', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="type_file" v-model="data.type_file">				
						<option value=''><?php esc_html_e( 'Автоматически определить', 'usam'); ?></option>
						<?php
						foreach (usam_get_types_file_exchange() as $key => $type_file)		
						{
							?><option value='<?php echo $key ?>'><?php echo $type_file['title']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='file_encoding'><?php esc_html_e( 'Кодировка файла' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<select id='file_encoding' v-model="data.encoding">						
						<option value=''><?php _e( 'Автоматически выбрать', 'usam'); ?></option>
						<option value='utf-8'>utf-8</option>
						<option value='utf-8-bom'>utf-8 BOM</option>		
						<option value='windows-1251'>windows-1251</option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='headings'><?php esc_html_e( 'Название колонок', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="headings" v-model="data.headings">				
						<?php
						foreach ($name_columns as $key => $title) 			
						{
							?><option value='<?php echo $key ?>'><?php echo $title ?></option><?php
						}
						?>
					</select>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='splitting_arrays'><?php esc_html_e( 'Разделение массивов', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id='splitting_arrays' type="text" v-model="data.splitting_array">
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='start_line'><?php esc_html_e( 'Начать со строки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id='start_line' type="text" v-model="data.start_line">
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='end_line'><?php esc_html_e( 'Закончить на строке', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id='end_line' type="text" v-model="data.end_line">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='exchange_option'><?php esc_html_e( 'Вариант обмена' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<?php $automations = usam_get_automations_exchange_rule(); ?>						
					<select id='exchange_option' v-model="data.exchange_option">
						<option value=''><?php _e("Вручную","usam"); ?></option>
						<?php
						foreach( $automations as $key => $title ) 			
						{			
							?><option value='<?php echo $key; ?>'><?php echo $title; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>		
			<div class ="edit_form__item" v-if="data.exchange_option=='folder'">
				<div class ="edit_form__item_name"><label for='option_folder'><?php _e( 'Папка','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<autocomplete :selected="folder.name" @change="data.file_data=$event.id; folder=$event" :request="'folders'" :none="'<?php _e('Нет данных','usam'); ?>'"></autocomplete>
				</div>
			</div>			
			<div class ="edit_form__item" v-else-if="data.exchange_option=='email'">
				<div class ="edit_form__item_name"><label for='option_folder'><?php _e( 'Отправитель','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "file_data" name="file_data" v-model="data.file_data">
				</div>
			</div>
			<div class ="edit_form__item"  v-else-if="data.exchange_option!=''">
				<div class ="edit_form__item_name"><label for='file_data'><?php esc_html_e( 'Путь к данным' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "file_data" name="file_data" v-model="data.file_data">
				</div>
			</div>				
			<div class ="edit_form__item" v-if="data.exchange_option=='email'">
				<div class ="edit_form__item_name"><?php _e( 'Получатель','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="to_email" v-model="data.to_email">
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.exchange_option=='email'">
				<div class ="edit_form__item_name"><?php _e( 'Тема письма содержит','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="subject" v-model="data.subject">
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='delete_file'><?php esc_html_e( 'Удалить файл после обработки' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='delete_file' v-model="data.delete_file">																	
						<option value='1'><?php esc_html_e('Да', 'usam'); ?></option>
						<option value='0'><?php esc_html_e('Нет', 'usam'); ?></option>
					</select>
				</div>
			</div>			
		</div>
		<?php
	}
			
	public function display_columns() 
	{
		?>		
		<usam-box :id="'usam_product_exporter_columns'" :handle="false" :title="'<?php _e( 'Настройки колонок', 'usam'); ?>'">
			<template v-slot:body>
				<level-table :lists='data.columns'>				
					<template v-slot:thead="slotProps">
						<th class="column_number"></th>	
						<th><?php _e( 'Свойство', 'usam'); ?></th>				
						<th class="column_name" v-show="data.headings==1"><?php _e( 'Название колонки', 'usam'); ?></th>					
						<th><?php _e( 'Исключение', 'usam'); ?></th>
						<th class="column_actions"></th>
					</template>			
					<template v-slot:tbody="slotProps">
						<td class="column_number">{{slotProps.k+1}}</td>
						<td class="column-code">					
							<div class="level_table_row">
								<select-list @change="slotProps.row.column=$event.id; slotProps.row.name=$event.id" :lists="columns_available" :selected="slotProps.row.column"></select-list>
								<input type="hidden" v-model="slotProps.row.column">							
								<span class="open_action" v-if="slotProps.row.column2==''" @click="slotProps.row.column2='-'"><?php _e( 'Скопировать в свойство', 'usam'); ?></span>
								<select-list v-if="slotProps.row.column2!=''" @change="slotProps.row.column2=$event.id" :lists="columns_available" :selected="slotProps.row.column2"></select-list>
							</div>
						</td>
						<td class='column_name' v-show="data.headings==1">									
							<input type="text" v-model="slotProps.row.name" class="input-text" placeholder='<?php _e('Название колонки в файле', 'usam'); ?>'/>
						</td>
						<td>
							<div class="level_table_row">
								<select class='columns_comparison' v-model="slotProps.row.exception.comparison">
									<option value=''></option>
									<option :value='t' v-for="(name, t) in column_conditions">{{name}}</option>
								</select>
								<input type='text' v-if="slotProps.row.exception.comparison!=''" class='compare_columns_value' v-model="slotProps.row.exception.value" placeholder='<?php _e('Исключение если нужно', 'usam'); ?>'>
							</div>
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
	
	public function display_automation() 
	{		
		?>
		<div class='edit_form'>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_schedule'><?php esc_html_e( 'Запускать каждые' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='option_schedule' v-model="data.schedule">						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_time'><?php esc_html_e( 'Время' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_time" v-model="data.time"/>
				</div>
			</div>									
			<div class ="edit_form__item">				
				<select-list @change="data.weekday=$event.id" :multiple='1' :lists='<?php echo json_encode( usam_get_weekday() ); ?>' :selected="data.weekday"></select-list>
			</div>
		</div>
		<?php
	}
	
	function display_right()
	{	
		usam_add_box(['id' => 'usam_automation', 'title' => __('Автоматизация','usam'), 'function' => [$this, 'display_automation'], 'close' => false, 'tag_parameter' => ['v-if="data.exchange_option!=`` && data.exchange_option!=`email`"']]);
		
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
		usam_add_box( 'usam_product_default_values', __('Настройки значения по умолчанию','usam'), array( $this, 'display_default_values' ));
		$this->display_columns();	
    }
}
?>