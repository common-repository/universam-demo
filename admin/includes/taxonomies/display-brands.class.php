<?php
/* Этот файл используется для добавления полей в страницу редактирования таксономии бренда продуктов и правильно сохранения
  */  
$brand_class = new USAM_Brands_Forms_Admin();
class USAM_Brands_Forms_Admin
{
	function __construct( ) 
	{		
		add_action( 'usam-brands_add_form_fields', array( &$this, 'forms_add') ); // форма добавления категорий
		add_action( 'usam-brands_edit_form_fields', array( &$this, 'forms_edit'),10, 2 ); // форма редактирования категорий
		add_action( 'create_usam-brands', array( &$this, 'save'), 10 , 2 ); //Выполнить после создания бренда
		add_action( 'edited_usam-brands', array( &$this, 'save'), 10 , 2 ); //Выполнить после сохранения бренда
		if ( isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] == 'usam-brands' )
			add_action( 'admin_footer', array(&$this, 'admin_footer') );		
	}
	
	function admin_footer()
	{
		echo usam_get_modal_window( __('Импорт брендов','usam'), 'term_import_window', $this->get_brand_import_window() );	
	}
		
	function get_brand_import_window()
	{			
		include( USAM_FILE_PATH . '/admin/includes/taxonomies/progress-form-brand_importer.php' );
		$progress_form = new USAM_Brand_Rule_Importer( );
		ob_start();	
		$progress_form->display();		
		return ob_get_clean();
	}

	function forms_add()
	{				
		?><script>jQuery(".wp-heading-inline").append('<a href="#" id="term_import" class="button button-primary"><?php _e('Импорт брендов', 'usam') ?></a>');</script><?php
	}	

	function forms_edit( $tag, $taxonomy) 
	{	   		
		$display_type = usam_get_term_metadata($tag->term_id, 'display_type');		
		$link = usam_get_term_metadata($tag->term_id, 'link');			
		?>
		<tr>
			<td colspan="2">
				<h3><?php esc_html_e( 'Дополнительные настройки', 'usam'); ?></h3>
			</td>
		</tr>				
		<tr class="form-field">
			<th scope="row" valign="top"><label for="display_type"><?php esc_html_e( 'Просмотр каталога', 'usam'); ?>:</label></th>
			<td>			
				<select name='display_type'>
					<option value='default' <?php checked( $display_type, 'default' ); ?>><?php esc_html_e( 'По умолчанию', 'usam'); ?></option>
					<option value='list' <?php checked( $display_type, 'list' ); ?>><?php esc_html_e('Списком', 'usam'); ?></option>
					<option value='grid' <?php checked( $display_type, 'grid' ); ?>><?php esc_html_e( 'Плиткой', 'usam'); ?></option>
				</select>
			</td>
		</tr>		
		<tr class="form-field">
			<th scope="row" valign="top"><label for="usam_cat_link"><?php esc_html_e( 'Ссылка на сайт бренда', 'usam'); ?>:</label></th>
			<td><input type='text' name='link' id = "usam_cat_link" class="usam_cat_link" value='<?php echo $link; ?>' /></td>
		</tr>	
	  <?php
	}
	
	/**
	 * Сохраняет данные Бренда
	 */
	function save( $term_id, $tt_id )
	{	
		if ( isset($_POST['display_type'] ) )
			usam_update_term_metadata($term_id, 'display_type',sanitize_title($_POST['display_type']));			
		if ( isset($_POST['link'] ) )
			usam_update_term_metadata( $term_id, 'link', sanitize_text_field($_POST['link']) );	
	}
}
?>