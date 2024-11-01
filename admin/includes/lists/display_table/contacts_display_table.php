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
				
		var id = jQuery(this).data('id');
		var td = jQuery(this).parents('tr').find('td');		
		var parentBody = window.parent.document.body;		
		if ( jQuery('.contacts-block #user_block-'+id, parentBody).length == 0 ) 
		{
			var html = "<div class='user_block'><input type='hidden' name='contacts_ids[]' value='"+id+"'/><div class='user_foto image_container usam_foto'><img src='"+td.find('img').attr('src')+"'></a></div><div class='user_name'>"+td.find('.js-object-value').text()+"</div><a class='js_delete_action' href='#'></a></div>";				
			jQuery(".contacts-block .items_empty",parentBody).hide();
			jQuery(".contacts-block",parentBody).append(html);	
		}		
	});		
</script>
<div class='usam_tab_table'>			
	<form method='GET' action='<?php echo admin_url('admin.php'); ?>' id='usam-tab_form'>
		<input type='hidden' value='display_items_list' name='usam_admin_action'/>	
		<input type='hidden' value='contacts' name='screen' />	
		<input type='hidden' value='contact' name='list' />	
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