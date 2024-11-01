<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_blank extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		$title = sprintf( __('Изменить бланк &laquo;%s&raquo;','usam'), trim($this->data['title']) );
		return $title;
	}
	
	protected function toolbar_buttons( ) 
	{
		$time = time();
		$url = usam_url_action( 'printed_form', ['form' => $this->id, 'type' => $this->data['type'], 'time' => $time]);	
		?>		
		<div class="action_buttons__button"><a class="button js-modal" data-modal="add_shortcode_modal_window" class="primary"><?php _e('Вставить шорткод', 'usam') ?></a></div>
		<div class="action_buttons__button"><?php submit_button( __('Сохранить', 'usam'), 'button-primary button_save', 'save', false, array( 'id' => 'submit-save' ) ); ?></div>
		<div class="action_buttons__button"><a href="<?php echo $url; ?>" target="_blank" class="button"><?php _e('Просмотр','usam'); ?></a></div>
		<?php	
	}
	
	function admin_footer()
	{		
		echo usam_get_modal_window( __('Добавить столбцы','usam'), 'add_column_modal_window', $this->get_add_column_modal_window(), 'medium' );	
		echo usam_get_modal_window( __('Добавить шорткод','usam'), 'add_shortcode_modal_window', $this->get_add_shortcode_modal_window(), 'medium' );	
	}
	
	function get_add_shortcode_modal_window()
	{		
		if ( $this->data['type'] == 'payment' || $this->data['type'] == 'order' )	
			$shortcode = usam_get_order_shortcode();
		else
			$shortcode = array( );			
		$files = usam_get_files( array('type' => 'seal') );
		foreach ( $files as $file )		
			$shortcode["%seal_{$file->id}%"] = __('Подпись','usam').' - '.$file->title;
					
		$properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'fields' => array('code', 'name') ) );	
		foreach ( $properties as $property )		
		{
			$shortcode["%recipient_{$property->code}%"] = __('Ваша компания','usam').' - '.$property->name;
		}	
		foreach ( $properties as $property )		
		{
			$shortcode["%customer_{$property->code}%"] = __('Клиент','usam').' - '.$property->name;
		}		
		$out = "<div class='modal-body modal-scroll'><div class='edit_form'>";
		foreach ( $shortcode as $key => $title )
		{
			$out .= "<div class='edit_form__item'>
						<div class='edit_form__item_name'>$title</div>
						<div class='edit_form__item_option'><span class='js-copy-clipboard'>{$key}</span></div>	
					</div>";
		}
		$out .=	"</div></div>";	
		return $out;
	}
	
	function get_add_column_modal_window()
	{		
		$system_attributes = ['n' => "№", 'image' => __('Фото','usam'), 'name' => __('Товары (работы, услуги)','usam'), 'sku' => __('Артикул','usam'), 'barcode_picture' => __('Штрих-код','usam'), 'quantity' => __('Количество','usam'), 'unit_measure' => __('Eд.','usam'), 'price' => __('Цена','usam'), 'discount_price' => __('Цена со скидкой','usam'), 'discount' => __('Скидка','usam'), 'tax' => __('Налог','usam'), 'total' => __('Всего','usam'), 'category' => __('Категория','usam'), 'brand' => __('Бренд','usam')];	
		$out = "<div class='modal-body modal-scroll'><div class='system_attributes edit_form'>";
		foreach ( $system_attributes as $key => $title )
		{
			$out .= "<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='attribute_$key'>$title</label>:</div>	
						<div class='edit_form__item_option'><input type='checkbox' id='attribute_$key' value='$key'/></div>	
					</div>";
		}
		$product_attributes = get_terms( array( 'hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes' ) );	
		foreach ( $product_attributes as $product_attribute )
		{
			$out .= "<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='attribute_'>$product_attribute->name</label>:</div>	
						<div class='edit_form__item_option'><input type='checkbox' id='attribute_$product_attribute->slug' value='$product_attribute->slug'/></div>	
					</div>";
		}
		$out .=	"</div></div>";	
		return $out;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )		
			$this->data = usam_get_data_printing_forms( $this->id );
		
		add_action( 'admin_footer', array(&$this, 'admin_footer') );	
	}
		
	public function display_setting_blank(  )
	{
		$shop_company = get_option( 'usam_shop_company' );
		?>		
		<div class="edit_form">	
			<?php usam_select_bank_accounts( $shop_company ); ?>
		</div>		
		<?php
	}	
	
	function display_right()
	{		
		usam_add_box( 'usam_display_setting_blank', __('Фирма','usam'), [$this, 'display_setting_blank']);	
	}
	
	function display_left()
	{		
		if ( $this->id == null )
			return;		
		
		$shop_company = get_option( 'usam_shop_company' ); 
		$url = usam_url_admin_action('edit_blank', ['blank' => $this->id, 'type' => $this->data['type'], 'company' => $shop_company] );	
		?>	
		<script>
		  function resizeIframe(obj){
			 obj.style.height = 0;
			 obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
		  }
		</script>				
		<div><?php echo trim($this->data['description']) ?></div>
		<input type="hidden" name ="save-close" value="1">		
		<iframe id="edit_form_blanks" data-id = "<?php echo $this->id; ?>" style="width:820px; height:1100px; border:1px solid #ccc;" onload='resizeIframe(this)' src="<?php echo $url; ?>"></iframe>
        <?php
    }	
}
?>