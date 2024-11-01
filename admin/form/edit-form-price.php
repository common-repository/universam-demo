<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_price extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить цену &#171;%s&#187;','usam'), $this->data['title'] );
		else
			$title = __('Добавить цену', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{					
		$default = ['title' => '', 'code' => strtolower(md5(uniqid(rand(),1))), 'type' => 'R', 'currency' => get_option("usam_currency_type"), 'base_type' => 0, 'underprice' => 0, 'available' => 1, 'rounding' => '0.01', 'locations' => array(), 'roles' => array(), 'sort' => 100, 'external_code' => ''];		
		if ( $this->id != null )
		{				
			$this->data = usam_get_data($this->id, 'usam_type_prices');
			$this->data = array_merge ( $default, $this->data );				
		}
		else
		{			
			$prices = usam_get_prices(['type' => 'all', 'orderby' => 'id', 'order' => 'ASC']);	
			$this->data = $default;	
			$price = array_pop($prices);
			if ( $price )
				$id = $price['id']+1;
			else
				$id = 1;
			$this->data['code'] = 'tp_'.$id;	
			$this->data['id']	= $id;		
		}
	}	
		
    public function box_prices( )
	{ 
		$prices = usam_get_prices(['type' => 'R', 'orderby' => 'title', 'order' => 'ASC']);
		?>	
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_code' name="code" value="<?php echo $this->data['code']; ?>" size="45"  autocomplete="off"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_currency'><?php esc_html_e( 'Тип валюты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_select_currencies( $this->data['currency'], array( "name" => "currency" ) ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name="type">
						<option value="R" <?php selected($this->data['type'], 'R'); ?>><?php _e('Розничная','usam'); ?></option>
						<option value="P" <?php selected($this->data['type'], 'P'); ?>><?php _e('Закупочная','usam'); ?></option>							
					</select>	
				</div>
			</div>	
	<?php if ( $this->data['type'] == 'R' ) { ?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_base_type'><?php esc_html_e( 'Базовая цена', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="option_base_type" class="chzn-select" name="base_type">
						<option value="0" <?php selected($this->data['base_type'], 0 ); ?>>
							<?php _e('Не наследуется','usam'); ?>
						</option>
						<?php												
						foreach ( $prices as $value )
						{					
							if  ( $value['base_type'] == 0 && $value['id'] != $this->data['id'] )
							{
								?><option value="<?php echo $value['code']; ?>" <?php selected($this->data['base_type'], $value['code']); ?>><?php echo $value['title']; ?></option><?php
							}
						}				
						?>
					</select>		
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_underprice'><?php esc_html_e( 'Наценка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_underprice' name="underprice" value="<?php echo $this->data['underprice']; ?>"  autocomplete="off"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_available'><?php esc_html_e( 'Доступна для покупателей', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='option_available' name="available" value="1" <?php checked( $this->data['available'], 1 ); ?>/>
				</div>
			</div>		
	<?php } ?>	
			<div class ="edit_form__item">
				<?php 
				if( $this->data['rounding']==0 )
				{							
					$rounding = $this->data['rounding'];
				}
				elseif( $this->data['rounding']>0 )
				{ 
					$rounding = '0.';
					for ($i=1;$i<=$this->data['rounding']-1;$i++)
						$rounding .= '0';
					$rounding .= '1';
				}
				else
				{
					$rounding = '1';
					$count = abs($this->data['rounding'])-1;
					for ($i=1;$i<=$count;$i++)
						$rounding .= '0';								
				}
				?>
				<div class ="edit_form__item_name"><label for='option_rounding'><?php esc_html_e( 'Порядок округления', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_rounding' name="rounding" value="<?php echo $rounding; ?>" size="5" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' name="sort" value="<?php echo $this->data['sort']; ?>" size="5" autocomplete="off"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Внешний код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_code' name="external_code" value="<?php echo $this->data['external_code']; ?>" size="255"  autocomplete="off"/>
				</div>
			</div>
		</div>
      <?php
	}     

	public function box_roles( ) 
	{			
		$this->checklist_meta_boxs(['roles' => $this->data['roles']]);   
	}	
  
	
	function display_left()
	{					
		$this->titlediv( $this->data['title'] );		
		usam_add_box( 'usam_type_price', __('Параметры','usam'), array( $this, 'box_prices' ) );	
		if ( $this->data['type'] == 'R' ) 
		{ 
			usam_add_box( 'usam_roles', __('Роли покупателей','usam'), array( $this, 'box_roles' ) );
			usam_add_box( 'usam_locations', __('Местоположение','usam'), array( $this, 'selecting_locations' ), $this->data['locations'] );
		}
    }
}
?>