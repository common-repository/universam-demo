<?php
if ( $list_table->search )
	printf( '<h3 class="search_title">' . __('Результаты поиска &#8220;%s&#8221;' ) . '</h3>', esc_html( stripcslashes(trim($_REQUEST['s'])) ) );
		
$list = !empty($_REQUEST['list']) ? sanitize_title($_REQUEST['list']) : 'contact';
?>			
<script>		
	window.onload = function() {			
		var parentBody = window.parent.document.body;	
		jQuery("#cube-loader",parentBody).remove();
		parent.iframeLoaded();
	}		
</script>	
<div class='usam_tab_table'>			
	<form method='GET' action='<?php echo admin_url('admin.php'); ?>' id='usam-tab_form'>
		<input type='hidden' value='display_items_list' name='usam_admin_action' />	
		<input type='hidden' value='phone_book' name='screen' />	
		<?php wp_nonce_field('display_items_list_nonce', 'nonce' );	?>			
		<div class="usam_select_address_book">			
			<span class="usam_field-label"><label for="phone_book"><?php _e('Адресная книга', 'usam'); ?>:</label></span>
			<span class="usam_field-val usam_field-title-inner">
				<select name="list" id="phone_book" onChange="this.form.submit()">				
					<option value='employees' <?php selected('employees', $list); ?>><?php _e('Сотрудники', 'usam'); ?></option>
					<option value='contact' <?php selected('contact', $list); ?>><?php _e('Контакты', 'usam'); ?></option>					
				</select>	
			</span>		
		</div>		
		<?php $list_table->search_box( __('Поиск', 'usam'), 'search_id' ); ?>					
		<div class='usam_list_table_wrapper'>
			<?php $list_table->display(); ?>
		</div>				
	</form>
</div>