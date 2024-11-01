<?php
/**
 *  Мастер импорта
 */
abstract class USAM_Importer
{
	protected $template = true;	
	protected $rule_type = '';		
	protected function get_columns()
	{
		return array();
	}
	
	public function get_url()
	{
		return admin_url('admin.php?page=exchange');
	}
	
	public function get_id()
	{
		return $this->rule_type;
	}
	
	public function get_steps()
	{
		return ['file' => __('Выбор файла', 'usam'), 'settings' => __('Настройки импорта', 'usam'), 'columns' => __('Назначение столбцов', 'usam'), 'finish' => __('Импорт', 'usam')];
	}	
	
	public function display( )
	{					
		?>
		<div id="<?php echo $this->get_id(); ?>" class="importer progress_form" v-cloak>
			<?php $this->display_content_import(); ?>
		</div>	
		<?php		
	}
	
	public function display_content_import( )
	{				
		add_action('admin_footer', array(&$this, 'admin_footer'), 100);			
		?>
		<ol class="progress_form__steps">
			<li v-for="(title, k) in steps" :class="[current_step==k?'active':'']" class="importer__step">{{title}}</li>
		</ol>			
		<?php
		foreach ($this->get_steps() as $key => $title) 		
		{
			$method = 'process_'.$key;			
			if ( method_exists($this, $method) )
			{
				?>
				<div class="importer__content progress_form_content progress_form_content_<?php echo $key; ?>" :class="{'active':current_step=='<?php echo $key; ?>'}">	
					<?php $this->$method(); ?>
				</div>
				<?php
			}
		}	
	}
	
	public function admin_footer( )
	{			
		wp_enqueue_style( 'usam-progress-form' );		
		wp_enqueue_script( 'usam-importer' );
		$columns = [['id' => '', 'name' => __('Не использовать', 'usam')]];
		foreach ($this->get_columns() as $id => $name) 
		{
			$columns[] = ['id' => $id, 'name' => $name];
		}
		wp_localize_script( 'usam-importer', 'USAM_Importer', [			
			'steps' => $this->get_steps(),	
			'itemProperties' => $columns,
			'form_save_nonce' => usam_create_ajax_nonce( 'form_save' ),				
			'rule_type' => $this->rule_type,
			'compare_invoices_nonce' => usam_create_ajax_nonce('compare_invoices'),				
		]);
	}
	
	protected function default_columns() { 	}
	
	public function file_selection()
	{
		?>
		<div class='usam_attachments' @click="fileAttach" @drop="fileDrop" @dragover="allowDrop" v-show="source=='file'">
			<div class='usam_attachments__file' v-if="file.title!='' || file.load" :class="[file.error?'loading_error':'']" >
				<a class='usam_attachments__file_delete delete' @click="fileDelete"></a>							
				<div class='attachment_icon'>	
					<img v-show="file.load==false" :src='file.icon'/>	
					<progress-circle v-show="file.load" :percent="file.percent"></progress-circle>
				</div>
				<div class='attachment__file_data'>
					<div class='filename'>{{file.title}}</div>					
					<div v-if="file.error" class='attachment__file_data__error'>{{file.error_message}}</div>
					<div v-else class='attachment__file_data__filesize'>{{file.size}}</div>
				</div>
			</div>
			<div class ='attachments__placeholder' v-else><?php esc_html_e( 'Перетащите или нажмите, чтобы', 'usam'); ?><br><?php esc_html_e( 'прикрепить файл', 'usam'); ?></div> 
			<input type='file' @change="fileChange"/>	
		</div>
		<?php
	}
		
