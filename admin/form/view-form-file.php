<?php		
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_file extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Файл &laquo;%s&raquo;','usam'), $this->data['title'] );
	}
	
	protected function get_data_tab(  )
	{		
		$this->data = usam_get_file( $this->id );
		$this->data['maximum_load'] = (string)usam_get_file_metadata( $this->data['id'], 'maximum_load' );
	}
	
	public function form_class( ) 
	{ 
		return 'file_form';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{
		?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php	
	}
	
	protected function main_content_cell_1( ) 
	{
		$folder = usam_get_folder( $this->id );			
		?>							
		<div class="view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php
					$statuses = usam_get_statuses_files(); 
					unset($statuses['delete']);
					?>
					<select v-model="data.status" name = "status" @change="save">						
						<?php								
						foreach ( $statuses as $key => $status ) 
						{					
							?><option value='<?php echo $key; ?>'><?php echo $status; ?></option><?php
						}
						?>
					</select>		
				</div>
			</div>
			<div class ="view_data__row" v-show="data.status=='open' || data.status=='limited'">
				<div class ="view_data__name"><?php _e('Публичная ссылка', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="js-copy-clipboard"><?php echo usam_get_file_link( $this->data['code'] ); ?></span>
				</div>
			</div>		
			<div class ="view_data__row" v-show="data.status=='limited'">
				<div class ="view_data__name"><?php esc_html_e( 'Максимальное количество скачиваний', 'usam'); ?>:</div>
				<div class ="view_data__option">{{data.maximum_load}}</div>
			</div>							
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		?>		
		<div class="view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Количество скачиваний', 'usam'); ?>:</div>
				<div class ="view_data__option">{{data.uploaded}}</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Создан', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_insert'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Папка', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo isset($folder['name'])?$folder['name']:__('Корневая','usam'); ?></div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Привязан', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<a href='<?php echo usam_get_contact_url( $this->data['user_id'], 'user_id' ); ?>'><?php echo usam_get_customer_name( $this->data['user_id'] ); ?></a>
				</div>
			</div>			
		</div>	
		<?php
	}		
	
	protected function main_content_cell_3( ) 
	{ 		
		$filepath = USAM_UPLOAD_DIR.$this->data['file_path'];	
		$url = get_bloginfo('url').'/file/'.$this->data['code'];	
		$size = file_exists($filepath)?size_format( filesize($filepath) ):'';				
		echo "<div class='usam_attachments download_disabled'><div class='usam_attachments__file'>
			<a href='".$url."' title ='".$this->data['title']."' target='_blank'><img src='".usam_get_file_icon( $this->data['id'] )."'/></a>
		<div class='filename'>".usam_get_formatted_filename( $this->data['title'] )."</div>
		<div class='attachment__file_data__filesize'><a download href='".$url."' title ='".__('Сохранить этот файл себе на компьютер','usam')."' target='_blank'>".__('Скачать','usam')."</a>".$size."</div></div></div>";		
	}	
}
?>