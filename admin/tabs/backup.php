<?php
class USAM_Tab_backup extends USAM_Page_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{
		return __('Резервирование сайта', 'usam');
	}		
	
	public function display() 
	{
		add_action( 'admin_footer', [&$this, 'admin_footer']);
		?>									
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Резервная копия базы данных', 'usam'); ?>:</div>
				<div class="edit_form__item_option">
					<input type="submit" @click="backup_bd" class="button" value="<?php _e( 'Скачать', 'usam'); ?>">	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Резервная копия темы', 'usam'); ?>:</div>
				<div class="edit_form__item_option">
					<input type="submit" @click="backup_themes" class="button" value="<?php _e( 'Скачать', 'usam'); ?>">	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Резервировать каждый день', 'usam'); ?>:</div>
				<div class="edit_form__item_option">
					<span id="rule-selected">				
						<label><input type="radio" v-model="options.backup_bd_active" value="1"/><?php _e('Да', 'usam'); ?></label>	
						<label><input type="radio" v-model="options.backup_bd_active" value="0"/><?php _e('Нет', 'usam'); ?></label>
					</span>
				</div>
			</div>
			<div class = "edit_form__buttons">
				<input type="submit" @click="save" class="button button-primary" value="<?php _e( 'Сохранить', 'usam') ?>"/>		
			</div>
		</div>			
		<?php
	}	
		
	public function admin_footer() 
	{
		?>
		<script>
		//<![CDATA[
		var usam_options = <?php echo json_encode(['backup_bd_active' => get_option("usam_backup_bd_active", 0)]); ?>;
		//]]>
		</script>
		<?php
	}
}