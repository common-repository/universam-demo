<?php
new USAM_Product_Variation_Forms_Admin();
class USAM_Product_Variation_Forms_Admin
{
	function __construct( ) 
	{		
		add_action( 'usam-variation_add_form_fields', array($this, 'add_forms') );		
		add_action( 'usam-variation_edit_form_fields', array($this, 'forms_edit'), 10 , 2 );		
		add_action( 'edited_usam-variation', array( $this, 'save' ), 10 , 2 ); //После сохранения		
		add_filter( 'bulk_actions-edit-usam-variation', array( $this, 'bulk_actions_edit' ) );
		
		if( isset($_REQUEST['action']) )
			$this->action_form();
	}

	public function bulk_actions_edit( $actions )
	{ 
		$actions['variant_management'] = __('Управление вариантами','usam');
		$terms = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => 0, 'parent' => 0]);
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
			$term = get_term($parent, 'usam-variation');
			if ( $term )
			{
				foreach( $ids as $id )	
					wp_update_term($id, 'usam-variation', ['parent' => $parent]);
			}
		}		
	}	
		
	/*
	Используйте Jquery для перемещения набора вариации полей в начало и добавить описание
	*/
	function variation_set_field()
	{
		?>
		<script>		
			jQuery("#parent option[value='-1']").text("Новый набор вариантов");		
			jQuery("#tag-name").parent().before( jQuery("#parent").parent().find(".description").html('Выберите "Новый набор вариантов" если вы хотите создать новый набор вариантов. Ели хотите добавить вариант в уже имеющий набор, выберете его.') );		
		</script>
		<?php
	}
	
	function add_forms( ) 
	{
		add_action( 'admin_footer', array($this, 'variation_set_field') );
	}
	
	function forms_edit($tag, $taxonomy) 
	{
		add_action( 'admin_footer', array($this, 'variation_set_field') );
		if ( $tag->parent == 0 )
		{
			$tag_template = usam_get_term_metadata($tag->term_id, 'template');	
			?>					
			<tr class="form-field">
				<th scope="row" valign="top"><label for="oprion_template"><?php esc_html_e( 'Шаблон', 'usam'); ?>:</label></th>
				<td>		
					<?php
					$templates = usam_get_templates( 'variations' );
					?>					
					<select id ="oprion_template" name='template'>
						<option value='' <?php selected( '', $tag_template ); ?>><?php _e('По умолчанию', 'usam'); ?></option>
						<?php
						foreach( $templates as $id => $template )
						{
							?><option value='<?php echo $id; ?>' <?php selected( $id, $tag_template ); ?>><?php echo $template['name']; ?></option><?php					
						} ?>
					</select>					
				</td>			
			</tr>	
		  <?php
		}
		else
		{
			$color = usam_get_term_metadata($tag->term_id, 'color');
			?>				
			<tr class="form-field">				
				<th scope="row" valign="top"><label for="oprion_color"><?php esc_html_e( 'Цвет', 'usam'); ?>:</label></th>
				<td><input type="text" class="js-color" size="6" maxlength="6" name="color" value="#<?php echo $color; ?>" id="oprion_color"></td>			
			</tr>	
			<?php			
		}
	}
	
	function save( $term_id, $tt_id )
	{			
		$term = get_term( $term_id, 'usam-variation' );		
		if ( $term->parent == 0 )
		{
			$template = !empty($_POST['template'])?sanitize_title($_POST['template']):'';
			usam_update_term_metadata( $term_id, 'template', $template );
		}
		else
		{
			$color = !empty($_POST['color'])?sanitize_title($_POST['color']):'ffffff';
			usam_update_term_metadata( $term_id, 'color', $color );
		}		
	}
}
?>