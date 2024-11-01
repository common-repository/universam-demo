<?php		
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_review extends USAM_Edit_Form
{
	protected $webform = array();
	function output_rating( $rating ) 
	{
        $out = '';
        $out .= '<div class="your_review_rating">';		
		for ($i = 1; $i <= 5; $i++) 
		{
			$selected = $rating >= $i?'selected':'';
			$out .= "<span class='star $selected'></span>";
		}		
        $out .= '</div>';
        return $out;
    }	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
		{
			usam_employee_viewing_objects(['object_type' => 'review', 'object_id' => $this->id]);
			$order_id = usam_get_review_metadata( $this->id, 'order_id' );
			if ( $order_id )
				$title = sprintf(__('Отзыв от заказе №%s', 'usam'), $order_id);
			else
				$title = __('Отзыв от посетителя', 'usam');
		}
		else
			$title = '';		
		return $title;
	}
	
	public function get_data_tab() 
	{		
		$default = ['id' => 0, 'status' => 0, 'contact_id' => 0];
		$webform = [];
		if ( $this->id != null )
		{				
			$this->data = usam_get_review( $this->id );		
			$webform_code = usam_get_review_metadata( $this->id, 'webform');	
			$webform = usam_get_webform( $webform_code, 'code' );				
		}
		$this->data = array_merge( $default, $this->data );		
		$this->js_args['webform'] = $webform;	
		$this->js_args['user'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['contact_id'] );
		if( $contact )
		{
			$this->js_args['user'] = $contact;
			$this->js_args['user']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['user']['url'] = usam_get_contact_url( $contact['id'] );	
		}		
	}
			
	public function display_main_data() 
	{			
		$statuses = [1 => 'Не утвержден', 2 => 'Утвержден', 3 => 'Удален'];	
		?>	
		<div class="edit_form">						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Дата','usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php usam_display_datetime_picker( 'date_insert', $this->data['date_insert'] ); ?></div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Статус','usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select name = "status">
						<?php							
						foreach( $statuses as $key => $title )
						{								
							?><option <?php selected($this->data['status'], $key ); ?> value='<?php echo $key ?>'><?php echo $title; ?></option><?php
						}	
						?>
					</select>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Веб-форма','usam'); ?>:</div>
				<div class ="edit_form__item_option">{{webform.title}}</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Автор','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<div class='user_block' v-if="data.contact_id>0">	
						<div class='user_foto'><a :href="user.url" class='image_container usam_foto'><img :src='user.foto'></a></div>	
						<a class='user_name':href="user.url" v-html="user.appeal"></a>			
					</div>
					<div class='user_block' v-else><?php _e('Не выбрано', 'usam'); ?></div>
					<input type="hidden" name="contact_id" v-model="data.contact_id"/>				
				</div>
			</div>			
		</div>
		<?php	
	}	
		
	public function display_official_answer()
	{	               
		wp_editor(stripslashes(str_replace('\\&quot;','',$this->data['review_response'])),'review_response',array(
			'textarea_name' => 'review_response',
			'media_buttons' => false,
			'textarea_rows' => 10,
			'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);
	}
	
	public function display_page()
	{	         	
		$product = get_post( $this->data['page_id'] );	
		if ( !empty($product) )
		{
			if ( $product->post_type == 'usam-product' )
			{					
				echo usam_get_product_thumbnail( $product->ID, 'product-thumbnails' );			
				echo "<br><a href='".usam_product_url( $product->ID )."' title='".__('Посмотреть товар','usam')."'>".$product->post_title."</a>";
			}
			elseif ( isset($product->post_title) )
			{	
				printf( __('Отправлено со странице %s', 'usam'), '&laquo;'.$product->post_title.'&raquo;' );
			}			
		}
	}
	
	public function display_left() 
	{				
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.title" placeholder="<?php _e('Название отзыва', 'usam') ?>" class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<?php include( usam_get_filepath_admin('templates/template-parts/crm/webform.php') ); ?>
		</div>
		<?php	
		$this->add_box_description( $this->data['review_text'], 'review_text', __('Отзыв','usam') );		
		usam_add_box( 'usam_official_answer', __('Официальный ответ','usam'), [$this, 'display_official_answer'] );
	}	
	
	function display_right()
	{					
		usam_add_box(['id' => 'usam_main_data', 'title' => sprintf( __('Номер отзыва № %s', 'usam'), $this->id ), 'function' => [$this, 'display_main_data'], 'close' => false]);
		usam_add_box( 'usam_page', __('Отправлено со страницы','usam'), array($this, 'display_page') );				
	}
}
?>