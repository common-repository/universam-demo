<?php
if ( $list_table->search )
	printf( '<h3 class="search_title">' . __('Результаты поиска &#8220;%s&#8221;' ) . '</h3>', esc_html( $list_table->search ) );
	
?>
<script>		
	window.onload = function() {				
		var parentBody = window.parent.document.body;	
		jQuery("#cube-loader",parentBody).remove();
	}	
	jQuery('body').delegate('.wp-list-table #add_user', 'click',  function(e) 
	{ 
		e.preventDefault();		
								
		var parentBody = window.parent.document.body;		
		var user_id = jQuery(this).data('id');
		
		var td = jQuery(this).parents('tr').find('td');			
		var html = td.find('.user_block').html(); 	
		var block_id = jQuery(".button_modal_active", parentBody).parents('.usam_box').attr('id');
		jQuery("#"+block_id+" .user_block", parentBody).html(html);		
		if ( jQuery("#"+block_id+" .js-user-id", parentBody).length == 0 )
		{				
			html = jQuery("#"+block_id+"  .js-user-none", parentBody).clone().removeClass('js-user-none').addClass('js-user-id');		
			jQuery("#"+block_id+" .user_block", parentBody).append(html);				
		}
		jQuery("#"+block_id+" .js-user-id", parentBody).val(user_id);
		usam_notifi({ 'text': '<?php _e('Пользователь добавлен', 'usam'); ?>' });		
	});	
</script>
<div class='usam_tab_table'>			
	<form method='GET' action='<?php echo admin_url('admin.php'); ?>' id='usam-tab_form'>
		<input type='hidden' value='display_items_list' name='usam_admin_action' />	
		<input type='hidden' value='<?php echo $_GET['screen']; ?>' name='screen' />	
		<input type='hidden' value='<?php echo $_GET['list']; ?>' name='list' />	
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