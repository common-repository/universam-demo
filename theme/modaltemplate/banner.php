<div id="banner_modal_<?php echo $banner['id']; ?>" class="modal fade modal-large" <?php echo $banner['type'] == 'image'?'style="overflow: hidden;"':'';?>>
	<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
	<?php
	$args = [];
	if ( $banner['type'] != 'image' )
		$args['class'] = 'modal-body';		
	echo usam_get_theme_banner( $banner, $args );
	?>
</div>