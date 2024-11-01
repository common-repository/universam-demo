<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_underprice extends USAM_Edit_Form
{			
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить наценку &#171;%s&#187;','usam'), $this->data['title'] );
		else
			$title = __('Добавить наценку', 'usam');	
		return $title;
	}	
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_underprice_rules');
		else
			$this->data = ['title' => '', 'value' => 0, 'category' => [], 'brands' => [], 'category_sale' => [], 'catalogs' => [], 'type_prices' => [], 'contractors' => []];
	}	
	
	function display_options( )
	{			
		?>		
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_value'><?php esc_html_e( 'Наценка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_value" value="<?php echo $this->data['value']; ?>" name="value"/>
				</div>
			</div>			
		</div>		
		<?php
	}		
	
	public function terms_settings( ) 
	{		
		$variant = !empty($this->data['category']) || !empty($this->data['brands']) || !empty($this->data['category_sale']) || !empty($this->data['catalogs']) || !empty($this->data['contractors'])?'group':'all';
		?>		  
		<div id="checklist_radio" class="columns">		
			<div class="columns__column1 checklist_radio">	
				<div class="usam_radio">	
					<div class="usam_radio__item usam_radio-all <?php echo $variant == 'all'?'checked':''; ?>">
						<div class="usam_radio_enable">
							<input type="radio" name="installation" class="input-radio" value="all" <?php checked($variant, 'all'); ?>/>
							<label><?php _e('Ручное применение', 'usam'); ?></label>
						</div>										
					</div>
					<div class="usam_radio__item usam_radio-group <?php echo $variant == 'group'?'checked':''; ?>">
						<div class="usam_radio_enable">
							<input type="radio" name="installation" class="input-radio" value="group" <?php checked($variant, 'group'); ?>/>
							<label><?php _e('Используя группы выбора', 'usam'); ?></label>
						</div>										
					</div>		
				</div>
			</div>				
			<div class ="columns__column2 groups_list <?php echo $variant=='all'?'hide':''; ?>">
				<?php $this->checklist_meta_boxs(['contractors' => $this->data['contractors'], 'category' => $this->data['category'], 'brands' => $this->data['brands'], 'category_sale' => $this->data['category_sale'], 'catalog' => $this->data['catalogs']]); ?>
			</div>		
		</div>			
	   <?php   
	}	  
		
	function display_left()
	{	
		$this->titlediv( $this->data['title'] );			
		usam_add_box( 'usam_options', __('Настройки','usam'), array( $this, 'display_options' ) );	
		usam_add_box( 'usam_terms_settings', __('Правило применения наценки','usam'), array( $this, 'terms_settings' ) );
    }	
	
	function display_right()
	{						
		usam_add_box( 'usam_prices', __('Цены, на которые установить','usam'), array( $this, 'selecting_type_prices' ) );	
    }
}
?>