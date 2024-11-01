<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/import-form.php' );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
class USAM_Form_order_import extends USAM_Import_Form
{			
	protected $rule_type = 'order_import';
	
	function display_left()
	{					
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<?php $this->display_settings(); ?>
		</div>	
		<?php	
		$this->display_columns();
    }
}
?>