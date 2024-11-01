<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_parser extends USAM_Edit_Form
{		
	protected $JSON = true;	
	protected $vue = true;
	protected function get_data_tab()
	{
		$default = ['id' => 0, 'name' => '', 'view_product' => 'card', 'translate' => '', 'product_loading' => '', 'type_import' => '', 'existence_check' => 'url', 'site_type' => $this->site_type, 'domain' => '', 'active' => 0, 'store' => 0, 'scheme' => 'https', 'bypass_speed' => 1, 'type_price' => usam_get_manager_type_price(), 'headers' => '', 'authorization' => 0, 'login_page' => '', 'authorization_parameters' => '', 'proxy' => 0, 'setting' => ['sku' => '', 'price' => '', 'title' => '', 'content' => '', 'not_available' => '', 'thumbnail' => ''], 'link_option' => 0, 'excluded' => '', 'urls' => [['conditions' => [['tag' => '', 'operator' => '', 'value' => '']], 'url' => '', 'category' => 0, 'status' => 0]], 'tags' => [], 'contractor' => 0, 'post_status' => ''];
		if ( $this->id != null )
		{				
			$this->data = usam_get_parsing_site( $this->id );
			if ( empty($this->data) )
				return false;
			foreach( $this->data as $k => $value )
				if ( $value )
					$this->data[$k] = htmlspecialchars($value);
			$metas = usam_get_parsing_site_metadata( $this->id );
			foreach($metas as $metadata )
			{
				if ( $metadata->meta_key != 'tags' && $metadata->meta_key != 'urls' )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);				
			}		
			$tags = usam_get_parsing_site_metadata( $this->id, 'tags' );				
			$urls = usam_get_parsing_site_metadata( $this->id, 'urls' );	
			if ( $urls )
			{
				$this->data['urls'] = [];				
				foreach( $urls as $url )
					$this->data['urls'][] = array_merge( $default['urls'][0], $url );
			}			
		}		
		$this->data = array_merge( $default, $this->data );		
		foreach( $this->get_tags() as $key => $block )
		{				
			$tag = !empty($tags[$key])?$tags[$key]:['number' => 0];			
			if( !empty($tag['plural']) && isset($tag['number']) )
				unset($tag['number']);
			else
				$block['number'] = 0;
			$tag = usam_format_data( $block, $tag );	
			$this->data['tags'][$key] = $tag;
		}	
		$blocks_variations = ['sku' => ['title' => __('Атрибут артикула вариации','usam')], 'price' => ['title' => __('Атрибут цены вариации','usam')]];
		foreach( $blocks_variations as $key => $block )
			$this->data['variations'][$key] = !empty($this->data['variations'][$key])?$this->data['variations'][$key]:'';
		$this->js_args = [		
			'blocks_variations' => $blocks_variations,
		];		
		add_action( 'admin_footer', [&$this, 'admin_footer']);	
	}
	
	protected function toolbar_buttons( ) 
	{						
		$this->display_toolbar_buttons();
		$this->main_actions_button();
	}
	
	function get_tags()
	{			
		return [];
    }
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	function display_right()
	{			
		?>
		<usam-box :id="'usam_storage_status_active'" :title="'<?php _e( 'Статус активности', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked v-model="data.active" :text="'<?php _e('Активно', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<?php
    }
	
	function admin_footer()
	{
		echo usam_get_modal_window( __('Проверить соединение','usam'), 'check_connection', "<div class='modal-body status_connection'></div>", 'medium' );	
	}
				
	public function display_login( )
	{		
		?>	
		<input type="hidden" name="link_option" v-model="data.link_option">
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login_page'><?php esc_html_e( 'Ссылка на страницу авторизации', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_login_page" type="text" name="login_page" v-model="data.login_page">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_user'><?php esc_html_e( 'Параметры в виде json', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_user" type="text" name="authorization_parameters" v-model="data.authorization_parameters">
				</div>
			</div>			
			<div class ="edit_form__item" v-if="data.id && data.login_page">
				<div class ="edit_form__item_name"></div>
				<div class ="edit_form__item_option">
					<button type="button" @click="testLogin" class="button"><?php _e( 'Тест', 'usam'); ?></button>
					<span v-if="resultTest===true" class="item_status item_status_valid"><?php _e( 'Авторизация прошла успешно', 'usam'); ?></span>
					<span v-if="resultTest===false" class="item_status item_status_attention"><?php _e( 'Авторизация не удалась', 'usam'); ?></span>
				</div>
			</div>
		</div>
      <?php
	}    
	
	function display_form_tags()
	{				
		?>
		<div class="usam_table_container">		
		<table class="table_options widefat striped">
			<thead>
				<tr>					
					<th class="column_name"><?php _e( 'Название тега', 'usam'); ?></th>	
					<th class="column_tag"><?php _e( 'Тег', 'usam'); ?></th>					
					<th class="column_rules"><?php _e( 'Процесс обработки', 'usam'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(tag, k) in data.tags">	
					<td class="column_name" v-html="tag.title+':'"></td>
					<td class="column_tag">				
						<div class ="level_table_row">
							<input type="text" v-model="tag.tag">
							<input v-if="!tag.plural || tag.json || tag.json_mass" style="width:50px;" type="text" v-model="tag.number">
							<a class="add_condition" @click="tag.json=1" v-if="!tag.json && !tag.json_mass">json</a>	
						</div>
						<div class ="level_table_row" v-if="tag.json || tag.json_mass">						
							<span class="nowrap"><?php _e( 'Ключи массива json', 'usam'); ?></span><input type="text" v-model="tag.json_mass">
						</div>
					</td>					
					<td class="column_rules">
						<div class ="level_table_row" v-for="(rule,i) in tag.rules" v-if="tag.tag!==''">
							<select class='columns_comparison' v-model="rule.operator">
								<option value=''></option>
								<option :value='t' v-for="(name, t) in {replace:'<?php _e('Заменить','usam'); ?>', regular:'<?php _e('Регулярное выражение','usam'); ?>', preg_match_all: 'preg_match_all', preg_match: 'preg_match', strip_tags:'<?php _e('Удаляет HTML теги','usam'); ?>', trim:'<?php _e('Удаляет символ из начала и конца строки','usam'); ?>', ltrim:'<?php _e('Удаляет символ из начала строки','usam'); ?>', rtrim:'<?php _e('Удаляет символ из конца строки','usam'); ?>', explode:'<?php _e('Разбивает строку с помощью разделителя','usam'); ?>', float:'<?php _e('Преобразовать в float','usam'); ?>', tabs:'<?php _e('Удалить табуляцию и пробелы из начала и конца','usam'); ?>'}">{{name}}</option>
							</select>
							<input type="text" v-model="rule.search"  v-if="rule.operator=='replace'||rule.operator=='regular'" placeholder="<?php echo _e( 'Искомое значение', 'usam'); ?>">
							<input type="text" v-model="rule.replace" v-if="rule.operator!==''&&rule.operator!=='strip_tags'&&rule.operator!=='tabs'&&rule.operator!=='float'" placeholder="<?php echo _e( 'Значение замены', 'usam'); ?>">
							<span class="dashicons dashicons-no-alt delete_row" @click="tag.rules.splice(i, 1)"></span>	
						</div>		
						<a class="add_condition" @click="tag.rules.push({operator:'', search:'', replace:''})" v-if="tag.tag!==''"><?php _e( 'Добавить', 'usam'); ?></a>	
					</td>
				</tr>
			</tbody>	
		</table>
		</div>	
		<?php
		require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-tag-testing.php' );		
	}

	public function display_urls() 
	{
		?>
		<level-table :lists='data.urls'>				
			<template v-slot:thead="slotProps">
				<th class="column_url"><?php echo esc_html_e( 'Ссылка', 'usam'); ?></th>	
				<th class="column_category" v-if="data.site_type=='supplier'"><?php echo _e( 'Прикрепить к категории', 'usam'); ?></th>					
				<th class="column_condition"><?php echo esc_html_e( 'Условие', 'usam'); ?></th>	
				<th class="column_actions"></th>	
			</template>	
			<template v-slot:tbody="slotProps">
				<td class="column_url">
					<div class="parser_process">
						<span class="parser_process_online customer_online" v-if="slotProps.row.status==1"></span>
						<input type="text" v-model="slotProps.row.url">
					</div>
				</td>
				<td class="column_category" v-if="data.site_type=='supplier'">				
					<select v-model="slotProps.row.category">
						<option value='0'><?php esc_html_e( 'Создать новую как у сайта донора', 'usam'); ?></option>
							<?php wp_terms_checklist( 0,  ['descendants_and_self' => 0, 'selected_cats' => [], 'popular_cats' => false, 'walker' => new Walker_Category_Select(), 'taxonomy' => 'usam-category', 'checked_ontop' => false, 'echo' => true]);?>
					</select>
				</td>					
				<td class="column_condition">
					<div class ="level_table_row" v-for="(condition, i) in slotProps.row.conditions">
						<select class='columns_comparison' v-model="condition.tag">
							<option value=''></option>
							<option :value='t' v-for="(tag, t) in data.tags" v-if="tag.tag!==''">{{tag.title}}</option>
						</select>							
						<select class='columns_comparison' v-model="condition.operator" v-if="condition.tag!==''">
							<option value=''></option>
							<option :value='t' v-for='(name, t) in <?php echo json_encode( usam_get_conditions() ); ?>'>{{name}}</option>
						</select>
						<input type="text" v-model="condition.value" v-if="condition.operator!==''">	
						<span class="dashicons dashicons-no-alt delete_row" @click="slotProps.row.conditions.splice(i, 1)"></span>								
					</div>		
					<a class="add_condition" @click="slotProps.row.conditions.push({tag: '', operator:'', value:''})"><?php echo esc_html_e( 'Добавить', 'usam'); ?></a>							
				</td>
				<td class="column_actions">
					<?php echo usam_get_system_svg_icon("drag", ["draggable" => "true"]); ?>
					<?php echo usam_get_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
					<?php echo usam_get_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>												
				</td>
			</template>
		</level-table>
		<?php
	}
}
?>