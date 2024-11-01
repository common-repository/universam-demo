<?php
require_once( USAM_FILE_PATH . '/admin/includes/theming.class.php' ); // перенос шаблонов в тему
class USAM_Tab_theme_file extends USAM_Page_Tab
{	
	protected $vue = true;		
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Файлы темы магазина', 'usam');	
	}
	
	public function display() 
	{		
		add_action( 'admin_footer', array(&$this, 'admin_footer') );
		usam_add_box( 'usam_theme_metabox', __('Если вы хотите что-то поменять, скопируйте шаблон в вашу тему', 'usam'), array( $this, 'theme_metabox' ) );	
		?>
		<div class="tab_buttons">
			<button type="button" @click="save" class="button button-primary"><?php _e( 'Сохранить', 'usam'); ?></button>							
		</div>		
		<?php
	}

	public function theme_metabox()
	{
		?>		
		<div class="usam_checked">
			<div class="usam_checked__item" :class="[template.selected?'checked':'']" v-for="(template, k) in templates" @click="template.selected = template.selected ? 0:1">
				<div class="usam_checked_enable">
					<label>{{template.name}}</label>
				</div>										
			</div>
		</div>		
		<?php
	}
	
	public function admin_footer() 
	{
		$templates = [];
		$downloaded_templates = usam_list_product_templates( USAM_THEMES_PATH );			
		foreach( usam_list_product_templates( USAM_CORE_THEME_PATH ) as $template )
		{
			if ( in_array($template, $downloaded_templates) )
				$templates[] = ['name' => $template, 'selected' => 1];
			else
				$templates[] = ['name' => $template, 'selected' => 0];
		}
		?>
		<script>
		//<![CDATA[
		var templates = <?php echo json_encode($templates); ?>;
		//]]>
		</script>
		<?php
	}
	
}