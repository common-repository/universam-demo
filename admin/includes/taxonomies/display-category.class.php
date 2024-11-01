<?php
/* Этот файл используется для добавления полей в страницу редактирование категорий продуктов и правильно сохранения
 */
new USAM_Category_Forms_Admin();
class USAM_Category_Forms_Admin
{
	function __construct( ) 
	{		
		add_action( 'created_usam-category', [&$this, 'save'], 10 , 2 ); //После создания
		add_action( 'edited_usam-category', [&$this, 'save'], 10 , 2 ); //После сохранения
		add_action( 'usam-category_add_form_fields', [&$this, 'add_forms'] ); // форма добавления категорий
		add_action( 'usam-category_edit_form_fields', [&$this, 'edit_forms'], 10, 2 ); // форма редактирования категорий			
		if ( isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] == 'usam-category' )
			add_action( 'admin_footer',  [&$this, 'admin_footer']);	

		if( isset($_REQUEST['action']) )
			$this->action_form();		

		add_filter( 'bulk_actions-edit-usam-category', array( $this, 'bulk_actions_edit' ) );		
	}
	
	public function bulk_actions_edit( $actions )
	{ 
		$terms = get_terms(['taxonomy' => 'usam-category', 'hide_empty' => 0, 'parent' => 0]);
		foreach( $terms as $term )
		{
			$actions[$term->term_id] = sprintf(__('Перенести в группу "%s"','usam'), $term->name);
		}
		return $actions;
	}
	
	public function action_form()
	{ 		
		if( !isset($_REQUEST['delete_tags']) )
			return;	
		
		$ids = array_map('intval', $_REQUEST['delete_tags']);
		if( is_numeric($_REQUEST['action']) && $ids )
		{			
			$parent = absint( $_REQUEST['action'] );
			$term = get_term($parent, 'usam-category');
			if ( $term )
			{
				foreach( $ids as $id )	
					wp_update_term($id, 'usam-category', ['parent' => $parent]);
			}
		}		
	}
			
	function admin_footer()
	{
		echo usam_get_modal_window( __('Импорт категорий','usam'), 'term_import_window', $this->get_category_import_window() );	
	}
		
	function get_category_import_window()
	{			
		include( USAM_FILE_PATH . '/admin/includes/taxonomies/progress-form-category_importer.php' );
		$progress_form = new USAM_Category_Rule_Importer( );
		ob_start();	
		$progress_form->display();		
		return ob_get_clean();
	}
	
	/**
	 * печатает левую часть страницы добавления новой категории
	 */		
	function add_forms( ) 
	{	
		?><script>document.querySelector(".wp-heading-inline").innerHTML += '<a href="#" id="term_import" class="button button-primary"><?php _e('Импорт категорий', 'usam') ?></a>';</script><?php
	}	
	
	function edit_forms( $tag, $taxonomy ) 
	{				
		$display_type = usam_get_term_metadata($tag->term_id, 'display_type');	
		?>		
		<tr class="form-field">
			<th scope="row" valign="top"><?php esc_html_e( 'Просмотр каталога', 'usam'); ?></th>
			<td>		
				<select name='display_type'>
					<option value='default' <?php selected( $display_type, 'default' ); ?>><?php esc_html_e( 'По умолчанию', 'usam'); ?></option>
					<option value='list' <?php selected( $display_type, 'list' ); ?>><?php esc_html_e('Списком', 'usam'); ?></option>
					<option value='grid' <?php selected( $display_type, 'grid' ); ?>><?php esc_html_e( 'Плиткой', 'usam'); ?></option>
				</select>
			</td>			
		</tr>			
		<?php
	}
	
	/**
	 * Сохраняет данные категории
	 */
	function save( $term_id, $tt_id )
	{					
		if ( isset($_POST['display_type'] ) )
			usam_update_term_metadata($term_id, 'display_type', sanitize_title($_POST['display_type']));
	}
}
?>