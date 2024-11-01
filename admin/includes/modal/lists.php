<?php 
$screen = !empty($_REQUEST['screen'])?sanitize_title($_REQUEST['screen']):sanitize_title($_REQUEST['modal']);
$list = !empty($_REQUEST['list'])?sanitize_title($_REQUEST['list']):'';
$id = !empty($_REQUEST['id'])?sanitize_title($_REQUEST['id']):'';
$modal = !empty($_REQUEST['modal'])?sanitize_title($_REQUEST['modal']):'';
$args = array( 'screen' => $screen, 'list' => $list );
if ( $id )
	$args['id'] = $id;
 ?>
<div id="<?php echo $modal; ?>" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Выбрать из списка','usam'); ?></div>
	</div>	
	<div class="usam_select_list modal-scroll">						
		<iframe class="js_iframe" src="<?php echo usam_url_admin_action( 'display_items_list', $args ); ?>"></iframe>
	</div>
</div>