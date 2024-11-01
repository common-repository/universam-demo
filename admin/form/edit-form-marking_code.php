<?php
require_once( USAM_FILE_PATH .'/includes/product/marking_code.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_marking_code extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить маркировочный код','usam');
		else
			$title = __('Добавить маркировочный код', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_marking_code( $this->id );			
		}
		else	
			$this->data = ['id' => 0,  'product_id' => 0,  'code' => '',  'storage_id' => 0, 'status' => 'available'];
	}	
	
	function display_settings()
	{		
		?>			
		<div class="edit_form">					
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='marking_code'><?php esc_html_e( 'Маркировочный код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="marking_code" name="code" v-model="data.code" required/>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Статус','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name = "status" v-model="data.status">
						<?php 
						$statuses = usam_get_statuses_marking_code( );	
						foreach ( $statuses as $key => $name )
						{	 
							?><option value='<?php echo $key; ?>'><?php echo $name; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Место нахождение', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<?php $storage = usam_get_storage( $this->data['storage_id'] ); ?>
					<autocomplete :selected="'<?php echo isset($storage['title'])?$storage['title']:''; ?>'" @change="data.storage_id=$event.id" :request="'storages'"></autocomplete>
					<input type="hidden" name="storage_id" v-model="data.storage_id">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Товар', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<autocomplete :selected="'<?php echo get_the_title( $this->data['product_id'] ); ?>'" @change="data.product_id=$event.id" :request="'products'"></autocomplete>
					<input type="hidden" name="product_id" v-model="data.product_id">
				</div>
			</div>					
		</div>		
		<?php			
	}
	
	function display_left()
	{					
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
}
?>