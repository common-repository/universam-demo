<?php
if ( $list_table->search )
	printf( '<h3 class="search_title">' . __('Результаты поиска &#8220;%s&#8221;' ) . '</h3>', esc_html( $list_table->search ) );
	
?>
<script>		
	window.onload = function() {				
		var parentBody = window.parent.document.body;	
		jQuery("#cube-loader",parentBody).remove();
		parent.add_manager_iframeLoaded();	
	}		
</script>
<div class='usam_tab_table'>			
	<form method='GET' action='<?php echo admin_url('admin.php'); ?>' id='usam-tab_form'>
		<input type='hidden' value='display_items_list' name='usam_admin_action' />	
		<input type='hidden' value='manager' name='screen' />	
		<input type='hidden' value='manager' name='list' />	
		<?php wp_nonce_field('display_items_list_nonce', 'nonce' );	?>			
		<?php
		$list_table->search_box( __('Поиск', 'usam'), 'search_id' );		
		$list_table->disable_bulk_actions();
		?>				
		<div class='usam_list_table_wrapper'>
			<?php $list_table->display(); ?>
		</div>				
	</form>
</div>