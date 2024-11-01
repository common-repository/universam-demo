<?php
if ( $list_table->search )
	printf( '<h3 class="search_title">' . __('Результаты поиска &#8220;%s&#8221;' ) . '</h3>', esc_html( $list_table->search ) );
	
$object = !empty($_REQUEST['list'])?$_REQUEST['list']: 'company';
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
		<input type='hidden' value='customer' name='screen' />	
		<?php wp_nonce_field('display_items_list_nonce', 'nonce' );	?>	
		<div class="usam_select_address_book">			
			<span class="usam_field-label"><label for="object"><?php _e('Клиенты', 'usam'); ?>:</label></span>
			<span class="usam_field-val usam_field-title-inner">
				<select name="list" id="object" onChange="this.form.submit()">							
					<option value='company' <?php selected('company', $object); ?>><?php _e('Компании', 'usam'); ?></option>		
					<option value='contact' <?php selected('contact', $object); ?>><?php _e('Контакты', 'usam'); ?></option>					
				</select>				
			</span>		
		</div>		
		<?php
		$list_table->search_box( __('Поиск', 'usam'), 'search_id' );		
		$list_table->disable_bulk_actions();
		?>					
		<div class='usam_list_table_wrapper'>
			<?php $list_table->display(); ?>
		</div>				
	</form>
</div>