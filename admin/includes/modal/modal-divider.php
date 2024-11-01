<?php
$dividers = array();
$list = usam_list_dir( USAM_CORE_IMAGES_PATH. '/mailtemplate/dividers' );		
foreach ( $list as $file )
{
	if ( stristr( $file, '.png' ) || stristr( $file, '.jpg' ))		
		$dividers[] = ['src' => USAM_CORE_IMAGES_URL.'/mailtemplate/dividers/'.$file, 'width' => 563, 'height' => 11];			
}	
?>		
<modal-panel ref="modaldivider">
	<template v-slot:title><?php _e('Разделители', 'usam'); ?></template>
	<template v-slot:body>
		<div class ="grid_select">
			<?php
			foreach( $dividers as $divider ) 
			{
				?><a :class="{'selected':block!== null && block.type == 'divider' && block.src=='<?php echo $divider['src'] ?>'}" @click="selectDivider('<?php echo $divider['src'] ?>')"><img src="<?php echo $divider['src'] ?>" alt="" width="<?php echo $divider['width'] ?>" height="<?php echo $divider['height'] ?>"></a><?php
			}	
			?>
		</div>
	</template>
</modal-panel>	