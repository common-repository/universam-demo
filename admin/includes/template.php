<?php
// Формы для страниц магазина Universam
function usam_add_box( $id, $title = '', $function = null, $parameters = null, $edit = false, $close = true ) 
{	
	if ( is_array($id) )
		extract( $id );
	ob_start();	
		
	call_user_func( $function, $parameters );
	
	$html = ob_get_contents();
 	ob_end_clean();		
	if ( $html )
	{		
		if ( $edit )
		{
			if ( !empty($change_parameter) )
				$title .= "<a class='edit' v-if='!$change_parameter' @click='$change_parameter=!$change_parameter'>".__('Изменить','usam')."</a><a v-else class='edit' @click='$change_parameter=!$change_parameter'>".__('Отменить','usam')."</a>";
			else
				$title .= '<a class="edit js-edit" href="#">'.__('Изменить','usam').'</a>';
		}		
		$metabox = get_user_option( 'usam_metaboxhidden_nav_menus' );	
		$closed = $close && isset($metabox[$id]) && $metabox[$id] ? 'closed' : '';	
		?>
		<a id ="nav-<?php echo $id; ?>"></a>
		<div id="<?php echo $id; ?>" class="postbox usam_box <?php echo $closed; ?>" <?php echo !empty($tag_parameter)? implode(' ', (array)$tag_parameter):''; ?>>			
			<?php 
			if ( $close )
			{ 
				?><div class="handlediv" title="<?php _e('Нажмите, чтобы переключить','usam'); ?>"></div><?php
			} ?>
			<h3 class="usam_box__title"><span><?php echo $title; ?></span></h3>
			<div class="inside"><?php echo $html; ?></div>
		</div>
		<?php
	}
}	

// Редактирование данных с помощью ajax
function usam_get_fast_data_editing( $text, $id, $col, $action, $data_type = 'input', $callback = null, $collection = '', $custom = '') 
{
	/*$attr = '';
	foreach( $args as $name => $value )
	{
		$attr .= "$name='$value' ";
	}*/
	if ( $custom != '' )
		$custom = "data-attribute='custom_$custom'";
	$out = "
	<span class='fast_editing'>
		<span class='best_in_place usam_edit_$col' data-id='$id' data-nonce='".usam_create_ajax_nonce( $action )."' data-action='$action' data-col='$col' data-callback='$callback' data-type='$data_type' data-collection='$collection' $custom>$text</span>
		<span class='pencil'></span>
	</span>";	
	return $out;
}

//usam_toggle( 'usam_stand_service_box', 'false' );		

// Формы для страниц магазина Universam
function usam_toggle( $id, $on, $title_1 = '', $title_2 = '' ) 
{	
	if ( $title_1 == '' ) 
		$title_1 = __('Вкл','usam');
	if ( $title_2 == '' ) 
		$title_2 = __('Выкл','usam');
	if ( $on == 'off' ) 
		$on_class = 'off';		
	?>
	<div id="<?php echo $id; ?>" class="usam_toggle">
		<div class="toggle <?php echo $on_class; ?>" data-ontext="<?php echo $title_1; ?>" data-offtext="<?php echo $title_2; ?>"></div>
	</div>
	<?php
}	
?>