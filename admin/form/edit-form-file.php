<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_file extends USAM_Edit_Form
{
	protected $folders = array();
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить файл','usam');
		else
			$title = __('Добавить файлы', 'usam');	
		return $title;
	}
	
	protected function form_class( ) 
	{ 
		return 'file_form';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_toolbar_buttons( ) 
	{
		return [	
			['name' => __('Посмотреть','usam'), 'action_url' => add_query_arg(['form' => 'view']), 'display' => 'not_null'],
			['submit' => "save", 'name' => __('Сохранить','usam'), 'display' => 'all'], 	
		];
	}
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();	
	}
	
	protected function get_data_tab(  )
	{			
		$default = ['id' => 0, 'maximum_load' => 0, 'groups' => [], 'user_id' => get_current_user_id(), 'title' => '', 'status' => 'closed', 'type' => '', 'folder_id' => 0, 'date_insert' => date("Y-m-d H:i:s"), 'thumbnail_id' => 0, 'thumbnail_url' => ''];
		if ( $this->id !== null )
		{
			$this->data = usam_get_file( $this->id );
			$this->data['maximum_load'] = (string)usam_get_file_metadata( $this->data['id'], 'maximum_load' );
			$this->data['thumbnail_id'] = (int)usam_get_file_metadata( $this->data['id'], 'thumbnail_id' );
			$this->data['thumbnail_url'] = (string)usam_get_file_metadata( $this->data['id'], 'thumbnail_url' );			
		}			
		$this->data = array_merge( $default, $this->data );
	}	
	
	protected function get_url_go_back( ) 
	{
		$url = remove_query_arg( array('id', 'n', 'form', 'form_name')  );	
		if ( $this->data['folder_id'] )
			$url = add_query_arg( array('folder' => $this->data['folder_id'] ), $url );		
		return $url;
	}
	
	function display_folders( $folder_id = 0, $recursion = 0 )
	{	
		$recursion++;	
		$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $recursion );			
		$ancestors_folders = get_option( 'usam_ancestors_folders', [] );
		foreach ( $this->folders as $folder ) 
		{	
			if ( $folder->parent_id == $folder_id )
			{
				?><option value='<?php echo $folder->id; ?>' <?php selected($this->data['folder_id'], $folder->id); ?>><?php echo $prefix.$folder->name; ?></option><?php
				$this->display_folders( $folder->id, $recursion );
			}
		}
	}
	
	function display_settings()
	{
		if ( $this->id == null )
			$this->display_attachments();
		?>		
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type'><?php esc_html_e( 'Дата добавления', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php echo usam_get_display_datetime_picker( 'insert', $this->data['date_insert'] ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Папка','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php $this->folders = usam_get_folders(['orderby' => 'name']); ?>						
					<select class="chzn-select" name = "folder">						
						<option value='0' <?php selected($this->data['folder_id'], 0); ?>><?php _e("Корневая папка","usam"); ?></option>
						<?php $this->display_folders( ); ?>
					</select>		
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Статус','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php 
					$statuses = usam_get_statuses_files(); 
					unset($statuses['delete']);
					?>
					<select v-model="data.status" name = "status">						
						<?php								
						foreach ( $statuses as $key => $status ) 
						{					
							?><option value='<?php echo $key; ?>'><?php echo $status; ?></option><?php
						}
						?>
					</select>		
				</div>
			</div>	
			<?php if ( $this->id ) { ?>	
			<div class ="edit_form__item" v-show="data.status=='open' || data.status=='limited'">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Публичная ссылка', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<span class="js-copy-clipboard"><?php echo usam_get_file_link( $this->data['code'] ); ?></span>
				</div>
			</div>
			<div class ="edit_form__item" v-show="data.status=='limited'">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Максимальное количество скачиваний', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<input type="text" name="maximum_load" v-model="data.maximum_load">
				</div>
			</div>			
			<?php } ?>				
			<?php 
			$types = usam_get_types_files();
			if ( !empty($types) )
			{
				$display = false;
				if ( $this->data['type'] )
				{
					foreach ( $types as $key => $type ) 
					{					
						if ( $this->data['type'] == $key && $this->data['type'] != 'loaded' )
						{
							$display = true;
							break;
						}
					}
				}
				if ( $display )
				{
					?>			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Тип файла', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">					
							<select v-model="data.type" name="type">									
								<?php							
								foreach ( $types as $key => $type ) 
								{					
									?><option value='<?php echo $key; ?>'><?php echo $type; ?></option><?php
								}
								?>
							</select>		
						</div>
					</div>	
					<?php 
				}
			}
			?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Обложка', 'usam'); ?>:</div>
				<div class ="edit_form__item_option" @click="addWpMedia">
					<div v-if="data.thumbnail_url" class="usam_thumbnail image_container"><img :src="data.thumbnail_url"></div>
					<span v-else class="button"><?php _e('Медиафайлы', 'usam'); ?></span>
					<input type='hidden' name='thumbnail_url' v-model='data.thumbnail_url'>
					<input type='hidden' name='thumbnail_id' v-model='data.thumbnail_id'>
				</div>
			</div>	
		</div>		
		<?php				
	}
	
	public function display_file( )
	{  			
		$filepath = USAM_UPLOAD_DIR.$this->data['file_path'];	
		$url = get_bloginfo('url').'/file/'.$this->data['code'];	
		$size = file_exists($filepath)?size_format( filesize($filepath) ):'';				
		echo "<div class='usam_attachments download_disabled'><div class='usam_attachments__file'>
			<a href='".$url."' title ='".$this->data['title']."' target='_blank'><img src='".usam_get_file_icon( $this->data['id'] )."'/></a>
		<div class='filename'>".usam_get_formatted_filename( $this->data['title'] )."</div>
		<div class='attachment__file_data__filesize'><a download href='".$url."' title ='".__('Сохранить этот файл себе на компьютер','usam')."' target='_blank'>".__('Скачать','usam')."</a>".$size."</div></div></div>";
	}
	
	function display_user_metabox()
	{		
		$this->display_user_block( $this->data['user_id'], 'user_id', __('Нет пользователя','usam'), true );
	}
	
	function display_left()
	{					
		?>	
		<div class='event_form_head'>			
			<?php 
			if ( $this->id )
			{
				?>	
				<div class='event_form_head__title'>
					<?php $this->titlediv( $this->data['title'] ); ?>				
				</div>
				<?php  
				$description = usam_get_file_metadata( $this->id, 'description' );
				$this->add_tinymce_description( $description, 'description' );
			}
			$this->display_settings();
			?>
		</div>
		<?php		
    }	
	
	function display_right()
	{		
		if ( $this->id !== null )		
		{
		//	usam_add_box( 'usam_file', __('Файл','usam'), [&$this, 'display_file']);
		}		
		$title = __('Личный кабинет','usam');
		$title_button = $this->data['user_id']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='personal_area' data-screen='personal_area' data-list='users' class='js-modal'>$title_button</a>";	
		
	//	usam_add_box( 'usam_manager', $title, [$this, 'display_user_metabox']);		
		?>
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>	
		<?php
    }
}
?>