	public function process_file()
	{
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
		?>
		<h3><?php _e('Выбор файла' , 'usam'); ?></h3>
		<?php $this->file_selection(); ?>				
		<?php if ( $this->template ) {  ?>
			<div class='edit_form'>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='template_id'><?php esc_html_e( 'Шаблон', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model="template_id" id="template_id">				
							<option value='0'><?php esc_html_e( 'Не использовать шаблон', 'usam'); ?></option>	
							<?php
							$rules = usam_get_exchange_rules(['type' => $this->rule_type]);									
							foreach ($rules as $rule) 			
							{
								?><option value='<?php echo $rule->id ?>'><?php echo $rule->name; ?></option><?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		<?php }  ?>
		<div class='actions'>	
			<button class="button button-primary" type="button" @click="next_step"><?php esc_html_e( 'Продолжить', 'usam'); ?></button>
		</div>
		<?php 
	}
	
	public function process_settings()
	{
		?>
		<div v-show="source=='file'">
			<h3><?php _e('Настройки импорта' , 'usam'); ?></h3>		
			<div class ="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='name_columns'><?php esc_html_e( 'Название колонок', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model="rule.headings" id="name_columns">				
							<?php	
							foreach ([0 => __('Ручное определение', 'usam'), 1 => __('Содержит название колонок', 'usam')] as $key => $title) 			
							{
								?><option value='<?php echo $key ?>' ><?php echo $title ?></option>	<?php
							}
							?>
						</select>
					</div>
				</div>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='type_import'><?php esc_html_e( 'Вариант импорта', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model="rule.type_import" id='type_import'>						
							<option value=''><?php _e( 'Обновлять или создавать'  , 'usam'); ?></option>							
							<option value='update'><?php _e( 'Только обновить'  , 'usam'); ?></option>
							<option value='insert'><?php _e( 'Только создать', 'usam'); ?></option>
						</select>
					</div>
				</div>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='type_file'><?php esc_html_e( 'Тип файла', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model="file_settings.type_file" id="type_file">				
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
						<select v-model="file_settings.encoding" id='file_encoding'>						
							<option value=''><?php _e( 'Автоматически выбрать', 'usam'); ?></option>
							<option value='utf-8'>utf-8</option>
							<option value='utf-8-bom'>utf-8 BOM</option>		
							<option value='windows-1251'>windows-1251</option>
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='start_line'><?php esc_html_e( 'Начать со строки', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input v-model="file_settings.start_line" id='start_line' type="text" value="">
					</div>
				</div>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='end_line'><?php esc_html_e( 'Закончить на строке', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input v-model="file_settings.end_line" id='end_line' type="text" value="">
					</div>
				</div>				
			</div>
			<div class='actions'>				
				<button class="button button-primary" type="button" @click="next_step"><?php esc_html_e( 'Продолжить', 'usam'); ?></button>		
			</div>	
		</div>	
		<?php
	}	
	
	public function process_columns()
	{			
		?>				
		<div v-show="source=='file'">
			<div v-if="data_loaded">		
				<div v-if="filedata.length>0">
					<h3><?php _e('Выбор столбцов' , 'usam'); ?></h3>						
					<div class ="table_columns" v-if="rule.headings==0">						
						<div class ="edit_form">		
							<div class ="edit_form__item" v-for="(datum, k) in filedata">
								<div class ="edit_form__item_name">
									<span class="column_number">{{k+1}}</span><span class ="column_title">{{datum}}</span>
								</div>
								<div class ="edit_form__item_option">									
									<select-list @change="value_name[k]=$event.id" :lists="itemProperties" :selected="datum" :search='1'></select-list>
								</div>
							</div>	
						</div>		
					</div>
					<?php $this->default_columns(); ?>					
					<div class='actions'>				
						<button class="button button-primary" type="button" @click="next_step"><?php esc_html_e( 'Импортировать', 'usam'); ?></button>						
						<?php 
						if ( $this->template ) { 
							?><button v-if="template_id==0" class="button" @click="template"><?php esc_html_e( 'Сделать шаблон', 'usam'); ?></button><?php	
						} ?>
						<button class="button" type="button" @click="current_step='settings'"><?php esc_html_e( 'Назад', 'usam'); ?></button>
					</div>	
				</div>			
				<div v-else-if="data_loaded">
					<h3><?php _e('Данные в вашем файле не найдены' , 'usam'); ?></h3>	
					<p><?php _e('Возможно файл пустой или использует не стандартный разделитель колонок.' , 'usam'); ?></p>		
					<div class='actions'>				
						<button class="button button-primary" type="button" @click="current_step='file'"><?php esc_html_e( 'Начать сначала', 'usam'); ?></button>	
					</div>	
				</div>
			</div>
			<div v-else >
				<h3><?php _e('Загрузка данных...' , 'usam'); ?></h3>				
			</div>
		</div>
		<?php			
	}
	
	public function process_finish()
	{		
		?>
		<h3><?php _e('Идет процесс импорта', 'usam'); ?></h3>
		<p><?php _e( 'Задание импорта добавлено', 'usam'); ?></p>			
		<div class='actions'>
			<button class="button" type="button" @click="current_step='file'"><?php esc_html_e( 'Начать сначала', 'usam'); ?></button>
			<a href='<?php echo $this->get_url(); ?>' class="button"><?php _e('Вернуться к шаблонам', 'usam') ?></a>
			<?php 
			if ( $this->template ) { 
				?><button v-if="template_id==0" type="button" class="button button-primary" @click="template"><?php esc_html_e( 'Сделать шаблон', 'usam'); ?></button><?php	
			} ?>
		</div>	
		<?php
	}
}
?>