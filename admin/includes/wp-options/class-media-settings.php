<?php
class Usam_Admin_Media_Settings 
{	
	protected  $name_save_field = 'usam_site_options';
	public function __construct() 
	{
		$this->settings_init();
	}
	
	public function settings_init() 
	{		
		add_settings_section( 'usam-media', __('Размеры изображений товара', 'usam'), array(&$this, 'display_settings'), 'media' );		
		add_settings_section( 'usam-format_uploaded_files', __('Обработка файлов, загружаемых в мультимедиа', 'usam'), array(&$this, 'format_uploaded_files'), 'media' );
	}
	
	public function display_settings() 
	{			
		$product_image = get_site_option( 'usam_product_image', ['width' => 300, 'height' => 300]);
		$single_view_image = get_site_option( 'usam_single_view_image', ['width' => 600, 'height' => 600]);
		$crop_thumbnails = get_site_option( 'usam_crop_thumbnails', 0 );
		$image_quality = get_site_option( 'usam_image_quality', 100 );
		?>
		<div class='usam_setting_table edit_form'>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Степень сжатия', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='number' size='6' name='<?php echo $this->name_save_field; ?>[image_quality]' value='<?php esc_attr_e( $image_quality ); ?>' class="small-text"/>
				</div>						
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Просмотр товара', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php esc_html_e( 'Ширина', 'usam'); ?>:<input type='number' size='6' name='<?php echo $this->name_save_field; ?>[single_view_image][width]' value='<?php esc_attr_e( $single_view_image['width'] ); ?>' class="small-text"/>&#8195;
					<?php esc_html_e( 'Высота', 'usam'); ?>:<input type='number' size='6' name='<?php echo $this->name_save_field; ?>[single_view_image][height]' value='<?php esc_attr_e( $single_view_image['height'] ); ?>' class="small-text"/>
				</div>						
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Миниатюра товара в плитке', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php esc_html_e( 'Ширина', 'usam'); ?>:<input type='number' size='6' name='<?php echo $this->name_save_field; ?>[product_image][width]' value='<?php esc_attr_e( $product_image['width'] ); ?>' class="small-text"/>&#8195;
					<?php esc_html_e( 'Высота', 'usam'); ?>:<input type='number' size='6' name='<?php echo $this->name_save_field; ?>[product_image][height]' value='<?php esc_attr_e( $product_image['height'] ); ?>' class="small-text"/>
				</div>						
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Обрезать миниатюры точно по размерам', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="radio" value="0" name="<?php echo $this->name_save_field; ?>[crop_thumbnails]" id="crop_thumbnails-0" <?php checked( $crop_thumbnails, 0 ); ?>>  <label for="crop_thumbnails-0"><?php esc_html_e( 'Нет', 'usam'); ?></label> &nbsp;						
					<input type="radio" value="1" name="<?php echo $this->name_save_field; ?>[crop_thumbnails]" id="crop_thumbnails-1" <?php checked( $crop_thumbnails, 1 ); ?>>  <label for="crop_thumbnails-1"><?php esc_html_e( 'Да', 'usam'); ?></label> &nbsp;	
				</div>						
			</div>	
		</div>
		<?php		
	}
	
				
	public function format_uploaded_files()
	{
		require_once( USAM_FILE_PATH .'/admin/includes/save-options.class.php' );
		$options = [						
			['type' => 'radio', 'title' => __('Переименовывать и конвертировать в WebP', 'usam'), 'option' => 'rename_attacment', 'default' => 0, 'global' => 1],
			['type' => 'input', 'title' => __('Формат названия файла', 'usam'), 'option' => 'format_file_name_attacment', 'description' => '', 'default' => 'post_name', 'global' => 1],
			['type' => 'input', 'title' => __('Формат имени файла', 'usam'), 'option' => 'format_file_title_attacment', 'description' => '', 'default' => 'post_title [sku]', 'global' => 1],
			['type' => 'input', 'title' => __('Максимальная ширина', 'usam'), 'option' => 'max_width', 'description' => '', 'default' => '', 'global' => 1],
			['type' => 'input', 'title' => __('Максимальная высота', 'usam'), 'option' => 'max_height', 'description' => '', 'default' => '', 'global' => 1],
		];
		$so = new USAM_Save_Option();
		$so->display( $options );	
	}
}
return new Usam_Admin_Media_Settings();