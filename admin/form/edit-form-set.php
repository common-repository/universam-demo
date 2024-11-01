<?php	
require_once(USAM_FILE_PATH.'/includes/product/set.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Set extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить набор %s','usam'), $this->data['name'] );
		else
			$title = __('Добавить набор', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_set( $this->id );
		else	
			$this->data = ['name' => '', 'status' => 'draft', 'thumbnail_id' => 0, 'purchase_name' =>  __('Ваш заказ', 'usam')];		
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	function display_left()
	{							
		$this->titlediv( $this->data['name'] );
		usam_add_box( 'usam_products', __('Товары','usam'), [$this, 'section_products']);	
		usam_add_box( 'usam_display_setting', __('Настройки', 'usam'), [$this, 'display_setting'] );	
		usam_add_box( 'usam_display_restrictions', __('Ограничения отображения', 'usam'), [$this, 'display_restrictions'] );
    }	
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['status'] == 'draft' ? 0 : 1 );	
		usam_add_box( 'usam_image', __('Фотография','usam'), [$this, 'imagediv'], ['thumbnail_id' => $this->data['thumbnail_id'], 'title' => __('Миниатюра', 'usam'), 'button_text' => __('Задать миниатюру', 'usam')] );			
    }
	
	function display_setting()
	{				
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Название покупки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" value="<?php echo $this->data['purchase_name']; ?>" id="option_sort" name="purchase_name"/>
				</div>
			</div>		
		</div>	
		<?php
	}
	
	function display_restrictions()
	{			
		$roles = usam_get_array_metadata( $this->id, 'set', 'role');
		$catalogs = usam_get_array_metadata( $this->id, 'set', 'catalog');		
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_option">
					<?php $this->checklist_meta_boxs(['roles' => $roles, 'catalog' => $catalogs]); ?>				
				</div>
			</div>				
		</div>	
		<?php
	}
	
	public function section_products( )
	{					
		$columns = [
			'n'         => __('№', 'usam'),
			'title'     => __('Товары', 'usam'),
			'quantity'  => __('Количество', 'usam'),
			'status'    => __('Выбрано по умолчанию', 'usam'),	
			'category'  => __('Категория', 'usam'),			
			'delete'    => '',
		];
		$this->table_products_add_button($columns, 'set');
	}	
			
	function display_settings()
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type_payer'><?php esc_html_e( 'Плательщик', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="tax[type_payer]" id='option_type_payer'>				
						<option value="" <?php selected( $this->data['type_payer'], '') ?> ><?php _e( 'Любой', 'usam'); ?></option>
						<?php				
						$types_payers = usam_get_group_payers();	
						foreach( $types_payers as $value )
						{						
							?>               
							<option value="<?php echo $value['id']; ?>" <?php selected($this->data['type_payer'], $value['id']); ?> ><?php echo $value['name']; ?></option>
							<?php
						}
						?>
					</select>	
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_value'><?php esc_html_e( 'Ставка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_value" name="tax[value]" maxlength = "12" size = "12" value="<?php echo $this->data['value']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_is_in_price'><?php esc_html_e( 'Входит в цену', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="tax[is_in_price]" id='option_is_in_price'>
						<option value="1" <?php selected($this->data['is_in_price'], 1); ?> ><?php _e( 'Да', 'usam'); ?></option>
						<option value="0" <?php selected($this->data['is_in_price'], 0); ?>><?php _e( 'Нет', 'usam'); ?></option>						
					</select>	
				</div>
			</div>
		</div>	
		<?php
    }
}
?>